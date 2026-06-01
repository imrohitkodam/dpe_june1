<?php

/**
 * @package     Joomla
 * @subpackage  com_seaichat
 *
 * @copyright   (C) 2026 SE Extensions
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace SolarEclipse\Component\SeAiChat\Administrator\Controller;

defined('_JEXEC') or die;

use Joomla\CMS\MVC\Controller\AdminController;
use Joomla\CMS\Factory;
use Joomla\CMS\Router\Route;
use SolarEclipse\Component\SeAiChat\Administrator\Helper\KbCrawlerHelper;
use SolarEclipse\Component\SeAiChat\Administrator\Helper\AiChatHelper;

class KnowledgebasesController extends AdminController
{
    public function getModel($name = 'Kbsource', $prefix = 'Administrator', $config = ['ignore_request' => true])
    {
        return parent::getModel($name, $prefix, $config);
    }

    /**
     * Index ALL published Joomla articles in one go.
     * Auto-creates a KB source entry (if one doesn't already exist) then processes it.
     */
    public function processallarticles(): void
    {
        $this->checkToken('get');

        $app = Factory::getApplication();
        $db = Factory::getContainer()->get('DatabaseDriver');

        try {
            // Check if an "All Articles" auto-created source already exists
            $query = $db->getQuery(true)
                ->select('id')
                ->from($db->quoteName('#__seaichat_kb_sources'))
                ->where($db->quoteName('source_type') . ' = ' . $db->quote('articles'))
                ->where($db->quoteName('url') . ' = ' . $db->quote('auto://all-articles'));
            $db->setQuery($query);
            $existingId = (int) $db->loadResult();

            if ($existingId > 0) {
                $sourceId = $existingId;
            } else {
                // Create a new source entry for all articles
                $now = \Joomla\CMS\Factory::getDate()->toSql();
                $source = new \stdClass();
                $source->title = 'All Joomla Articles';
                $source->source_type = 'articles';
                $source->url = 'auto://all-articles';
                $source->content = '';
                $source->categories = '';  // Empty = all categories
                $source->published = 1;
                $source->crawl_status = 'pending';
                $source->created = $now;
                $source->ordering = 0;
                $db->insertObject('#__seaichat_kb_sources', $source, 'id');
                $sourceId = (int) $source->id;
            }

            // Process all articles through the existing article processor
            $result = KbCrawlerHelper::processArticleSource($sourceId);

            $app->enqueueMessage(
                sprintf('All articles indexed: %d articles found, %d chunks created.', $result['pages'], $result['chunks']),
                'success'
            );
        } catch (\Exception $e) {
            $app->enqueueMessage('Error indexing articles: ' . $e->getMessage(), 'error');
        }

        $this->setRedirect(Route::_('index.php?option=com_seaichat&view=knowledgebases', false));
    }

    /**
     * Process/crawl ALL knowledge base sources at once.
     */
    public function processall(): void
    {
        $this->checkToken('get');

        $app = Factory::getApplication();
        $db = Factory::getContainer()->get('DatabaseDriver');

        // Load all published sources
        $query = $db->getQuery(true)
            ->select('*')
            ->from($db->quoteName('#__seaichat_kb_sources'))
            ->where($db->quoteName('published') . ' = 1')
            ->order('id ASC');
        $db->setQuery($query);
        $sources = $db->loadObjectList() ?: [];

        if (empty($sources)) {
            $app->enqueueMessage('No published knowledge base sources found.', 'warning');
            $this->setRedirect(Route::_('index.php?option=com_seaichat&view=knowledgebases', false));
            return;
        }

        $totalPages = 0;
        $totalChunks = 0;
        $successCount = 0;
        $errorCount = 0;
        $errors = [];

        foreach ($sources as $source) {
            try {
                $type = $source->source_type ?? 'url';
                switch ($type) {
                    case 'articles':
                        $result = KbCrawlerHelper::processArticleSource((int) $source->id);
                        break;
                    case 'sppb':
                        $result = KbCrawlerHelper::processSppbSource((int) $source->id);
                        break;
                    case 'text':
                        $result = KbCrawlerHelper::processTextSource((int) $source->id);
                        break;
                    default: // url
                        $result = KbCrawlerHelper::crawlSource((int) $source->id);
                        break;
                }
                $totalPages += $result['pages'] ?? 0;
                $totalChunks += $result['chunks'] ?? 0;
                $successCount++;
            } catch (\Exception $e) {
                $errorCount++;
                $errors[] = $source->title . ': ' . $e->getMessage();
            }
        }

        $app->enqueueMessage(
            sprintf(
                'Batch processing complete: %d of %d sources processed successfully — %d pages, %d chunks created.',
                $successCount,
                count($sources),
                $totalPages,
                $totalChunks
            ),
            'success'
        );

        if ($errorCount > 0) {
            foreach ($errors as $err) {
                $app->enqueueMessage('Error — ' . $err, 'error');
            }
        }

        $this->setRedirect(Route::_('index.php?option=com_seaichat&view=knowledgebases', false));
    }

    /**
     * Trigger a crawl for a specific source.
     */
    public function crawl(): void
    {
        $this->checkToken('get');

        $app = Factory::getApplication();
        $id = $app->getInput()->getInt('id', 0);

        if ($id <= 0) {
            $app->enqueueMessage('Invalid source ID.', 'error');
            $this->setRedirect(Route::_('index.php?option=com_seaichat&view=knowledgebases', false));
            return;
        }

        try {
            $result = KbCrawlerHelper::crawlSource($id);
            $app->enqueueMessage(
                sprintf('Crawl complete: %d pages found, %d chunks created.', $result['pages'], $result['chunks']),
                'success'
            );
        } catch (\Exception $e) {
            $app->enqueueMessage('Crawl error: ' . $e->getMessage(), 'error');
        }

        $this->setRedirect(Route::_('index.php?option=com_seaichat&view=knowledgebases', false));
    }

    /**
     * Process pasted text content for a source.
     */
    public function processtext(): void
    {
        $this->checkToken('get');

        $app = Factory::getApplication();
        $id = $app->getInput()->getInt('id', 0);

        if ($id <= 0) {
            $app->enqueueMessage('Invalid source ID.', 'error');
            $this->setRedirect(Route::_('index.php?option=com_seaichat&view=knowledgebases', false));
            return;
        }

        try {
            $result = KbCrawlerHelper::processTextSource($id);
            $app->enqueueMessage(
                sprintf('Text processed: %d chunks created.', $result['chunks']),
                'success'
            );
        } catch (\Exception $e) {
            $app->enqueueMessage('Processing error: ' . $e->getMessage(), 'error');
        }

        $this->setRedirect(Route::_('index.php?option=com_seaichat&view=knowledgebases', false));
    }

    /**
     * Process Joomla articles for a KB source.
     */
    public function processarticles(): void
    {
        $this->checkToken('get');

        $app = Factory::getApplication();
        $id = $app->getInput()->getInt('id', 0);

        if ($id <= 0) {
            $app->enqueueMessage('Invalid source ID.', 'error');
            $this->setRedirect(Route::_('index.php?option=com_seaichat&view=knowledgebases', false));
            return;
        }

        try {
            $result = KbCrawlerHelper::processArticleSource($id);
            $app->enqueueMessage(
                sprintf('Articles processed: %d articles found, %d chunks created.', $result['pages'], $result['chunks']),
                'success'
            );
        } catch (\Exception $e) {
            $app->enqueueMessage('Processing error: ' . $e->getMessage(), 'error');
        }

        $this->setRedirect(Route::_('index.php?option=com_seaichat&view=knowledgebases', false));
    }

    /**
     * Process SP Page Builder pages for a KB source.
     */
    public function processsppb(): void
    {
        $this->checkToken('get');

        $app = Factory::getApplication();
        $id = $app->getInput()->getInt('id', 0);

        if ($id <= 0) {
            $app->enqueueMessage('Invalid source ID.', 'error');
            $this->setRedirect(Route::_('index.php?option=com_seaichat&view=knowledgebases', false));
            return;
        }

        try {
            $result = KbCrawlerHelper::processSppbSource($id);
            $app->enqueueMessage(
                sprintf('SP Page Builder pages processed: %d pages found, %d chunks created.', $result['pages'], $result['chunks']),
                'success'
            );
        } catch (\Exception $e) {
            $app->enqueueMessage('Processing error: ' . $e->getMessage(), 'error');
        }

        $this->setRedirect(Route::_('index.php?option=com_seaichat&view=knowledgebases', false));
    }

    /**
     * Test an API key pasted directly by the user (POST).
     */
    public function testdirect(): void
    {
        $this->checkToken();

        $app = Factory::getApplication();
        $apiKey = trim($app->getInput()->post->getRaw('api_key', ''));
        $postProvider = trim($app->getInput()->post->getString('provider', ''));
        $postModel = trim($app->getInput()->post->getString('model', ''));

        if (empty($apiKey)) {
            $this->sendJson(['success' => false, 'error' => 'No API key provided.']);
            return;
        }

        // Use posted provider/model if available, otherwise read from DB
        if (!empty($postProvider)) {
            $provider = $postProvider;
            $model = !empty($postModel) ? $postModel : $this->getDefaultModel($provider);
        } else {
            $db = Factory::getContainer()->get('DatabaseDriver');
            $query = $db->getQuery(true)
                ->select($db->quoteName('params'))
                ->from($db->quoteName('#__extensions'))
                ->where($db->quoteName('element') . ' = ' . $db->quote('com_seaichat'))
                ->where($db->quoteName('type') . ' = ' . $db->quote('component'));
            $db->setQuery($query);
            $paramsDecoded = json_decode($db->loadResult(), true) ?: [];
            $provider = $paramsDecoded['aichat_provider'] ?? 'anthropic';
            $model = AiChatHelper::getModelForProviderStatic($provider, $paramsDecoded);
        }

        try {
            $testMessages = [['role' => 'user', 'content' => 'Say "Connection successful" and nothing else.']];
            $result = self::testProviderConnection($provider, $apiKey, $model, $testMessages);

            if (!empty($result['error'])) {
                $this->sendJson(['success' => false, 'error' => $result['error']]);
            } else {
                $this->sendJson([
                    'success' => true,
                    'model' => $model,
                    'provider' => ucfirst($provider),
                    'response' => $result['content'],
                ]);
            }
        } catch (\Exception $e) {
            $this->sendJson(['success' => false, 'error' => 'Exception: ' . $e->getMessage()]);
        }
    }

    private function getDefaultModel(string $provider): string
    {
        $defaults = [
            'anthropic' => 'claude-sonnet-4-20250514',
            'openai'    => 'gpt-4o-mini',
            'gemini'    => 'gemini-2.5-flash',
            'deepseek'  => 'deepseek-chat',
        ];
        return $defaults[$provider] ?? 'gpt-4o-mini';
    }

    /**
     * Save an API key directly to the database, bypassing Joomla's config form.
     */
    public function savekey(): void
    {
        $this->checkToken();

        $app = Factory::getApplication();
        $apiKey = trim($app->getInput()->post->getRaw('api_key', ''));

        if (empty($apiKey)) {
            $this->sendJson(['success' => false, 'error' => 'No API key provided.']);
            return;
        }

        try {
            $db = Factory::getContainer()->get('DatabaseDriver');

            // Read current params
            $query = $db->getQuery(true)
                ->select($db->quoteName('params'))
                ->from($db->quoteName('#__extensions'))
                ->where($db->quoteName('element') . ' = ' . $db->quote('com_seaichat'))
                ->where($db->quoteName('type') . ' = ' . $db->quote('component'));
            $db->setQuery($query);
            $rawParams = $db->loadResult();
            $paramsArray = json_decode($rawParams, true) ?: [];

            // Update the API key
            $paramsArray['aichat_api_key'] = $apiKey;

            // Write back
            $newParams = json_encode($paramsArray);
            $query = $db->getQuery(true)
                ->update($db->quoteName('#__extensions'))
                ->set($db->quoteName('params') . ' = ' . $db->quote($newParams))
                ->where($db->quoteName('element') . ' = ' . $db->quote('com_seaichat'))
                ->where($db->quoteName('type') . ' = ' . $db->quote('component'));
            $db->setQuery($query);
            $db->execute();

            // Clear Joomla's component params cache
            $refClass = new \ReflectionClass(\Joomla\CMS\Component\ComponentHelper::class);
            $refProp = $refClass->getProperty('components');
            $refProp->setAccessible(true);
            $components = $refProp->getValue();
            if (isset($components['com_seaichat'])) {
                unset($components['com_seaichat']);
                $refProp->setValue(null, $components);
            }

            $this->sendJson(['success' => true]);
        } catch (\Exception $e) {
            $this->sendJson(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
        }
    }

    /**
     * Save all AI chat settings directly to the database.
     */
    public function savesettings(): void
    {
        $this->checkToken();

        $app = Factory::getApplication();
        $settingsJson = $app->getInput()->post->getRaw('settings', '');
        $settings = json_decode($settingsJson, true);

        if (empty($settings) || !is_array($settings)) {
            $this->sendJson(['success' => false, 'error' => 'No settings provided.']);
            return;
        }

        // Whitelist of allowed settings keys
        $allowed = [
            'aichat_enabled', 'aichat_menu_items', 'aichat_provider',
            'aichat_model', 'aichat_model_openai', 'aichat_model_gemini', 'aichat_model_deepseek',
            'aichat_max_messages', 'aichat_system_prompt', 'aichat_welcome_message',
            'aichat_widget_position', 'aichat_primary_color',
            'aichat_header_title',
                'aichat_contact_url', 'aichat_contact_text', 'aichat_contact_target',
            'aichat_cta_enabled', 'aichat_sources_enabled',
            'aichat_cta_bg_color', 'aichat_cta_text_color',
            'aichat_cta_max', 'aichat_sources_max',
        ];

        try {
            $db = Factory::getContainer()->get('DatabaseDriver');

            $query = $db->getQuery(true)
                ->select($db->quoteName('params'))
                ->from($db->quoteName('#__extensions'))
                ->where($db->quoteName('element') . ' = ' . $db->quote('com_seaichat'))
                ->where($db->quoteName('type') . ' = ' . $db->quote('component'));
            $db->setQuery($query);
            $paramsArray = json_decode($db->loadResult(), true) ?: [];

            foreach ($settings as $key => $value) {
                if (in_array($key, $allowed)) {
                    $paramsArray[$key] = $value;
                }
            }

            $query = $db->getQuery(true)
                ->update($db->quoteName('#__extensions'))
                ->set($db->quoteName('params') . ' = ' . $db->quote(json_encode($paramsArray)))
                ->where($db->quoteName('element') . ' = ' . $db->quote('com_seaichat'))
                ->where($db->quoteName('type') . ' = ' . $db->quote('component'));
            $db->setQuery($query);
            $db->execute();

            // Clear component params cache
            try {
                $refClass = new \ReflectionClass(\Joomla\CMS\Component\ComponentHelper::class);
                $refProp = $refClass->getProperty('components');
                $refProp->setAccessible(true);
                $components = $refProp->getValue();
                if (isset($components['com_seaichat'])) {
                    unset($components['com_seaichat']);
                    $refProp->setValue(null, $components);
                }
            } catch (\Exception $e) {}

            $this->sendJson(['success' => true]);
        } catch (\Exception $e) {
            $this->sendJson(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
        }
    }

    /**
     * Send a JSON response and close.
     */
    /**
     * Test a provider connection with the given API key.
     */
    private static function testProviderConnection(string $provider, string $apiKey, string $model, array $messages): array
    {
        // Use a minimal system prompt for testing
        $systemPrompt = 'You are a test assistant. Respond briefly.';

        // Use the AiChatHelper methods via reflection or just call the public method
        switch ($provider) {
            case 'openai':
                return self::testOpenAi($apiKey, $model, $messages);
            case 'gemini':
                return self::testGemini($apiKey, $model, $messages);
            case 'deepseek':
                return self::testDeepSeek($apiKey, $model, $messages);
            default:
                return self::testAnthropic($apiKey, $model, $messages);
        }
    }

    private static function testAnthropic(string $apiKey, string $model, array $messages): array
    {
        $ch = curl_init('https://api.anthropic.com/v1/messages');
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode(['model' => $model, 'max_tokens' => 50, 'messages' => $messages]),
            CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 20,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json', 'x-api-key: ' . $apiKey, 'anthropic-version: 2023-06-01'],
        ]);
        $body = curl_exec($ch); $http = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE); $err = curl_error($ch); curl_close($ch);
        if ($body === false || $http === 0) return ['error' => 'Connection failed: ' . ($err ?: 'No response')];
        if ($http !== 200) { $d = json_decode($body, true); return ['error' => ($d['error']['message'] ?? 'HTTP ' . $http)]; }
        $d = json_decode($body, true);
        return ['content' => $d['content'][0]['text'] ?? 'OK'];
    }

    private static function testOpenAi(string $apiKey, string $model, array $messages): array
    {
        $apiMessages = array_merge([['role' => 'system', 'content' => 'Respond briefly.']], $messages);
        $ch = curl_init('https://api.openai.com/v1/chat/completions');
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode(['model' => $model, 'max_tokens' => 50, 'messages' => $apiMessages]),
            CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 20,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json', 'Authorization: Bearer ' . $apiKey],
        ]);
        $body = curl_exec($ch); $http = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE); $err = curl_error($ch); curl_close($ch);
        if ($body === false || $http === 0) return ['error' => 'Connection failed: ' . ($err ?: 'No response')];
        if ($http !== 200) { $d = json_decode($body, true); return ['error' => ($d['error']['message'] ?? 'HTTP ' . $http)]; }
        $d = json_decode($body, true);
        return ['content' => $d['choices'][0]['message']['content'] ?? 'OK'];
    }

    private static function testGemini(string $apiKey, string $model, array $messages): array
    {
        $contents = [];
        foreach ($messages as $m) {
            $contents[] = ['role' => 'user', 'parts' => [['text' => $m['content']]]];
        }
        $url = 'https://generativelanguage.googleapis.com/v1beta/models/' . $model . ':generateContent?key=' . $apiKey;
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode(['contents' => $contents, 'generationConfig' => ['maxOutputTokens' => 50]]),
            CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 20,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
        ]);
        $body = curl_exec($ch); $http = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE); $err = curl_error($ch); curl_close($ch);
        if ($body === false || $http === 0) return ['error' => 'Connection failed: ' . ($err ?: 'No response')];
        if ($http !== 200) { $d = json_decode($body, true); return ['error' => ($d['error']['message'] ?? 'HTTP ' . $http)]; }
        $d = json_decode($body, true);
        return ['content' => $d['candidates'][0]['content']['parts'][0]['text'] ?? 'OK'];
    }

    private static function testDeepSeek(string $apiKey, string $model, array $messages): array
    {
        $apiMessages = array_merge([['role' => 'system', 'content' => 'Respond briefly.']], $messages);
        $ch = curl_init('https://api.deepseek.com/chat/completions');
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode(['model' => $model, 'max_tokens' => 50, 'messages' => $apiMessages]),
            CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json', 'Authorization: Bearer ' . $apiKey],
        ]);
        $body = curl_exec($ch); $http = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE); $err = curl_error($ch); curl_close($ch);
        if ($body === false || $http === 0) return ['error' => 'Connection failed: ' . ($err ?: 'No response')];
        if ($http !== 200) { $d = json_decode($body, true); return ['error' => ($d['error']['message'] ?? 'HTTP ' . $http)]; }
        $d = json_decode($body, true);
        return ['content' => $d['choices'][0]['message']['content'] ?? 'OK'];
    }

    private function sendJson(array $data): void
    {
        @ob_end_clean();
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data);
        die;
    }
}
