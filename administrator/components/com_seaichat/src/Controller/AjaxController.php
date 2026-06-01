<?php


/**
 * @package     Joomla
 * @subpackage  com_seaichat
 *
 * @copyright   (C) 2026 SE Extensions
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

declare(strict_types=1);

namespace SolarEclipse\Component\SeAiChat\Administrator\Controller;

defined('_JEXEC') or die;

use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Factory;
use Joomla\CMS\Session\Session;
use SolarEclipse\Component\SeAiChat\Administrator\Helper\AiChatHelper;
use SolarEclipse\Component\SeAiChat\Administrator\Helper\PluginHelper;

/**
 * Dedicated AJAX controller for settings operations.
 * Extends BaseController (not AdminController) to avoid list-management overhead.
 */
class AjaxController extends BaseController
{
    /**
     * Test an API key.
     */
    public function testdirect(): void
    {
        if (!$this->validateToken()) return;

        try {
            $app = Factory::getApplication();
            $apiKey = trim($app->getInput()->post->getRaw('api_key', ''));
            $provider = trim($app->getInput()->post->getString('provider', 'anthropic'));
            $model = trim($app->getInput()->post->getString('model', ''));

            if (empty($apiKey)) {
                $this->sendJson(['success' => false, 'error' => 'No API key provided.']);
                return;
            }

            if (empty($model)) {
                $model = $this->getDefaultModel($provider);
            }

            $testMessages = [['role' => 'user', 'content' => 'Say "Connection successful" and nothing else.']];
            $result = $this->testProvider($provider, $apiKey, $model, $testMessages);

            if (!empty($result['error'])) {
                $this->sendJson(['success' => false, 'error' => $result['error']]);
            } else {
                $this->sendJson(['success' => true, 'model' => $model, 'provider' => ucfirst($provider), 'response' => $result['content']]);
            }
        } catch (\Exception $e) {
            $this->sendJson(['success' => false, 'error' => 'Exception: ' . $e->getMessage()]);
        }
    }

    /**
     * Save an API key.
     */
    public function savekey(): void
    {
        if (!$this->validateToken()) return;

        try {
            $apiKey = trim(Factory::getApplication()->getInput()->post->getRaw('api_key', ''));
            if (empty($apiKey)) {
                $this->sendJson(['success' => false, 'error' => 'No API key provided.']);
                return;
            }

            $db = Factory::getContainer()->get('DatabaseDriver');
            $query = $db->getQuery(true)->select($db->quoteName('params'))->from($db->quoteName('#__extensions'))
                ->where($db->quoteName('element') . ' = ' . $db->quote('com_seaichat'))
                ->where($db->quoteName('type') . ' = ' . $db->quote('component'));
            $db->setQuery($query);
            $paramsArray = json_decode($db->loadResult(), true) ?: [];
            $paramsArray['aichat_api_key'] = $apiKey;

            $query = $db->getQuery(true)->update($db->quoteName('#__extensions'))
                ->set($db->quoteName('params') . ' = ' . $db->quote(json_encode($paramsArray)))
                ->where($db->quoteName('element') . ' = ' . $db->quote('com_seaichat'))
                ->where($db->quoteName('type') . ' = ' . $db->quote('component'));
            $db->setQuery($query);
            $db->execute();

            // Clear component params cache
            $this->clearParamsCache();

            $this->sendJson(['success' => true]);
        } catch (\Exception $e) {
            $this->sendJson(['success' => false, 'error' => 'Error: ' . $e->getMessage()]);
        }
    }

