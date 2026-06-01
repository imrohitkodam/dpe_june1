<?php
/**
 * @package     JLike
 * @subpackage  COM_JLIKE
 *
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (C) 2009 - 2020 Techjoomla. All rights reserved.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 */

// No direct access
defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Table\Table;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;

JLoader::import('content', JPATH_ADMINISTRATOR . '/components/com_jlike/tables');

jimport('techjoomla.common');

/**
 * Interaction view class
 *
 * @since  1.0.0
 */
class JlikeViewInteraction extends HtmlView
{
	protected $state;

	protected $item;

	protected $form;

	protected $lesson_id;

	protected $todo_id;

	protected $interactionData;

	protected $contentInteractionData;

	protected $contentInteractionDataObj;

	public  $jlikeTjlmslessonPlugin;

	/**
	 * Execute and display a template script.
	 *
	 * @param   string  $tpl  The name of the template file to parse; automatically searches through the template paths.
	 *
	 * @return  mixed  A string if successful, otherwise an Error object.
	 *
	 * @since   1.0.0
	 */
	public function display($tpl = null)
	{
		$app             = Factory::getApplication();
		$user            = Factory::getUser();
		$this->lesson_id = $app->input->get('lesson_id', '0', 'INT');
		$element         = 'com_tjlms.lesson';

		// Load Leasson regarding content data
		$jlikeModelContent = Table::getInstance('content', 'JlikeTable', array());
		$jlikeModelContent->load(array('element_id' => $this->lesson_id, 'element' => $element));
		$this->contentInteractionData    = $jlikeModelContent->params;
		$this->contentInteractionDataObj = '';

		if (!empty($this->contentInteractionData))
		{
			$this->contentInteractionDataObj = (array) json_decode($this->contentInteractionData);
		}

		BaseDatabaseModel::addIncludePath(JPATH_SITE . '/components/com_jlike/models');
		$JLikeRecommendationModel = BaseDatabaseModel::getInstance('Recommendations', 'JLikeModel');
		$JLikeRecommendationModel->setState("client", $element);
		$JLikeRecommendationModel->setState("assigned_to", $user->id);
		$JLikeRecommendationModel->setState("element_id", $this->lesson_id);
		$TodoData = $JLikeRecommendationModel->getItems();

		// Get Plugins: jLike Shika lesson plugin params
		$this->jlikeTjlmslessonPlugin = PluginHelper::getPlugin('content', 'jlike_tjlmslesson');

	
		$todoData = $JLikeRecommendationModel->getTodoId($this->lesson_id, $user->id);


		if ($todoData[0]->id)
		{
			$this->todo_id = $todoData[0]->id;
			$model = $this->getModel('interaction');
			/** @scrutinizer ignore-call */
			$this->interactionData = $model->getItem($this->todo_id);

			parent::display($tpl);
		}
		else
		{
			echo Text::_('COM_JLIKE_JERROR_INTERACTION_ALERTNOAUTHOR');
		}
	}

	/**
	 * Method to get a model object, loading it if required.
	 *
	 * @param   string  $name    The model name. Optional.
	 * @param   array   $config  Configuration array for model. Optional.
	 *
	 * @return  BaseDatabaseModel|boolean  Model object on success; otherwise false on failure.
	 *
	 * @since   3.0
	 */
	public function getModel($name = '', $config = array())
	{
		BaseDatabaseModel::addIncludePath(JPATH_SITE . 'com_jlike/models/interaction');

		return BaseDatabaseModel::getInstance($name, 'JlikeModel', $config);
	}
}
