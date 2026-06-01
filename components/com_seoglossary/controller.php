<?php
/**    
 * SEO Glossary Base Controller
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
defined('_JEXEC') or die;

jimport('joomla.application.component.controller');
require_once(JPATH_SITE . '/components/com_seoglossary/helpers/compat.php');

class SeoglossaryController extends SeogController
{
    public function display($cachable = false, $urlparams = false)
    {
        $view = JFactory::getApplication()->input->getString('view', 'none');
		if ($view == 'none') {
			JFactory::getApplication()->enqueueMessage(JText::_("Page Not Found"),'error');
		}
		
		// Workaround to /0 bug that is not routed
		if ($view == 'glossaries' && JFactory::getApplication()->input->getString('letter', NULL) == '') {
			$path = JURI::getInstance()->getPath();
			$segments = explode ( '/', $path );
			if ( end($segments) == '0' ) {
				JFactory::getApplication()->input->set('letter', '0');
			}
		}

		parent::display();

		return $this;
    }

}