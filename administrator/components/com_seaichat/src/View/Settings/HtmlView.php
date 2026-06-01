<?php


/**
 * @package     Joomla
 * @subpackage  com_seaichat
 *
 * @copyright   (C) 2026 SE Extensions
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace SolarEclipse\Component\SeAiChat\Administrator\View\Settings;

defined('_JEXEC') or die;

use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;

class HtmlView extends BaseHtmlView
{
    public function display($tpl = null): void
    {
        ToolbarHelper::title('SE AI Chatbot: Settings', 'cog');
        parent::display($tpl);
    }
}
