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

use Joomla\CMS\MVC\View\HtmlView;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Component\ComponentHelper;

/**
 * Todo view
 *
 * @since  __DEPLOY_VERSION__
 */
class JlikeViewRecommendationForm extends HtmlView
{
	protected $state;

	protected $item;

	protected $form;

	protected $create;

	protected $params;

	public $isAgencyEnabled = false;

	protected $comJlike = 'com_jlike';

	/**
	 * Display the view
	 *
	 * @param   string  $tpl  The name of the template file to parse; automatically searches through the template paths.
	 *
	 * @return  mixed  A string if successful, otherwise a Error object.
	 *
	 * @since  __DEPLOY_VERSION__
	 */
	public function display($tpl = null)
	{
		$app          = Factory::getApplication();
		$this->input  = $app->input;
		$this->user   = Factory::getUser();
		$this->item   = $this->get('Data');
		$this->params = ComponentHelper::getParams($this->comJlike);
		$this->create = $this->user->authorise('core.create', 'com_jlike');

		if (!$this->create)
		{
			throw new Exception(Text::_('JERROR_ALERTNOAUTHOR'), 403);
		}

		// DPE Hack for RBACL
		if (!$this->user->authorise('core.manageall', 'com_cluster'))
		{
			$menu = $app->getMenu();
			$menuDefaultId = $menu->getDefault();

			if ($this->item->id && $this->item->id != $menuDefaultId->query['id'])
			{
				$manageNotifications = RBACL::check($this->user->id, 'com_cluster', 'core.manageNotificationManager', 'com_jlike', $this->item->clusters);
				$manageOwnNotifications = RBACL::check($this->user->id, 'com_cluster', 'core.own.manageNotifications', 'com_jlike', $this->item->clusters);

				if (!$manageNotifications && !$manageOwnNotifications)
				{
					$app->enqueueMessage(Text::_('JERROR_ALERTNOAUTHOR'), 'error');
					$app->setHeader('status', 403, true);

					return;
				}

				$ownTodoCondition = ($this->item->assigned_by != $this->user->id || $this->item->assigned_to != $this->user->id);

				if (!$manageNotifications && ($manageOwnNotifications && $ownTodoCondition) && $this->item->assigned_to != $this->user->id)
				{
					$app->enqueueMessage(Text::_('JERROR_ALERTNOAUTHOR'), 'error');
					$app->setHeader('status', 403, true);

					return;
				}
			}
		}

		if (ComponentHelper::isEnabled($this->comJlike) && $this->params->get('enable_multiagency'))
		{

			$this->isAgencyEnabled = true;
		}

		$this->state = $this->get('State');
		$this->form  = $this->get('Form');
		

		// Check for errors.
		if (count($errors = $this->get('Errors')))
		{
			throw new Exception(implode("\n", $errors), 500);
		}

		parent::display($tpl);
	}
}
