<?php

/**
 * SEO Glossary Helper
 *
 * We developed this code with our hearts and passion.
 * We hope you found it useful, easy to understand and change.
 * Otherwise, please feel free to contact us at contact@joomunited.com
 *
 * @package 	SEO Glossary
 * @copyright 	Copyright (C) 2012 JoomUnited (http://www.joomunited.com). All rights reserved.
 * @license 	GNU General Public License version 2 or later; http://www.gnu.org/licenses/gpl-2.0.html
 */
defined( '_JEXEC' ) or die ;
jimport('joomla.html.html');
jimport('joomla.form.formfield');
jimport('joomla.filesystem.file');

/**
 * Supports an HTML select list of categories
 */
class JFormFieldHelperOverride extends JFormField {

    /**
     * The form field type.
     *
     * @var		string
     * @since	1.6
     */
    protected $type = 'helperoverride';

    /**
     * Method to get the field input markup.
     *
     * @return	string	The field input markup.
     * @since	1.6
     */
    protected function getInput() {
        
        // Initialize variable
        
        $document=JFactory::getDocument();
        $db=JFactory::getDbo();
        $sql="select * from #__template_styles where client_id=0 and home=1";
        $db->setQuery($sql);
        $result=$db->loadObject();
        $tpath=JPATH_SITE."/templates/".$result->template."/html/com_seoglossary/seoglossary.php";
        if(JFile::exists($tpath))
        {
            $html=JText::_("COM_SEOGLOSSARY_FORM_LBL_HELPER_EXIST").": templates/".$result->template."/html/com_seoglossary/seoglossary.php";
        }
        else
        {
            $html=JText::_("COM_SEOGLOSSARY_FORM_LBL_HELPER_NOEXIST");
            $html.=" <a href='".JURI::base()."/index.php?option=com_seoglossary&task=overridehelper' class='btn btn-primary'>".JTEXT::_('COM_SEOGLOSSARY_FORM_LBL_OVERRIDE')."</a>";
        }
        return $html;
    }
}