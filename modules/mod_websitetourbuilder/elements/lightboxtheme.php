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
JFormHelper::loadFieldClass('list');
/**
 * Form Field class for the component
 */
class FormFieldLightboxtheme extends JFormFieldList
{
	/**
	 * The field type.
	 *
	 * @var		string
	 */
	protected $type = 'Lightboxtheme';
 
	/**
	 * Method to get a list of options for a list input.
	 *
	 * @return	array		An array of HTMLHelper options.
	 */
	protected function getOptions() 
	{
		
		$options = array();
        $ligthboxDir = JPATH_SITE.'/modules/mod_websitetourbuilder/theme/lightbox';
        if ($handle = opendir($ligthboxDir)) {            
        
            /* This is the correct way to loop over the directory. */
            while (false !== ($entry = readdir($handle))) {
                if($entry!="." && $entry!=".." && is_file($ligthboxDir.'/'.$entry) && (strstr($entry,'.php')!==false) )
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
