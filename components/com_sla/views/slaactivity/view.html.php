<?php
/**
 * @package    Sla
 * @author     Techjoomla <extensions@techjoomla.com>
 * @copyright  Copyright (C) 2009 - 2019 Techjoomla. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */
// No direct access
defined('_JEXEC') or die;
use Joomla\CMS\Object\CMSObject;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\View\HtmlView;
use Joomla\CMS\Component\ComponentHelper;

/**
 * View to edit
 *
 * @since  1.0.0
 */
class SlaViewSlaActivity extends HtmlView
{
	protected $state;

	protected $item;

	protected $form;

	/**
	 * Logged in User
	 *
	 * @var  CMSObject
	 */
	public $user;

	public $input;

	protected $canCreateSlaActivity;

	/**
	 * Display the view
	 *
	 * @param   string  $tpl  Template name
	 *
	 * @return void
	 *
	 * @throws Exception
	 */
	public function display($tpl = null)
	{
		$app         = Factory::getApplication();
		$this->input = $app->input;
		$this->user  = Factory::getUser();

		if (!$this->user->id)
		{
			$msg = Text::_('COM_SLA_MESSAGE_LOGIN_FIRST');
			$uri = $this->input->server->get('REQUEST_URI', '', 'STRING');
			$url = base64_encode($uri);
			$app->redirect(Route::_('index.php?option=com_users&view=login&return=' . $url, false), $msg);
		}

		$this->state = $this->get('State');
		$this->item  = $this->get('Item');
		$this->form  = $this->get('Form');

		$this->canCreateSlaActivity = false;

		if (!empty($this->item->id))
		{
			JLoader::import('components.com_sla.includes.sla', JPATH_ADMINISTRATOR);

			// Get SLA details
			$slaClusterXrefTable = SlaFactory::table("slaclusterxrefs");
			$licenceId           = $app->input->get('licence_id', 0, 'INT'); 

			if (!empty($this->item->license_id))
			{
				$slaClusterXrefTable->load(array('license_id' => $this->item->license_id));
			}
			else
			{
				$slaClusterXrefTable->load(array('license_id' => $licenceId));
			}

			if (!$this->item->cluster_id)
			{
					throw new Exception(Text::_('JERROR_ALERTNOAUTHOR'), 403);
			}

			$clusterId = $this->item->cluster_id;

			if (!$this->user->authorise('core.manageall', 'com_cluster'))
			{
				if (!$clusterId)
				{
					throw new Exception(Text::_('JERROR_ALERTNOAUTHOR'), 403);
				}

				$this->canCreateSlaActivity = RBACL::check($this->user->id, 'com_cluster', 'core.create.activity', 'com_sla', $clusterId);
			}
		}

		$params                   = ComponentHelper::getParams('com_multiagency');
		$externalLeadConsultant   = (int) $params->get('multiagency_leadconsultant_group');

		if (in_array($externalLeadConsultant, $this->user->groups))
		{
			$this->canCreateSlaActivity = true;
		}

		if ($this->user->authorise('core.manageall', 'com_cluster'))
		{
			$this->canCreateSlaActivity = true;
		}

		if (!$this->canCreateSlaActivity)
		{
			throw new Exception(Text::_('JERROR_ALERTNOAUTHOR'), 403);
		}

		// Check for errors.
		if (count($errors = $this->get('Errors')))
		{
			throw new Exception(implode("\n", $errors), 500);
		}

		parent::display($tpl);
	}
}
