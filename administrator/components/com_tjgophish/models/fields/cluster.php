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

use Joomla\CMS\Factory;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Form\FormHelper;

FormHelper::loadFieldClass('list');

/**
 * Supports an HTML select list of allocated cluster
 *
 * @since  1.0.0
 */

class JFormFieldCluster extends JFormFieldList
{
	/**
	 * The form field type.
	 *
	 * @var    string
	 * @since  1.0.0
	 */
	protected $type = 'Cluster';

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
	 * Method to get a list of options for cluster field.
	 *
	 * @return array An array of JHtml options.
	 *
	 * @since   1.0.0
	 */
	protected function getOptions()
	{
		$app = Factory::getApplication();
		$user = Factory::getUser();

		$options = array();

		if (!$user->id)
		{
			return $options;
		}

		/**
		 *  DPE - hack - start
		 *  Initialize array to store dropdown options
		 */
		if (($app->input->get('view') == 'items' && $app->input->get('layout') !== 'importitems')
			|| ($app->input->get('view') == 'rsticketspro') || ($app->input->get('view') == 'schools'))
		{
			// Initialize array option
			$options[] = HTMLHelper::_('select.option', 'all', JText::_('COM_MULTIAGENCY_SELECT_ALL_AGENCY')
			);

			if ($user->authorise('core.admin'))
			{
				$options[] = HTMLHelper::_(
				'select.option', 'none', JText::_('COM_MULTIAGENCY_SELECT_NONE')
				);
			}
		}
		else
		{
			$options[] = HTMLHelper::_('select.option', "", Text::sprintf('COM_TJFIELDS_OWNERSHIP_CLUSTER', Text::_('COM_MULTIAGENCY_ORGANISATION')));
		}

		// Get com_subusers component status
		$clusterExist = ComponentHelper::getComponent('com_cluster', true)->enabled;

		if (!$clusterExist)
		{
			return $options;
		}

		JLoader::import("/components/com_cluster/includes/cluster", JPATH_ADMINISTRATOR);
		$clusterUserModel = ClusterFactory::model('ClusterUser', array('ignore_request' => true));
		$clusters = $clusterUserModel->getUsersClusters($user->id);

		$IsRsticket = false;

		if ($app->input->get('view') == 'submit' && $app->input->get('option') == 'com_rsticketspro' || $app->input->get('view') == 'rsticketspro')
		{
			$IsRsticket = true;
		}

		$createAction = true;

		// Create option for each cluster
		foreach ($clusters as $cluster)
		{
			/*
			 * This field used only on Campaign and group form 
			 * if user having core.createCampaign or core.creategroup permission
			 * then own org available in dropdown
			 */
			if (!$user->authorise('core.admin') && !$user->authorise('core.manageall', 'com_cluster'))
			{
				$createCampaign = RBACL::check($user->id, 'com_cluster', 'core.createCampaign', 'com_tjgophish', $cluster->cluster_id);
				$createGroup    = RBACL::check($user->id, 'com_cluster', 'core.createGroup', 'com_tjgophish', $cluster->cluster_id);

				$createAction	= ($createCampaign || $createGroup) ? true : false;
			}

			if ($createAction)
			{
				$options[] = HTMLHelper::_('select.option', $cluster->cluster_id, trim($cluster->name));
			}
		}

		// Comment unwanted code - No need to unset All option for rstickets view @todo-> need discussion

		/*
		if (count($options) == 2 && $app->input->get('view') != 'rsticketspro' && $app->input->get('view') != 'itemform')
		{
			unset($options[0]);
		}
		*/

		// DPE - hack - end

		if (!$this->loadExternally)
		{
			// Merge any additional options in the XML definition.
			$options = array_merge(parent::getOptions(), $options);
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
		$clusterId = Factory::getApplication()->input->getInt('cluster_id', 0);

		if (!empty($clusterId))
		{
			$this->value = $clusterId;
		}

		return parent::getInput();
	}
}
