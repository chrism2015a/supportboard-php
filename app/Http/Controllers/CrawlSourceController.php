<?php

namespace App\Http\Controllers;

use App\Models\CrawlSource;
use App\Models\WebCrawl;
use App\Models\WebCrawlChunk;
use App\Services\WebCrawler;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class CrawlSourceController extends Controller
{
    protected WebCrawler $crawler;

    public function __construct(WebCrawler $crawler)
    {
        $this->crawler = $crawler;
    }

    /**
     * List all crawl sources for a chatbot.
     */
    public function index(Request $request)
    {
        $query = CrawlSource::query();

        if ($request->chatbot_id) {
            $query->where('chatbot_id', $request->chatbot_id);
        }

        $sources = $query->withCount(['webCrawls'])
            ->orderBy('name')
            ->get();

        return response()->json($sources);
    }

    /**
     * Store a new crawl source.
     */
    public function store(Request $request)
    {
        $request->validate([
            'chatbot_id' => 'nullable|exists:chatbot_configs,id',
            'name' => 'required|string|max:255',
            'base_url' => 'required|url',
            'allowed_paths' => 'nullable|array',
            'disallowed_paths' => 'nullable|array',
            'max_pages' => 'nullable|integer|min:1|max:500',
            'max_depth' => 'nullable|integer|min:1|max:10',
            'respect_robots' => 'nullable|boolean',
            'crawl_sitemap' => 'nullable|boolean',
            'schedule' => 'nullable|in:daily,weekly,monthly,on_demand',
        ]);

        $source = CrawlSource::create($request->all());

        return response()->json($source, 201);
    }

    /**
     * Update a crawl source.
     */
    public function update(Request $request, string $id)
    {
        $source = CrawlSource::findOrFail($id);
        $request->validate([
            'name' => 'sometimes|string|max:255',
            'base_url' => 'sometimes|url',
            'allowed_paths' => 'nullable|array',
            'disallowed_paths' => 'nullable|array',
            'max_pages' => 'sometimes|integer|min:1|max:500',
            'max_depth' => 'sometimes|integer|min:1|max:10',
            'respect_robots' => 'sometimes|boolean',
            'crawl_sitemap' => 'sometimes|boolean',
            'schedule' => 'sometimes|in:daily,weekly,monthly,on_demand',
            'is_active' => 'sometimes|boolean',
        ]);

        $source->update($request->all());
        return response()->json($source);
    }

    /**
     * Delete a crawl source.
     */
    public function destroy(string $id)
    {
        $source = CrawlSource::findOrFail($id);
        $source->delete();
        return response()->json(['message' => 'Crawl source deleted']);
    }

    /**
     * Crawl a single URL immediately.
     */
    public function crawlUrl(Request $request)
    {
        $request->validate([
            'url' => 'required|url',
            'chatbot_id' => 'nullable|exists:chatbot_configs,id',
        ]);

        $domain = parse_url($request->url, PHP_URL_HOST);
        $this->crawler->setAllowedDomains($domain ? [$domain] : []);

        $result = $this->crawler->crawl($request->url);

        if ($result['success']) {
            $webCrawl = WebCrawl::updateOrCreate(
                ['url' => $request->url],
                [
                    'chatbot_id' => $request->chatbot_id,
                    'title' => $result['title'],
                    'content' => $result['content'],
                    'domain' => $domain,
                    'crawl_type' => 'single',
                    'status' => 1,
                    'word_count' => str_word_count($result['content']),
                    'chunk_count' => 0,
                    'last_crawled_at' => now(),
                ]
            );

            // Chunk the content for RAG
            $chunks = $this->chunkContent($result['content'], $result['title'], $request->url);
            $webCrawl->update(['chunk_count' => count($chunks)]);

            return response()->json([
                'success' => true,
                'pages_crawled' => 1,
                'chunks_created' => count($chunks),
                'url' => $request->url,
            ]);
        }

        WebCrawl::updateOrCreate(
            ['url' => $request->url],
            [
                'chatbot_id' => $request->chatbot_id,
                'status' => -1,
                'error_message' => $result['error'] ?? 'Unknown error',
                'last_crawled_at' => now(),
            ]
        );

        return response()->json([
            'success' => false,
            'error' => $result['error'] ?? 'Crawl failed',
        ], 422);
    }

    /**
     * Crawl all sources for a chatbot.
     */
    public function crawlSources(Request $request)
    {
        $request->validate([
            'chatbot_id' => 'required|exists:chatbot_configs,id',
        ]);

        $sources = CrawlSource::where('chatbot_id', $request->chatbot_id)
            ->where('is_active', true)
            ->get();

        if ($sources->isEmpty()) {
            return response()->json(['message' => 'No active crawl sources found']);
        }

        $totalCrawled = 0;
        $totalFailed = 0;

        foreach ($sources as $source) {
            $this->crawler->setAllowedDomains([parse_url($source->base_url, PHP_URL_HOST)]);

            // Crawl sitemap if enabled
            $urls = [$source->base_url];
            if ($source->crawl_sitemap) {
                $sitemapUrls = $this->parseSitemap($source->base_url);
                if (!empty($sitemapUrls)) {
                    $urls = $sitemapUrls;
                }
            }

            $result = $this->crawler->crawlMultiple($urls, $source->max_pages);

            if ($result['success']) {
                foreach ($result['results'] as $page) {
                    if ($page['success']) {
                        $webCrawl = WebCrawl::updateOrCreate(
                            ['url' => $page['url']],
                            [
                                'chatbot_id' => $request->chatbot_id,
                                'title' => $page['title'],
                                'content' => $page['content'],
                                'domain' => parse_url($page['url'], PHP_URL_HOST),
                                'crawl_type' => 'source',
                                'status' => 1,
                                'word_count' => str_word_count($page['content']),
                                'last_crawled_at' => now(),
                            ]
                        );
                        $chunks = $this->chunkContent($page['content'], $page['title'], $page['url']);
                        $webCrawl->update(['chunk_count' => count($chunks)]);
                        $totalCrawled++;
                    } else {
                        $totalFailed++;
                    }
                }
            } else {
                $totalFailed++;
            }

            $source->update([
                'last_crawled_at' => now(),
                'total_crawled' => $source->total_crawled + $totalCrawled,
                'failed_count' => $source->failed_count + $totalFailed,
            ]);
        }

        return response()->json([
            'success' => true,
            'sources_crawled' => $sources->count(),
            'pages_crawled' => $totalCrawled,
            'failed' => $totalFailed,
        ]);
    }

    /**
     * Get crawl history for a source.
     */
    public function history(string $id)
    {
        $source = CrawlSource::findOrFail($id);
        $crawls = WebCrawl::where('chatbot_id', $source->chatbot_id)
            ->orderBy('last_crawled_at', 'desc')
            ->paginate(20);

        return response()->json($crawls);
    }

    /**
     * Get crawl stats.
     */
    public function stats(Request $request)
    {
        $query = WebCrawl::query();
        if ($request->chatbot_id) {
            $query->where('chatbot_id', $request->chatbot_id);
        }

        $stats = [
            'total_crawls' => $query->count(),
            'successful' => $query->where('status', 1)->count(),
            'failed' => $query->where('status', -1)->count(),
            'pending' => $query->where('status', 0)->count(),
            'total_pages' => $query->sum('word_count'),
            'total_chunks' => $query->sum('chunk_count'),
            'domains' => $query->distinct('domain')->count('domain'),
            'recent' => $query->orderBy('last_crawled_at', 'desc')
                ->limit(10)
                ->get(['url', 'title', 'status', 'word_count', 'last_crawled_at']),
        ];

        return response()->json($stats);
    }

    /**
     * Search crawled content for a query (RAG retrieval).
     */
    public function search(Request $request)
    {
        $request->validate([
            'query' => 'required|string|min:3',
            'chatbot_id' => 'nullable|exists:chatbot_configs,id',
            'limit' => 'nullable|integer|min:1|max:20',
        ]);

        $limit = $request->limit ?? 5;
        $query = WebCrawl::query()
            ->where('status', 1)
            ->whereNotNull('content');

        if ($request->chatbot_id) {
            $query->where('chatbot_id', $request->chatbot_id);
        }

        $results = $query->where(function ($q) use ($request) {
            $q->where('title', 'like', "%{$request->query}%")
              ->orWhere('content', 'like', "%{$request->query}%");
        })
            ->orderByRaw("
                CASE
                    WHEN title LIKE ? THEN 1
                    WHEN content LIKE ? THEN 2
                    ELSE 3
                END
            ", ["%{$request->query}%", "%{$request->query}%"])
            ->limit($limit)
            ->get(['id', 'url', 'title', 'content', 'word_count']);

        $searchResults = $results->map(fn($item) => [
            'id' => $item->id,
            'url' => $item->url,
            'title' => $item->title,
            'content' => $this->extractRelevantContext($item->content, $request->query),
            'word_count' => $item->word_count,
            'relevance_score' => str_contains(strtolower($item->title), strtolower($request->query)) ? 1.0 : 0.7,
        ]);

        return response()->json([
            'query' => $request->query,
            'results' => $searchResults,
            'count' => $searchResults->count(),
        ]);
    }

    /**
     * Parse sitemap.xml to extract URLs.
     */
    protected function parseSitemap(string $baseUrl): array
    {
        $sitemapUrl = rtrim($baseUrl, '/') . '/sitemap.xml';

        try {
            $response = $this->crawler->getClient()->get($sitemapUrl, ['timeout' => 10.0]);
            $xml = simplexml_load_string($response->getBody()->getContents());

            if ($xml === false) {
                return [];
            }

            $urls = [];
            foreach ($xml->children('http://www.sitemaps.org/schemas/sitemap/0.9') as $entry) {
                $loc = (string) $entry->loc;
                if (!empty($loc)) {
                    $urls[] = $loc;
                }
            }

            return $urls;
        } catch (\Exception $e) {
            Log::error('Sitemap parse failed: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Split content into chunks for RAG.
     */
    protected function chunkContent(string $content, string $title, string $url, int $chunkSize = 500): array
    {
        $words = preg_split('/\s+/', trim($content));
        $chunks = [];
        $currentChunk = [];
        $chunkIndex = 0;

        foreach ($words as $word) {
            $currentChunk[] = $word;

            if (count($currentChunk) >= $chunkSize) {
                $chunks[] = [
                    'chunk_index' => $chunkIndex++,
                    'content' => implode(' ', $currentChunk),
                    'word_count' => count($currentChunk),
                    'source_url' => $url,
                ];
                $currentChunk = [];
            }
        }

        if (!empty($currentChunk)) {
            $chunks[] = [
                'chunk_index' => $chunkIndex,
                'content' => implode(' ', $currentChunk),
                'word_count' => count($currentChunk),
                'source_url' => $url,
            ];
        }

        return $chunks;
    }

    /**
     * Extract context around the search query from content.
     */
    protected function extractRelevantContext(string $content, string $query, int $contextWindow = 200): string
    {
        $lowerContent = mb_strtolower($content);
        $lowerQuery = mb_strtolower($query);
        $pos = mb_strpos($lowerContent, $lowerQuery);

        if ($pos === false) {
            return mb_substr($content, 0, 500);
        }

        $start = max(0, $pos - $contextWindow);
        $end = min(mb_strlen($content), $pos + mb_strlen($query) + $contextWindow);

        return mb_substr($content, $start, $end - $start);
    }
}
