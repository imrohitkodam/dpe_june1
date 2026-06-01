<?php
/**
 * @package    Com_Timelog
 * @author     Techjoomla <extensions@techjoomla.com>
 * @copyright  Copyright (C) 2009 - 2018 Techjoomla. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */
// No direct access
defined('_JEXEC') or die;
use Joomla\CMS\Form\Form;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView;

/**
 * View to edit
 *
 * @since  1.0.0
 */
class TimelogViewActivity extends HtmlView
{
	/**
	 * The model state
	 *
	 * @var  object
	 */
	protected $state;

	/**
	 * The active item
	 *
	 * @var  object
	 */
	protected $item;

	/**
	 * The Form object
	 *
	 * @var  Form
	 */
	protected $form;

	/**
	 * The model state
	 *
	 * @var  object|array
	 */
	protected $params;

	/**
	 * Logged in User
	 *
	 * @var  Object
	 */
	protected $user;

	/**
	 * @var  boolean
	 *
	 * @since __DEPLOY_VERSION__
	 */
	protected $canEdit;

	/**
	 * @var  boolean
	 *
	 * @since __DEPLOY_VERSION__
	 */
	protected $canEditOwn;

	/**
	 * @var  boolean
	 *
	 * @since __DEPLOY_VERSION__
	 */
	protected $canDelete;

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
		$app  = Factory::getApplication();
		$this->user = Factory::getUser();

		$this->state  = $this->get('State');
		$this->item   = $this->get('Item');
		$this->params = $app->getParams('com_timelog');

		if (!empty($this->item))
		{
			$this->form = $this->get('Form');
		}

		// Check for errors.
		if (count($errors = $this->get('Errors')))
		{
			throw new Exception(implode("\n", $errors));
		}

		if ($this->_layout == 'edit')
		{
			$authorised = $this->user->authorise('core.create', 'com_timelog');

			if ($authorised !== true)
			{
				throw new Exception(Text::_('JERROR_ALERTNOAUTHOR'));
			}
		}

		// Get ACL actions
		$this->canEdit    = $this->user->authorise('core.edit', 'com_timelog');
		$this->canEditOwn = $this->user->authorise('core.edit.own', 'com_timelog');
		$this->canDelete = $this->user->authorise('core.delete', 'com_timelog.activity.' . $this->item->id);

		parent::display($tpl);
	}
}
