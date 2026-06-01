<?php
/**
 * @package     JLike
 * @subpackage  com_jlike
 *
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (C) 2009 - 2019 Techjoomla. All rights reserved.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 */

defined('_JEXEC') or die();

use Joomla\CMS\Factory;
use Joomla\CMS\Filter\InputFilter;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\AdminController;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\CMS\Response\JsonResponse;
use Joomla\CMS\Session\Session;
use Joomla\CMS\Table\Table;

/**
 * JLike extended todos Controller
 *
 * @since  __DEPLOY_VERSION__
 */
class JLikeControllerExtendedTodos extends AdminController
{
	/**
	 * This function display the default assignment list view
	 *
	 * @param   boolean  $cachable   If true, the view output will be cached
	 * @param   array    $urlparams  An array of safe URL parameters and their variable types, for valid values see {@link \JFilterInput::clean()}.
	 *
	 * @return  string   html to build a assignment list view
	 *
	 * @since   __DEPLOY__VERSION__
	 */
	public function display($cachable = false, $urlparams = array())
	{
		$app = Factory::getApplication();

		if (!Session::checkToken())
		{
			$app->enqueueMessage(Text::_('JINVALID_TOKEN'), 'error');
			echo new JsonResponse(null, null, true);
			$app->close();
		}

		$input = $app->input;
		$user       = Factory::getUser();

		if (!$user->authorise('core.manageall', 'com_cluster'))
		{
			JLoader::import("/components/com_subusers/includes/rbacl", JPATH_ADMINISTRATOR);

			// Get cluster id associated with document
			Table::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_dpe/tables');
			$clustertableInstance = Table::getInstance('TjlmsClusterXref', 'DpeTable');
			$clustertableInstance->load(array('lesson_id' => $input->getInt('element_id')));

			$viewInteractions = RBACL::check($user->id, 'com_cluster', 'core.view.interactions', 'com_tjlms', $clustertableInstance->cluster_id);

			if (!$viewInteractions)
			{
				echo  new JsonResponse(null, Text::_("JERROR_ALERTNOAUTHOR"), true);
				$app->close();
			}
		}

		require_once JPATH_SITE . '/components/com_jlike/models/contentform.php';
		$model = BaseDatabaseModel::getInstance('contentform', 'JlikeModel', array('ignore_request' => true));
		$data = $input->getArray();
		$contentId = $model->getContentID($data);

		if (!empty($contentId))
		{
			$document = Factory::getDocument();
			$viewType = $document->getType();
			$viewName = $this->input->get('view', $this->default_view);
			$viewLayout = $this->input->get('layout', 'default', 'string');
			$view = $this->getView($viewName, $viewType, '', array('base_path' => $this->basePath, 'layout' => $viewLayout));

			$inputFilter = InputFilter::getInstance();
			$model = $this->getModel($viewName, '', array("ignore_request" => true));

			if (isset($data['filter']))
			{
				foreach ($data['filter'] as $key => $value)
				{
					$model->setState('filter.' . $key, $value);
				}
			}

			// Explicit set this
			$model->setState('filter.contentId', $contentId);
			$model->setState('list.limit', '15');

			if (isset($data['limitstart']))
			{
				$model->setState('list.start', $inputFilter->clean($data['limitstart'], 'int'));
			}

			$view->setModel($model, true);
			$view->document = $document;
			$view->display();
			$app->close();
		}

		echo  new JsonResponse(null, Text::_("COM_JLIKE_ASSIGNMENT_INVALID_CONTENT_ID"), true);
		$app->close();
	}

	/**
	 * This function loads the assignment items based on the filters
	 *
	 * @return  string   html to build a assignment list view
	 *
	 * @since   __DEPLOY__VERSION__
	 */
	public function loadMore()
	{
		$app = Factory::getApplication();

		if (!Session::checkToken())
		{
			$app->enqueueMessage(Text::_('JINVALID_TOKEN'), 'error');
			echo new JsonResponse(null, null, true);
			$app->close();
		}

		$document = Factory::getDocument();
		$viewType = $document->getType();
		$data = $app->input->getArray();

		if (empty($data['filter']['contentId']))
		{
			echo new JsonResponse('', Text::_("COM_JLIKE_ASSIGNMENT_INVALID_CONTENT_ID"), true);
			$app->close();
		}

		$user       = Factory::getUser();

		if (!$user->authorise('core.manageall', 'com_cluster'))
		{
			JLoader::import("/components/com_subusers/includes/rbacl", JPATH_ADMINISTRATOR);

			require_once JPATH_SITE . '/components/com_jlike/models/content.php';
			$model = BaseDatabaseModel::getInstance('content', 'JlikeModel', array('ignore_request' => true));
			$content = $model->getData($data['filter']['contentId']);

			// Get cluster id associated with document
			Table::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_dpe/tables');
			$clustertableInstance = Table::getInstance('TjlmsClusterXref', 'DpeTable');
			$clustertableInstance->load(array('lesson_id' => $content->element_id));

			$viewInteractions = RBACL::check($user->id, 'com_cluster', 'core.view.interactions', 'com_tjlms', $clustertableInstance->cluster_id);

			if (!$viewInteractions)
			{
				echo  new JsonResponse(null, Text::_("JERROR_ALERTNOAUTHOR"), true);
				$app->close();
			}
		}

		$viewName = $this->input->get('view', $this->default_view);
		$viewLayout = $this->input->get('layout', 'default', 'string');
		$view = $this->getView($viewName, $viewType, '', array('base_path' => $this->basePath, 'layout' => $viewLayout));

		$inputFilter = InputFilter::getInstance();
		$model = $this->getModel($viewName, '', array("ignore_request" => true));

		if (isset($data['filter']))
		{
			foreach ($data['filter'] as $key => $value)
			{
				$model->setState('filter.' . $key, $value);
			}
		}

		if (isset($data['limit']))
		{
			$model->setState('list.limit', $inputFilter->clean($data['limit'], 'int'));
		}

		if (isset($data['limitstart']))
		{
			$model->setState('list.start', $inputFilter->clean($data['limitstart'], 'int'));
		}

		$view->setModel($model, true);
		$view->document = $document;
		$view->loadMore();
		$app->close();
	}
}
