<?php
/**
 * @package     MPF
 * @subpackage  Form
 *
 * @copyright   Copyright (C) 2016 Ossolution Team, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE
 */
defined('_JEXEC') or die;
use Joomla\CMS\Form\FormField;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Factory;
use Joomla\CMS\Form\FormHelper;

FormHelper::loadFieldClass('list');


$doc = Factory::getDocument();
$doc->addScriptDeclaration('const site_root = "' . Uri::root() . '"');
HTMLHelper::script('modules/mod_ucm_data/assets/js/ucmtype.js');


/**
 * Supports a custom field which display list of countries
 *
 * @package     Joomla.MPF
 * @subpackage  Form
 */
class JFormFieldUcmtype extends JFormFieldList
{
	/**
	 * The form field type.
	 *
	 * @var    string
	 * @since  11.1
	 */
	public $type = 'ucmtype';

	/**
	 * Method to get a list of options for a list input.
	 *
	 * @return	array		An array of HTMLHelper options.
	 *
	 * @since   1.3.39
	 */
	protected function getOptions()
	{
		$db    = Factory::getDbo();
		$app   = Factory::getApplication();
		$query = $db->getQuery(true);

		// Select the required fields from the table.
		$query->select('tut.id, tut.title');
		$query->from($db->qn('#__tj_ucm_types', 'tut'));
		$query->where($db->qn('tut.state') . '= 1');
		$query->order($db->escape('tut.id ASC'));
		$db->setQuery($query);

		// Get all countries.
		$allUcmType = $db->loadObjectList();

		$options = array();
		$options[] = HTMLHelper::_('select.option', '0', Text::_('MOD_UCM_DATA_UCM_TYPE_DEFAULT_OPTION'));

		foreach ($allUcmType as $type)
		{
			$options[] = HTMLHelper::_('select.option', $type->id, $type->title);
		}

		return $options;
	}
}
