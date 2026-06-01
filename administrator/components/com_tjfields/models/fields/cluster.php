<?php
/**
 * @package    Tjfields
 * @author     Techjoomla <extensions@techjoomla.com>
 * @copyright  Copyright (c) 2009-2019 TechJoomla. All rights reserved.
 * @license    GNU General Public License version 2 or later.
 */

defined('JPATH_BASE') or die;

use Joomla\CMS\Form\FormHelper;
use Joomla\CMS\Table\Table;
use Joomla\CMS\Factory;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;


FormHelper::loadFieldClass('list');

/**
 * Supports an HTML select list of allocated cluster
 *
 * @since  __DEPLOY_VERSION__
 */

class JFormFieldCluster extends JFormFieldList
{
	/**
	 * The form field type.
	 *
	 * @var    string
	 * @since  __DEPLOY_VERSION__
	 */
	protected $type = 'Cluster';

	/**
	 * Fiedd to decide if options are being loaded externally and from xml
	 *
	 * @var		integer
	 * @since	__DEPLOY_VERSION__
	 */
	protected $loadExternally = 0;

	/**
	 * The form field value.
	 *
	 * @var    mixed
	 * @since  __DEPLOY_VERSION__
	 */
	protected $value = '';

	/**
	 * Method to attach a JForm object to the field.
	 *
	 * @param   SimpleXMLElement  $element  The SimpleXMLElement object representing the `<field>` tag for the form field object.
	 * @param   mixed             $value    The form field value to validate.
	 * @param   string            $group    The field name group control value. This acts as as an array container for the field.
	 *                                      For example if the field has name="foo" and the group value is set to "bar" then the
	 *                                      full field name would end up being "bar[foo]".
	 *
	 * @return  boolean  True on success.
	 *
	 * @see     JFormField::setup()
	 * @since   DEPLOY_VERSION
	 */
	public function setup(SimpleXMLElement $element, $value, $group = null)
	{
		$return = parent::setup($element, $value, $group);

		// If the field is required and we have only one option to select then dont need to how the option
		if ($this->required)
		{
			$optionCount = 0;
			$optionValue = "";

			foreach ($this->options as $option)
			{
				if ($option->value)
				{
					$optionValue = $option->value;
					$optionCount++;
				}
			}

			if ($optionCount == 1)
			{
				//$this->hidden = true;
				$this->value = $optionValue;
				$this->default = $optionValue;

				// Render the field as hidden if only one option to select
				//echo "<input type='hidden' name='" . $this->name . "' id='" . $this->id ."' value='" . $this->value . "' />";
			}
		}

		return $return;
	}
	

