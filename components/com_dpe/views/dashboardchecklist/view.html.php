<?php

/**
 * @version    CVS: 1.0.0
 * @package    Com_Test123
 * @author     Parth Lawate <contact@techjoomla.com>
 * @copyright  2017 Parth Lawate
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */
// No direct access
defined('_JEXEC') or die;
use Joomla\CMS\MVC\View\HtmlView;

use Joomla\CMS\Factory;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Router\Route;

/**
 * View to edit
 *
 * @since  1.6
 */
class DpeViewDashboardchecklist extends HtmlView
{
	protected $state;

	protected $item;

	protected $form;

	protected $params;

	protected $clusterId;

	public $user;

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
		$app        = Factory::getApplication();
		$input      = Factory::getApplication()->input;
		$this->user = Factory::getUser();

		// DPE - Hack - while user checking Assigned Lesson he should redirect first to login page.
		if (!$this->user->id)
		{
			$msg = Text::_('COM_TJLMS_MESSAGE_LOGIN_FIRST');

			// Get current url.
			$current = Uri::getInstance()->toString();
			$url     = base64_encode($current);
			$app->redirect(Route::_('index.php?option=com_users&view=login&return=' . $url, false), $msg);
		}

		$this->state = $this->get('State');

		if($input->get('filter')['cluster_id'])
		{
			$clusterId = $input->get('filter')['cluster_id'];
			$this->state->set('filter.cluster_id',$clusterId);
		}

		    JLoader::import("/components/com_cluster/includes/cluster", JPATH_ADMINISTRATOR);
			$clusterUserModel = ClusterFactory::model('ClusterUser', array('ignore_request' => true));
			$clusters         = $clusterUserModel->getUsersClusters($this->user->id);

			if(count($clusters)=='1')
			{
				$app->redirect(Route::_(Uri::root() .'index.php/component/dpe/dashboard', false), );
			}

		// Check cluster filter set
		$this->clusterId = $this->state->get('filter.cluster_id');

		if (!$this->clusterId)
		{
			
			if(count($clusters)=='1'){

				
					// Reset the array index of cluster array
					$clusters = array_values($clusters);
					$this->clusterId = $clusters[0]->cluster_id;
					$this->state->set('filter.cluster_id', $this->clusterId);
			}


			if (!empty($clusters) && empty($this->state->get('filter.tags')[0]) )
			{  

				// Todo instead of using cluster directly get cluster ids from tj_su_user RBACL by passing parameters like action, user_id
				if (!$this->user->authorise('core.manageall', 'com_cluster'))
				{
					foreach ($clusters as $key => $cluster)
					{
						// Remove cluster if user doesn't have permission for that cluster ( User role is staff)
						if (!RBACL::check($this->user->id, 'com_cluster', 'core.viewChecklist', 'com_multiagency', $cluster->cluster_id))
						{
							unset($clusters[$key]);
						}
					}
				}

				if (!empty($clusters))
				{
					// Reset the array index of cluster array
					$clusters = array_values($clusters);
				foreach($clusters as $key => $cluster)
				{
				 $clusterList[$key] = $cluster->cluster_id;
				}
					$this->clusterId = $clusterList;
				}
			}
		}

		$this->params          = $app->getParams('com_multiagency');

		if (!empty($this->item))
		{
			$this->form = $this->get('Form');
		}

		// Check for errors.
		if (count($errors = $this->get('Errors')))
		{
			$app->enqueueMessage(implode("\n", $errors));

			return $app->setHeader('status', 403, true);
		}

		if ($this->_layout == 'edit')
		{
			$authorised = $this->user->authorise('core.create', 'com_dpe');

			if ($authorised !== true)
			{
				$app->enqueueMessage(Text::_('JERROR_ALERTNOAUTHOR'), 'error');

				return $app->setHeader('status', 403, true);
			}
		}

		// Get com_subusers component status
		$subUserExist = ComponentHelper::getComponent('com_subusers', true)->enabled;


		// Check user have permission to edit record of assigned cluster
		if ($subUserExist && !$this->user->authorise('core.manageall', 'com_cluster') )
		{
			JLoader::import("/components/com_subusers/includes/rbacl", JPATH_ADMINISTRATOR);

			/*
			 *  @Todo migration for client 'com_cluster' in dpe
			 *  Com_dpe - Hack - start
			 *  Original Code : RBACL::authorise($user->id, 'com_cluster', 'core.manage', 'com_cluster', $this->clusterId)
			 */

			// Check user has permission for mentioned cluster
			if (!RBACL::check($this->user->id, 'com_cluster', 'core.viewChecklist', 'com_multiagency', $input->get('filter')['cluster_id']))
			{
				$app->enqueueMessage(Text::_('JERROR_ALERTNOAUTHOR'), 'error');
				$app->setHeader('status', 403, true);

				return;
			}
		}

		$this->_prepareDocument();

		parent::display($tpl);
	}

	/**
	 * Prepares the document
	 *
	 * @return void
	 *
	 * @throws Exception
	 */
	protected function _prepareDocument()
	{
		$app   = Factory::getApplication();
		$menus = $app->getMenu();
		$title = null;

		// Because the application sets a default page title,
		// We need to get it from the menu item itself
		$menu = $menus->getActive();

		if ($menu)
		{
			$this->params->def('page_heading', $this->params->get('page_title', $menu->title));
		}
		else
		{
			$this->params->def('page_heading', Text::_('COM_TEST123_DEFAULT_PAGE_TITLE'));
		}

		$title = $this->params->get('page_title', '');

		if (empty($title))
		{
			$title = $app->get('sitename');
		}
		elseif ($app->get('sitename_pagetitles', 0) == 1)
		{
			$title = Text::sprintf('JPAGETITLE', $app->get('sitename'), $title);
		}
		elseif ($app->get('sitename_pagetitles', 0) == 2)
		{
			$title = Text::sprintf('JPAGETITLE', $title, $app->get('sitename'));
		}

		$this->document->setTitle($title);

		if ($this->params->get('menu-meta_description'))
		{
			$this->document->setDescription($this->params->get('menu-meta_description'));
		}

		if ($this->params->get('menu-meta_keywords'))
		{
			$this->document->setMetadata('keywords', $this->params->get('menu-meta_keywords'));
		}

		if ($this->params->get('robots'))
		{
			$this->document->setMetadata('robots', $this->params->get('robots'));
		}
	}
}
