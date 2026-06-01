<?php
/**
 * @package     Jlike
 * @subpackage  com_jlike
 *
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (C) 2009 - 2021 Techjoomla. All rights reserved.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 */

// No direct access to this file
defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Model\AdminModel;
use Joomla\CMS\Component\ComponentHelper;

/**
 * Jlike Agency Model.
 *
 * @since  __DEPLOY_VERSION__
 */
class JlikeModelAgency extends AdminModel
{
	protected $comMultiAgency = 'com_multiagency';

	protected $comJlike       = 'com_jlike';

	public $params;

	public $user;

	public $manageOwn;

	public $manage;

	/**
	 * Constructor.
	 *
	 * @param   array  $config  An optional associative array of configuration settings.
	 *
	 * @since   __DEPLOY_VERSION__
	 */
	public function __construct($config = array())
	{
		$this->user      = Factory::getuser();
		$this->manageOwn = $this->user->authorise('core.manage.own.agency.user', $this->comMultiAgency);
		$this->manage    = $this->user->authorise('core.manage.all.agency.user', $this->comMultiAgency);
		$this->params    = ComponentHelper::getParams($this->comJlike);

		parent::__construct($config);
	}

	/**
	 * Abstract method for getting the form from the model.
	 *
	 * @param   array    $data      Data for the form.
	 * @param   boolean  $loadData  True if the form is to load its own data (default case), false if not.
	 *
	 * @return  \JForm|boolean  A \JForm object on success, false on failure
	 *
	 * @since   __DEPLOY_VERSION__
	 */
	public function getForm($data = array(), $loadData = true)
	{
		return;
	}

	/**
	 * Function to get users
	 *
	 * @param   integer  $clusterId  cluster id
	 *
	 * @return  Object  Users object
	 *
	 * @since  __DEPLOY_VERSION__
	 */
	public function getUsers($clusterId = null)
	{
		// If multiagency component enable and agency support config is true then execute following block
		if (ComponentHelper::isEnabled($this->comMultiAgency) && $this->params->get('enable_multiagency'))
		{
			if ($clusterId)
			{
				$db = Factory::getDBO();
				$query = $db->getQuery(true);
				// Dpe Hack
				$query->select('distinct(u.id), u.name, u.email');
				// Dpe Hack end
				$query->from($db->quoteName('#__users', 'u'));
				$query->where($db->qn('u.block') . ' = 0');

				$query->join('INNER', '#__tj_cluster_nodes AS cn ON cn.user_id = u.id');
				$query->join('INNER', $db->qn('#__tj_clusters', 'clusters') .
						' ON (' . $db->qn('clusters.id') . ' = ' . $db->qn('cn.cluster_id') .
						' AND ' . $db->qn('clusters.client') . " = 'com_multiagency' ) ");
				$query->join('INNER', $db->qn('#__tjmultiagency_multiagency', 'ml') .
						' ON (' . $db->qn('ml.id') . ' = ' . $db->qn('clusters.client_id') . ')');
				$query->where($db->qn('clusters.id') . ' = ' . (int) $clusterId);
				$query->order($db->escape('u.name' . ' ' . 'asc'));
				$db->setQuery($query);

				return $db->loadObjectList();
			}
		}
		elseif (!$this->params->get('enable_multiagency'))
		{
			$db = Factory::getDBO();
			$query = $db->getQuery(true);
			$query->select('distinct(u.id), u.name');
			$query->from($db->quoteName('#__users', 'u'));
			$query->where($db->qn('u.block') . ' = 0');
			$query->order($db->escape('u.name' . ' ' . 'asc'));
			$db->setQuery($query);

			return $db->loadObjectList();
		}
	}

	/**
	 * Function to check user agency
	 *
	 * @param   int  $clusterId  cluster id
	 *
	 * @return  integer|boolean
	 *
	 * @since  __DEPLOY_VERSION__
	 */
	public function validateUserAgency($clusterId)
	{
		// If user having multiagency manage all permission then return true
		if ($this->manage)
		{
			return true;
		}

		$db    = Factory::getDBO();
		$query = $db->getQuery(true);
		$query->select($db->quoteName('u.id'));
		$query->from($db->quoteName('#__users', 'u'));
		$query->join('INNER', $db->quoteName('#__tj_cluster_nodes', 'cn') . ' ON ' . $db->quoteName('cn.user_id') . '=' . $db->quoteName('u.id'));
		$query->join('INNER', $db->quoteName('#__tj_clusters', 'c') . ' ON ' . $db->quoteName('c.id') . '=' . $db->quoteName('cn.cluster_id'));
		$query->join('INNER', $db->quoteName('#__tjmultiagency_multiagency', 'ml') .
			' ON ' . $db->quoteName('ml.id') . ' = ' . $db->quoteName('c.client_id')
			);
		$query->where($db->qn('c.id') . ' = ' . (int) $clusterId);
		$query->where($db->qn('cn.user_id') . ' = ' . (int) $this->user->id);
		$db->setQuery($query);

		return $db->loadResult();
	}
}
