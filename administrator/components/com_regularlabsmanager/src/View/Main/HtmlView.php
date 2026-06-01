<?php
/**
 * @package         Regular Labs Extension Manager
 * @version         9.2.5
 * 
 * @author          Peter van Westen <info@regularlabs.com>
 * @link            https://regularlabs.com
 * @copyright       Copyright © 2025 Regular Labs All Rights Reserved
 * @license         GNU General Public License version 2 or later
 */

namespace RegularLabs\Component\RegularLabsExtensionsManager\Administrator\View\Main;

use Joomla\CMS\Factory as JFactory;
use Joomla\CMS\Helper\ContentHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\GenericDataException;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;
use RegularLabs\Library\Document as RL_Document;
use RegularLabs\Library\Parameters as RL_Parameters;

defined('_JEXEC') or die;

class HtmlView extends BaseHtmlView
{
    /**
     * @var  object
     */
    protected $config;

    /**
     * @param string $tpl The name of the template file to parse; automatically searches through the template paths.
     *
     * @return  mixed  False if unsuccessful, otherwise void.
     *
     * @throws  GenericDataException
     */
    public function display($tpl = null)
    {
        $this->items  = $this->get('Items');
        $this->config = RL_Parameters::getComponent('regularlabsmanager');

        $errors = $this->get('Errors');

        if (count($errors))
        {
            throw new GenericDataException(implode("\n", $errors), 500);
        }

        $this->addToolbar();

        parent::display($tpl);
    }

    /**
     * @return  void
     */
    protected function addToolbar()
    {
        $canDo = ContentHelper::getActions('com_regularlabsmanager');

        $toolbar = RL_Document::getToolbar();

        ToolbarHelper::title(Text::_('REGULARLABSEXTENSIONMANAGER'), 'regularlabsmanager icon-reglab');

        $arrow = JFactory::getApplication()->getLanguage()->isRtl() ? 'arrow-right' : 'arrow-left';

        $toolbar->standardButton('back')
            ->text(Text::_('JTOOLBAR_BACK'))
            ->buttonClass('btn btn-success hidden')
            ->icon('icon-' . $arrow)
            ->onclick('RegularLabs.Manager.refresh(true);');

        $toolbar->standardButton('refresh')
            ->text(Text::_('RLEM_REFRESH'))
            ->buttonClass('btn btn-success hidden')
            ->icon('icon-refresh')
            ->onclick('RegularLabs.Manager.refresh(true);');

        $toolbar->standardButton('retry')
            ->text(Text::_('RLEM_RETRY'))
            ->buttonClass('btn btn-primary hidden')
            ->icon('icon-refresh')
            ->onclick('RegularLabs.Manager.retry();');

        $toolbar->standardButton('update_all')
            ->text(Text::_('RLEM_UPDATE_ALL'))
            ->buttonClass('btn btn-primary hidden')
            ->icon('icon-upload')
            ->onclick('RegularLabs.Manager.update();');

        if ($canDo->get('core.admin'))
        {
            $toolbar->preferences('com_regularlabsmanager');
        }
    }
}
