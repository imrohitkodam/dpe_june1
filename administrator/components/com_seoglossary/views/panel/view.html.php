<?php
/**    
 * SEO Glossary Panel view
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
class SeoglossaryViewPanel extends SeogView
{

	/**
	 * Display the view
	 */
	public function display( $tpl = null )
	{
		//jimport( 'joomla.html.html.tabs' );
		if (!version_compare(JVERSION, '4.0', 'ge'))
		{
		$this->sidebar = JHtmlSidebar::render();
		}

		$this->addToolbar();
		parent::display( $tpl );
	}

	protected function addToolbar()
	{
		require_once JPATH_COMPONENT . '/helpers/seoglossary.php';

		$state = $this->get( 'State' );
		JToolBarHelper::title( JText::_( 'COM_SEOGLOSSARY_PANEL_GLOSSARIES' ), 'catglossaries.png' );
		JToolBarHelper::preferences( 'com_seoglossary' );
	}

}
