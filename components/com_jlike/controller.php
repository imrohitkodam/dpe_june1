<?php
/**
 * @package     JLike
 * @subpackage  com_jlike
 *
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (C) 2009 - 2019 Techjoomla. All rights reserved.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 */

// No direct access.
defined('_JEXEC') or die();
use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Filter\InputFilter;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\CMS\Session\Session;
use Joomla\CMS\Response\JsonResponse;

/**
 * jLikeController main Controller
 *
 * @package     Jlike
 * @subpackage  site
 * @since       2.2
 */
class JLikeController extends BaseController
{
	/**
	 * Display.
	 *
	 * @param   boolean  $cachable   cachable status.
	 * @param   boolean  $urlparams  urlparams status.
	 *
	 * @return void
	 *
	 * @since 3.0
	 */
	public function display($cachable = false, $urlparams = false)
	{
		$view  = $this->getView('jlike', 'raw');
		$model = $this->getModel('jlike_likes');
		$view->setModel($model);
		parent::display();
	}

	/**
	 * method to get the reviews for view more
	 *
	 * @return void
	 *
	 * @since 3.0
	 */
	public function LoadReviews()
	{
		$input       = Factory::getApplication()->input;
		$post        = $input->post;
		$element_id  = $post->get('element_id', 0, 'INT');
		$element     = $post->get('element', '', 'CMD');
		$callIdetity = $post->get('callIdetity', 0, 'INT');
		$ordering    = $post->get('sorting', 1, 'INT');

		$sortingType = 'DESC';

		// Get the comment sorting latest or oldest
		switch ($ordering)
		{
			case 1:
			$sortingType = 'DESC';
			break;
			case 2:
			$sortingType = 'ASC';
			break;
		}

		// If this request not call from assending decending function
		if (!$callIdetity)
		{
			$annotaionIdsArr = json_decode($post->get('annotaionIdsArr', '', 'STRING'));
			$viewmoreId      = $post->get('viewmoreId', array(), 'ARRAY');
		}
		else
		{
			$annotaionIdsArr = '';
			$viewmoreId      = '';
		}

		$childrensId = '';
		$getchildren = $post->get('getchildren', 0, 'INT');

		if ($getchildren)
		{
			$childrensId = ($post->get('childrensId', array(), 'ARRAY'));
		}

		$model = $this->getModel('jlike_likes');
		$response = json_encode(
			$model->getRatingReviewData(
				$element_id, $element, $getchildren, $childrensId,
				$sortingType, $annotaionIdsArr, $viewmoreId, 2
			)
		);

		echo $response;

		jexit();
	}

	/**
	 * method to get the commets for view more
	 *
	 * @return void
	 *
	 * @since 3.0
	 */
	public function LoadComment()
	{
		$input       = Factory::getApplication()->input;
		$post        = $input->post;
		$filter = InputFilter::getInstance();
		$extraParams = $post->get('extraParams', array(), 'ARRAY');
		$cont_id     = (int) $extraParams['cont_id'];
		$element     = $extraParams['element'];
		$element 	 = (string) preg_replace('/[^A-Z0-9_\.-]/i', '', $element);
		$element 	 = ltrim($element, '.');
		$callIdetity = $post->get('callIdetity', 0, 'INT');
		$ordering    = $post->get('sorting', 1, 'INT');

		$sortingType = 'DESC';

		// Get the comment sorting latest or oldest
		switch ($ordering)
		{
			case 1:
			$sortingType = 'DESC';
			break;

			case 2:
			$sortingType = 'ASC';
			break;
		}

		// If this request not call from assending decending function
		if (!$callIdetity)
		{
			$annotaionIdsArr = json_decode($post->get('annotaionIdsArr', '', 'STRING'));
			$viewmoreId      = $post->get('viewmoreId', array(), 'ARRAY');

			if (!empty($viewmoreId))
			{
				foreach ($viewmoreId as $i => $v)
				{
					$viewmoreId[$i] = $filter->clean($v, 'int');
				}
			}

			if (!empty($annotaionIdsArr))
			{
				foreach ($annotaionIdsArr as $i => $v)
				{
					$annotaionIdsArr[$i] = $filter->clean($v, 'int');
				}
			}
		}
		else
		{
			$annotaionIdsArr = array();
			$viewmoreId      = '';
		}

		$childrensId = array();
		$getchildren = $post->get('getchildren', 0, 'INT');

		if ($getchildren)
		{
			$childrensId = $post->get('childrensId', array(), 'ARRAY');

			foreach ($childrensId as $i => $v)
			{
				$childrensId[$i] = $filter->clean($v, 'int');
			}
		}

		$model = $this->getModel('jlike_likes');
		$response = json_encode(
			$model->getCommentsData(
				$cont_id, $element, $getchildren, $childrensId,
				$sortingType, $annotaionIdsArr, $viewmoreId, '', $extraParams
			)
		);
		echo $response;

		jexit();
	}

