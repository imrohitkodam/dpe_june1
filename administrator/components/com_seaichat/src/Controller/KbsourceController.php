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

use Joomla\CMS\MVC\Controller\FormController;
use Joomla\CMS\Factory;
use Joomla\CMS\Router\Route;

class KbsourceController extends FormController
{
    protected $text_prefix = 'COM_SEAICHAT_KB';
    protected $view_list   = 'knowledgebases';
    protected $view_item   = 'kbsource';

    /**
     * Override save to enforce the free-tier KB source limit (max 1).
     */
    public function save($key = null, $urlVar = null)
    {
        require_once JPATH_ADMINISTRATOR . '/components/com_seaichat/helpers/LicenseChecker.php';

        if (!\SeAiChatLicenseChecker::isPro()) {
            $app   = Factory::getApplication();
            $input = $app->input;

            // Only check on NEW records (id = 0)
            $recordId = (int) $input->get('jform', [], 'array')['id'] ?? 0;

            if ($recordId === 0) {
                // Count existing KB sources
                $db    = Factory::getContainer()->get('DatabaseDriver');
                $query = $db->getQuery(true)
                    ->select('COUNT(*)')
                    ->from($db->quoteName('#__seaichat_kb_sources'));
                $db->setQuery($query);
                $count = (int) $db->loadResult();

                if ($count >= 1) {
                    $upgradeUrl = \SeAiChatLicenseChecker::upgradeUrl();
                    $app->enqueueMessage(
                        'Free plan: limited to 1 knowledge base source. '
                        . '<a href="' . $upgradeUrl . '" target="_blank"><strong>Upgrade to Pro</strong></a> for unlimited sources.',
                        'warning'
                    );
                    $this->setRedirect(Route::_('index.php?option=com_seaichat&view=knowledgebases', false));
                    return false;
                }
            }
        }

        return parent::save($key, $urlVar);
    }

    protected function getRedirectToListRoute($append = '')
    {
        return Route::_('index.php?option=com_seaichat&view=knowledgebases' . $append, false);
    }

    protected function getRedirectToItemRoute($append = '')
    {
        return Route::_('index.php?option=com_seaichat&view=kbsource&layout=edit' . $append, false);
    }
}
