<?php


/**
 * @package     Joomla
 * @subpackage  com_seaichat
 *
 * @copyright   (C) 2026 SE Extensions
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

declare(strict_types=1);

namespace SolarEclipse\Component\SeAiChat\Administrator\View\Dashboard;

defined('_JEXEC') or die;

use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;

class HtmlView extends BaseHtmlView
{
    protected $stats;
    protected $licenseStatus;
    protected $recentChats;
    protected $diagnostics;

    public function display($tpl = null): void
    {
        // Load license checker
        require_once JPATH_ADMINISTRATOR . '/components/com_seaichat/helpers/LicenseChecker.php';
        $this->licenseStatus = \SeAiChatLicenseChecker::getStatus();

        $this->stats = $this->get('Stats');
        $this->recentChats = $this->get('RecentChats');
        $this->diagnostics = $this->get('Diagnostics');

        ToolbarHelper::title('SE AI Chatbot: Dashboard', 'comments');
        ToolbarHelper::preferences('com_seaichat');

        parent::display($tpl);
    }
}