    /**
     * Save all settings.
     */
    public function savesettings(): void
    {
        if (!$this->validateToken()) return;

        try {
            $settingsJson = Factory::getApplication()->getInput()->post->getRaw('settings', '');
            $settings = json_decode($settingsJson, true);

            if (empty($settings) || !is_array($settings)) {
                $this->sendJson(['success' => false, 'error' => 'No settings received.']);
                return;
            }

            $allowed = [
                'aichat_enabled', 'aichat_menu_items', 'aichat_provider',
                'aichat_model', 'aichat_model_openai', 'aichat_model_gemini', 'aichat_model_deepseek',
                'aichat_max_messages', 'aichat_system_prompt', 'aichat_welcome_message',
                'aichat_placeholder_text',
                'aichat_widget_position', 'aichat_primary_color',
                'aichat_header_title', 'aichat_avatar',
                'aichat_contact_url', 'aichat_contact_text', 'aichat_contact_target',
                'aichat_gdpr_enabled', 'aichat_gdpr_text', 'aichat_gdpr_privacy_url',
                'aichat_cta_enabled', 'aichat_sources_enabled',
                'aichat_cta_max', 'aichat_sources_max',
                'aichat_cta_bg_color', 'aichat_cta_text_color',
                'aichat_str_status_online', 'aichat_str_chat_with_ai', 'aichat_str_new_conversation',
                'aichat_str_close', 'aichat_str_send', 'aichat_str_gdpr_title',
                'aichat_str_gdpr_accept', 'aichat_str_gdpr_decline',
                'aichat_str_gdpr_unavailable_title', 'aichat_str_gdpr_unavailable_msg',
                'aichat_str_error_prefix', 'aichat_str_error_generic',
            ];

            $db = Factory::getContainer()->get('DatabaseDriver');
            $query = $db->getQuery(true)->select($db->quoteName('params'))->from($db->quoteName('#__extensions'))
                ->where($db->quoteName('element') . ' = ' . $db->quote('com_seaichat'))
                ->where($db->quoteName('type') . ' = ' . $db->quote('component'));
            $db->setQuery($query);
            $paramsArray = json_decode($db->loadResult(), true) ?: [];

            foreach ($settings as $key => $value) {
                if (in_array($key, $allowed)) {
                    $paramsArray[$key] = $value;
                }
            }

            $query = $db->getQuery(true)->update($db->quoteName('#__extensions'))
                ->set($db->quoteName('params') . ' = ' . $db->quote(json_encode($paramsArray)))
                ->where($db->quoteName('element') . ' = ' . $db->quote('com_seaichat'))
                ->where($db->quoteName('type') . ' = ' . $db->quote('component'));
            $db->setQuery($query);
            $db->execute();

            $this->clearParamsCache();

            $this->sendJson(['success' => true]);
        } catch (\Exception $e) {
            $this->sendJson(['success' => false, 'error' => 'Error: ' . $e->getMessage()]);
        }
    }

