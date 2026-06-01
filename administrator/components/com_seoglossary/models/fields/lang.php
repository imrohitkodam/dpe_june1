<?php

/**
 * @version    3.2.5
 * @package     com_seoglossary
 * @copyright   JoomUnited (C) 2011. All rights reserved.
 * @license     http://www.gnu.org/copyleft/gpl.html GNU/GPL
 * ****@author      joomunited - contact@joomunited.com
 */
defined('JPATH_BASE') or die;

jimport('joomla.html.html');
jimport('joomla.form.formfield');

/**
 * Supports an HTML select list of categories
 */
class JFormFieldLang extends JFormField {

    /**
     * The form field type.
     *
     * @var		string
     * @since	1.6
     */
    protected $type = 'lang';

    /**
     * Method to get the field input markup.
     *
     * @return	string	The field input markup.
     * @since	1.6
     */
    protected function getInput() {
        
        // Initialize variable
             $lang = JFactory::getLanguage();
             $document = JFactory::getDocument();        
            $document->addStyleDeclaration(".form-horizontal .control-label{ min-width:250px;}");
            $lang->load('com_seoglossary', JPATH_ADMINISTRATOR.'/components/com_seoglossary',null, true);
            $lang->load('com_seoglossary.override',JPATH_ADMINISTRATOR.'/components/com_seoglossary',null, true );
            $lang->load('com_seoglossary.sys',JPATH_ADMINISTRATOR.'/components/com_seoglossary',null, true );
        return;
    }
    protected function getLabel() {
            return;
        }

}