<?php

/**
 * @version     1.0.0
 * @package     com_tmt
 * @copyright   Copyright (C) 2013. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @author      Techjoomla <contact@techjoomla.com> - http://techjoomla.com
 */
// No direct access
defined('_JEXEC') or die;

jimport('joomla.application.component.view');

/**
 * View to edit
 */
class TmtViewSinglepaper extends JViewLegacy {

	protected $state;
	protected $item;
	protected $form;
	protected $params;

	/**
	 * Display the view
	 */
	public function display($tpl = null) {
		$app	= JFactory::getApplication();
   		$user=JFactory::getUser();
		$uid=$user->id;
		//$SubusersHelper=new SubusersHelper();
		//$company		= $SubusersHelper->getMyCompaniesListData($uid);

/*		 if(!$company){
		 	return JError::raiseWarning(404, JText::_('JERROR_ALERTNOAUTHOR'));

		 }
		 */
		//check if user is logged in
		if (! $user->id) {
			JError::raise(E_WARNING, $code=503, JText::_('COM_TMT_MESSAGE_LOGIN_FIRST'), $info='');
			return false;
		}
		$this->state = $this->get('State');
		$this->item = $this->get('Items');
		$this->params = $app->getParams('com_tmt');
   		$this->form		= $this->get('Form');

        $tmtFrontendHelper=new tmtFrontendHelper();
		$this->checkpaper_itemid=$tmtFrontendHelper->getItemId('index.php?option=com_tmt&view=checkpaper');

   		// Fetch answers data
   		$this->answers = $this->get('Answers');

		// Check for errors.
		if (count($errors = $this->get('Errors'))) {
			throw new Exception(implode("\n", $errors));
		}
		$authorised = $user->authorise('core.create', 'com_tmt');
		if ($authorised !== true) {
			throw new Exception(JText::_('JERROR_ALERTNOAUTHOR'));
		}
		$this->_prepareDocument();
		parent::display($tpl);
	}


	/**
	 * Prepares the document
	 */
	protected function _prepareDocument()
	{
		$app	= JFactory::getApplication();
		$menus	= $app->getMenu();
		$title	= null;

		// Because the application sets a default page title,
		// we need to get it from the menu item itself
		$menu = $menus->getActive();
		if($menu)
		{
			$this->params->def('page_heading', $this->params->get('page_title', $menu->title));
		} else {
			$this->params->def('page_heading', JText::_('com_tmt_DEFAULT_PAGE_TITLE'));
		}
		$title = $this->params->get('page_title', '');
		if (empty($title)) {
			$title = $app->getCfg('sitename');
		}
		elseif ($app->getCfg('sitename_pagetitles', 0) == 1) {
			$title = JText::sprintf('JPAGETITLE', $app->getCfg('sitename'), $title);
		}
		elseif ($app->getCfg('sitename_pagetitles', 0) == 2) {
			$title = JText::sprintf('JPAGETITLE', $title, $app->getCfg('sitename'));
		}
		$this->document->setTitle($title);

		if ($this->params->get('menu-meta_description'))
		{
			$this->document->setDescription($this->params->get('menu-meta_description'));
		}

		if ($this->params->get('menu-meta_keywords'))
		{
			$this->document->setMetadata('keywords', $this->params->get('menu-meta_keywords'));
		}

		if ($this->params->get('robots'))
		{
			$this->document->setMetadata('robots', $this->params->get('robots'));
		}
	}

}
