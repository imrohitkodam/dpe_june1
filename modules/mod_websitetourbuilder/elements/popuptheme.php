<?php

/**
 * @version     1.5
 * @package     mod_websitetourbuilder
 * @copyright   Copyright (C) 2013. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @author      JoomlaForce Team <support@joomlaforce.com> - http://www.joomlaforce.com
 */
 
// No direct access to this file
defined('_JEXEC') or die;
use Joomla\CMS\Form\FormHelper;
use Joomla\CMS\HTML\HTMLHelper;
 
// import the list field type
jimport('joomla.form.helper');
FormHelper::loadFieldClass('list');

/**
 * Form Field class for the component
 */
class JFormFieldPopuptheme extends JFormFieldList
{
	/**
	 * The field type.
	 *
	 * @var		string
	 */
	protected $type = 'Popuptheme';
 
	/**
	 * Method to get a list of options for a list input.
	 *
	 * @return	array		An array of HTMLHelper options.
	 */
	protected function getOptions() 
	{
		
		$options = array();
        $popupDir = JPATH_SITE.'/modules/mod_websitetourbuilder/theme/popup';
        if ($handle = opendir($popupDir)) {            
        
            /* This is the correct way to loop over the directory. */
            while (false !== ($entry = readdir($handle))) {
                if($entry!="." && $entry!=".." && is_file($popupDir.'/'.$entry) && (strstr($entry,'.php')!==false) )
                {
                    $optionName = str_replace('.php','',$entry);
                    $options[] = HTMLHelper::_('select.option', $optionName, ucfirst($optionName));
                }
            }
            closedir($handle);
        }
		$options = array_merge(parent::getOptions(), $options);
		return $options;
	}
}
