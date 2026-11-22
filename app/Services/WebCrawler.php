<?php

namespace App\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Promise;
use GuzzleHttp\Promise\Utils;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use DOMDocument;
use DOMXPath;

class WebCrawler
{
    protected Client $client;
    protected array $allowedDomains = [];
    protected int $maxPages;
    protected int $maxDepth;
    protected bool $respectRobotsTxt;
    protected int $timeout;

    public function __construct()
    {
        $this->client = new Client([
            'timeout' => 15.0,
            'headers' => [
                'User-Agent' => env('CRAWLER_USER_AGENT', 'Mozilla/5.0 (SupportBot/1.0; +https://supportboard.local/bot)')
                    . ' (' . env('APP_NAME', 'SupportBoard') . ' Bot)',
                'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                'Accept-Language' => 'en-US,en;q=0.5',
            ],
        ]);

        $this->maxPages = (int) env('CRAWLER_MAX_PAGES', 50);
        $this->maxDepth = (int) env('CRAWLER_MAX_DEPTH', 3);
        $this->respectRobotsTxt = env('CRAWLER_RESPECT_ROBOTS', true);
        $this->timeout = (int) env('CRAWLER_TIMEOUT', 30);
    }

    /**
     * Configure allowed domains for crawling.
     */
    public function setAllowedDomains(array $domains): void
    {
        $this->allowedDomains = $domains;
    }

