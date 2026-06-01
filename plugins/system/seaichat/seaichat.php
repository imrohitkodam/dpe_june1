<?php


/**
 * @package     Joomla
 * @subpackage  com_seaichat
 *
 * @copyright   (C) 2026 SE Extensions
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Session\Session;

class PlgSystemSeaichat extends CMSPlugin
{
    /**
     * Inject the chat widget on frontend pages.
     */
    public function onAfterRender(): void
    {
        $app = Factory::getApplication();

        if (!$app->isClient('site')) return;
        if ($app->getInput()->getCmd('format', 'html') !== 'html') return;
        if (!ComponentHelper::isEnabled('com_seaichat')) return;
        if ($app->getInput()->getCmd('option', '') === 'com_seaichat') return;

        $db = Factory::getContainer()->get('DatabaseDriver');
        $query = $db->getQuery(true)
            ->select($db->quoteName('params'))
            ->from($db->quoteName('#__extensions'))
            ->where($db->quoteName('element') . ' = ' . $db->quote('com_seaichat'))
            ->where($db->quoteName('type') . ' = ' . $db->quote('component'));
        $db->setQuery($query);
        $paramsArray = json_decode($db->loadResult(), true) ?: [];

        if (empty($paramsArray['aichat_enabled'])) return;

        $apiKey = trim($paramsArray['aichat_api_key'] ?? '');
        if (empty($apiKey)) return;

        // Menu item filtering — only show on ticked pages
        $allowedMenuItems = trim($paramsArray['aichat_menu_items'] ?? '');
        $allowedIds = !empty($allowedMenuItems) ? array_filter(array_map('intval', explode(',', $allowedMenuItems))) : [];

        if (empty($allowedIds)) {
            // Nothing ticked — hide widget everywhere
            return;
        }

        $activeMenu = $app->getMenu()->getActive();
        $activeId = $activeMenu ? (int) $activeMenu->id : 0;
        if (!in_array($activeId, $allowedIds)) {
            return;
        }

        $providerNames = [
            'anthropic' => 'Anthropic Claude',
            'openai'    => 'OpenAI GPT',
            'gemini'    => 'Google Gemini',
            'deepseek'  => 'DeepSeek',
        ];

        $currentProvider = $paramsArray['aichat_provider'] ?? 'anthropic';
        $providerDisplayName = $providerNames[$currentProvider] ?? $currentProvider;

        $gdprEnabled = !empty($paramsArray['aichat_gdpr_enabled']);
        $gdprText = $paramsArray['aichat_gdpr_text'] ?? 'This chatbot uses {ai_provider} to process your requests. Your inputs and conversation history are transmitted to and processed by {ai_provider}. No personal data is stored. Data is transmitted on the basis of your consent (Art. 6 (1) (a) GDPR). You may withdraw your consent at any time by closing the chat. For further information, please refer to our privacy policy.';
        $gdprText = str_replace('{ai_provider}', $providerDisplayName, $gdprText);

        $user = $app->getIdentity();

        // Load frontend language strings (with admin overrides taking priority)
        $lang = $app->getLanguage();
        $lang->load('com_seaichat', JPATH_SITE);
        $lang->load('com_seaichat', JPATH_SITE . '/components/com_seaichat');
        $stringKeys = [
            'status_online'          => 'COM_SEAICHAT_STATUS_ONLINE',
            'chat_with_ai'           => 'COM_SEAICHAT_CHAT_WITH_AI',
            'new_conversation'       => 'COM_SEAICHAT_NEW_CONVERSATION',
            'close'                  => 'COM_SEAICHAT_CLOSE',
            'send'                   => 'COM_SEAICHAT_SEND',
            'gdpr_title'             => 'COM_SEAICHAT_GDPR_TITLE',
            'gdpr_accept'            => 'COM_SEAICHAT_GDPR_ACCEPT',
            'gdpr_decline'           => 'COM_SEAICHAT_GDPR_DECLINE',
            'gdpr_unavailable_title' => 'COM_SEAICHAT_GDPR_UNAVAILABLE_TITLE',
            'gdpr_unavailable_msg'   => 'COM_SEAICHAT_GDPR_UNAVAILABLE_MSG',
            'error_prefix'           => 'COM_SEAICHAT_ERROR_PREFIX',
            'error_generic'          => 'COM_SEAICHAT_ERROR_GENERIC',
        ];
        // Hardcoded fallbacks in case language file fails to load
        $fallbacks = [
            'status_online' => 'Online', 'chat_with_ai' => 'Chat with AI',
            'new_conversation' => 'New conversation', 'close' => 'Close', 'send' => 'Send',
            'gdpr_title' => 'Data Privacy Notice', 'gdpr_accept' => 'Accept', 'gdpr_decline' => 'Decline',
            'gdpr_unavailable_title' => 'Chat Unavailable',
            'gdpr_unavailable_msg' => 'You have declined the data privacy notice. The chat is not available without your consent. You can refresh the chat above to try again.',
            'error_prefix' => 'Sorry:', 'error_generic' => 'Sorry, something went wrong.',
        ];
        $strings = [];
        foreach ($stringKeys as $jsKey => $langKey) {
            $overrideParam = 'aichat_str_' . $jsKey;
            $override = trim($paramsArray[$overrideParam] ?? '');
            if (!empty($override)) {
                $strings[$jsKey] = $override;
            } else {
                $translated = $lang->_($langKey);
                // If Joomla returned the raw key, use the hardcoded fallback
                $strings[$jsKey] = ($translated !== $langKey) ? $translated : $fallbacks[$jsKey];
            }
        }

        $config = [
            'enabled'         => true,
            'welcome_message' => $paramsArray['aichat_welcome_message'] ?? 'Hi! How can I help you today?',
            'position'        => $paramsArray['aichat_widget_position'] ?? 'bottom-right',
            'color'           => $paramsArray['aichat_primary_color'] ?? '#2E486B',
            'max_messages'    => (int) ($paramsArray['aichat_max_messages'] ?? 10),
            'header_title'    => $paramsArray['aichat_header_title'] ?? 'AI Assistant',
            'placeholder_text'=> $paramsArray['aichat_placeholder_text'] ?? 'Type your question...',
            'contact_url'     => $paramsArray['aichat_contact_url'] ?? '',
            'contact_text'    => $paramsArray['aichat_contact_text'] ?? 'Contact Support',
            'contact_target'  => $paramsArray['aichat_contact_target'] ?? '_blank',
            'gdpr_enabled'    => $gdprEnabled,
            'gdpr_text'       => $gdprText,
            'gdpr_privacy_url'=> $paramsArray['aichat_gdpr_privacy_url'] ?? '',
            'avatar_url'      => !empty($paramsArray['aichat_avatar']) ? rtrim(Uri::root(), '/') . '/' . $paramsArray['aichat_avatar'] : '',
            'strings'         => $strings,
            'token'           => Session::getFormToken(),
            'logged_in'       => !$user->guest,
            'base_url'        => rtrim(Uri::root(), '/') . '/',
        ];

        $mediaUrl = Uri::root() . 'media/com_seaichat';
        $color = $config['color'];
        $colorCss = ($color !== '#2E486B') ? '<style>:root { --se-chat-color: ' . $color . '; }</style>' : '';
        $cacheBust = '?v=2.0.2c';

        $inject = "\n<!-- SE AI Chatbot Widget -->\n"
            . '<script>if(window.SE_CHAT_MODULE_ACTIVE){window.SE_CHAT_SKIP_PLUGIN=true;}</script>' . "\n"
            . '<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" crossorigin="anonymous" />' . "\n"
            . '<link rel="stylesheet" href="' . $mediaUrl . '/css/chat-widget.css' . $cacheBust . '" />' . "\n"
            . $colorCss . "\n"
            . '<script>if(!window.SE_CHAT_MODULE_ACTIVE){window.SE_CHAT_CONFIG = ' . json_encode($config) . ';}</script>' . "\n"
            . '<script src="' . $mediaUrl . '/js/chat-widget.js' . $cacheBust . '" defer></script>' . "\n";

        $body = $app->getBody();
        $body = str_replace('</body>', $inject . '</body>', $body);
        $app->setBody($body);
    }

    /**
     * Auto-reprocess article-based KB sources when a Joomla article is saved.
     */
    public function onContentAfterSave($context, $article, $isNew): void
    {
        // Trigger for Joomla articles AND SP Page Builder pages
        $isArticle = ($context === 'com_content.article' || $context === 'com_content.form');
        $isSppb = ($context === 'com_sppagebuilder.page' || $context === 'com_sppagebuilder.form'
                   || strpos($context, 'sppb') !== false);

        if (!$isArticle && !$isSppb) {
            return;
        }

        if (!ComponentHelper::isEnabled('com_seaichat')) {
            return;
        }

        try {
            $db = Factory::getContainer()->get('DatabaseDriver');

            // Determine which source types to reprocess
            $sourceTypes = [];
            if ($isArticle) $sourceTypes[] = 'articles';
            if ($isSppb) $sourceTypes[] = 'sppb';

            foreach ($sourceTypes as $sourceType) {
                $query = $db->getQuery(true)
                    ->select('id, categories')
                    ->from($db->quoteName('#__seaichat_kb_sources'))
                    ->where($db->quoteName('source_type') . ' = ' . $db->quote($sourceType))
                    ->where($db->quoteName('published') . ' = 1');
                $db->setQuery($query);
                $sources = $db->loadObjectList() ?: [];

                $itemCatId = (int) ($article->catid ?? 0);

                foreach ($sources as $source) {
                    $selectedCats = trim($source->categories ?? '');
                    $shouldProcess = false;

                    if (empty($selectedCats) || $selectedCats === '0') {
                        $shouldProcess = true;
                    } else {
                        $catIds = array_filter(array_map('intval', explode(',', $selectedCats)));
                        if (in_array($itemCatId, $catIds)) {
                            $shouldProcess = true;
                        }
                    }

                    if ($shouldProcess) {
                        $helperFile = JPATH_ADMINISTRATOR . '/components/com_seaichat/src/Helper/KbCrawlerHelper.php';
                        if (!class_exists('\\SolarEclipse\\Component\\SeAiChat\\Administrator\\Helper\\KbCrawlerHelper') && file_exists($helperFile)) {
                            require_once $helperFile;
                        }

                        if (class_exists('\\SolarEclipse\\Component\\SeAiChat\\Administrator\\Helper\\KbCrawlerHelper')) {
                            try {
                                if ($sourceType === 'sppb') {
                                    \SolarEclipse\Component\SeAiChat\Administrator\Helper\KbCrawlerHelper::processSppbSource((int) $source->id);
                                } else {
                                    \SolarEclipse\Component\SeAiChat\Administrator\Helper\KbCrawlerHelper::processArticleSource((int) $source->id);
                                }
                            } catch (\Exception $e) {
                                Factory::getApplication()->enqueueMessage('SE AI Chatbot: KB auto-update error — ' . $e->getMessage(), 'warning');
                            }
                        }
                    }
                }
            }
        } catch (\Exception $e) {
            // Never break the article save
        }
    }
}