	/**
	 * getUserdetails.
	 *
	 * @return void
	 *
	 * @since 3.0
	 */
	public function getUserdetails()
	{
		header('Cache-Control: no-cache, must-revalidate');
		header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
		header('Content-type: application/json');
		$jlikehelpobj = new comjlikeHelper;
		$userData     = Factory::getUser();

		if (JVERSION < 3.0)
		{
			$data = /** @scrutinizer ignore-deprecated */ Factory::getApplication()->input->get('data');
		}
		else
		{
			$input = Factory::getApplication()->input;
			$post  = $input->getArray($_POST);
			$data  = $post;
		}

		$result       = $jlikehelpobj->getUserDetails($userData, $data['extraParams']);

		if ($result)
		{
			$oluser 		  = new stdClass;
			$oluser->uname    = $userData->name;
			$oluser->img_url  = $result['img_url'];
			$oluser->link_url = $result['link_url'];
			echo json_encode($oluser);
		}
		else
		{
			echo json_encode(-1);
		}

		jexit();
	}

	/**
	 * Send Reminders to Users before due date
	 *
	 * @return void
	 */
	public function remindersCron()
	{
		// Load jlike reminders model to call api to send the reminders
		require_once JPATH_ADMINISTRATOR . '/components/com_jlike/models/reminders.php';

		// Call the actual cron code which will send the reminders
		$model         = BaseDatabaseModel::getInstance('Reminders', 'JlikeModel');
		$reminders     = $model->sendReminders();
	}

	/**
	 * Send Reminders for DPE to Users before due date DPE Hack
	 *
	 * @return void
	 */
	public function remindersCronTodoDpe()
	{
		// Load jlike reminders model to call api to send the reminders
		require_once JPATH_ADMINISTRATOR . '/components/com_jlike/models/reminders.php';

		// Call the actual cron code which will send the reminders
		$model         = JModelLegacy::getInstance('Reminders', 'JlikeModel');
		$reminders     = $model->sendRemindersDpe();
	}

	/**
	 * Store.
	 *
	 * @return void|boolean
	 *
	 * @since 3.0
	 */
	public function store()
	{
		Session::checkToken() or jexit('Invalid Token');

		if (!$this->isLoggedInUser())
		{
			return false;
		}

		header('Cache-Control: no-cache, must-revalidate');
		header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
		header('Content-type: application/json');

		if (JVERSION < 3.0)
		{
			$data = /** @scrutinizer ignore-deprecated */ Factory::getApplication()->input->get('data');
		}
		else
		{
			$input = Factory::getApplication()->input;
			$post  = $input->getArray($_POST);
			$data  = $post;
		}

		$jlikehelpobj = new comjlikeHelper;
		$result       = 0;

		if (!empty($data))
		{
			$result = $jlikehelpobj->registerLike($data);
		}

		if ($result)
		{
			echo json_encode($result);
		}
		else
		{
			echo json_encode(-1);
		}

		jexit();
	}

	/**
	 * Addlables.
	 *
	 * @return void|boolean
	 *
	 * @since 3.0
	 */
	public function addlables()
	{
		if (!$this->isLoggedInUser())
		{
			return false;
		}

		header('Cache-Control: no-cache, must-revalidate');
		header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
		header('Content-type: application/json');

		if (JVERSION < 3.0)
		{
			$data = /** @scrutinizer ignore-deprecated */ Factory::getApplication()->input->get('data');
		}
		else
		{
			$input = Factory::getApplication()->input;
			$post  = $input->getArray($_POST);
			$data  = $post;
		}

		$jlikehelpobj = new comjlikeHelper;
		$result       = new stdclass;

		if (!empty($data['label']))
		{
			$db       = Factory::getDBO();
			$result->id = $jlikehelpobj->addlables($data);
			$result->label = $db->escape($data['label']);
		}

		if (!empty($result))
		{
			echo new JsonResponse($result);
		}
		else
		{
			echo json_encode(-1);
		}

		jexit();
	}

	/**
	 * savedata.
	 *
	 * @return void|boolean
	 *
	 * @since 3.0
	 */
	public function savedata()
	{
		if (!$this->isLoggedInUser())
		{
			return false;
		}

		header('Cache-Control: no-cache, must-revalidate');
		header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
		header('Content-type: application/json');

		$input = Factory::getApplication()->input;
		$data = $input->getArray(
			array(
				'formdata' => 'string',
				'element_id' => 'int',
				'element' => 'cmd',
				'title' => 'cmd',
				'url' => 'string')
		);

		$output = array();
		parse_str($data['formdata'], $output);

		unset($data['formdata']);
		$output = array_merge(/** @scrutinizer ignore-type */ $data, $output);

		$jlikehelpobj = new comjlikeHelper;
		$result       = 0;

		if (!empty($output))
		{
			$result = $jlikehelpobj->savedata($output);
		}

		if ($result)
		{
			echo json_encode($result);
		}
		else
		{
			echo json_encode(-1);
		}

		jexit();
	}