	/**
	 * Method to get a list of options for cluster field.
	 *
	 * @return array An array of JHtml options.
	 *
	 * @since   __DEPLOY_VERSION__
	 */
	protected function getOptions()
	{
		$app     = Factory::getApplication()->input;
		$user    = Factory::getUser();
		$options = array();

		if (!$user->id)
		{
			return $options;
		}

		$view      = $app->get('view');
		$component = $app->get('option');

		/**
		 *  DPE - hack - start
		 *  Initialize array to store dropdown options
		 */
		if (($view == 'items' && $app->get('layout') !== 'importitems') || ($view == 'rsticketspro') || ($view == 'schools'))
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
		elseif ($view == 'users' && $app->input->get('layout') != 'import')
		{
			// This code is using for DPE admin on users list view to load 'All' and 'None' option in cluster dropdown

			if ($user->authorise('core.manageall', 'com_cluster'))
			{
				$options[] = HTMLHelper::_('select.option', 'all', Text::_('COM_MULTIAGENCY_SELECT_ALL_AGENCY')
				);

				$options[] = HTMLHelper::_(
					'select.option', 'none', Text::_('COM_MULTIAGENCY_SELECT_NONE')
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

		JLoader::import("/components/com_subusers/includes/rbacl", JPATH_ADMINISTRATOR);
		JLoader::import("/components/com_cluster/includes/cluster", JPATH_ADMINISTRATOR);
		$clusterUserModel = ClusterFactory::model('ClusterUser', array('ignore_request' => true));
		$clusters = $clusterUserModel->getUsersClusters($user->id);
		$usersClusters = array();
		// Get UCM type ID
		$client = "com_tjucm." . str_replace("_clusterclusterid]", "", str_replace("jform[com_tjucm_", "", $this->name));
		JLoader::import('components.com_tjucm.tables.type', JPATH_ADMINISTRATOR);
		$typeTable = Table::getInstance('Type', 'TjucmTable', array('dbo', Factory::getDbo()));
		$typeTable->load(array("unique_identifier" => $client));
		
		$IsRsticket = false;

		if ($view == 'submit' && $component == 'com_rsticketspro' || $view == 'rsticketspro')
		{
			$IsRsticket    = true;
		}

		$manageCluster = true;
		$assignedUsers = array();
		$contentId     = $app->getInt('id') ? $app->getInt('id') : null;
		$client        = $app->get('client');
		$tjucm         = 'com_tjucm';
		$dpeAdmin      = $user->authorise('core.manageall', 'com_cluster');
		$orgAdmin      = ComponentHelper::getParams('com_multiagency')->get('school_admin_role_id');

		if (!$dpeAdmin)
		{
			if ($component == $tjucm || ($component === "com_dpe" && ($view === "ropform" || $view === "roplist") ))
			{
				if ($client)
				{
					Table::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_tjucm/tables');
					$tjUcmTypeTable = Table::getInstance('Type', 'TjucmTable');
					$tjUcmTypeTable->load(array('unique_identifier' => $client));
					$ucmTypeId = $tjUcmTypeTable->id;
				}
				else
				{
					JModelLegacy::addIncludePath(JPATH_SITE . '/components/com_tjucm/models/itemform.php');
					$itemFormModel = JModelLegacy::getInstance('ItemForm', 'TjucmModel');
					$ucmTypeId = $itemFormModel->getState('ucmType.id');

					Table::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_tjucm/tables');
					$tjUcmTypeTable = Table::getInstance('Type', 'TjucmTable');
					$tjUcmTypeTable->load(array('id' => $ucmTypeId));

					if (property_exists($tjUcmTypeTable, 'unique_identifier'))
					{
						$client = $tjUcmTypeTable->unique_identifier;
					}
				}
			}
		}

		// Create oprion for each cluster
		foreach ($clusters as $cluster)
		{
			// If user is staff of school then do not show the school in dropdown except ticket submit view
			if (!$user->authorise('core.admin') && !$dpeAdmin && !$IsRsticket)
			{
				if ($component == $tjucm || ($component === "com_dpe" && ($view === "ropform" || $view === "roplist")))
				{
					if ($view == 'documents' || $view == 'document')
					{
						$manageCluster = RBACL::check($user->id, 'com_cluster', 'document.generate', 'com_multiagency', $cluster->cluster_id);
					}
					else
					{
						$manageCluster = RBACL::check($user->id, 'com_cluster', 'core.manageCluster.' . $ucmTypeId, $tjucm, $cluster->cluster_id);
					}
				}
				elseif ($component == 'com_tjlms')
				{
					$manageCluster = RBACL::check($user->id, 'com_cluster', 'core.compliancemanagerManageCluster', 'com_tjlms', $cluster->cluster_id);
				}
				elseif($component == 'com_tjreports')
				{
					// This action is added to core role of admin, manager, External Lead Consultant
					$manageCluster = RBACL::check($user->id, 'com_cluster', 'view.reports', 'com_tjreports', $cluster->cluster_id);
				}
				elseif($component === "com_dpe" && $view === "dashboard")
				{
					$manageCluster = RBACL::check($user->id, 'com_cluster', 'core.manageClusterChecklist', $tjucm, $cluster->cluster_id);
				}
				elseif($component === "com_multiagency" && $view === "users")
				{
					/*
					 This block is executing on users view for org admin
					Here in cluster dropdown system will load orgs where user is org admin
					*/

					$coreRoleId = RBACL::getCoreRoleByUser($user->id, 'com_cluster', $cluster->cluster_id);

					$isOrgAdmin = false;

					if (in_array($orgAdmin, $coreRoleId))
					{
						$isOrgAdmin = true;
					}

					$manageCluster = $isOrgAdmin;
				}
				elseif ($component === "com_jlike" && $view === "recommendationform")
				{
					// Allow to add notification for org where user having following permission
					$manageCluster = false;
					$isNotificationAllowed    = RBACL::check($user->id, 'com_cluster', 'core.manageNotificationManager	', 'com_jlike', $cluster->cluster_id);
					$staffNotificationAllowed = RBACL::check($user->id, 'com_cluster', 'core.own.manageNotifications', 'com_jlike', $cluster->cluster_id);

					if ($isNotificationAllowed)
					{
						$manageCluster = true;
					}
					elseif ($staffNotificationAllowed)
					{
						$manageCluster = true;
					}
				}

				// If user is staff then check he is available in assignee
				if (!$manageCluster && ($contentId || $client))
				{
					$mainHelper = JPATH_SITE . '/components/com_dpe/helpers/main.php';
					JLoader::register('DpeMainHelper', $mainHelper);

					$dpeMainHelper = new DpeMainHelper;

					if ($contentId)
					{
						$assignedUsers = $dpeMainHelper->getFieldValues($user->id, $contentId, null, $cluster->cluster_id);
					}
					elseif ($client)
					{
						$assignedUsers = $dpeMainHelper->getFieldValues($user->id, null, $client, $cluster->cluster_id);
					}

					if ($assignedUsers)
					{
						// If user is only assignee of record then don't show org on add ucm form
						if ($view === 'itemform' && empty($contentId))
						{
							$manageCluster = false;
						}
						else
						{
							$manageCluster = true;
						}
					}
				}
			}

			if ($manageCluster)
			{
				$options[] = HTMLHelper::_('select.option', $cluster->cluster_id, trim($cluster->name));
			}
		}

		// No need to unset All option for rstickets view @todo-> need discussion
		if ((count($options) == 2 && $view != 'rsticketspro' && $view != 'itemform' && $view != 'users') || $app->get('iscopy'))
		{
			unset($options[0]);
		}

		// DPE - hack - end

		if (!$this->loadExternally)
		{
			// Merge any additional options in the XML definition.
			$options = array_merge(parent::getOptions(), $options);
		}

		// If no clusters in dropdown then don't show all option
		if (count($options) == 1 && $options[0]->value === "all")
		{
			$options = array();
		}

		return $options;
	}

	/**
	 * Method to get a list of options for a list input externally and not from xml.
	 *
	 * @return	array	An array of JHtml options.
	 *
	 * @since   __DEPLOY_VERSION__
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
	 * @since   __DEPLOY_VERSION__
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
