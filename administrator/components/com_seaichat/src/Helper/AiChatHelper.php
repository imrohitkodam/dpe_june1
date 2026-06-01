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
use Joomla\CMS\Component\ComponentHelper;

class AiChatHelper
{
    // Pro-only models per provider. Free tier is limited to the first (cheapest) model.
    private const PRO_MODELS = [
        'anthropic' => ['claude-sonnet-4-20250514'],   // Haiku is free, Sonnet is pro
        'openai'    => ['gpt-4o', 'gpt-4.1-mini'],     // gpt-4o-mini and gpt-4.1-nano are free
        'gemini'    => ['gemini-2.5-pro'],              // Flash variants are free, Pro is pro
        'deepseek'  => ['deepseek-reasoner'],           // Chat is free, Reasoner is pro
    ];

    // Default free-tier model per provider
    private const FREE_MODELS = [
        'anthropic' => 'claude-haiku-4-5-20251001',
        'openai'    => 'gpt-4o-mini',
        'gemini'    => 'gemini-2.5-flash',
        'deepseek'  => 'deepseek-chat',
    ];

    public static function chat(string $sessionKey, string $userMessage, int $userId = 0, string $topicContext = ''): array
    {
        $params = ComponentHelper::getParams('com_seaichat');
        if (!$params->get('aichat_enabled', 0)) return ['error' => 'AI chat is not enabled.'];
        $apiKey = self::getRawApiKey();
        if (empty($apiKey)) return ['error' => 'AI chat is not configured.'];

        $provider = $params->get('aichat_provider', 'anthropic');

        // Enforce model tier — free users get the base model regardless of what's saved in settings
        $model = self::getEffectiveModel($provider, $params);

        // Free tier: cap at 10 messages. Pro: use whatever is configured (default 10, can go higher).
        $isPro      = self::checkIsPro();
        $maxMessages = $isPro
            ? (int) $params->get('aichat_max_messages', 10)
            : 10;

        $systemPrompt = $params->get('aichat_system_prompt', 'You are a helpful AI assistant. Answer questions based on the provided documentation.');

        if (!empty($topicContext)) {
            $systemPrompt .= "\n\nThe user is asking about: " . $topicContext . ". Focus your answers on this topic where possible.";
        }

        $session  = self::getOrCreateSession($sessionKey, $userId);
        $messages = json_decode($session->messages, true) ?: [];
        $messages[] = ['role' => 'user', 'content' => $userMessage];

        $kbResults = KbCrawlerHelper::searchKnowledge($userMessage, 5);
        $context   = '';
        if (!empty($kbResults)) {
            $context = "\n\n--- DOCUMENTATION CONTEXT ---\n";
            foreach ($kbResults as $chunk) {
                $context .= "\n[Source: " . $chunk->page_title . " — " . $chunk->page_url . "]\n" . $chunk->content . "\n";
            }
            $context .= "\n--- END OF DOCUMENTATION ---\n";
        }

        $fullSystemPrompt = $systemPrompt;
        if (!empty($context)) {
            $fullSystemPrompt .= $context;
        } else {
            $fullSystemPrompt .= "\n\nNote: No documentation was found matching this query. Let the user know you couldn't find a specific answer in the documentation and offer to help with something else.";
        }

        $userMessageCount = 0;
        foreach ($messages as $msg) {
            if ($msg['role'] === 'user') $userMessageCount++;
        }

        $reachedLimit = ($userMessageCount >= $maxMessages);
        if ($reachedLimit) {
            $fullSystemPrompt .= "\n\nIMPORTANT: The user has sent " . $userMessageCount . " messages. At the end of your response, let them know they can start a new conversation if they have more questions.";
        }

        $apiMessages = self::prepareApiMessages($messages);
        $aiResponse  = self::callAiApi($provider, $apiKey, $model, $fullSystemPrompt, $apiMessages);
        if (isset($aiResponse['error'])) return $aiResponse;

        $assistantMessage = $aiResponse['content'];
        $messages[]       = ['role' => 'assistant', 'content' => $assistantMessage];
        self::saveSession($sessionKey, $messages, $userId);

        $ctaEnabled    = ($params->get('aichat_cta_enabled', '1') !== '0');
        $ctaMax        = max(1, (int) $params->get('aichat_cta_max', 3));

        // Source links are a Pro-only feature
        $sourcesEnabled = $isPro && ($params->get('aichat_sources_enabled', '1') !== '0');
        $sourcesMax     = max(1, (int) $params->get('aichat_sources_max', 3));

        $actions = $ctaEnabled ? self::matchActions($userMessage, $assistantMessage, $ctaMax) : [];
        $sources = $sourcesEnabled ? self::extractSourceLinks($kbResults, $sourcesMax) : [];

        $result = [
            'message'       => $assistantMessage,
            'session_key'   => $sessionKey,
            'message_count' => $userMessageCount,
            'reached_limit' => $reachedLimit,
            'has_context'   => !empty($kbResults),
            'actions'       => $actions,
            'sources'       => $sources,
        ];

        $ctaBg   = trim($params->get('aichat_cta_bg_color', ''));
        $ctaText = trim($params->get('aichat_cta_text_color', ''));
        if ($ctaBg !== '' || $ctaText !== '') {
            $result['cta_style'] = ['bg' => $ctaBg, 'text' => $ctaText];
        }

        return $result;
    }