    /**
     * Crawl a single URL and extract content.
     */
    public function crawl(string $url, int $depth = 0, array $visited = []): array
    {
        // Prevent infinite recursion
        if ($depth > $this->maxDepth || in_array($url, $visited)) {
            return ['success' => false, 'error' => 'Depth limit or already visited'];
        }

        // Check domain allowlist
        if (!empty($this->allowedDomains) && !$this->isAllowedDomain($url)) {
            return ['success' => false, 'error' => 'Domain not allowed'];
        }

        // Check robots.txt
        if ($this->respectRobotsTxt && !$this->isAllowedByRobots($url)) {
            return ['success' => false, 'error' => 'Blocked by robots.txt'];
        }

        try {
            $response = $this->client->get($url, [
                'timeout' => 15.0,
            ]);

            $contentType = $response->getHeaderLine('Content-Type');
            if (!str_contains($contentType, 'text/html')) {
                return ['success' => false, 'error' => 'Not an HTML page'];
            }

            $html = $response->getBody()->getContents();
            $content = $this->extractContent($html, $url);

            return [
                'success' => true,
                'url' => $url,
                'title' => $content['title'],
                'content' => $content['text'],
                'links' => $content['links'],
                'depth' => $depth,
            ];
        } catch (GuzzleException $e) {
            Log::error('Crawl failed: ' . $e->getMessage(), ['url' => $url]);
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Batch crawl multiple URLs concurrently.
     */
    public function crawlMultiple(array $urls, int $maxPages = null): array
    {
        $maxPages = $maxPages ?? $this->maxPages;
        $results = [];
        $visited = [];

        $pending = [];
        foreach ($urls as $index => $url) {
            $pending[$index] = $this->crawlAsync($url, 0, []);
        }

        $count = 0;
        foreach ($pending as $index => $promise) {
            if ($count >= $maxPages) break;

            try {
                $result = Utils::unwrap([$promise]);
                if ($result['success']) {
                    $count++;
                    $results[] = $result;
                    $visited[] = $result['url'];

                    // Crawl discovered links up to maxDepth
                    if ($result['depth'] < $this->maxDepth - 1) {
                        $nextUrls = array_filter($result['links'] ?? [], function ($link) use ($visited) {
                            return !$this->isAllowedDomain($link) === false
                                && !in_array($link, $visited)
                                && count($visited) < $maxPages;
                        });

                        $nextPromises = [];
                        foreach (array_slice($nextUrls, 0, 3) as $nextUrl) {
                            $nextPromises[] = $this->crawlAsync($nextUrl, $result['depth'] + 1, $visited);
                        }

                        if (!empty($nextPromises)) {
                            $nextResults = Utils::unwrap($nextPromises);
                            foreach ($nextResults as $nextResult) {
                                if ($nextResult['success'] && count($results) < $maxPages) {
                                    $results[] = $nextResult;
                                    $visited[] = $nextResult['url'];
                                }
                            }
                        }
                    }
                }
            } catch (\Exception $e) {
                continue;
            }
        }

        return [
            'success' => true,
            'pages_crawled' => count($results),
            'results' => $results,
        ];
    }

    /**
     * Crawl a single URL asynchronously.
     */
    protected function crawlAsync(string $url, int $depth, array $visited): \GuzzleHttp\Promise\PromiseInterface
    {
        return $this->client->getAsync($url, ['timeout' => 15.0])
            ->then(
                function ($response) use ($url, $depth, $visited) {
                    $contentType = $response->getHeaderLine('Content-Type');
                    if (!str_contains($contentType, 'text/html')) {
                        return ['success' => false, 'url' => $url, 'error' => 'Not HTML'];
                    }

                    $html = $response->getBody()->getContents();
                    $content = $this->extractContent($html, $url);

                    return [
                        'success' => true,
                        'url' => $url,
                        'title' => $content['title'],
                        'content' => $content['text'],
                        'links' => $content['links'],
                        'depth' => $depth,
                    ];
                },
                function ($reason) use ($url) {
                    return ['success' => false, 'url' => $url, 'error' => $reason->getMessage()];
                }
            );
    }

    /**
     * Extract structured content from HTML.
     */
    protected function extractContent(string $html, string $url): array
    {
        libxml_use_internal_errors(true);
        $dom = new DOMDocument();
        @$dom->loadHTML($html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();

        $xpath = new DOMXPath($dom);

        // Extract title
        $title = '';
        $titleTag = $xpath->query('//title');
        if ($titleTag->length > 0) {
            $title = trim($titleTag->item(0)->textContent);
        }

        // Remove scripts, styles, nav, footer, header
        foreach ($xpath->query('//script|//style|//nav|//footer|//header|//aside|//noscript') as $element) {
            $element->parentNode->removeChild($element);
        }

        // Extract main content
        $mainContent = '';
        $mainTags = $xpath->query('//main|//article|//content|//div[@id="content"]|//div[@class="content"]');
        if ($mainTags->length > 0) {
            $mainContent = $mainTags->item(0)->textContent;
        }

        // Fallback to body
        if (empty($mainContent)) {
            $body = $xpath->query('//body');
            if ($body->length > 0) {
                $mainContent = $body->item(0)->textContent;
            }
        }

        // Extract links
        $links = [];
        foreach ($xpath->query('//a[@href]') as $link) {
            $href = $link->getAttribute('href');
            $text = trim($link->textContent);
            if (!empty($href) && !str_starts_with($href, '#') && !str_starts_with($href, 'javascript:')) {
                // Resolve relative URLs
                $parsed = parse_url($url);
                $base = $parsed['scheme'] . '://' . ($parsed['host'] ?? '') . ($parsed['port'] ? ':' . $parsed['port'] : '');
                $fullUrl = $base . (str_starts_with($href, '/') ? $href : '/' . $href);
                $links[] = $fullUrl;
            }
        }

        return [
            'title' => $title,
            'content' => strip_tags($mainContent),
            'links' => array_unique($links),
        ];
    }

    /**
     * Check if a URL is within allowed domains.
     */
    protected function isAllowedDomain(string $url): bool
    {
        if (empty($this->allowedDomains)) {
            return true;
        }

        $host = parse_url($url, PHP_URL_HOST);
        return in_array($host, $this->allowedDomains);
    }

    /**
     * Check if URL is allowed by robots.txt.
     */
    protected function isAllowedByRobots(string $url): bool
    {
        $parsed = parse_url($url);
        $baseUrl = $parsed['scheme'] . '://' . $parsed['host'];
        $robotsUrl = $baseUrl . '/robots.txt';

        try {
            $response = $this->client->get($robotsUrl, ['timeout' => 5.0]);
            $content = $response->getBody()->getContents();

            // Simple robots.txt parser
            $lines = explode("\n", $content);
            $disallowPaths = [];
            $currentAgent = null;

            foreach ($lines as $line) {
                $line = trim($line);
                if (str_starts_with(strtolower($line), 'user-agent:')) {
                    $currentAgent = strtolower(explode(':', $line)[1]);
                } elseif (str_starts_with(strtolower($line), 'disallow:') && $currentAgent === null) {
                    $disallowPaths[] = strtolower(explode(':', $line)[1]);
                }
            }

            $path = $parsed['path'] ?? '/';
            foreach ($disallowPaths as $path) {
                if (str_starts_with($path, $path)) {
                    return false;
                }
            }

            return true;
        } catch (GuzzleException $e) {
            // If robots.txt doesn't exist or can't be fetched, allow crawling
            return true;
        }
    }

    /**
     * Get crawler configuration.
     */
    public function getConfig(): array
    {
        return [
            'max_pages' => $this->maxPages,
            'max_depth' => $this->maxDepth,
            'respect_robots' => $this->respectRobotsTxt,
            'allowed_domains' => $this->allowedDomains,
        ];
    }
}
