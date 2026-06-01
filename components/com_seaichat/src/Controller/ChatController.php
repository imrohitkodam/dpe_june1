<?php


/**
 * @package     Joomla
 * @subpackage  com_seaichat
 *
 * @copyright   (C) 2026 SE Extensions
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

declare(strict_types=1);

namespace SolarEclipse\Component\SeAiChat\Site\Controller;

defined('_JEXEC') or die;

use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Factory;
use SolarEclipse\Component\SeAiChat\Administrator\Helper\AiChatHelper;

class ChatController extends BaseController
{
    /**
     * Handle an incoming chat message via AJAX.
     */
    public function send(): void
    {
        $this->checkToken();

        $app = Factory::getApplication();
        $user = $app->getIdentity();

        $message = $app->getInput()->post->getString('message', '');
        $sessionKey = $app->getInput()->post->getString('session_key', '');

        if (empty($message)) {
            $this->sendJson(['error' => 'Message is required.']);
            return;
        }

        if (empty($sessionKey)) {
            $sessionKey = bin2hex(random_bytes(16));
        }

        $userId = $user->guest ? 0 : (int) $user->id;
        $topicContext = $app->getInput()->post->getString('topic', '');

        $result = AiChatHelper::chat($sessionKey, $message, $userId, $topicContext);

        $this->sendJson($result);
    }

    /**
     * Get widget configuration (public, no auth required).
     */
    public function config(): void
    {
        $config = AiChatHelper::getWidgetConfig();
        $config['token'] = \Joomla\CMS\Session\Session::getFormToken();

        $user = Factory::getApplication()->getIdentity();
        $config['logged_in'] = !$user->guest;

        $this->sendJson($config);
    }

    /**
     * Reset the chat session (start new conversation).
     */
    public function reset(): void
    {
        $this->checkToken();

        $app = Factory::getApplication();
        $sessionKey = $app->getInput()->post->getString('session_key', '');

        if (!empty($sessionKey)) {
            $db = Factory::getContainer()->get('DatabaseDriver');
            $query = $db->getQuery(true)
                ->update($db->quoteName('#__seaichat_chat_sessions'))
                ->set($db->quoteName('status') . ' = ' . $db->quote('closed'))
                ->set($db->quoteName('modified') . ' = ' . $db->quote(Factory::getDate()->toSql()))
                ->where($db->quoteName('session_key') . ' = ' . $db->quote($sessionKey));
            $db->setQuery($query);
            try { $db->execute(); } catch (\Exception $e) {}
        }

        $this->sendJson(['success' => true, 'new_session_key' => bin2hex(random_bytes(16))]);
    }

    private function sendJson(array $data): void
    {
        while (ob_get_level()) {
            ob_end_clean();
        }
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-cache, no-store, must-revalidate');
        echo json_encode($data);
        jexit();
    }
}
