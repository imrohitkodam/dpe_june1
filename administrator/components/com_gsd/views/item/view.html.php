<?php

/**
 * @package         Google Structured Data
 * @version         6.0.1 Pro
 * 
 * @author          Tassos Marinos <info@tassos.gr>
 * @link            http://www.tassos.gr
 * @copyright       Copyright © 2021 Tassos Marinos All Rights Reserved
 * @license         GNU GPLv3 <http://www.gnu.org/licenses/gpl.html> or later
*/

defined('_JEXEC') or die('Restricted access');

use GSD\Helper;
use Joomla\CMS\MVC\View\HtmlView;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Factory;
use Joomla\CMS\Toolbar\ToolbarHelper;

/**
 * Campaign View Class
 */
class GSDViewItem extends HtmlView
{
    /**
     * display method of Item view
     * @return void
     */
    public function display($tpl = null) 
    {
        // Check for errors.
        if (!is_null($this->get('Errors')) && count($errors = $this->get('Errors')))
        {
            Factory::getApplication()->enqueueMessage(implode("\n", $errors), 'error');
            return false;
        }

        // Assign the Data
        $this->form  = $this->get('Form');
        $this->item  = $this->get('Item');
        $this->isnew = is_null($this->item->id) ? true : false;

        // We don't need toolbar in the modal window.
        if ($this->getLayout() !== 'modal')
        {
            $this->addToolbar();
        }

        $this->title = $this->isnew ? Text::_('GSD_NEW_ITEM') : Text::_('GSD_EDIT_ITEM') . ": #" . $this->item->id;

        // Display the template
        parent::display($tpl);
    }

    /**
     * Setting the toolbar
     */
    protected function addToolBar() 
    {
        $input = Factory::getApplication()->input;
        $input->set('hidemainmenu', true);
        $isNew = ($this->item->id == 0);

        ToolBarHelper::title(Text::_('GSD') . ': ' . ($this->isnew ? Text::_('GSD_NEW_ITEM') : Text::_('GSD_EDIT_ITEM')));

        ToolbarHelper::saveGroup(
            [
                ['apply', 'item.apply'],
                ['save', 'item.save'],
                ['save2new', 'item.save2new']
            ],
            'btn-success'
        );

        ToolbarHelper::cancel('item.cancel', $isNew ? 'JTOOLBAR_CANCEL' : 'JTOOLBAR_CLOSE');
        ToolbarHelper::help('Help', false, "https://www.tassos.gr/docs/google-structured-data");
    }
}