<?php
/**
 * @version    SVN: <svn_id>
 * @package    Com_Dpe
 * @author     Techjoomla <extensions@techjoomla.com>
 * @copyright  Copyright (c) 2009-2019 TechJoomla. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access
defined('_JEXEC') or die;
use Joomla\CMS\MVC\View\HtmlView;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\Registry\Registry;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\CMS\HTML\HTMLHelper;

/**
 * View to edit
 *
 * @since  1.0.0
 */
class DpeViewJlike extends HtmlView
{
	protected $dpeMainHelper = null;

	protected $privateComment = null;

	protected $publicComment = null;

	protected $accessByApiTask = 'api';

	protected $lessonData = null;

	protected $context = '';

	protected $contentId = '';

	protected $defaultUser = '';

	protected $userInfo = null;

	protected $urldata = null;

	protected $lessonUrl = null;

	protected $assignUserArray = array();

	/**
	 * Execute and display a template script.
	 *
	 * @param   string  $tpl  The name of the template file to parse; automatically searches through the template paths.
	 *
	 * @return  mixed  A string if successful, otherwise a Error object.
	 */
	public function display($tpl = null)
	{
		$mainHelper = JPATH_SITE . '/components/com_dpe/helpers/main.php';

		if (!class_exists('DpeMainHelper'))
		{
			JLoader::register('DpeMainHelper', $mainHelper);
			JLoader::load('DpeMainHelper');
		}

		$this->dpeMainHelper = new DpeMainHelper;

		// Get plugin 'jlike_privatecomment' of plugin type/folder 'content'
		$pluginJlikeComment = PluginHelper::getPlugin('content', 'jlike_comment');

		$pluginParams = new Registry($pluginJlikeComment->params);

		$this->privateComment = $pluginParams->get('private_comments');
		$this->publicComment = $pluginParams->get('public_comments');
		$this->accessByApiTask    = $pluginParams->get('comment_access');

		// Check if plugin configurations are sets

		if (!$this->privateComment && !$this->publicComment)
		{
			echo Text::_('COM_DPE_JLIKE_COMMENT_PLG_CONFIGURATION');
		}

		$this->userInfo = Factory::getUser();

		$app = Factory::getApplication();
		$setdata = JRequest::get('request');
		$this->urldata = json_decode($setdata['data']);

		$extraParams = array(
		"element" => $this->urldata->element,
		"cont_id" => $this->urldata->cont_id,
		"url" => $this->urldata->url,
		"title" => $this->urldata->title
		);

		// Get Content Id
		BaseDatabaseModel::addIncludePath(JPATH_SITE . '/components/com_jlike/models');
		$jlikeModel = BaseDatabaseModel::getInstance('jlike_likes', 'JlikeModel', array('ignore_request' => true));
		$this->contentId  = $jlikeModel->getConentId($extraParams);

		// Used to create lesson URL
		$this->lessonUrl = 'index.php?option=com_tjlms&view=lesson&lesson_id=' . $this->urldata->cont_id;

		// Get Lesson Details
		BaseDatabaseModel::addIncludePath(JPATH_SITE . '/components/com_tjlms/models');
		$tjlmsModel = BaseDatabaseModel::getInstance('lesson', 'TjlmsModel', array('ignore_request' => true));
		$this->lessonData     = $tjlmsModel->getlessondata($this->urldata->cont_id);

		// Todo@ to show comment sections for course under lessons
		if ($this->lessonData->course_id)
		{
			$this->privateComment = 0;
			$this->publicComment = 1;
		}

		// Check if private commentting is enabled

		if ($this->privateComment)
		{
			$currentUserSuperUser = $this->userInfo->authorise('core.admin');

			// Check User is Content Owner or Admin
			if ( $currentUserSuperUser || ($this->lessonData->created_by == $this->userInfo->id))
			{
				$assignedUsers = $this->dpeMainHelper->getAssignedUser($this->contentId, null, $this->userInfo->id);
				$this->assignUserArray[] = HTMLHelper::_('select.option', "", Text::_('COM_DPE_JLIKE_USER'));

				if (!empty($assignedUsers))
				{
					foreach ($assignedUsers as $users)
					{
						$this->assignUserArray[] = JHTML::_('select.option', $users->id, $users->name);
					}

					$this->defaultUser = $assignedUsers[0]->id;
				}

				$this->context = $this->lessonData->created_by . ':' . $this->defaultUser;
			}
			else
			{
				$this->context = $this->lessonData->created_by . ':' . $this->userInfo->id;
			}
		}

		parent::display($tpl);
	}
}