    /**
     * Repair the system plugin.
     */
    public function repairplugin(): void
    {
        if (!$this->validateToken()) return;

        try {
            $result = PluginHelper::ensurePluginInstalled();
            $this->sendJson($result);
        } catch (\Exception $e) {
            $this->sendJson(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
        }
    }

    /**
     * Upload a custom avatar image.
     */
    public function uploadavatar(): void
    {
        if (!$this->validateToken()) return;

        try {
            $app = Factory::getApplication();
            $file = $app->getInput()->files->get('avatar_file', null, 'raw');

            if (empty($file) || empty($file['tmp_name']) || $file['error'] !== UPLOAD_ERR_OK) {
                $this->sendJson(['success' => false, 'error' => 'No file uploaded or upload error.']);
                return;
            }

            // Validate file type
            $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/svg+xml'];
            $finfo = new \finfo(FILEINFO_MIME_TYPE);
            $mimeType = $finfo->file($file['tmp_name']);

            if (!in_array($mimeType, $allowedTypes)) {
                $this->sendJson(['success' => false, 'error' => 'Invalid file type. Allowed: JPG, PNG, GIF, WebP, SVG.']);
                return;
            }

            // Validate file size (max 2MB)
            if ($file['size'] > 2 * 1024 * 1024) {
                $this->sendJson(['success' => false, 'error' => 'File too large. Maximum size is 2MB.']);
                return;
            }

            // Determine extension from mime type
            $extMap = [
                'image/jpeg' => 'jpg', 'image/png' => 'png', 'image/gif' => 'gif',
                'image/webp' => 'webp', 'image/svg+xml' => 'svg',
            ];
            $ext = $extMap[$mimeType] ?? 'png';

            // Save to media/com_seaichat/images/
            $destDir = JPATH_ROOT . '/media/com_seaichat/images';
            if (!is_dir($destDir)) {
                mkdir($destDir, 0755, true);
            }

            $filename = 'avatar.' . $ext;
            $destPath = $destDir . '/' . $filename;

            // Remove any existing avatar files
            foreach (glob($destDir . '/avatar.*') as $oldFile) {
                if (is_file($oldFile)) {
                    unlink($oldFile);
                }
            }

            if (!move_uploaded_file($file['tmp_name'], $destPath)) {
                $this->sendJson(['success' => false, 'error' => 'Failed to save the file.']);
                return;
            }

            // Save the avatar path in component params
            $avatarUrl = 'media/com_seaichat/images/' . $filename;

            $db = Factory::getContainer()->get('DatabaseDriver');
            $query = $db->getQuery(true)->select($db->quoteName('params'))->from($db->quoteName('#__extensions'))
                ->where($db->quoteName('element') . ' = ' . $db->quote('com_seaichat'))
                ->where($db->quoteName('type') . ' = ' . $db->quote('component'));
            $db->setQuery($query);
            $paramsArray = json_decode($db->loadResult(), true) ?: [];
            $paramsArray['aichat_avatar'] = $avatarUrl;

            $query = $db->getQuery(true)->update($db->quoteName('#__extensions'))
                ->set($db->quoteName('params') . ' = ' . $db->quote(json_encode($paramsArray)))
                ->where($db->quoteName('element') . ' = ' . $db->quote('com_seaichat'))
                ->where($db->quoteName('type') . ' = ' . $db->quote('component'));
            $db->setQuery($query);
            $db->execute();
            $this->clearParamsCache();

            $this->sendJson(['success' => true, 'avatar_url' => $avatarUrl]);
        } catch (\Exception $e) {
            $this->sendJson(['success' => false, 'error' => 'Error: ' . $e->getMessage()]);
        }
    }

    /**
     * Remove the custom avatar image (revert to default robot icon).
     */
    public function removeavatar(): void
    {
        if (!$this->validateToken()) return;

        try {
            $destDir = JPATH_ROOT . '/media/com_seaichat/images';
            foreach (glob($destDir . '/avatar.*') as $oldFile) {
                if (is_file($oldFile)) {
                    unlink($oldFile);
                }
            }

            $db = Factory::getContainer()->get('DatabaseDriver');
            $query = $db->getQuery(true)->select($db->quoteName('params'))->from($db->quoteName('#__extensions'))
                ->where($db->quoteName('element') . ' = ' . $db->quote('com_seaichat'))
                ->where($db->quoteName('type') . ' = ' . $db->quote('component'));
            $db->setQuery($query);
            $paramsArray = json_decode($db->loadResult(), true) ?: [];
            unset($paramsArray['aichat_avatar']);

            $query = $db->getQuery(true)->update($db->quoteName('#__extensions'))
                ->set($db->quoteName('params') . ' = ' . $db->quote(json_encode($paramsArray)))
                ->where($db->quoteName('element') . ' = ' . $db->quote('com_seaichat'))
                ->where($db->quoteName('type') . ' = ' . $db->quote('component'));
            $db->setQuery($query);
            $db->execute();
            $this->clearParamsCache();

            $this->sendJson(['success' => true]);
        } catch (\Exception $e) {
            $this->sendJson(['success' => false, 'error' => 'Error: ' . $e->getMessage()]);
        }
    }

    /**
     * Export chat logs as CSV.
     */
    public function exportcsv(): void
    {
        if (!Session::checkToken('get')) {
            $this->sendJson(['success' => false, 'error' => 'Invalid security token.']);
            return;
        }

        try {
            $app = Factory::getApplication();

            /** @var \SolarEclipse\Component\SeAiChat\Administrator\Model\ChatlogsModel $model */
            $model = $app->bootComponent('com_seaichat')
                ->getMVCFactory()
                ->createModel('Chatlogs', 'Administrator', ['ignore_request' => false]);

            $items = $model->getExportItems();

            // Build CSV in memory
            $fp = fopen('php://temp', 'r+');

            // Header row — one row per message for full visibility
            fputcsv($fp, [
                'Session ID',
                'User Name',
                'User Email',
                'Status',
                'Started',
                'Last Activity',
                'Message #',
                'Role',
                'Message',
            ]);

            foreach ($items as $item) {
                $messages = json_decode($item->messages, true) ?: [];
                $msgNum = 0;

                if (empty($messages)) {
                    // Still output a row so the session appears in the export
                    fputcsv($fp, [
                        $item->session_key,
                        $item->user_name ?: 'Guest',
                        $item->user_email ?: '',
                        ucfirst($item->status),
                        $item->created,
                        $item->modified,
                        0,
                        '',
                        '(no messages)',
                    ]);
                    continue;
                }

                foreach ($messages as $m) {
                    $msgNum++;
                    $role = ($m['role'] ?? 'user') === 'assistant' ? 'AI' : 'User';
                    $content = trim($m['content'] ?? '');
                    // Normalise line breaks within the message
                    $content = str_replace(["\r\n", "\r"], "\n", $content);

                    fputcsv($fp, [
                        $item->session_key,
                        $item->user_name ?: 'Guest',
                        $item->user_email ?: '',
                        ucfirst($item->status),
                        $item->created,
                        $item->modified,
                        $msgNum,
                        $role,
                        $content,
                    ]);
                }
            }

            // Read back the CSV content
            rewind($fp);
            $csv = stream_get_contents($fp);
            fclose($fp);

            // Send as download
            while (ob_get_level()) {
                ob_end_clean();
            }

            $filename = 'chat-logs-' . Factory::getDate()->format('Y-m-d') . '.csv';

            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            header('Content-Length: ' . strlen($csv));
            header('Cache-Control: no-cache, no-store, must-revalidate');
            // BOM for Excel UTF-8 compatibility
            echo "\xEF\xBB\xBF";
            echo $csv;
            die;
        } catch (\Exception $e) {
            $this->sendJson(['success' => false, 'error' => 'Export failed: ' . $e->getMessage()]);
        }
    }

    /**
     * Validate CSRF token manually — returns false and sends error JSON if invalid.
     */
    private function validateToken(): bool
    {
        if (Session::checkToken('post')) {
            return true;
        }
        // Also check GET for compatibility
        if (Session::checkToken('get')) {
            return true;
        }
        $this->sendJson(['success' => false, 'error' => 'Invalid security token. Please reload the page and try again.']);
        return false;
    }

    private function getDefaultModel(string $provider): string
    {
        return ['anthropic' => 'claude-sonnet-4-20250514', 'openai' => 'gpt-4o-mini', 'gemini' => 'gemini-2.5-flash', 'deepseek' => 'deepseek-chat'][$provider] ?? 'claude-sonnet-4-20250514';
    }

    private function clearParamsCache(): void
    {
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
    }

    private function testProvider(string $provider, string $apiKey, string $model, array $messages): array
    {
        switch ($provider) {
            case 'openai': return $this->testOpenAi($apiKey, $model, $messages);
            case 'gemini': return $this->testGemini($apiKey, $model, $messages);
            case 'deepseek': return $this->testDeepSeek($apiKey, $model, $messages);
            default: return $this->testAnthropic($apiKey, $model, $messages);
        }
    }

    private function testAnthropic(string $apiKey, string $model, array $messages): array
    {
        $payload = json_encode(['model' => $model, 'max_tokens' => 50, 'messages' => $messages]);
        $headers = ['Content-Type: application/json', 'x-api-key: ' . $apiKey, 'anthropic-version: 2023-06-01'];

        $ch = curl_init('https://api.anthropic.com/v1/messages');
        curl_setopt_array($ch, [
            CURLOPT_POST => true, CURLOPT_POSTFIELDS => $payload,
            CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 30,
            CURLOPT_FOLLOWLOCATION => true, CURLOPT_HTTPHEADER => $headers,
        ]);
        $body = curl_exec($ch); $http = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err = curl_error($ch); $errno = curl_errno($ch); curl_close($ch);

        // SSL fallback
        if (($body === false || $http === 0) && in_array($errno, [60, 77, 35])) {
            $ch = curl_init('https://api.anthropic.com/v1/messages');
            curl_setopt_array($ch, [
                CURLOPT_POST => true, CURLOPT_POSTFIELDS => $payload,
                CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 30,
                CURLOPT_FOLLOWLOCATION => true, CURLOPT_HTTPHEADER => $headers,
                CURLOPT_SSL_VERIFYPEER => false, CURLOPT_SSL_VERIFYHOST => 0,
            ]);
            $body = curl_exec($ch); $http = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $err = curl_error($ch); curl_close($ch);
        }

        if ($body === false || $http === 0) return ['error' => 'Connection failed (curl ' . $errno . '): ' . ($err ?: 'No response')];
        if ($http !== 200) { $d = json_decode($body, true); return ['error' => ($d['error']['message'] ?? 'HTTP ' . $http . ': ' . substr($body, 0, 200))]; }
        $d = json_decode($body, true);
        return empty($d['content'][0]['text']) ? ['error' => 'Empty response: ' . substr($body, 0, 200)] : ['content' => $d['content'][0]['text']];
    }

    private function testOpenAi(string $apiKey, string $model, array $messages): array
    {
        $apiMessages = array_merge([['role' => 'system', 'content' => 'Respond briefly.']], $messages);
        $ch = curl_init('https://api.openai.com/v1/chat/completions');
        curl_setopt_array($ch, [
            CURLOPT_POST => true, CURLOPT_POSTFIELDS => json_encode(['model' => $model, 'max_tokens' => 50, 'messages' => $apiMessages]),
            CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 20,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json', 'Authorization: Bearer ' . $apiKey],
        ]);
        $body = curl_exec($ch); $http = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE); $err = curl_error($ch); curl_close($ch);
        if ($body === false || $http === 0) return ['error' => 'Connection failed: ' . ($err ?: 'No response')];
        if ($http !== 200) { $d = json_decode($body, true); return ['error' => ($d['error']['message'] ?? 'HTTP ' . $http)]; }
        $d = json_decode($body, true);
        return ['content' => $d['choices'][0]['message']['content'] ?? 'OK'];
    }

