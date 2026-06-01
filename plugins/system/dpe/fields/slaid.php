<?php
/**
 * @package    dpe
 * @author     Techjoomla <extensions@techjoomla.com>
 * @copyright  Copyright (C) 2005 - 2021. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access.
defined('_JEXEC') or die();
use Joomla\CMS\Form\FormHelper;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Factory;
use Joomla\Registry\Registry;

JFormHelper::loadFieldClass('list');

/**
 * Supports an HTML select list of sla
 *
 * @since  __DEPLOY_VERSION__
 */
class JFormFieldSlaid extends JFormFieldList
{
	/**
	 * The form field type.
	 *
	 * @var		string
	 * @since	__DEPLOY_VERSION__
	 */
	protected $type = 'sla_id';

	/**
	 * Fiedd to decide if options are being loaded externally and from xml
	 *
	 * @var		integer
	 * @since	__DEPLOY_VERSION__
	 */
	protected $loadExternally = 0;

	/**
	 * Method to get a list of options for a list input.
	 *
	 * @return	array		An array of HTMLHelper options.
	 *
	 * @since   __DEPLOY_VERSION__
	 */
	protected function getOptions()
	{
		$db    = Factory::getDbo();
		$app   = Factory::getApplication();
		$query = $db->getQuery(true);
		$id    = $app->input->get('id', 0, 'INT');

		// Select the required fields from the table.
		$query->select('ts.id, ts.title, ts.params');
		$query->from($db->qn('#__tj_slas', 'ts'));

		$db->setQuery($query);

		// Get all countries.
		$allSla = $db->loadObjectList();

		if ($id)
		{
			$db    = Factory::getDbo();
			$query = $db->getQuery(true);

			// Select the required fields from the table.
			$query->select('tscx.sla_id, tsl.title');
			$query->from($db->qn('#__tj_sla_cluster_xref', 'tscx'));
			$query->join(
			'INNER', $db->quoteName('#__tj_slas', 'tsl') .
				' ON ' . $db->quoteName('tsl.id') . '=' . $db->quoteName('tscx.sla_id')
			);
			$query->where($db->qn('tscx.license_id') . ' = ' . (int) $id);

			$db->setQuery($query);
			$savedSla = $db->loadObject();
		}

		$options = array();

		foreach ($allSla as $sla)
		{
			$params = new Registry($sla->params);

			// Display on Core and Show in List SLA Slas in sla field
			if ($params->get('core') || $params->get('show_in_sla_list'))
			{
				$options[] = HTMLHelper::_('select.option', $sla->id, $sla->title);
			}
		}

		if ($savedSla->sla_id && $id)
		{
			$optionsIds = array_column($options, 'id');

			// Add the sla id that are saved in #__tj_sla_cluster_xref and not part of core sla. It is for edit licence view
			if (!in_array($savedSla->sla_id, $optionsIds))
			{
				$options[] = HTMLHelper::_('select.option', $savedSla->sla_id, $savedSla->title);
			}
		}

		if (!$this->loadExternally)
		{
			// Merge any additional options in the XML definition.
			$options = array_merge(parent::getOptions(), $options);
		}

		return $options;
	}
}
