<?php
/**
 * @package     TjGoPhish
 * @subpackage  com_tjgophish
 *
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (C) 2009 - 2020 Techjoomla. All rights reserved.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Form\FormHelper;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Http\Http;
use Joomla\CMS\Factory;

FormHelper::loadFieldClass('list');

/**
 * Supports an HTML select list of allocated GoPhishGroups
 *
 * @since  1.0.0
 */

class JFormFieldGoPhishGroups extends JFormFieldList
{
	/**
	 * The form field type.
	 *
	 * @var    string
	 * @since  1.0.0
	 */
	protected $type = 'Gophishgroups';

	/**
	 * Fiedd to decide if options are being loaded externally and from xml
	 *
	 * @var		integer
	 * @since	1.0.0
	 */
	protected $loadExternally = 0;

	/**
	 * The form field value.
	 *
	 * @var    mixed
	 * @since  1.0.0
	 */
	protected $value = '';

	/**
	 * Method to get a list of options for GoPhishGroups field.
	 *
	 * @return array An array of JHtml options.
	 *
	 * @since   1.0.0
	 */
	protected function getOptions()
	{
		$doc = Factory::getDocument();
		$doc->addScript(JUri::root() . 'media/com_tjgophish/js/ui/groupslistfield.min.js');

		$clusterids = array();

		// Check if cluster field is there in the form
		$clusterAware = $this->getAttribute("clusterAware");
		$clusterField = $this->getAttribute("clusterField");
		$fieldName = $this->getAttribute("name");

		$clusterFieldId = str_replace($fieldName, $clusterField, $this->id);
		$fields = $this->form->getFieldset();

		if (!empty($clusterAware) && !empty($clusterField))
		{
			$clusterids[] = (INT) $fields[$clusterFieldId]->value;
		}
		else
		{
			// Get users clusters
			JLoader::import('components.com_tjgophish.models.fields.cluster', JPATH_ADMINISTRATOR);
			$cluster = FormHelper::loadFieldType('cluster', false);
			$usersClusters = $cluster->getOptionsExternally();

			foreach ($usersClusters as $usersCluster)
			{
				if (!empty($usersCluster->value))
				{
					$clusterids[] = $usersCluster->value;
				}
			}
		}

		// Get all records from the group xref table
		JLoader::import('components.com_tjgophish.models.groups', JPATH_SITE);
		$groupsModel = BaseDatabaseModel::getInstance('Groups', 'TjGoPhishModel', array("ignore_request" => true));
		$groups = $groupsModel->getItems();

		$options = array();

		foreach ($groups as $group)
		{
			if (in_array($group->cluster_id, $clusterids))
			{
				$options[] = HTMLHelper::_('select.option', $group->gophish_group_title, trim($group->gophish_group_title));
			}
		}

		return $options;
	}

	/**
	 * Method to get a list of options for a list input externally and not from xml.
	 *
	 * @return	array	An array of JHtml options.
	 *
	 * @since   1.0.0
	 */
	public function getOptionsExternally()
	{
		$this->loadExternally = 1;

		return $this->getOptions();
	}

	/**
	 * Method to get the field input markup.
	 *
	 * @return  string  The field input markup.
	 *
	 * @since   1.0.0
	 */
	protected function getInput()
	{
		$clusterAware = $this->getAttribute("clusterAware");
		$clusterField = $this->getAttribute("clusterField");
		$fieldName = $this->getAttribute("name");

		if ($clusterAware)
		{
			$fields = $this->form->getFieldset();

			// Check if cluster field is there in the form
			$clusterFieldId = str_replace($fieldName, $clusterField, $this->id);

			// If cluster field is not there in the form then show groups of all the clusters to which user belongs to
			if (array_key_exists($clusterFieldId, $fields))
			{
				// Add script to initialise groupslist field
				$document = JFactory::getDocument();

				// Add script to update groupslist field onchange of cluster field
				$document->addScriptDeclaration('jQuery(document).ready(function() {
					jQuery("#' . $clusterFieldId . '").change(function(){
						groupslist.updategroupslistField("' . $clusterFieldId . '", "' . $this->id . '");
					});
				});');
			}
		}

		return parent::getInput();
	}
}