    // -------------------------------------------------------------------------
    // Model resolution — enforces free/pro tier
    // -------------------------------------------------------------------------

    /**
     * Returns the model to actually use, downgrading to the free model if needed.
     */
    public static function getEffectiveModel(string $provider, object $params): string
    {
        $configured = self::getModelForProvider($provider, $params);

        if (self::checkIsPro()) {
            return $configured;
        }

        // Is the configured model a pro-only model?
        $proModels = self::PRO_MODELS[$provider] ?? [];
        if (in_array($configured, $proModels, true)) {
            return self::FREE_MODELS[$provider] ?? $configured;
        }

        return $configured;
    }

    /**
     * Used by the settings page to know which models need a (Pro) badge.
     */
    public static function isProModel(string $provider, string $model): bool
    {
        return in_array($model, self::PRO_MODELS[$provider] ?? [], true);
    }

    public static function getModelForProviderStatic(string $provider, array $params): string
    {
        switch ($provider) {
            case 'openai':    return $params['aichat_model_openai'] ?? 'gpt-4o-mini';
            case 'gemini':    return $params['aichat_model_gemini'] ?? 'gemini-2.5-flash';
            case 'deepseek':  return $params['aichat_model_deepseek'] ?? 'deepseek-chat';
            default:          return $params['aichat_model'] ?? 'claude-haiku-4-5-20251001';
        }
    }

    // -------------------------------------------------------------------------
    // License check (cached per request)
    // -------------------------------------------------------------------------

    private static ?bool $isProCache = null;

    private static function checkIsPro(): bool
    {
        if (self::$isProCache !== null) {
            return self::$isProCache;
        }
        require_once JPATH_ADMINISTRATOR . '/components/com_seaichat/helpers/LicenseChecker.php';
        self::$isProCache = \SeAiChatLicenseChecker::isPro();
        return self::$isProCache;
    }

    // -------------------------------------------------------------------------
    // Everything below is unchanged from the original
    // -------------------------------------------------------------------------

    private static function matchActions(string $userMessage, string $assistantMessage, int $max = 3): array
    {
        $db    = Factory::getContainer()->get('DatabaseDriver');
        $query = $db->getQuery(true)
            ->select('*')
            ->from($db->quoteName('#__seaichat_ctas'))
            ->where($db->quoteName('published') . ' = 1')
            ->order($db->quoteName('ordering') . ' ASC');
        $db->setQuery($query);

        try {
            $ctas = $db->loadObjectList();
        } catch (\Exception $e) {
            return [];
        }

        if (empty($ctas)) return [];

        $combinedText = mb_strtolower($userMessage . ' ' . $assistantMessage);
        $actions = [];

        foreach ($ctas as $cta) {
            $keywords = array_map('trim', explode(',', mb_strtolower($cta->keywords)));
            foreach ($keywords as $keyword) {
                if ($keyword === '') continue;
                if (preg_match('/\b' . preg_quote($keyword, '/') . '\b/iu', $combinedText)) {
                    $actions[] = [
                        'label'  => $cta->button_label,
                        'url'    => $cta->button_url,
                        'icon'   => $cta->button_icon,
                        'target' => $cta->button_target ?: '_self',
                    ];
                    break;
                }
            }
            if (count($actions) >= $max) break;
        }

        return $actions;
    }

    private static function extractSourceLinks(array $kbResults, int $maxSources = 3): array
    {
        if (empty($kbResults)) return [];

        $seen    = [];
        $sources = [];

        foreach ($kbResults as $chunk) {
            if (empty($chunk->page_url) || empty($chunk->page_title)) continue;

            $url = self::resolveSourceUrl($chunk->page_url);
            if (empty($url) || isset($seen[$url])) continue;

            $seen[$url]  = true;
            $sources[]   = ['title' => $chunk->page_title, 'url' => $url];

            if (count($sources) >= $maxSources) break;
        }

        return $sources;
    }