	/**
	 * Method to save the edited comment.
	 *
	 * @return void|boolean
	 *
	 * @since 3.0
	 */
	public function SaveComment()
	{
		Session::checkToken() or jexit('Invalid Token');

		if (!$this->isLoggedInUser())
		{
			return false;
		}

		$post         = Factory::getApplication()->input->post;
		$annotation_id = $post->get('annotation_id', 0, 'INT');
		$comment       = $post->get('comment', '', 'RAW'); // DPE HACK ADDED RAW
		$note_type     = $post->get('note_type', '0', 'HTML');
		$extraParams   = $post->get('extraParams', array(), 'Array');

		$commentData = array();
		$commentData['comment'] = $comment;
		$commentData['annotation_id'] = $annotation_id;
		$commentData['note_type'] = $note_type;
		$commentData['parent_id'] = trim($post->get('parent_id', 0, 'INT'));

		$model  = $this->getModel('comment');
		$result = $model->SaveComment($commentData, $extraParams);

		echo json_encode($result);
		jexit();
	}

	/**
	 * Method to delete the user reviews
	 *
	 * @return void|boolean
	 *
	 * @since 3.0
	 */
	public function DeleteReviews()
	{
		if (!$this->isLoggedInUser())
		{
			return false;
		}

		$input         = Factory::getApplication()->input;
		$annotation_id = $input->get('annotation_id', 0, 'INT');
		$model         = $this->getModel('jlike_likes');
		$response      = $model->DeleteReviews($annotation_id);
		echo json_encode($response);
		jexit();
	}

	/**
	 * Method to delete the user commment
	 *
	 * @return void|boolean
	 *
	 * @since 3.0
	 */
	public function DeleteComment()
	{
		Session::checkToken() or jexit('Invalid Token');

		$extraParams = Factory::getApplication()->input->get('extraParams', array(), 'Array');

		if (!$this->isLoggedInUser())
		{
			return false;
		}

		$input         = Factory::getApplication()->input;
		$annotation_id = $input->get('annotation_id', 0, 'INT');
		$model         = $this->getModel('jlike_likes');
		$response      = $model->DeleteComment($annotation_id, $extraParams);
		echo json_encode($response);
		jexit();
	}

	/**
	 * SaveNewRating
	 *
	 * @return void|boolean
	 *
	 * @since 3.0
	 */
	public function SaveNewRating()
	{
		if (!$this->isLoggedInUser())
		{
			return false;
		}

		$input          = Factory::getApplication()->input;
		$post           = $input->post;
		$element_id     = $post->get('element_id', 0, 'INT');
		$element        = $post->get('element', '', 'CMD');
		$url            = $post->get('url', '', 'STRING');
		$title          = $post->get('title', '', 'CMD');
		$user_rating    = $post->get('user_rating', 0, 'INT');
		$rating_upto    = $post->get('rating_upto', '', 'STRING');
		$plg_name       = $post->get('plg_name', '', 'CMD');

		$comjlikeHelper = new comjlikeHelper;
		$response       = $comjlikeHelper->addRating($element_id, $user_rating, $rating_upto, $plg_name, $element, $url, $title);
		echo json_encode($response);
		jexit();
	}

	/**
	 * SaveNewComment
	 *
	 * @return void|boolean
	 *
	 * @since 3.0
	 */
	public function SaveNewComment()
	{
		Session::checkToken() or jexit('Invalid Token');

		if (!$this->isLoggedInUser())
		{
			return false;
		}

		$input          = Factory::getApplication()->input;
		$post           = $input->post;
		$extraParams = $post->get('extraParams', array(), 'Array');
		$commentData 	= array();
		$commentData['comment'] = $post->get('comment','','RAW'); // DPE HACK ADDED RAW
		$commentData['note_type'] = trim($post->get('note_type', 0, 'INT'));
		$commentData['parent_id'] = trim($post->get('parent_id', '', 'INT'));

		$comjlikeHelper = new comjlikeHelper;

		$response       = $comjlikeHelper->addComment($commentData, $extraParams);

		echo json_encode($response);
		jexit();
	}

	/**
	 * IncreaseLikeCount
	 *
	 * @return void|boolean
	 *
	 * @since 3.0
	 */
	public function increaseLikeCount()
	{
		if (!$this->isLoggedInUser())
		{
			return false;
		}

		$input          = Factory::getApplication()->input;
		$post           = $input->post;
		$annotationid   = $post->get('annotationid', 0, 'INT');
		$comment        = $post->get('comment', '', 'STRING');
		$extraParams        = $post->get('extraParams', array(), 'ARRAY');

		$comjlikeHelper = new comjlikeHelper;
		$response       = $comjlikeHelper->increaseLikeCount($annotationid, $comment, $extraParams);
		echo json_encode($response);
		jexit();
	}

