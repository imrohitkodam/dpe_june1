<?php
/**
 * @package    Tjucm
 * @author     Techjoomla <extensions@techjoomla.com>
 * @copyright  Copyright (c) 2009-2019 TechJoomla. All rights reserved.
 * @license    GNU General Public License version 2 or later.
 */

defined('_JEXEC') or die();

use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\CMS\Plugin\CMSPlugin;

/**
 * Plug-in to show a field and value of tjucm
 *
 * @since  __DEPLOY_VERSION__
 */
class PlgTjucmFields extends CMSPlugin
{
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
	 * This function provides the fields of tjucm
	 *
	 * @param   array  $data  field data
	 *
	 * @return array
	 *
	 * @since  __DEPLOY_VERSION__
	 */
	public function getFields($data)
	{
		BaseDatabaseModel::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_tjfields/models');
		$tjfieldsModel = BaseDatabaseModel::getInstance('Fields', 'TjfieldsModel', array("ignore_request" => true));
		$tjfieldsModel->setState('filter.state', 1);
		$tjfieldsModel->setState('filter.client', $data['unique_identifier']);
		$fields = $tjfieldsModel->getItems();

		$subFormFields = array();
		$extraFields = array();

		foreach ($fields as $field)
		{
			$formSource = json_decode($field->params)->formsource;

			if (!empty($formSource))
			{
				$subFieldsModel = BaseDatabaseModel::getInstance('Fields', 'TjfieldsModel', array("ignore_request" => true));
				$client = str_replace('components/com_tjucm/models/forms/', '', $formSource);
				$client = 'com_tjucm.' . str_replace('form_extra.xml', '', $client);
				$subFieldsModel->setState('filter.state', 1);
				$subFieldsModel->setState('filter.client', $client);
				$subFormFields[] = $subFieldsModel->getItems();
			}
		}

		foreach ($subFormFields as $subFormField)
		{
			foreach ($subFormField as $subField)
			{
				$extraFields[] = $subField;
			}
		}

		return array_merge($fields, $extraFields);
	}
}
