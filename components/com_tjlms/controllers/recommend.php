<?php
/**
 * @version    SVN: <svn_id>
 * @package    Com_Tjlms
 * @copyright  Copyright (C) 2009 - 2017. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * JLike is free software. This version may have been modified pursuant
 * to the GNU General Public License, and as distributed it includes or
 * is derivative of works licensed under the GNU General Public License or
 * other free or open source software licenses.
 */

// No direct access
defined('_JEXEC') or die;
use Joomla\CMS\MVC\Controller\FormController;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Access\Access;


/**
 * Tjlms recommend controller class.
 *
 * @since  1.0.0
 */
class TjlmsControllerRecommend extends FormController
{
	/**
	 * Function used to recommend user.
	 *
	 * @return  redirects
	 *
	 * @since  1.0.0
	 */
	public function assignRecommendUsers()
	{
		$post  = Factory::getApplication()->input->post;

		$data = $post->get('filter', '', 'ARRAY');

		$data['type']     = $post->get('type', 'reco');

		if ($data['type'] != "assign" && $data['type'] != "reco" && $data['type'] != "enroll" )
		{
			$data['type']  = 'reco';
		}

			$data['group_assignment']  = $post->get('group_assignment', 0, 'INT');
			$data['update_existing_users']  = $post->get('update_existing_users', 0, 'INT');
			$data['onlysubuser']  		= $post->get('onlysubuser', 0, 'INT');

			// Check self Assign
			if ($post->get('sub_type', '', 'STRING') == 'self')
			{
				$data['recommend_friends'] = array(Factory::getUser()->id);

				if ($post->get('todo_id', '', 'INT'))
				{
					$data['todo_id'] = $post->get('todo_id', '', 'INT');
				}
			}
			else
			{
				$data['recommend_friends'] = $post->get('recommend_friends', '', 'ARRAY');
			}

			$model = $this->getModel('recommend', ' TjlmsModel');
			$plgName   = $post->get('plg_name', '');
			$plgType   = $post->get('plg_type', 'content');
			$element    = $post->get('element', '');
			$elementId = $post->get('element_id', '', 'INT');

			$options = array('element' => $element, 'element_id' => $elementId, 'plg_name' => $plgName, 'plg_type' => $plgType);

			if ($model->assignRecommendUsers($data, $options))
			{
				$session = Factory::getSession();
				$session->set('refresh_page', 1);
				$msg = ($data['type'] == 'reco') ? Text::_('COM_TJLMS_RECOMMEND_SUCCESSFULL') : Text::_("COM_TJLMS_ASSIGN_SUCCESSFULL");
			}
			else
			{
				$msg = ($data['type'] == 'reco') ? Text::_('COM_TJLMS_RECOMMEND_FAILED') : Text::_("COM_TJLMS_ASSIGN_FAILED");
			}

			// Redirect successfull message
			if ($post->get('sub_type', '', 'STRING') == 'self')
			{
				$link = 'index.php?option=com_tjlms&view=recommend&layout=default_setgoal&tmpl=component';
				$msg = Text::_("COM_TJLMS_ASSIGN_SETGOAL_SUCCESSFULL");

				$this->setRedirect(
				$link . '&id=' . $elementId . '&plg_name=' . $plgName . '&plg_type=' . $plgType . '&element=' . $element . '&type=' . $data['type'] .
				'&assignto=' . $post->get('sub_type', '', 'STRING'), $msg
				);
			}
			else
			{
				$link = 'index.php?option=com_tjlms&view=recommend&layout=popup&tmpl=component';
				$this->setRedirect(
				$link . '&id=' . $elementId . '&plg_name=' . $plgName . '&plg_type=' . $plgType . '&element=' . $element . '&type=' . $data['type'], $msg
				);
			}
	}

	/**
	 * Function used to recommend user.
	 *
	 * @return  redirects
	 *
	 * @since  1.0.0
	 */
	public function assignRecommendGroups()
	{
		$input = Factory::getApplication()->input;
		$post  = $input->post;

		$recommend_friends = array();
		$groups = $post->get('user_groups', '', 'ARRAY');

		if (!empty($groups))
		{
			foreach ($groups as $group)
			{
				$group_users = Access::getUsersByGroup($group);
				$recommend_friends	= array_merge($recommend_friends, $group_users);
			}

			$recommend_friends = array_unique($recommend_friends);
		}

		$post->set('recommend_friends', $recommend_friends);
		$post->set('group_assignment', 1);

		$this->assignRecommendUsers();

		return;
	}
}
