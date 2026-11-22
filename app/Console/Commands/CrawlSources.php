<?php

namespace App\Console\Commands;

use App\Models\CrawlSource;
use App\Models\WebCrawl;
use App\Models\WebCrawlChunk;
use App\Services\WebCrawler;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CrawlSources extends Command
{
    protected $signature = 'crawl:sources
                            {--chatbot= : Filter by chatbot ID}
                            {--source= : Crawl a specific source ID}
                            {--url= : Crawl a single URL}
                            {--force : Force crawl even if recently crawled}';

    protected $description = 'Crawl configured web sources for the chatbot knowledge base';

    protected WebCrawler $crawler;

    public function __construct(WebCrawler $crawler)
    {
        parent::__construct();
        $this->crawler = $crawler;
    }

    public function handle(): int
    {
        // Single URL mode
        if ($this->option('url')) {
            $this->crawlSingleUrl($this->option('url'));
            return Command::SUCCESS;
        }

        // Single source mode
        if ($this->option('source')) {
            $this->crawlSingleSource($this->option('source'));
            return Command::SUCCESS;
        }

        // Batch crawl mode
        $query = CrawlSource::where('is_active', true);
        if ($this->option('chatbot')) {
            $query->where('chatbot_id', $this->option('chatbot'));
        }

        $sources = $query->get();

        if ($sources->isEmpty()) {
            $this->info('No active crawl sources found.');
            return Command::SUCCESS;
        }

        $this->info("Found {$sources->count()} active crawl source(s).");
        $this->line('');

        $totalPages = 0;
        $totalFailed = 0;

        foreach ($sources as $source) {
            $this->task($source->name, function () use ($source, &$totalPages, &$totalFailed) {
                return $this->processSource($source, $totalPages, $totalFailed);
            });
        }

        $this->line('');
        $this->info("Crawl complete: {$totalPages} pages crawled, {$totalFailed} failed.");

        return Command::SUCCESS;
    }

    protected function crawlSingleUrl(string $url): void
    {
        $this->info("Crawling URL: {$url}");
        $domain = parse_url($url, PHP_URL_HOST);
        $this->crawler->setAllowedDomains($domain ? [$domain] : []);

        $result = $this->crawler->crawl($url);

        if ($result['success']) {
            WebCrawl::updateOrCreate(
                ['url' => $url],
                [
                    'title' => $result['title'],
                    'content' => $result['content'],
                    'domain' => $domain,
                    'crawl_type' => 'single',
                    'status' => 1,
                    'word_count' => str_word_count($result['content']),
                    'last_crawled_at' => now(),
                ]
            );
            $this->info("✓ Crawled: {$result['title']} ({$result['word_count']} words)");
        } else {
            $this->error("✗ Failed: {$result['error']}");
        }
    }

    protected function crawlSingleSource(int $sourceId): void
    {
        $source = CrawlSource::findOrFail($sourceId);
        $this->info("Crawling source: {$source->name} ({$source->base_url})");

        $this->processSource($source, $totalPages, $totalFailed);
        $this->info("Source crawl complete: {$totalPages} pages, {$totalFailed} failed");
    }

    protected function processSource(CrawlSource $source, int &$totalPages, int &$totalFailed): bool
    {
        // Check if recently crawled
        if (!$this->option('force') && $source->last_crawled_at && $source->last_crawled_at->gt(now()->subMinutes(30))) {
            $this->info("  Skipped (recently crawled: {$source->last_crawled_at})");
            return true;
        }

        $this->crawler->setAllowedDomains([parse_url($source->base_url, PHP_URL_HOST)]);

        $urls = [$source->base_url];

        // Parse sitemap if enabled
        if ($source->crawl_sitemap) {
            $sitemapUrls = $this->parseSitemap($source->base_url);
            if (!empty($sitemapUrls)) {
                $urls = $sitemapUrls;
                $this->info("  Found " . count($sitemapUrls) . " URLs in sitemap");
            }
        }

        $this->info("  Crawling " . count($urls) . " URL(s)...");
        $result = $this->crawler->crawlMultiple($urls, $source->max_pages);

        $pagesCrawled = 0;
        if ($result['success']) {
            foreach ($result['results'] as $page) {
                if ($page['success']) {
                    $webCrawl = WebCrawl::updateOrCreate(
                        ['url' => $page['url']],
                        [
                            'chatbot_id' => $source->chatbot_id,
                            'title' => $page['title'],
                            'content' => $page['content'],
                            'domain' => parse_url($page['url'], PHP_URL_HOST),
                            'crawl_type' => 'source',
                            'status' => 1,
                            'word_count' => str_word_count($page['content']),
                            'last_crawled_at' => now(),
                        ]
                    );
                    $pagesCrawled++;
                    $totalPages++;
                } else {
                    $totalFailed++;
                }
            }
        }

        $source->update([
            'last_crawled_at' => now(),
            'total_crawled' => $source->total_crawled + $pagesCrawled,
        ]);

        $this->info("  ✓ {$pagesCrawled} pages crawled");
        return true;
    }

    protected function parseSitemap(string $baseUrl): array
    {
        $sitemapUrl = rtrim($baseUrl, '/') . '/sitemap.xml';
        try {
            $response = $this->crawler->getClient()->get($sitemapUrl, ['timeout' => 10.0]);
            $xml = simplexml_load_string($response->getBody()->getContents());
            if ($xml === false) return [];

            $urls = [];
            foreach ($xml->children('http://www.sitemaps.org/schemas/sitemap/0.9') as $entry) {
                $loc = (string) $entry->loc;
                if (!empty($loc)) $urls[] = $loc;
            }
            return $urls;
        } catch (\Exception $e) {
            Log::error('Sitemap parse failed: ' . $e->getMessage());
            return [];
        }
    }
}
