<?php


/**
 * @package     Joomla
 * @subpackage  com_seaichat
 *
 * @copyright   (C) 2026 SE Extensions
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

declare(strict_types=1);

namespace SolarEclipse\Component\SeAiChat\Site\View\Chat;

defined('_JEXEC') or die;

use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use SolarEclipse\Component\SeAiChat\Administrator\Helper\AiChatHelper;

class HtmlView extends BaseHtmlView
{
    protected $config;

    public function display($tpl = null): void
    {
        $this->config = AiChatHelper::getWidgetConfig();
        parent::display($tpl);
    }
}