	/**
	 * Method to Dislike the comment
	 *
	 * @return void|boolean
	 *
	 * @since 3.0
	 */
	public function increaseDislikeCount()
	{
		if (!$this->isLoggedInUser())
		{
			return false;
		}

		$input          = Factory::getApplication()->input;
		$post           = $input->post;
		$annotationid   = $post->get('annotationid', 0, 'INT');
		$comment        = $post->get('comment', '', 'STRING');
		$extraParams        = $post->get('extraParams', array(), 'ARRAY');
		$comjlikeHelper = new comjlikeHelper;
		$response       = $comjlikeHelper->increaseDislikeCount($annotationid, $comment, $extraParams);
		echo json_encode($response);
		jexit();
	}

	/**
	 * Method to getUserByCommentId
	 *
	 * @return  void|boolean
	 *
	 * @since 3.0
	 */
	public function getUserByCommentId()
	{
		if (!$this->isLoggedInUser())
		{
			return false;
		}

		$response = array();

		$input           = Factory::getApplication()->input;
		$post            = $input->post;
		$annotationid    = $post->get('annotationid', '-1', 'INT');
		$likedOrdisliked = $post->get('likedOrdisliked', '1', 'INT');
		$comjlikeHelper  = new comjlikeHelper;
		$response        = $comjlikeHelper->getUserByCommentId($annotationid, $likedOrdisliked);

		echo json_encode($response);
		jexit();
	}

	/**
	 * Delete the lable list.
	 *
	 * @return void|boolean
	 *
	 * @since 3.0
	 */
	public function jlike_deleteList()
	{
		if (!$this->isLoggedInUser())
		{
			return false;
		}

		$jinput = Factory::getApplication()->input;

		// Get list id
		$lableId = $jinput->get("lableId");

		$model = $this->getModel('likes');
		$response = $model->jlike_deleteList($lableId);

		echo json_encode($response);
		jexit();
	}

	/**
	 * Delete the likes from my like view @TODO create seperate controller for likes view. (vm: Other functionality will break if i do now)
	 *
	 * @return void|boolean
	 *
	 * @since 3.0
	 */
	public function delete()
	{
		if (!$this->isLoggedInUser())
		{
			return false;
		}

		$jinput = Factory::getApplication()->input;

		// Get list id
		$lableId = $jinput->get("lableId");

		$model = $this->getModel('likes');
		$response = $model->jlike_deleteList($lableId);

		echo json_encode($response);
		jexit();
	}

	/**
	 * On status change change.
	 *
	 * @return void|boolean
	 *
	 * @since 3.0
	 */
	public function changeItemStatus()
	{
		if (!$this->isLoggedInUser())
		{
			return false;
		}

		$comjlikeHelper = new comjlikeHelper;
		$jinput = Factory::getApplication()->input;

		$element = $jinput->get('element', '', 'CMD');
		$element_id = $jinput->get('element_id', 0, 'INT');
		$like_statusId = $jinput->get('status_id', 0, 'INT');

		$response = array();

		$response['status'] = 0;
		$response['msg'] = '';

		if (!empty($element) || $element_id || !empty($like_statusId))
		{
			$content_id = $comjlikeHelper->getContentId($element_id, $element);

			// Check whether user is liked to content
			$isliked = $comjlikeHelper->isUserLikedContent($content_id, Factory::getUser()->id);

			if ($isliked)
			{
				$response['status'] = $comjlikeHelper->storeExtraData($content_id, $like_statusId);
			}
		}
		else
		{
			$response['msg'] = ' PARAMETERS_CHECK_FAIL';
		}

		echo json_encode($response);
		jexit();
	}

	/**
	 * addContenttoList.
	 *
	 * @return void|boolean
	 *
	 * @since 3.0
	 */
	public function manageListforContent()
	{
		if (!$this->isLoggedInUser())
		{
			return false;
		}

		header('Cache-Control: no-cache, must-revalidate');
		header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
		header('Content-type: application/json');

		$input = Factory::getApplication()->input;
		$post  = $input->getArray($_POST);
		$model         = $this->getModel('jlike_likes');
		$result       = 0;

		if ($post)
		{
			$result = $model->manageListforContent($post);

			if ($result)
			{
				echo json_encode($result);
			}
			else
			{
				echo json_encode(-1);
			}
		}
		else
		{
			echo json_encode($result);
		}

		jexit();
	}

	/**
	 * isLoggedInUsers
	 *
	 * @return integer
	 */
	public function isLoggedInUser()
	{
		return Factory::getUser()->id;
	}
}
