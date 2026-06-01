<?php
/**
 * @package    Tjfields
 * @author     Techjoomla <extensions@techjoomla.com>
 * @copyright  Copyright (c) 2009-2019 TechJoomla. All rights reserved.
 * @license    GNU General Public License version 2 or later.
 */

defined('JPATH_BASE') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Form\FormField;

jimport('joomla.form.formfield');

/**
 * Supports an HTML select list of allocated cluster
 *
 * @since  __DEPLOY_VERSION__
 */

class JFormFieldFreetext extends FormField
{
	/**
	 * The form field type.
	 *
	 * @var    string
	 * @since  __DEPLOY_VERSION__
	 */
	protected $type = 'Freetext';

	/**
	 * Field for getting field labels
	 *
	 * @return  html mapping fields
	 *
	 * @since   1.0
	 */
	public function getInput()
	{
		$document = Factory::getDocument();
		$document->addStyleSheet(Uri::base() . 'administrator/components/com_tjfields/assets/css/tjfields.css');
		$freetextClass = 'tjfield-elements-freetext';

		$formName = explode('.', $this->form->getName());

		if ($formName[0] != 'subform')
		{
			// Let's remove controls class from parent
			// And, remove control-group class from grandparent
			$script = 'jQuery(document).ready(function(){
				jQuery("#' . $this->id . '").parent().removeClass();
				jQuery("#' . $this->id . '").parent().parent().removeClass("form-group");
				jQuery("#' . $this->id . '").parent().parent().parent().removeClass();
				jQuery("#' . $this->id . '").parent().parent().parent().parent().removeClass();
				jQuery("#' . $this->id . '").parent().siblings().addClass("tjfield-d-none");
			});';
		}
		else
		{
			$script = 'jQuery(document).ready(function(){
				jQuery("#' . $this->id . '").parent().removeClass();
				jQuery("#' . $this->id . '").parent().parent().removeClass();
				jQuery("#' . $this->id . '").parent().siblings().addClass("tjfield-d-none");
			});
			jQuery(document).on("subform-row-add", function(event, row){
				jQuery("#' . $this->id . '").parent().removeClass();
				jQuery("#' . $this->id . '").parent().parent().removeClass();
				jQuery("#' . $this->id . '").parent().siblings().addClass("tjfield-d-none");
			});';
		}

		$document->addScriptDeclaration($script);

		// Show them a freetext.
		$return = '<freetext class="clearfix ' . $freetextClass . ' pull-right" id="' . $this->id . '">' . $this->element->attributes()->freetext . '</freetext>';

		return $return;
	}
}
