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
use Joomla\CMS\Helper\ContentHelper;
use Joomla\CMS\Language\Text;

use Joomla\CMS\MVC\View\HtmlView;
use Joomla\CMS\Factory;

/**
 * TJGOPHISH - Groups View
 *
 * @since  1.0.0
 */
class TjGoPhishViewGroups extends HtmlView
{
	protected $createCampaign;

	protected $deleteGroup;

	protected $createGroup;

	protected $comGophish = 'com_tjgophish';

	protected $comCluster = 'com_cluster';

	/**
	 * Display the groups list view
	 *
	 * @param   string  $tpl  The name of the template file to parse; automatically searches through the template paths.
	 *
	 * @return  void
	 */
	public function display($tpl = null)
	{
		// Get application
		$app  = Factory::getApplication();
		$user = Factory::getUser();

		// DPE hack to check the RBACL permission
		$this->createGroup    = RBACL::check($user->id, $this->comCluster, 'core.createGroup', $this->comGophish);

		if (!$user->authorise('core.manageall', $this->comCluster) && !$this->createGroup)
		{
			$app->enqueueMessage(Text::_('JERROR_ALERTNOAUTHOR'), 'error');

			return;
		}

		$this->createCampaign = RBACL::check($user->id, $this->comCluster, 'core.createCampaign', $this->comGophish);
		$this->deleteGroup    = RBACL::check($user->id, $this->comCluster, 'core.deleteGroup', $this->comGophish);

		if ($user->authorise('core.manageall', $this->comCluster))
		{
			$this->createGroup = true;
			$this->deleteGroup = true;
			$this->createCampaign = true;
		}

		// DPE hack end

		// Get data from the model
		$this->items         = $this->get('Items');
		$this->pagination    = $this->get('Pagination');
		$this->state         = $this->get('State');
		$this->filterForm    = $this->get('FilterForm');
		$this->activeFilters = $this->get('ActiveFilters');

		// What Access Permissions does this user have? What can (s)he do?
		$this->canDo = ContentHelper::getActions('com_tjgophish');

		// Check for errors.
		if (count($errors = $this->get('Errors')))
		{
			JError::raiseError(500, implode('<br />', $errors));

			return false;
		}

		// Display the template
		parent::display($tpl);

		// Set the document
		$this->setDocuments();
	}

	/**
	 * Method to set up the document properties
	 *
	 * @return void
	 */
	protected function setDocuments()
	{
		$document = Factory::getDocument();
		$document->setTitle(Text::_('COM_TJGOPHISH_MANAGE_GROUPS'));
	}
}