    private static function resolveSourceUrl(string $url): string
    {
        if (preg_match('#^https?://#i', $url)) return $url;
        if (strpos($url, '/') === 0) return $url;

        if (preg_match('#^article://(\d+)$#', $url, $m)) {
            $articleId = (int) $m[1];
            try {
                $db    = Factory::getContainer()->get('DatabaseDriver');
                if (class_exists('\\Joomla\\Component\\Content\\Site\\Helper\\RouteHelper')) {
                    $query = $db->getQuery(true)
                        ->select($db->quoteName(['id', 'catid', 'language']))
                        ->from($db->quoteName('#__content'))
                        ->where($db->quoteName('id') . ' = ' . $articleId);
                    $db->setQuery($query);
                    $article = $db->loadObject();
                    if ($article) {
                        $link = \Joomla\Component\Content\Site\Helper\RouteHelper::getArticleRoute($article->id, (int) $article->catid, $article->language ?? '*');
                        return \Joomla\CMS\Router\Route::_($link);
                    }
                }
                return \Joomla\CMS\Router\Route::_('index.php?option=com_content&view=article&id=' . $articleId);
            } catch (\Exception $e) {
                return 'index.php?option=com_content&view=article&id=' . $articleId;
            }
        }

        if (preg_match('#^sppb://(\d+)$#', $url, $m)) {
            try {
                return \Joomla\CMS\Router\Route::_('index.php?option=com_sppagebuilder&view=page&id=' . (int) $m[1]);
            } catch (\Exception $e) {
                return 'index.php?option=com_sppagebuilder&view=page&id=' . (int) $m[1];
            }
        }

        return $url;
    }

    private static function getModelForProvider(string $provider, object $params): string
    {
        switch ($provider) {
            case 'openai':   return $params->get('aichat_model_openai', 'gpt-4o-mini');
            case 'gemini':   return $params->get('aichat_model_gemini', 'gemini-2.5-flash');
            case 'deepseek': return $params->get('aichat_model_deepseek', 'deepseek-chat');
            default:         return $params->get('aichat_model', 'claude-haiku-4-5-20251001');
        }
    }

    private static function callAiApi(string $provider, string $apiKey, string $model, string $systemPrompt, array $messages): array
    {
        switch ($provider) {
            case 'openai':   return self::callOpenAiApi($apiKey, $model, $systemPrompt, $messages);
            case 'gemini':   return self::callGeminiApi($apiKey, $model, $systemPrompt, $messages);
            case 'deepseek': return self::callDeepSeekApi($apiKey, $model, $systemPrompt, $messages);
            default:         return self::callAnthropicApi($apiKey, $model, $systemPrompt, $messages);
        }
    }

