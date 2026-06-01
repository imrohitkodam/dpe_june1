<?php
/**    
 * SEO Glossary Glossaries View
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
 * View class for a list of Seoglossary.
 */
class SeoglossaryViewGlossaries extends SeogView
{
	protected $items;
	protected $pagination;
	protected $state;
	protected $glossariesList;

	/**
	 * Display the view
	 */
	public function display( $tpl = null )
	{
		$this->params = JComponentHelper::getParams( 'com_seoglossary' );

		$this->searchform = SeoglossaryHelper::getSearchform( );
		
		$this->glossariesList = SeoglossaryHelper::getGlossariesList();

		$this->state = $this->get( 'State' );
		$this->items = $this->get( 'Items' );
		$myglossdata = new SeoglossaryModelglossaries;
		$gloss = array( );
		$glossvalue = array( );
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
			$catid = JFactory::getApplication()->input->getInt('catid', null);
			$category = $myglossdata->getGlossaryCategory($catid);
			$pathway->addItem($category->name, '');
			SeoglossaryHelper::addTitleElement($category->name);
			$this->glossarytheme=$this->params->get('template','default');
			if($this->glossarytheme=="default")
			{
				$this->glossarytheme="table";
			}
			
			$this->show_grid_theme=$this->params->get('show_grid_image',false);
		} else {
			SeoglossaryHelper::addTitleElement($menuItem->title);
			$this->glossarytheme=$menuItem->query['theme'];
			$this->show_grid_theme=$menuItem->query['show_grid_image'];
		}
		if($this->glossarytheme=="alpha")
		{
		$this->alphabetform = SeoglossaryHelper::getAlphabetForm("alphabet");
		}
		else
		{
		$this->alphabetform = SeoglossaryHelper::getAlphabetForm( );
			
		}
		$glossaryItems = array( );
		$i = 0;
		foreach ($this->items as $item )
		{
			$i++;
			$a = new SeoglossaryHelper( );
			$defination = $a->processimage( $item->tdefinition );
			$link=SeoglossaryHelper::getUrlLink($item,JFactory::getApplication()->input->getInt( 'Itemid' ));
			$glossaryItems[] = array(
				'link' => $link,
				'phrase' => ($item->tterm),
				'explanation' => ($defination)
			);

		}
		$this->glossaryItems = $glossaryItems;
		$this->pagination = $this->get( 'Pagination' );
		// Check for errors.
		if ( count( $errors = $this->get( 'Errors' ) ) )
		{
			JFactory::getApplication()->enqueueMessage(implode("\n", $errors),'error');
			return false;
		}
		parent::display( $tpl );
	}

	/**
	 * Add the page title and toolbar.
	 *
	 * @since	1.6
	 */

}
