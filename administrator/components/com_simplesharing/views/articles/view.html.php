<?php

/**
 * @version     1.0.2
 * @package     com_simplesharing
 * @copyright   Copyright (C) 2014. All rights reserved.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 * @author      NYC HelpDesk.co LLC <support@nychelpdesk.co> - nychelpdesk.co
 */

// No direct access
defined('_JEXEC') or die;

jimport('joomla.application.component.view');

/**
 * View class for a list of Simplesharing.
 */
class SimplesharingViewArticles extends JViewLegacy {

    protected $items;
    protected $pagination;
    protected $state;

    /**
     * Display the view
     */
    public function display($tpl = null) {
        $this->state = $this->get('State');
        $this->items = $this->get('Items');
        $this->pagination = $this->get('Pagination');

        $model = JModelList::getInstance('SlaveWebsites', 'SimpleSharingModel', array('context'=>'com_simplesharing.articles'));
        $model->setState('filter.state', 1);
        $websites = $model->getItems();
        $this->set('websites', $websites);
        
        // Check for errors.
        if (count($errors = $this->get('Errors'))) {
            throw new Exception(implode("\n", $errors));
        }
        
        SimplesharingHelper::addSubmenu('articles');

        $this->addToolbar();

        $this->sidebar = JHtmlSidebar::render();
        parent::display($tpl);
    }

    /**
     * Add the page title and toolbar.
     *
     * @since	1.6
     */
    protected function addToolbar() {
        require_once JPATH_COMPONENT . '/helpers/simplesharing.php';
        require_once JPATH_COMPONENT . '/helpers/toolbar.php';

        $state = $this->get('State');
        $canDo = SimplesharingHelper::getActions($state->get('filter.category_id'));

        JToolBarHelper::title(JText::_('COM_SIMPLESHARING_TITLE_ARTICLES'), 'articles.png');

        SimpleSharingToolbarHelper::modal("collapseModal", null, 'COM_SIMPLESHARING_ARTICLES_SHARE');
        if ($canDo->get('core.admin')) {
            JToolBarHelper::preferences('com_simplesharing');
        }

        //Set sidebar action - New in 3.0
        JHtmlSidebar::setAction('index.php?option=com_simplesharing&view=articles');

        $this->extra_sidebar = '';
        //
    }

	protected function getSortFields()
	{
		return array(
		'a.id' => JText::_('JGRID_HEADING_ID'),
		'a.title' => JText::_('COM_SIMPLESHARING_ARTICLE_TITLE'),
        'category_title' => JText::_('COM_SIMPLESHARING_ARTICLE_CATEGORY'),
		'a.ordering' => JText::_('JGRID_HEADING_ORDERING'),
		'a.state' => JText::_('JSTATUS'),
		);
	}

}