    private static function callAnthropicApi(string $apiKey, string $model, string $systemPrompt, array $messages): array
    {
        try {
            $payload = json_encode(['model' => $model, 'max_tokens' => 1024, 'system' => $systemPrompt, 'messages' => $messages]);
            $headers = ['Content-Type: application/json', 'x-api-key: ' . $apiKey, 'anthropic-version: 2023-06-01'];
            $ch = curl_init('https://api.anthropic.com/v1/messages');
            curl_setopt_array($ch, [CURLOPT_POST => true, CURLOPT_POSTFIELDS => $payload, CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 30, CURLOPT_FOLLOWLOCATION => true, CURLOPT_HTTPHEADER => $headers]);
            $body = curl_exec($ch); $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE); $err = curl_error($ch); $errno = curl_errno($ch); curl_close($ch);
            if (($body === false || $httpCode === 0) && ($errno === 60 || $errno === 77 || $errno === 35)) {
                $ch = curl_init('https://api.anthropic.com/v1/messages');
                curl_setopt_array($ch, [CURLOPT_POST => true, CURLOPT_POSTFIELDS => $payload, CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 30, CURLOPT_FOLLOWLOCATION => true, CURLOPT_HTTPHEADER => $headers, CURLOPT_SSL_VERIFYPEER => false, CURLOPT_SSL_VERIFYHOST => 0]);
                $body = curl_exec($ch); $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE); curl_close($ch);
            }
            if ($body === false || $httpCode === 0) return ['error' => 'Failed to contact Anthropic: ' . ($err ?: 'No response')];
            if ($httpCode !== 200) { $e = json_decode($body, true); return ['error' => $e['error']['message'] ?? 'Anthropic API error (HTTP ' . $httpCode . ')']; }
            $data = json_decode($body, true);
            return empty($data['content'][0]['text']) ? ['error' => 'Empty response from Claude.'] : ['content' => $data['content'][0]['text']];
        } catch (\Exception $e) { return ['error' => 'Anthropic error: ' . $e->getMessage()]; }
    }

    private static function callOpenAiApi(string $apiKey, string $model, string $systemPrompt, array $messages): array
    {
        try {
            $apiMessages = array_merge([['role' => 'system', 'content' => $systemPrompt]], $messages);
            $ch = curl_init('https://api.openai.com/v1/chat/completions');
            curl_setopt_array($ch, [CURLOPT_POST => true, CURLOPT_POSTFIELDS => json_encode(['model' => $model, 'max_tokens' => 1024, 'messages' => $apiMessages]), CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 30, CURLOPT_HTTPHEADER => ['Content-Type: application/json', 'Authorization: Bearer ' . $apiKey]]);
            $body = curl_exec($ch); $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE); $err = curl_error($ch); curl_close($ch);
            if ($body === false || $httpCode === 0) return ['error' => 'Failed to contact OpenAI: ' . ($err ?: 'No response')];
            if ($httpCode !== 200) { $e = json_decode($body, true); return ['error' => $e['error']['message'] ?? 'OpenAI API error (HTTP ' . $httpCode . ')']; }
            $data = json_decode($body, true);
            return empty($data['choices'][0]['message']['content']) ? ['error' => 'Empty response from OpenAI.'] : ['content' => $data['choices'][0]['message']['content']];
        } catch (\Exception $e) { return ['error' => 'OpenAI error: ' . $e->getMessage()]; }
    }

    private static function callGeminiApi(string $apiKey, string $model, string $systemPrompt, array $messages): array
    {
        try {
            $contents = [];
            foreach ($messages as $msg) { $contents[] = ['role' => $msg['role'] === 'assistant' ? 'model' : 'user', 'parts' => [['text' => $msg['content']]]]; }
            $url = 'https://generativelanguage.googleapis.com/v1beta/models/' . $model . ':generateContent?key=' . $apiKey;
            $ch  = curl_init($url);
            curl_setopt_array($ch, [CURLOPT_POST => true, CURLOPT_POSTFIELDS => json_encode(['contents' => $contents, 'systemInstruction' => ['parts' => [['text' => $systemPrompt]]], 'generationConfig' => ['maxOutputTokens' => 1024]]), CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 30, CURLOPT_HTTPHEADER => ['Content-Type: application/json']]);
            $body = curl_exec($ch); $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE); $err = curl_error($ch); curl_close($ch);
            if ($body === false || $httpCode === 0) return ['error' => 'Failed to contact Gemini: ' . ($err ?: 'No response')];
            if ($httpCode !== 200) { $e = json_decode($body, true); return ['error' => $e['error']['message'] ?? 'Gemini API error (HTTP ' . $httpCode . ')']; }
            $data = json_decode($body, true);
            return empty($data['candidates'][0]['content']['parts'][0]['text']) ? ['error' => 'Empty response from Gemini.'] : ['content' => $data['candidates'][0]['content']['parts'][0]['text']];
        } catch (\Exception $e) { return ['error' => 'Gemini error: ' . $e->getMessage()]; }
    }

    private static function callDeepSeekApi(string $apiKey, string $model, string $systemPrompt, array $messages): array
    {
        try {
            $apiMessages = array_merge([['role' => 'system', 'content' => $systemPrompt]], $messages);
            $ch = curl_init('https://api.deepseek.com/chat/completions');
            curl_setopt_array($ch, [CURLOPT_POST => true, CURLOPT_POSTFIELDS => json_encode(['model' => $model, 'max_tokens' => 1024, 'messages' => $apiMessages]), CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 60, CURLOPT_HTTPHEADER => ['Content-Type: application/json', 'Authorization: Bearer ' . $apiKey]]);
            $body = curl_exec($ch); $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE); $err = curl_error($ch); curl_close($ch);
            if ($body === false || $httpCode === 0) return ['error' => 'Failed to contact DeepSeek: ' . ($err ?: 'No response')];
            if ($httpCode !== 200) { $e = json_decode($body, true); return ['error' => $e['error']['message'] ?? 'DeepSeek API error (HTTP ' . $httpCode . ')']; }
            $data = json_decode($body, true);
            return empty($data['choices'][0]['message']['content']) ? ['error' => 'Empty response from DeepSeek.'] : ['content' => $data['choices'][0]['message']['content']];
        } catch (\Exception $e) { return ['error' => 'DeepSeek error: ' . $e->getMessage()]; }
    }

    private static function prepareApiMessages(array $messages): array
    {
        $maxHistory = 20;
        if (count($messages) > $maxHistory) $messages = array_slice($messages, -$maxHistory);
        $cleaned = [];
        foreach ($messages as $msg) {
            if (in_array($msg['role'], ['user', 'assistant'])) $cleaned[] = ['role' => $msg['role'], 'content' => $msg['content']];
        }
        return $cleaned;
    }

    private static function getOrCreateSession(string $sessionKey, int $userId): object
    {
        $db    = Factory::getContainer()->get('DatabaseDriver');
        $query = $db->getQuery(true)->select('*')->from($db->quoteName('#__seaichat_chat_sessions'))
            ->where($db->quoteName('session_key') . ' = :key')->bind(':key', $sessionKey);
        $db->setQuery($query);
        $session = $db->loadObject();
        if ($session) return $session;

        $now = Factory::getDate()->toSql();
        $ns  = new \stdClass();
        $ns->session_key = $sessionKey; $ns->user_id = $userId;
        $ns->messages = '[]'; $ns->status = 'active'; $ns->created = $now; $ns->modified = $now;
        $db->insertObject('#__seaichat_chat_sessions', $ns, 'id');
        return $ns;
    }

    private static function saveSession(string $sessionKey, array $messages, int $userId): void
    {
        $db  = Factory::getContainer()->get('DatabaseDriver');
        $now = Factory::getDate()->toSql();
        $u   = new \stdClass();
        $u->session_key = $sessionKey; $u->messages = json_encode($messages); $u->modified = $now; $u->user_id = $userId;

        $query = $db->getQuery(true)->select('id')->from($db->quoteName('#__seaichat_chat_sessions'))
            ->where($db->quoteName('session_key') . ' = :key')->bind(':key', $sessionKey);
        $db->setQuery($query);
        $id = $db->loadResult();

        if ($id) { $u->id = (int) $id; $db->updateObject('#__seaichat_chat_sessions', $u, 'id'); }
        else { $u->status = 'active'; $u->created = $now; $db->insertObject('#__seaichat_chat_sessions', $u); }
    }

    public static function getTranscript(string $sessionKey): string
    {
        $db    = Factory::getContainer()->get('DatabaseDriver');
        $query = $db->getQuery(true)->select('messages')->from($db->quoteName('#__seaichat_chat_sessions'))
            ->where($db->quoteName('session_key') . ' = :key')->bind(':key', $sessionKey);
        $db->setQuery($query);
        $json = $db->loadResult();
        if (empty($json)) return '';
        $messages = json_decode($json, true) ?: [];
        $t = "--- AI Chat Transcript ---\n\n";
        foreach ($messages as $msg) { $t .= ($msg['role'] === 'user' ? 'User' : 'AI Assistant') . ":\n" . $msg['content'] . "\n\n"; }
        return $t . "--- End of Transcript ---";
    }

    public static function isEnabled(): bool
    {
        $params = ComponentHelper::getParams('com_seaichat');
        return (bool) $params->get('aichat_enabled', 0) && !empty(self::getRawApiKey());
    }

    public static function getRawApiKey(): string
    {
        $db    = Factory::getContainer()->get('DatabaseDriver');
        $query = $db->getQuery(true)->select($db->quoteName('params'))->from($db->quoteName('#__extensions'))
            ->where($db->quoteName('element') . ' = ' . $db->quote('com_seaichat'))
            ->where($db->quoteName('type') . ' = ' . $db->quote('component'));
        $db->setQuery($query);
        $decoded = json_decode($db->loadResult(), true) ?: [];
        return trim($decoded['aichat_api_key'] ?? '');
    }

    public static function getWidgetConfig(): array
    {
        $params = ComponentHelper::getParams('com_seaichat');
        return [
            'enabled'         => self::isEnabled(),
            'welcome_message' => $params->get('aichat_welcome_message', 'Hi! How can I help you today?'),
            'position'        => $params->get('aichat_widget_position', 'bottom-right'),
            'color'           => $params->get('aichat_primary_color', '#2E486B'),
            'max_messages'    => (int) $params->get('aichat_max_messages', 10),
        ];
    }
}
