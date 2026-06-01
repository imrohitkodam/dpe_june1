<?php
/**    
 * SEO Glossary Glossary view
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

jimport('joomla.application.component.view');

/**
 * View to edit
 */
class SeoglossaryViewImport extends JViewLegacy {

    public function display($tpl = null) {
        $user = JFactory::getUser();
        $isroot = $user->authorise('core.admin');
        if(!$isroot)
        {
           JFactory::getApplication()->enqueueMessage('No Authorized', 'error');
            $redir = 'index.php?option=com_seoglossary';
            JFactory::getApplication()->redirect($redir);
        }
        JToolBarHelper::title(JText::_('COM_SEOGLOSSARY_IMPORT'));
        if (!version_compare(JVERSION, '4.0', 'ge'))
		{
        $this->sidebar = JHtmlSidebar::render();
        }
        parent::display($tpl);
    }
}
