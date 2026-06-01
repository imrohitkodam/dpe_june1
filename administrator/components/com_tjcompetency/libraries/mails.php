<?php
/**
 * @package     TjCompetency
 * @subpackage  com_tjcompetency
 *
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (C) 2009 - 2021 Techjoomla. All rights reserved.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 */

defined('_JEXEC') or die;
jimport('techjoomla.tjnotifications.tjnotifications');

use Joomla\CMS\Factory;
use Joomla\CMS\User\User;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\Registry\Registry;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Router\Route;

/**
 * Class TjCompetencyMails
 *
 * @since  1.0.0
 */
class TjCompetencyMails
{
	protected $params;

	protected $siteConfig;

	protected $sitename;

	protected $siteadminname;

	protected $user;

	protected $client;

	protected $tjnotifications;

	protected $siteinfo;

	/**
	 * Method acts as a consturctor
	 *
	 * @since   1.0.0
	 */
	public function __construct()
	{
		$this->params             = ComponentHelper::getParams('com_tjcompetency');
		$this->siteConfig         = Factory::getConfig();
		$this->sitename           = $this->siteConfig->get('sitename');
		$this->siteadminname      = $this->siteConfig->get('fromname');
		$this->user               = Factory::getUser();
		$this->client             = TjCompetency::getClient();
		$this->tjnotifications    = new Tjnotifications;

		$this->siteinfo           = new stdClass;
		$this->siteinfo->sitename = $this->sitename;
	}

	/**
	 * Get all admin users
	 *
	 * @return  array
	 *
	 * @since	1.0.0
	 */
	public function getAdminRecipients()
	{
		// Get all admin users
		$db    = Factory::getDBO();
		$query = $db->getQuery(true);
		$query->select('id');
		$query->from($db->quoteName('#__users'));
		$query->where($db->quoteName('sendEmail') . '= 1');
		$db->setQuery($query);
		$adminUsers = $db->loadObjectList();

		$adminRecipients = array();

		if (!empty($adminUsers))
		{
			foreach ($adminUsers as $adminUser)
			{
				$adminRecipients[]                = Factory::getUser($adminUser->id);
				$adminRecipients['email']['to'][] = Factory::getUser($adminUser->id)->email;
			}
		}

		return $adminRecipients;
	}

	/**
	 * Trigger emails when the skill is awarded
	 *
	 * @param   object  $item   item object
	 *
	 * @return  void
	 *
	 * @since	1.0.0
	 */
	public function onSkillAfterAwarded($item)
	{
		$adminRecipients = $this->getAdminRecipients();
		$userEmailArray  = array();

		$model = TjCompetency::model("SkillContentUserMaps", array('ignore_request' => true));
		$model->setState('filter.id', $item->id);
		$items = $model->getItems();

		$item = $items[0];
		$item->admin_link = $this->getAdminLinkForTheContent($item);

		if ($item->user_id)
		{
			$user             = Factory::getUser($item->user_id);
			$userEmailArray[] = $user->email;
		}

		$recipients = array('email' => array('to' => $userEmailArray));

		$adminkey = "skillAwardedMailToAdmin";
		$userkey  = "skillAwardedMailToUser";

		$siteInfo           = new stdClass;
		$siteInfo->sitename = $this->sitename;

		$replacements         = new stdClass;
		$replacements->info   = $this->siteinfo;
		$replacements->record = $item;
		$replacements->user   = $user;

		$options = new Registry;
		$options->set('record', $item);

		if (!empty($adminRecipients))
		{
			// Mail to site admin
			$this->tjnotifications->send($this->client, $adminkey, $adminRecipients, $replacements, $options);
		}

		if (!empty($recipients))
		{
			// Mail to User
			$this->tjnotifications->send($this->client, $userkey, $recipients, $replacements, $options);
		}
	}

	/**
	 * Get Admin link for the content
	 *
	 * @param   object  $obj item object
	 *
	 * @return  string
	 *
	 * @since	1.0.0
	 */
	public function getAdminLinkForTheContent($obj)
	{
		$app      = Factory::getApplication();
		$linkMode = $app->get('force_ssl', 0) >= 1 ? Route::TLS_FORCE : Route::TLS_IGNORE;

		return Route::link(
					"administrator",
					'index.php?option=com_tjcompetency&view=skillcontentusermaps&filter[id]=' . $obj->id,
					false,
					$linkMode,
					true
				);
	}

	/**
	 * Trigger emails when the skill is pending for approval
	 *
	 * @param   object  $item   item object
	 *
	 * @return  void
	 *
	 * @since	1.0.0
	 */
	public function onSkillAfterPendingApproval($item)
	{
		$adminRecipients = $this->getAdminRecipients();
		$userEmailArray  = array();

		$model = TjCompetency::model("SkillContentUserMaps", array('ignore_request' => true));
		$model->setState('filter.id', $item->id);
		$items = $model->getItems();

		$item = $items[0];
		$item->admin_link = $this->getAdminLinkForTheContent($item);

		if ($item->user_id)
		{
			$user             = Factory::getUser($item->user_id);
			$userEmailArray[] = $user->email;
		}

		$recipients = array('email' => array('to' => $userEmailArray));

		$adminkey = "skillPendingApprovalMailToAdmin";
		$userkey  = "skillPendingApprovalMailToUser";

		$siteInfo           = new stdClass;
		$siteInfo->sitename = $this->sitename;

		$replacements         = new stdClass;
		$replacements->info   = $this->siteinfo;
		$replacements->record = $item;
		$replacements->user   = $user;

		$options = new Registry;
		$options->set('record', $item);

		if (!empty($adminRecipients))
		{
			// Mail to site admin
			$this->tjnotifications->send($this->client, $adminkey, $adminRecipients, $replacements, $options);
		}

		if (!empty($recipients))
		{
			// Mail to User
			$this->tjnotifications->send($this->client, $userkey, $recipients, $replacements, $options);
		}
	}
}
