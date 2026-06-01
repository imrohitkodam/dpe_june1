<?php
/**    
 * SEO Glossary Glossary View
 *
 * We developed this code with our hearts and passion.
 * We hope you found it useful, easy to understand and change.
 * Otherwise, please feel free to contact us at contact@joomunited.com
 *
 * @package 	SEO Glossary
 * @copyright 	Copyright (C) 2012 JoomUnited (http://www.joomunited.com). All rights reserved.
 * @license 	GNU General Public License version 2 or later; http://www.gnu.org/licenses/gpl-2.0.html
 */

// No direct access
defined( '_JEXEC' ) or die ;

jimport( 'joomla.application.component.view' );
require_once(JPATH_SITE . '/components/com_seoglossary/helpers/compat.php');

/**
 * View to edit
 */
class SeoglossaryViewGlossary extends SeogView
{
	protected $state;
	protected $item;
	protected $form;
	protected $glossariesList;

	/**
	 * Display the view
	 */
	public function display( $tpl = null )
	{
		// add required stylesheets from admin template
		$this->params = JComponentHelper::getParams( 'com_seoglossary' );

		$document = JFactory::getDocument( );
		$this->searchform = SeoglossaryHelper::getSearchform( );
		$this->alphabetform = SeoglossaryHelper::getAlphabetForm();
		$this->glossariesList = SeoglossaryHelper::getGlossariesList();

		$this->state = $this->get( 'State' );
		$this->item = $this->get( 'Item' );
		$this->form = $this->get( 'Form' );
		$model      = $this->getModel();
		$this->hits=$model->hit($this->item->id);
		require_once JPATH_COMPONENT . '/models/glossaries.php';
		$myglossdata = new SeoglossaryModelglossaries;
		
		$mainframe = JFactory::getApplication();
		$pathway   = $mainframe->getPathway();
		$menu = $mainframe->getMenu();
		$menuItem = $menu->getActive();
		if (strpos(@$menuItem->link, 'view=glossary') !== false) {
		}
		else
		{
				$menuItem=null;
		}
		if ($menuItem == null) {
			// We need to add the name of the glossary
            $catid = (!empty($this->item->catid)) ? $this->item->catid : JFactory::getApplication()->input->getInt('catid', NULL);
			if( $catid)
			{
				$category = $myglossdata->getGlossaryCategory($catid);
			//$pathway->addItem($category->name, JRoute::_('index.php?option=com_seoglossary&view=glossaries&catid='.$catid));
			SeoglossaryHelper::addTitleElement($category->name);
			$this->glossarytheme=$this->params->get('template','default');
			if($this->glossarytheme=="default")
			{
				$this->glossarytheme="table";
			}
			$this->show_grid_theme=$this->params->get('show_grid_image',false);
			}
		} else {
                if (!($menuItem->getParams()->get('page_title') == '')) {
                    SeoglossaryHelper::addTitleElement($menuItem->getParams()->get('page_title'));
                } else {
                    SeoglossaryHelper::addTitleElement($menuItem->title);
                }
				if(@$menuItem->query['theme'])
				{
					$this->glossarytheme=$menuItem->query['theme'];
					$this->show_grid_theme=$menuItem->query['show_grid_image'];
				}
		}
		$pathway->addItem($this->item->tterm, '');
		SeoglossaryHelper::addTitleElement($this->item->tterm);
		// Check for errors.
		if ( count( $errors = $this->get( 'Errors' ) ) )
		{
			JFactory::getApplication()->enqueueMessage(implode("\n", $errors),'error');
			return false;
		}

		parent::display( $tpl );

	}

}