    private function testGemini(string $apiKey, string $model, array $messages): array
    {
        $contents = []; foreach ($messages as $m) { $contents[] = ['role' => 'user', 'parts' => [['text' => $m['content']]]]; }
        $ch = curl_init('https://generativelanguage.googleapis.com/v1beta/models/' . $model . ':generateContent?key=' . $apiKey);
        curl_setopt_array($ch, [
            CURLOPT_POST => true, CURLOPT_POSTFIELDS => json_encode(['contents' => $contents, 'generationConfig' => ['maxOutputTokens' => 50]]),
            CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 20, CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
        ]);
        $body = curl_exec($ch); $http = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE); $err = curl_error($ch); curl_close($ch);
        if ($body === false || $http === 0) return ['error' => 'Connection failed: ' . ($err ?: 'No response')];
        if ($http !== 200) { $d = json_decode($body, true); return ['error' => ($d['error']['message'] ?? 'HTTP ' . $http)]; }
        $d = json_decode($body, true);
        return ['content' => $d['candidates'][0]['content']['parts'][0]['text'] ?? 'OK'];
    }

    private function testDeepSeek(string $apiKey, string $model, array $messages): array
    {
        $apiMessages = array_merge([['role' => 'system', 'content' => 'Respond briefly.']], $messages);
        $ch = curl_init('https://api.deepseek.com/chat/completions');
        curl_setopt_array($ch, [
            CURLOPT_POST => true, CURLOPT_POSTFIELDS => json_encode(['model' => $model, 'max_tokens' => 50, 'messages' => $apiMessages]),
            CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json', 'Authorization: Bearer ' . $apiKey],
        ]);
        $body = curl_exec($ch); $http = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE); $err = curl_error($ch); curl_close($ch);
        if ($body === false || $http === 0) return ['error' => 'Connection failed: ' . ($err ?: 'No response')];
        if ($http !== 200) { $d = json_decode($body, true); return ['error' => ($d['error']['message'] ?? 'HTTP ' . $http)]; }
        $d = json_decode($body, true);
        return ['content' => $d['choices'][0]['message']['content'] ?? 'OK'];
    }

    /**
     * AJAX: Index all published Joomla articles into the knowledge base.
     */
    public function processallarticles(): void
    {
        if (!$this->validateToken()) return;

        try {
            $db = Factory::getContainer()->get('DatabaseDriver');

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
                $now = Factory::getDate()->toSql();
                $source = new \stdClass();
                $source->title = 'All Joomla Articles';
                $source->source_type = 'articles';
                $source->url = 'auto://all-articles';
                $source->content = '';
                $source->categories = '';
                $source->published = 1;
                $source->crawl_status = 'pending';
                $source->created = $now;
                $source->ordering = 0;
                $db->insertObject('#__seaichat_kb_sources', $source, 'id');
                $sourceId = (int) $source->id;
            }

            $result = \SolarEclipse\Component\SeAiChat\Administrator\Helper\KbCrawlerHelper::processArticleSource($sourceId);

            $this->sendJson([
                'success' => true,
                'pages'   => $result['pages'] ?? 0,
                'chunks'  => $result['chunks'] ?? 0,
            ]);
        } catch (\Exception $e) {
            $this->sendJson(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    private function sendJson(array $data): void
    {
        @ob_end_clean();
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data);
        die;
    }
}
