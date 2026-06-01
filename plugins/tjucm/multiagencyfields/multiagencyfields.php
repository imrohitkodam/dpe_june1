<?php
/**
 * @package    Tjucm
 * @author     Techjoomla <extensions@techjoomla.com>
 * @copyright  Copyright (c) 2009-2019 TechJoomla. All rights reserved.
 * @license    GNU General Public License version 2 or later.
 */

defined('_JEXEC') or die();

JLoader::import('components.com_cluster.includes.cluster', JPATH_ADMINISTRATOR);

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\CMS\Plugin\CMSPlugin;

/**
 * Plug-in to get the fields and value of multiagency
 *
 * @since  __DEPLOY_VERSION__
 */
class PlgTjucmMultiagencyFields extends CMSPlugin
{
	/**
	 * Load plugin language file automatically so that it can be used inside component
	 *
	 * @var    boolean
	 * @since  __DEPLOY_VERSION__
	 */
	protected $autoloadLanguage = true;

	/**
	 * Plugin that shows a field and value
	 *
	 * @param   object  $data  The data object.
	 *
	 * @return array
	 *
	 * @since  __DEPLOY_VERSION__
	 */
	public function onDocumentBeforeDisplay($data)
	{
		return $this->getFieldData($data);
	}

	/**
	 * Plugin that shows a fields
	 *
	 * @param   object  $data  The data object.
	 *
	 * @return array
	 *
	 * @since  __DEPLOY_VERSION__
	 */
	public function onDocumentBeforeCreate($data)
	{
		return $this->getFields($data);
	}

	/**
	 * This function provides the field value array of multiagency
	 *
	 * @param   array  $data  field data
	 *
	 * @return array
	 *
	 * @since  __DEPLOY_VERSION__
	 */
	public function getFieldData($data)
	{
		JLoader::register('FieldsHelper', JPATH_ADMINISTRATOR . '/components/com_fields/helpers/fields.php');
		$agencyFields = FieldsHelper::getFields('com_multiagency.multiagency');

		$schoolObj  = new stdClass;
		$schoolObj->id = 0;
		$schoolObj->name = Text::_('PLG_MULTIAGENCY_SCHOOL_FIELD_NAME');
		$schoolObj->title = Text::_('PLG_MULTIAGENCY_SCHOOL_FIELD_TITLE');
		$agencyFields[] = $schoolObj;

		BaseDatabaseModel::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_fields/models');
		$fieldModel = BaseDatabaseModel::getInstance('Field', 'FieldsModel', array("ignore_request" => true));

		foreach ($agencyFields as $agencyField)
		{
			$fieldIds[] = $agencyField->id;
			$fieldnames[$agencyField->id] = $agencyField->name;
		}

		$clusterTable = ClusterFactory::table("Clusters");
		$clusterTable->load(array('id' => $data['clusterId']));

		$agencyValues = $fieldModel->getFieldValues($fieldIds, $clusterTable->client_id);

		foreach ($agencyValues as &$agencyValue)
		{
			if (is_array($agencyValue))
			{
				// Conver all values to comma separated one

				$agencyValue[0] = implode(', ', $agencyValue);

				// Remove All elements except first
				$agencyValue    = array_slice($agencyValue, 0, 1);
			}
		}

		// Get cluster name
		$clustertable = ClusterFactory::table('Clusters');
		$clustertable->load(array('id' => $data['clusterId']));

		$agencyValues[0] = $clustertable->name;

		ksort($fieldnames);
		ksort($agencyValues);

		// Add current date field and value
		$agencyValues[array_key_last($fieldnames) + 1] = HtmlHelper::date('now', Text::_('PLG_MULTIAGENCY_DATE_FORMAT'));
		$fieldnames[array_key_last($fieldnames) + 1] = Text::_('PLG_MULTIAGENCY_DATE_FIELD_NAME');

		return array_combine(array_intersect_key($fieldnames, $agencyValues), array_intersect_key($agencyValues, $fieldnames));
	}

	/**
	 * This function provides the fields of multiagency
	 *
	 * @param   array  $data  field data
	 *
	 * @return array
	 *
	 * @since  __DEPLOY_VERSION__
	 */
	public function getFields($data)
	{
		JLoader::register('FieldsHelper', JPATH_ADMINISTRATOR . '/components/com_fields/helpers/fields.php');

		$multifield = FieldsHelper::getFields($data['context']);

		// School object to add school name field manually
		$schoolObj  = new stdClass;
		$schoolObj->name = Text::_('PLG_MULTIAGENCY_SCHOOL_FIELD_NAME');
		$schoolObj->title = Text::_('PLG_MULTIAGENCY_SCHOOL_FIELD_TITLE');

		$multifield[] = $schoolObj;

		// Add Date field
		$dateObj  = new stdClass;
		$dateObj->name = Text::_('PLG_MULTIAGENCY_DATE_FIELD_NAME');
		$dateObj->title = Text::_('PLG_MULTIAGENCY_DATE_FIELD_TITLE');

		$multifield[] = $dateObj;

		return $multifield;
	}
}
