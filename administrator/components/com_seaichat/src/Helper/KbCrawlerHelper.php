<?php


/**
 * @package     Joomla
 * @subpackage  com_seaichat
 *
 * @copyright   (C) 2026 SE Extensions
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

declare(strict_types=1);

namespace SolarEclipse\Component\SeAiChat\Administrator\Helper;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Http\HttpFactory;
use Joomla\Database\ParameterType;

class KbCrawlerHelper
{
    private static int $maxPages = 100;
    private static int $chunkSize = 600; // approximate words per chunk
    private static int $chunkOverlap = 80; // word overlap between chunks

    /**
     * Crawl a documentation source and store chunks.
     */
    public static function crawlSource(int $sourceId): array
    {
        $db = Factory::getContainer()->get('DatabaseDriver');

        // Get source
        $query = $db->getQuery(true)
            ->select('*')
            ->from($db->quoteName('#__seaichat_kb_sources'))
            ->where($db->quoteName('id') . ' = :id')
            ->bind(':id', $sourceId, ParameterType::INTEGER);
        $db->setQuery($query);
        $source = $db->loadObject();

        if (!$source) {
            throw new \RuntimeException('Source not found.');
        }

        // Update status to crawling
        $update = new \stdClass();
        $update->id = $sourceId;
        $update->crawl_status = 'crawling';
        $db->updateObject('#__seaichat_kb_sources', $update, 'id');

        try {
            // Delete existing pages and chunks for this source
            $query = $db->getQuery(true)
                ->delete($db->quoteName('#__seaichat_kb_chunks'))
                ->where($db->quoteName('source_id') . ' = :sid')
                ->bind(':sid', $sourceId, ParameterType::INTEGER);
            $db->setQuery($query);
            $db->execute();

            $query = $db->getQuery(true)
                ->delete($db->quoteName('#__seaichat_kb_pages'))
                ->where($db->quoteName('source_id') . ' = :sid')
                ->bind(':sid', $sourceId, ParameterType::INTEGER);
            $db->setQuery($query);
            $db->execute();

            // Crawl starting from the base URL
            $baseUrl = rtrim($source->url, '/');
            $crawled = [];
            $toCrawl = [$baseUrl];
            $pageCount = 0;
            $chunkCount = 0;
            $now = Factory::getDate()->toSql();

            while (!empty($toCrawl) && $pageCount < self::$maxPages) {
                $url = array_shift($toCrawl);
                $normalised = self::normaliseUrl($url);

                if (isset($crawled[$normalised])) {
                    continue;
                }
                $crawled[$normalised] = true;

                // Fetch the page
                $html = self::fetchUrl($url);
                if (empty($html)) {
                    continue;
                }

                // Extract text content and title
                $title = self::extractTitle($html);
                $text = self::extractText($html);

                if (strlen(trim($text)) < 50) {
                    continue; // Skip near-empty pages
                }

                // Store the page
                $page = new \stdClass();
                $page->source_id = $sourceId;
                $page->url = $url;
                $page->title = substr($title, 0, 500);
                $page->content_hash = hash('sha256', $text);
                $page->crawled = $now;
                $db->insertObject('#__seaichat_kb_pages', $page, 'id');
                $pageId = (int) $page->id;
                $pageCount++;

                // Chunk the text
                $chunks = self::chunkText($text);
                foreach ($chunks as $index => $chunkText) {
                    $keywords = self::extractKeywords($chunkText . ' ' . $title);
                    $wordCount = str_word_count($chunkText);

                    $chunk = new \stdClass();
                    $chunk->source_id = $sourceId;
                    $chunk->page_id = $pageId;
                    $chunk->page_title = substr($title, 0, 500);
                    $chunk->page_url = $url;
                    $chunk->content = $chunkText;
                    $chunk->keywords = $keywords;
                    $chunk->chunk_index = $index;
                    $chunk->token_count = (int) ($wordCount * 1.3); // approximate tokens
                    $db->insertObject('#__seaichat_kb_chunks', $chunk);
                    $chunkCount++;
                }

                // Find internal links for further crawling
                $links = self::extractLinks($html, $baseUrl);
                foreach ($links as $link) {
                    $normLink = self::normaliseUrl($link);
                    if (!isset($crawled[$normLink]) && !in_array($link, $toCrawl)) {
                        $toCrawl[] = $link;
                    }
                }
            }

            // Update source status
            $update = new \stdClass();
            $update->id = $sourceId;
            $update->crawl_status = 'complete';
            $update->last_crawled = $now;
            $update->page_count = $pageCount;
            $update->chunk_count = $chunkCount;
            $update->crawl_error = null;
            $db->updateObject('#__seaichat_kb_sources', $update, 'id');

            return ['pages' => $pageCount, 'chunks' => $chunkCount];

        } catch (\Exception $e) {
            $update = new \stdClass();
            $update->id = $sourceId;
            $update->crawl_status = 'error';
            $update->crawl_error = substr($e->getMessage(), 0, 1000);
            $db->updateObject('#__seaichat_kb_sources', $update, 'id');

            throw $e;
        }
    }

    /**
     * Fetch a URL and return HTML content.
     */
    private static function fetchUrl(string $url): string
    {
        try {
            $http = HttpFactory::getHttp();
            $response = $http->get($url, [], 15);

            if ($response->code !== 200) {
                return '';
            }

            $contentType = $response->headers['content-type'] ?? $response->headers['Content-Type'] ?? '';
            if (is_array($contentType)) {
                $contentType = $contentType[0];
            }

            // Only process HTML pages
            if (stripos($contentType, 'text/html') === false && !empty($contentType)) {
                return '';
            }

            return $response->body ?? '';
        } catch (\Exception $e) {
            return '';
        }
    }

    /**
     * Extract the page title from HTML.
     */
    private static function extractTitle(string $html): string
    {
        if (preg_match('/<title[^>]*>(.*?)<\/title>/si', $html, $matches)) {
            return html_entity_decode(trim($matches[1]), ENT_QUOTES, 'UTF-8');
        }

        if (preg_match('/<h1[^>]*>(.*?)<\/h1>/si', $html, $matches)) {
            return html_entity_decode(strip_tags(trim($matches[1])), ENT_QUOTES, 'UTF-8');
        }

        return 'Untitled';
    }

    /**
     * Extract readable text content from HTML, stripping nav, footer, scripts, etc.
     */
    private static function extractText(string $html): string
    {
        // Remove scripts, styles, nav, footer, header, aside
        $html = preg_replace('/<(script|style|nav|footer|header|aside|noscript)[^>]*>.*?<\/\1>/si', '', $html);
        $html = preg_replace('/<!--.*?-->/s', '', $html);

        // Try to get main content area
        $mainContent = '';
        if (preg_match('/<(?:main|article)[^>]*>(.*?)<\/(?:main|article)>/si', $html, $matches)) {
            $mainContent = $matches[1];
        } elseif (preg_match('/<div[^>]*(?:class|id)\s*=\s*["\'][^"\']*(?:content|main|article|docs|documentation)[^"\']*["\'][^>]*>(.*?)<\/div>/si', $html, $matches)) {
            $mainContent = $matches[1];
        }

        $textSource = !empty($mainContent) ? $mainContent : $html;

        // Convert some HTML elements to preserve structure
        $textSource = preg_replace('/<br\s*\/?>/i', "\n", $textSource);
        $textSource = preg_replace('/<\/(?:p|div|h[1-6]|li|tr)>/i', "\n\n", $textSource);

        // Strip remaining tags
        $text = strip_tags($textSource);
        $text = html_entity_decode($text, ENT_QUOTES, 'UTF-8');

        // Clean up whitespace
        $text = preg_replace('/[ \t]+/', ' ', $text);
        $text = preg_replace('/\n{3,}/', "\n\n", $text);
        $text = trim($text);

        return $text;
    }

    /**
     * Extract internal links from HTML.
     */
    private static function extractLinks(string $html, string $baseUrl): array
    {
        $links = [];
        $baseParsed = parse_url($baseUrl);
        $baseHost = $baseParsed['host'] ?? '';
        $basePath = rtrim($baseParsed['path'] ?? '', '/');

        preg_match_all('/<a[^>]+href\s*=\s*["\']([^"\'#]+)/i', $html, $matches);

        foreach ($matches[1] ?? [] as $href) {
            $href = trim($href);

            // Skip non-web links
            if (preg_match('/^(mailto:|tel:|javascript:|#)/i', $href)) {
                continue;
            }

            // Skip file downloads
            if (preg_match('/\.(pdf|zip|tar|gz|exe|dmg|jpg|jpeg|png|gif|svg|css|js|xml|json)$/i', $href)) {
                continue;
            }

            // Resolve relative URLs
            if (!preg_match('#^https?://#i', $href)) {
                if (strpos($href, '/') === 0) {
                    $href = $baseParsed['scheme'] . '://' . $baseHost . $href;
                } else {
                    $href = rtrim($baseUrl, '/') . '/' . $href;
                }
            }

            // Only follow links on the same host and under the base path
            $hrefParsed = parse_url($href);
            $hrefHost = $hrefParsed['host'] ?? '';
            $hrefPath = $hrefParsed['path'] ?? '';

            if ($hrefHost === $baseHost && (empty($basePath) || strpos($hrefPath, $basePath) === 0)) {
                // Remove query string and fragment
                $cleanUrl = $hrefParsed['scheme'] . '://' . $hrefHost . $hrefPath;
                $links[] = $cleanUrl;
            }
        }

        return array_unique($links);
    }

    /**
     * Chunk text into overlapping segments.
     */
    private static function chunkText(string $text): array
    {
        $words = preg_split('/\s+/', $text);
        $totalWords = count($words);
        $chunks = [];

        if ($totalWords <= self::$chunkSize) {
            return [trim($text)];
        }

        $start = 0;
        while ($start < $totalWords) {
            $end = min($start + self::$chunkSize, $totalWords);
            $chunkWords = array_slice($words, $start, $end - $start);
            $chunks[] = implode(' ', $chunkWords);
            $start += self::$chunkSize - self::$chunkOverlap;
        }

        return $chunks;
    }

    /**
     * Extract keywords from text for search matching.
     */
    private static function extractKeywords(string $text): string
    {
        $text = strtolower($text);
        $text = preg_replace('/[^a-z0-9\s]/', ' ', $text);
        $words = preg_split('/\s+/', $text);

        // Remove common stop words
        $stopWords = ['the', 'a', 'an', 'is', 'are', 'was', 'were', 'be', 'been', 'being',
            'have', 'has', 'had', 'do', 'does', 'did', 'will', 'would', 'could', 'should',
            'may', 'might', 'can', 'shall', 'to', 'of', 'in', 'for', 'on', 'with', 'at',
            'by', 'from', 'as', 'into', 'through', 'during', 'before', 'after', 'above',
            'below', 'between', 'and', 'but', 'or', 'nor', 'not', 'so', 'yet', 'both',
            'either', 'neither', 'each', 'every', 'all', 'any', 'few', 'more', 'most',
            'other', 'some', 'such', 'no', 'only', 'own', 'same', 'than', 'too', 'very',
            'just', 'because', 'if', 'when', 'where', 'how', 'what', 'which', 'who',
            'this', 'that', 'these', 'those', 'it', 'its', 'they', 'them', 'their',
            'we', 'us', 'our', 'you', 'your', 'he', 'she', 'him', 'her', 'his',
            'i', 'me', 'my', 'about', 'up', 'out', 'then', 'also', 'over', 'new'];

        $filtered = array_filter($words, function ($w) use ($stopWords) {
            return strlen($w) > 2 && !in_array($w, $stopWords);
        });

        // Get word frequency and take top keywords
        $freq = array_count_values($filtered);
        arsort($freq);
        $topWords = array_slice(array_keys($freq), 0, 50);

        return implode(' ', $topWords);
    }

    /**
     * Normalise a URL for deduplication.
     */
    private static function normaliseUrl(string $url): string
    {
        $url = strtolower(trim($url));
        $url = rtrim($url, '/');
        $url = preg_replace('/[#?].*$/', '', $url);
        return $url;
    }


    /**
     * Process SP Page Builder pages as a KB source.
     */
    public static function processSppbSource(int $sourceId): array
    {
        $db = Factory::getContainer()->get('DatabaseDriver');

        $query = $db->getQuery(true)
            ->select('*')
            ->from($db->quoteName('#__seaichat_kb_sources'))
            ->where($db->quoteName('id') . ' = ' . (int) $sourceId);
        $db->setQuery($query);
        $source = $db->loadObject();

        if (!$source) {
            throw new \RuntimeException('Source not found.');
        }

        if (($source->source_type ?? '') !== 'sppb') {
            throw new \RuntimeException('Source is not an SP Page Builder type.');
        }

        // Update status
        $update = new \stdClass();
        $update->id = $sourceId;
        $update->crawl_status = 'processing';
        $update->crawl_error = '';
        $db->updateObject('#__seaichat_kb_sources', $update, 'id');

        try {
            // Delete existing chunks and pages
            $db->setQuery('DELETE FROM ' . $db->quoteName('#__seaichat_kb_chunks') . ' WHERE source_id = ' . (int) $sourceId);
            $db->execute();
            $db->setQuery('DELETE FROM ' . $db->quoteName('#__seaichat_kb_pages') . ' WHERE source_id = ' . (int) $sourceId);
            $db->execute();

            // Detect which SP Page Builder table exists
            // SP PB 4+ uses #__sppagebuilder_pages, SP PB 3 uses #__sppagebuilder
            $sppbTable = null;
            $tableColumns = [];
            $tables = $db->getTableList();
            $prefix = $db->getPrefix();

            // Check all known SP Page Builder table names
            $candidates = [
                $prefix . 'sppagebuilder_pages',  // SP PB 4+
                $prefix . 'sppagebuilder',         // SP PB 3
            ];

            foreach ($candidates as $candidate) {
                if (in_array($candidate, $tables)) {
                    // Convert back to Joomla prefix notation
                    $sppbTable = '#__' . substr($candidate, strlen($prefix));
                    $tableColumns = array_keys($db->getTableColumns($sppbTable));
                    break;
                }
            }

            if (!$sppbTable) {
                // List available tables for debugging
                $sppbTables = array_filter($tables, function($t) { return stripos($t, 'sppagebuilder') !== false || stripos($t, 'sppb') !== false; });
                $hint = !empty($sppbTables) ? ' Found similar tables: ' . implode(', ', $sppbTables) : ' No tables containing "sppagebuilder" found.';
                throw new \RuntimeException('SP Page Builder tables not found. Is SP Page Builder installed?' . $hint);
            }

            // Build query
            $hasContent = in_array('content', $tableColumns);
            $hasText = in_array('text', $tableColumns);
            $hasCatid = in_array('catid', $tableColumns);
            $hasPublished = in_array('published', $tableColumns);
            $hasActive = in_array('active', $tableColumns);

            $selectCols = 'a.id, a.title';
            if ($hasContent) $selectCols .= ', a.content';
            if ($hasText) $selectCols .= ', a.text';

            $sppbQuery = $db->getQuery(true)
                ->select($selectCols)
                ->from($db->quoteName($sppbTable, 'a'));

            // Filter by published/active status
            if ($hasPublished) {
                $sppbQuery->where($db->quoteName('a.published') . ' = 1');
            } elseif ($hasActive) {
                $sppbQuery->where($db->quoteName('a.active') . ' = 1');
            }

            // Filter by categories if specified
            $selectedCats = trim($source->categories ?? '');
            if (!empty($selectedCats) && $selectedCats !== '0' && $hasCatid) {
                $catIds = array_filter(array_map('intval', explode(',', $selectedCats)));
                if (!empty($catIds)) {
                    $sppbQuery->where($db->quoteName('a.catid') . ' IN (' . implode(',', $catIds) . ')');
                }
            }

            $sppbQuery->order('a.id ASC');
            $db->setQuery($sppbQuery);
            $pages = $db->loadObjectList() ?: [];

            $now = Factory::getDate()->toSql();
            $totalChunks = 0;
            $pageCount = 0;

            foreach ($pages as $sppbPage) {
                // Extract text from SP Page Builder content
                $rawContent = '';

                // Try JSON content first (SP Page Builder 3+/4+)
                $jsonContent = $sppbPage->content ?? $sppbPage->text ?? '';
                if (!empty($jsonContent)) {
                    $decoded = json_decode($jsonContent, true);
                    if (is_array($decoded)) {
                        $rawContent = self::extractSppbText($decoded);
                    }
                }

                // If JSON extraction yielded nothing, try as HTML
                if (empty(trim($rawContent))) {
                    $htmlContent = $sppbPage->text ?? $sppbPage->content ?? '';
                    if (!empty($htmlContent) && !json_decode($htmlContent)) {
                        $rawContent = strip_tags($htmlContent);
                    }
                }

                $text = html_entity_decode($rawContent, ENT_QUOTES, 'UTF-8');
                $text = preg_replace('/\s+/', ' ', $text);
                $text = trim($text);

                if (strlen($text) < 30) {
                    continue;
                }

                $pageTitle = $sppbPage->title ?? 'SP Page ' . $sppbPage->id;

                // Create page entry
                $page = new \stdClass();
                $page->source_id = $sourceId;
                $page->url = 'sppb://' . (int) $sppbPage->id;
                $page->title = substr($pageTitle, 0, 500);
                $page->content_hash = md5($text);
                $page->crawled = $now;
                $db->insertObject('#__seaichat_kb_pages', $page, 'id');
                $pageId = (int) $page->id;
                $pageCount++;

                // Chunk the text
                $chunks = self::chunkText($text);
                foreach ($chunks as $chunkIdx => $chunkText) {
                    $keywords = self::extractKeywords($chunkText . ' ' . $pageTitle);
                    $chunk = new \stdClass();
                    $chunk->source_id = $sourceId;
                    $chunk->page_id = $pageId;
                    $chunk->page_title = substr($pageTitle, 0, 500);
                    $chunk->page_url = 'sppb://' . (int) $sppbPage->id;
                    $chunk->content = $chunkText;
                    $chunk->keywords = $keywords;
                    $chunk->chunk_index = $chunkIdx;
                    $chunk->token_count = (int) (str_word_count($chunkText) * 1.3);
                    $db->insertObject('#__seaichat_kb_chunks', $chunk);
                    $totalChunks++;
                }
            }

            // Update source
            $update = new \stdClass();
            $update->id = $sourceId;
            $update->crawl_status = 'complete';
            $update->last_crawled = $now;
            $update->page_count = $pageCount;
            $update->chunk_count = $totalChunks;
            $update->crawl_error = '';
            $db->updateObject('#__seaichat_kb_sources', $update, 'id');

            return ['pages' => $pageCount, 'chunks' => $totalChunks];
        } catch (\Exception $e) {
            $update = new \stdClass();
            $update->id = $sourceId;
            $update->crawl_status = 'error';
            $update->crawl_error = $e->getMessage();
            $db->updateObject('#__seaichat_kb_sources', $update, 'id');
            throw $e;
        }
    }

    /**
     * Recursively extract text content from SP Page Builder JSON structure.
     * Handles sections > columns > addons > nested addons.
     */
    private static function extractSppbText(array $data): string
    {
        $texts = [];

        foreach ($data as $item) {
            if (!is_array($item)) continue;

            // Extract from addon settings
            if (isset($item['settings']) || isset($item['attrs'])) {
                $settings = $item['settings'] ?? $item['attrs'] ?? [];
                if (is_array($settings)) {
                    // Common text fields in SP Page Builder addons
                    $textFields = ['text', 'title', 'subtitle', 'content', 'description',
                                   'button_text', 'heading', 'feature_text', 'tab_title',
                                   'accordion_title', 'accordion_text', 'testimonial_text',
                                   'testimonial_name', 'pricing_title', 'pricing_text',
                                   'counter_title', 'progress_label', 'alert_text',
                                   'blockquote_text', 'list_items'];
                    foreach ($textFields as $field) {
                        if (!empty($settings[$field])) {
                            $val = $settings[$field];
                            if (is_string($val)) {
                                $clean = strip_tags($val);
                                if (!empty(trim($clean))) {
                                    $texts[] = trim($clean);
                                }
                            } elseif (is_array($val)) {
                                // Handle arrays (e.g. list_items, tab content)
                                foreach ($val as $subItem) {
                                    if (is_string($subItem)) {
                                        $clean = strip_tags($subItem);
                                        if (!empty(trim($clean))) $texts[] = trim($clean);
                                    } elseif (is_array($subItem)) {
                                        // Nested items with title/content
                                        foreach (['title', 'text', 'content', 'description'] as $sf) {
                                            if (!empty($subItem[$sf]) && is_string($subItem[$sf])) {
                                                $clean = strip_tags($subItem[$sf]);
                                                if (!empty(trim($clean))) $texts[] = trim($clean);
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }

            // Recurse into columns
            if (!empty($item['columns']) && is_array($item['columns'])) {
                $texts[] = self::extractSppbText($item['columns']);
            }

            // Recurse into addons
            if (!empty($item['addons']) && is_array($item['addons'])) {
                $texts[] = self::extractSppbText($item['addons']);
            }

            // Recurse into children (SP PB 4)
            if (!empty($item['children']) && is_array($item['children'])) {
                $texts[] = self::extractSppbText($item['children']);
            }

            // Recurse into inner items
            if (!empty($item['inner']) && is_array($item['inner'])) {
                $texts[] = self::extractSppbText($item['inner']);
            }
        }

        return implode("\n", array_filter($texts));
    }

    /**
     * Search the knowledge base for relevant chunks.
     */

    /**
     * Process Joomla articles as a KB source.
     */
    public static function processArticleSource(int $sourceId): array
    {
        $db = Factory::getContainer()->get('DatabaseDriver');

        $query = $db->getQuery(true)
            ->select('*')
            ->from($db->quoteName('#__seaichat_kb_sources'))
            ->where($db->quoteName('id') . ' = ' . (int) $sourceId);
        $db->setQuery($query);
        $source = $db->loadObject();

        if (!$source) {
            throw new \RuntimeException('Source not found.');
        }

        if (($source->source_type ?? '') !== 'articles') {
            throw new \RuntimeException('Source is not an articles type.');
        }

        // Update status
        $update = new \stdClass();
        $update->id = $sourceId;
        $update->crawl_status = 'processing';
        $update->crawl_error = '';
        $db->updateObject('#__seaichat_kb_sources', $update, 'id');

        try {
            // Delete existing chunks and pages for this source
            $db->setQuery('DELETE FROM ' . $db->quoteName('#__seaichat_kb_chunks') . ' WHERE source_id = ' . (int) $sourceId);
            $db->execute();
            $db->setQuery('DELETE FROM ' . $db->quoteName('#__seaichat_kb_pages') . ' WHERE source_id = ' . (int) $sourceId);
            $db->execute();

            // Build query for Joomla articles
            $artQuery = $db->getQuery(true)
                ->select('a.id, a.title, a.introtext, a.fulltext, a.alias, a.catid, c.title AS category_title')
                ->from($db->quoteName('#__content', 'a'))
                ->join('LEFT', $db->quoteName('#__categories', 'c') . ' ON c.id = a.catid')
                ->where($db->quoteName('a.state') . ' = 1');

            // Filter by categories if specified
            $selectedCats = trim($source->categories ?? '');
            if (!empty($selectedCats) && $selectedCats !== '0') {
                $catIds = array_filter(array_map('intval', explode(',', $selectedCats)));
                if (!empty($catIds)) {
                    $artQuery->where($db->quoteName('a.catid') . ' IN (' . implode(',', $catIds) . ')');
                }
            }

            $artQuery->order('a.catid ASC, a.ordering ASC, a.created DESC');
            $db->setQuery($artQuery);
            $articles = $db->loadObjectList() ?: [];

            $now = Factory::getDate()->toSql();
            $totalChunks = 0;
            $pageCount = 0;

            foreach ($articles as $article) {
                // Combine introtext and fulltext, strip HTML
                $rawHtml = ($article->introtext ?? '') . ' ' . ($article->fulltext ?? '');
                $text = strip_tags($rawHtml);
                $text = html_entity_decode($text, ENT_QUOTES, 'UTF-8');
                $text = preg_replace('/\s+/', ' ', $text);
                $text = trim($text);

                if (strlen($text) < 30) {
                    continue;
                }

                $articleTitle = $article->title;
                if (!empty($article->category_title)) {
                    $articleTitle = $article->category_title . ' > ' . $article->title;
                }

                // Create page entry
                $page = new \stdClass();
                $page->source_id = $sourceId;
                $page->url = 'article://' . (int) $article->id;
                $page->title = substr($articleTitle, 0, 500);
                $page->content_hash = md5($text);
                $page->crawled = $now;
                $db->insertObject('#__seaichat_kb_pages', $page, 'id');
                $pageId = (int) $page->id;
                $pageCount++;

                // Chunk the text
                $chunks = self::chunkText($text);
                foreach ($chunks as $chunkIdx => $chunkText) {
                    $keywords = self::extractKeywords($chunkText . ' ' . $articleTitle);
                    $chunk = new \stdClass();
                    $chunk->source_id = $sourceId;
                    $chunk->page_id = $pageId;
                    $chunk->page_title = substr($articleTitle, 0, 500);
                    $chunk->page_url = 'article://' . (int) $article->id;
                    $chunk->content = $chunkText;
                    $chunk->keywords = $keywords;
                    $chunk->chunk_index = $chunkIdx;
                    $chunk->token_count = (int) (str_word_count($chunkText) * 1.3);
                    $db->insertObject('#__seaichat_kb_chunks', $chunk);
                    $totalChunks++;
                }
            }

            // Update source with results
            $update = new \stdClass();
            $update->id = $sourceId;
            $update->crawl_status = 'complete';
            $update->last_crawled = $now;
            $update->page_count = $pageCount;
            $update->chunk_count = $totalChunks;
            $update->crawl_error = '';
            $db->updateObject('#__seaichat_kb_sources', $update, 'id');

            return ['pages' => $pageCount, 'chunks' => $totalChunks];
        } catch (\Exception $e) {
            $update = new \stdClass();
            $update->id = $sourceId;
            $update->crawl_status = 'error';
            $update->crawl_error = $e->getMessage();
            $db->updateObject('#__seaichat_kb_sources', $update, 'id');
            throw $e;
        }
    }

    public static function searchKnowledge(string $query, int $limit = 5): array
    {
        $db = Factory::getContainer()->get('DatabaseDriver');

        // Strip stop words and short words from the user query to improve matching
        $stopWords = ['the', 'a', 'an', 'is', 'are', 'was', 'were', 'be', 'been', 'being',
            'have', 'has', 'had', 'do', 'does', 'did', 'will', 'would', 'could', 'should',
            'may', 'might', 'can', 'shall', 'to', 'of', 'in', 'for', 'on', 'with', 'at',
            'by', 'from', 'as', 'into', 'through', 'during', 'before', 'after', 'above',
            'below', 'between', 'and', 'but', 'or', 'nor', 'not', 'so', 'yet', 'both',
            'either', 'neither', 'each', 'every', 'all', 'any', 'few', 'more', 'most',
            'other', 'some', 'such', 'no', 'only', 'own', 'same', 'than', 'too', 'very',
            'just', 'because', 'if', 'when', 'where', 'how', 'what', 'which', 'who',
            'this', 'that', 'these', 'those', 'it', 'its', 'they', 'them', 'their',
            'we', 'us', 'our', 'you', 'your', 'he', 'she', 'him', 'her', 'his',
            'i', 'me', 'my', 'about', 'up', 'out', 'then', 'also', 'over', 'new',
            'tell', 'know', 'get', 'got', 'like', 'want', 'need', 'find', 'show',
            'give', 'make', 'made', 'let', 'see', 'say', 'said', 'ask', 'asked',
            'think', 'use', 'used', 'much', 'many', 'well', 'way', 'look', 'thing',
            'went', 'come', 'came', 'take', 'took', 'put', 'back', 'still', 'here',
            'there', 'why', 'did', 'play', 'played', 'game', 'match', 'against',
            'happen', 'happened', 'result', 'results', 'info', 'information', 'details'];

        $rawWords = preg_split('/\s+/', strtolower(trim($query)));
        $keywords = array_values(array_filter($rawWords, function ($w) use ($stopWords) {
            return strlen($w) > 2 && !in_array($w, $stopWords);
        }));

        // If all words were stripped, fall back to the raw words > 2 chars
        if (empty($keywords)) {
            $keywords = array_values(array_filter($rawWords, function ($w) {
                return strlen($w) > 2;
            }));
        }

        if (empty($keywords)) {
            return [];
        }

        $results = [];

        // 1) Try BOOLEAN MODE with all keywords required (+word)
        $booleanTerms = array_map(function ($w) {
            // Clean word for boolean mode — remove special chars
            $clean = preg_replace('/[^a-z0-9]/', '', $w);
            return !empty($clean) ? '+' . $clean . '*' : '';
        }, $keywords);
        $booleanTerms = array_filter($booleanTerms);

        if (!empty($booleanTerms)) {
            $booleanQuery = implode(' ', $booleanTerms);
            $sql = $db->getQuery(true)
                ->select('c.*, MATCH(c.content, c.keywords, c.page_title) AGAINST (' . $db->quote($booleanQuery) . ' IN BOOLEAN MODE) AS relevance')
                ->from($db->quoteName('#__seaichat_kb_chunks', 'c'))
                ->join('INNER', $db->quoteName('#__seaichat_kb_sources', 's') . ' ON s.id = c.source_id AND s.published = 1')
                ->where('MATCH(c.content, c.keywords, c.page_title) AGAINST (' . $db->quote($booleanQuery) . ' IN BOOLEAN MODE)')
                ->order('relevance DESC');
            $db->setQuery($sql, 0, $limit);
            $results = $db->loadObjectList() ?: [];
        }

        // 2) If boolean AND matched nothing, try boolean OR (any keyword)
        if (empty($results) && !empty($keywords)) {
            $orTerms = array_map(function ($w) {
                $clean = preg_replace('/[^a-z0-9]/', '', $w);
                return !empty($clean) ? $clean . '*' : '';
            }, $keywords);
            $orTerms = array_filter($orTerms);

            if (!empty($orTerms)) {
                $orQuery = implode(' ', $orTerms);
                $sql = $db->getQuery(true)
                    ->select('c.*, MATCH(c.content, c.keywords, c.page_title) AGAINST (' . $db->quote($orQuery) . ' IN BOOLEAN MODE) AS relevance')
                    ->from($db->quoteName('#__seaichat_kb_chunks', 'c'))
                    ->join('INNER', $db->quoteName('#__seaichat_kb_sources', 's') . ' ON s.id = c.source_id AND s.published = 1')
                    ->where('MATCH(c.content, c.keywords, c.page_title) AGAINST (' . $db->quote($orQuery) . ' IN BOOLEAN MODE)')
                    ->order('relevance DESC');
                $db->setQuery($sql, 0, $limit);
                $results = $db->loadObjectList() ?: [];
            }
        }

        // 3) LIKE fallback — ranked by number of matching keywords
        //    For words 5+ chars, also try a truncated prefix to catch misspellings
        //    e.g. "carlise" (misspelling) → "%carli%" will match "carlisle"
        if (empty($results)) {
            $likeConditions = [];
            $matchCountParts = [];
            $binds = [];
            $i = 0;

            foreach (array_slice($keywords, 0, 8) as $word) {
                $clean = preg_replace('/[^a-z0-9]/', '', $word);
                if (empty($clean)) continue;

                $paramExact = ':kw' . $i;
                $exactPattern = '%' . $clean . '%';

                // For words 5+ chars, also create a truncated prefix pattern (first 75% of chars, min 4)
                $prefixLen = max(4, (int) floor(strlen($clean) * 0.75));
                $usePrefix = (strlen($clean) >= 5 && $prefixLen < strlen($clean));

                if ($usePrefix) {
                    $paramPrefix = ':kwp' . $i;
                    $prefixPattern = '%' . substr($clean, 0, $prefixLen) . '%';

                    $likeConditions[] = '(' . $db->quoteName('c.content') . ' LIKE ' . $paramExact
                        . ' OR ' . $db->quoteName('c.keywords') . ' LIKE ' . $paramExact
                        . ' OR ' . $db->quoteName('c.page_title') . ' LIKE ' . $paramExact
                        . ' OR ' . $db->quoteName('c.content') . ' LIKE ' . $paramPrefix
                        . ' OR ' . $db->quoteName('c.keywords') . ' LIKE ' . $paramPrefix
                        . ' OR ' . $db->quoteName('c.page_title') . ' LIKE ' . $paramPrefix . ')';

                    // Score: 2 points for exact, 1 for prefix match
                    $matchCountParts[] = '(CASE WHEN ' . $db->quoteName('c.content') . ' LIKE ' . $paramExact
                        . ' OR ' . $db->quoteName('c.keywords') . ' LIKE ' . $paramExact
                        . ' OR ' . $db->quoteName('c.page_title') . ' LIKE ' . $paramExact
                        . ' THEN 2 WHEN ' . $db->quoteName('c.content') . ' LIKE ' . $paramPrefix
                        . ' OR ' . $db->quoteName('c.keywords') . ' LIKE ' . $paramPrefix
                        . ' OR ' . $db->quoteName('c.page_title') . ' LIKE ' . $paramPrefix
                        . ' THEN 1 ELSE 0 END)';

                    $binds[$paramExact] = $exactPattern;
                    $binds[$paramPrefix] = $prefixPattern;
                } else {
                    $likeConditions[] = '(' . $db->quoteName('c.content') . ' LIKE ' . $paramExact
                        . ' OR ' . $db->quoteName('c.keywords') . ' LIKE ' . $paramExact
                        . ' OR ' . $db->quoteName('c.page_title') . ' LIKE ' . $paramExact . ')';

                    $matchCountParts[] = '(CASE WHEN ' . $db->quoteName('c.content') . ' LIKE ' . $paramExact
                        . ' OR ' . $db->quoteName('c.keywords') . ' LIKE ' . $paramExact
                        . ' OR ' . $db->quoteName('c.page_title') . ' LIKE ' . $paramExact
                        . ' THEN 2 ELSE 0 END)';

                    $binds[$paramExact] = $exactPattern;
                }
                $i++;
            }

            $matchScore = implode(' + ', $matchCountParts);

            $sql = $db->getQuery(true)
                ->select('c.*, (' . $matchScore . ') AS relevance')
                ->from($db->quoteName('#__seaichat_kb_chunks', 'c'))
                ->join('INNER', $db->quoteName('#__seaichat_kb_sources', 's') . ' ON s.id = c.source_id AND s.published = 1')
                ->where('(' . implode(' OR ', $likeConditions) . ')')
                ->order('relevance DESC, c.id ASC');

            foreach ($binds as $param => $val) {
                $sql->bind($param, $val);
            }

            $db->setQuery($sql, 0, $limit);
            $results = $db->loadObjectList() ?: [];
        }

        // 4) Last resort — short prefix matching for misspellings
        //    "carlise" → "%carl%" will match "carlisle"
        //    Try AND first (all keywords must partially match), then OR
        if (empty($results)) {
            $fuzzyConditions = [];
            foreach (array_slice($keywords, 0, 5) as $word) {
                $clean = preg_replace('/[^a-z0-9]/', '', $word);
                if (strlen($clean) < 3) continue;
                $shortPrefix = substr($clean, 0, min(4, strlen($clean)));
                $fuzzyConditions[] = '(' . $db->quoteName('c.content') . ' LIKE ' . $db->quote('%' . $shortPrefix . '%')
                    . ' OR ' . $db->quoteName('c.keywords') . ' LIKE ' . $db->quote('%' . $shortPrefix . '%')
                    . ' OR ' . $db->quoteName('c.page_title') . ' LIKE ' . $db->quote('%' . $shortPrefix . '%') . ')';
            }

            if (!empty($fuzzyConditions)) {
                // Try AND — all keywords must partially match
                $sql = $db->getQuery(true)
                    ->select('c.*')
                    ->from($db->quoteName('#__seaichat_kb_chunks', 'c'))
                    ->join('INNER', $db->quoteName('#__seaichat_kb_sources', 's') . ' ON s.id = c.source_id AND s.published = 1')
                    ->where('(' . implode(' AND ', $fuzzyConditions) . ')')
                    ->order('c.id ASC');
                $db->setQuery($sql, 0, $limit);
                $results = $db->loadObjectList() ?: [];

                // If AND is too strict, try OR
                if (empty($results)) {
                    $sql = $db->getQuery(true)
                        ->select('c.*')
                        ->from($db->quoteName('#__seaichat_kb_chunks', 'c'))
                        ->join('INNER', $db->quoteName('#__seaichat_kb_sources', 's') . ' ON s.id = c.source_id AND s.published = 1')
                        ->where('(' . implode(' OR ', $fuzzyConditions) . ')')
                        ->order('c.id ASC');
                    $db->setQuery($sql, 0, $limit);
                    $results = $db->loadObjectList() ?: [];
                }
            }
        }

        return $results;
    }

    /**
     * Process a text-based KB source into chunks.
     */
    public static function processTextSource(int $sourceId): array
    {
        $db = Factory::getContainer()->get('DatabaseDriver');

        // Load the source
        $query = $db->getQuery(true)
            ->select('*')
            ->from($db->quoteName('#__seaichat_kb_sources'))
            ->where($db->quoteName('id') . ' = ' . (int) $sourceId);
        $db->setQuery($query);
        $source = $db->loadObject();

        if (!$source) {
            throw new \RuntimeException('Source not found.');
        }

        if ($source->source_type !== 'text' || empty($source->content)) {
            throw new \RuntimeException('No text content to process. Please paste your documentation text and save first.');
        }

        // Update status to processing
        $update = new \stdClass();
        $update->id = $sourceId;
        $update->crawl_status = 'processing';
        $update->crawl_error = '';
        $db->updateObject('#__seaichat_kb_sources', $update, 'id');

        try {
            // Delete existing chunks and pages for this source
            $db->setQuery('DELETE FROM ' . $db->quoteName('#__seaichat_kb_chunks') . ' WHERE source_id = ' . (int) $sourceId);
            $db->execute();
            $db->setQuery('DELETE FROM ' . $db->quoteName('#__seaichat_kb_pages') . ' WHERE source_id = ' . (int) $sourceId);
            $db->execute();

            $content = $source->content;
            $title = $source->title;
            $now = Factory::getDate()->toSql();

            // Split content into sections by double newlines or headings
            $sections = self::splitTextIntoSections($content, $title);

            $totalChunks = 0;

            foreach ($sections as $idx => $section) {
                $sectionTitle = $section['title'];
                $sectionText = trim($section['text']);

                if (empty($sectionText) || strlen($sectionText) < 20) {
                    continue;
                }

                // Create a "page" entry for each section
                $page = new \stdClass();
                $page->source_id = $sourceId;
                $page->url = 'text://' . $sourceId . '/section/' . ($idx + 1);
                $page->title = $sectionTitle;
                $page->content_hash = md5($sectionText);
                $page->crawled = $now;
                $db->insertObject('#__seaichat_kb_pages', $page, 'id');
                $pageId = (int) $page->id;

                // Chunk the section text
                $chunks = self::chunkText($sectionText);

                foreach ($chunks as $chunkIdx => $chunkText) {
                    $keywords = self::extractKeywords($chunkText);

                    $chunk = new \stdClass();
                    $chunk->source_id = $sourceId;
                    $chunk->page_id = $pageId;
                    $chunk->page_title = $sectionTitle;
                    $chunk->page_url = 'text://' . $sourceId . '/section/' . ($idx + 1);
                    $chunk->content = $chunkText;
                    $chunk->keywords = $keywords;
                    $chunk->chunk_index = $chunkIdx;
                    $chunk->token_count = (int) (str_word_count($chunkText) * 1.3);
                    $db->insertObject('#__seaichat_kb_chunks', $chunk);
                    $totalChunks++;
                }
            }

            // Update source with results
            $update = new \stdClass();
            $update->id = $sourceId;
            $update->crawl_status = 'complete';
            $update->last_crawled = $now;
            $update->page_count = count($sections);
            $update->chunk_count = $totalChunks;
            $update->crawl_error = '';
            $db->updateObject('#__seaichat_kb_sources', $update, 'id');

            return ['pages' => count($sections), 'chunks' => $totalChunks];

        } catch (\Exception $e) {
            $update = new \stdClass();
            $update->id = $sourceId;
            $update->crawl_status = 'error';
            $update->crawl_error = $e->getMessage();
            $db->updateObject('#__seaichat_kb_sources', $update, 'id');

            throw $e;
        }
    }

    /**
     * Split a block of text into logical sections.
     * Splits on double newlines, markdown-style headings, or lines ending with colons.
     */
    private static function splitTextIntoSections(string $text, string $defaultTitle): array
    {
        $sections = [];
        $lines = explode("\n", $text);
        $currentTitle = $defaultTitle;
        $currentText = '';

        foreach ($lines as $line) {
            $trimmed = trim($line);

            // Detect headings: markdown # headings, lines ending with colon, or ALL CAPS short lines
            $isHeading = false;
            if (preg_match('/^#{1,4}\s+(.+)$/', $trimmed, $m)) {
                $isHeading = true;
                $headingTitle = trim($m[1]);
            } elseif (preg_match('/^[A-Z][A-Za-z0-9 \-&]{2,80}:$/', $trimmed)) {
                $isHeading = true;
                $headingTitle = rtrim($trimmed, ':');
            } elseif (strlen($trimmed) > 3 && strlen($trimmed) < 100 && $trimmed === strtoupper($trimmed) && preg_match('/[A-Z]/', $trimmed)) {
                $isHeading = true;
                $headingTitle = $trimmed;
            }

            if ($isHeading && !empty(trim($currentText))) {
                $sections[] = ['title' => $currentTitle, 'text' => $currentText];
                $currentTitle = $headingTitle ?? $trimmed;
                $currentText = '';
            } elseif ($isHeading) {
                $currentTitle = $headingTitle ?? $trimmed;
            } else {
                $currentText .= $line . "\n";
            }
        }

        // Don't forget the last section
        if (!empty(trim($currentText))) {
            $sections[] = ['title' => $currentTitle, 'text' => $currentText];
        }

        // If no sections were created, treat the whole text as one section
        if (empty($sections)) {
            $sections[] = ['title' => $defaultTitle, 'text' => $text];
        }

        return $sections;
    }
}
