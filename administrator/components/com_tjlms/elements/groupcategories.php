<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_tjlms
 *
 * @copyright   Copyright (C) 2005 - 2015 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access
defined('_JEXEC') or die;
use Joomla\CMS\Form\FormField;
use Joomla\CMS\Factory;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Filesystem\File;
use Joomla\CMS\Language\Text;

jimport('joomla.form.formfield');

/**
 * Supports an HTML select list of categories
 *
 * @since  1.0.0
 */
class JFormFieldGroupcategories extends JFormField
{
	protected $type = 'groupcategories';

	/**
	 * Method to get the field input markup.
	 *
	 * @return   string  The field input markup.
	 *
	 * @since  1.0.0
	 */
	public function getInput()
	{
		return self::fetchElement($this->name, $this->value, $this->element, $this->options['control']);
	}

	/**
	 * Method to get a element
	 *
	 * @param   string  $name          Field name
	 * @param   string  $value         Field value
	 * @param   string  &$node         Node
	 * @param   string  $control_name  Controler name
	 *
	 * @return  string  A store id.
	 *
	 * @since	1.0.0
	 */
	public function fetchElement($name, $value, &$node, $control_name)
	{
		$db = Factory::getDBO();

		$componentParams = ComponentHelper::getParams('com_tjlms');
		$integration = $componentParams->get('social_integration', 'joomla', 'STRING');
		$esmainfile = JPATH_ROOT . '/administrator/components/com_easysocial/includes/foundry.php';
		$jsmainfile = JPATH_ROOT . '/components/com_community/libraries/core.php';

		if ($integration == 'easysocial' && File::exists($esmainfile))
		{
			require_once $esmainfile;

			$userProfileId = $creator_uid = Foundry::user()->id;
			$model = FD::model('Groups');
			$list = $model->getCreatableCategories($userProfileId);

			// $createNewLink = 'index.php?option=com_easysocial&view=groups&layout=categoryForm';
		}
		elseif ($integration == 'jomsocial' && File::exists($jsmainfile))
		{
			require_once $jsmainfile;

			$model = CFactory::getModel('groups');
			$list = $model->getAllCategories();

			// $createNewLink = 'index.php?option=com_community&view=groupcategories';
		}
		else
		{
			return '<div class="alert alert-warning">' . Text::_('NO_SOCIAL_GROUPS_CAT_FOUND') . '</div>';
		}

		$options = array();

		foreach ($list as $eachCat)
		{
			$options[] = JHTML::_('select.option', $eachCat->id, isset($eachCat->title) ? $eachCat->title : $eachCat->name);
		}

		$addedField = 'class="inputbox form-control"';
		$fieldName = $name;

		$html = '<div id="grpCategoriesField">';
		$html .= JHTML::_('select.genericlist', $options, $fieldName, $addedField, 'value', 'text', $value, $control_name . $name);

		// $html .= '<a href="' . $createNewLink . '">' . JText::_('COM_TJLMS_CREATE_NEW_CAT_LINK') . '</a>';
		$html .= '</div>';

		return $html;
	}
}
