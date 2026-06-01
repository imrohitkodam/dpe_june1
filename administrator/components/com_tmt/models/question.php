<?php
/**
 * @package     Shika
 * @subpackage  com_tmt
 *
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (C) 2009 - 2020 Techjoomla. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access.
defined('_JEXEC') or die;
use Joomla\CMS\Table\Table;
use Joomla\CMS\Filter\InputFilter;

jimport('joomla.application.component.modeladmin');
jimport('joomla.event.dispatcher');
jimport('techjoomla.common');

use Joomla\CMS\Factory;
use Joomla\CMS\Log\Log;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Model\AdminModel;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;

require_once JPATH_ADMINISTRATOR . '/components/com_tmt/helpers/tmt.php';
JLoader::import("/techjoomla/media/storage/local", JPATH_LIBRARIES);
JLoader::import("/techjoomla/media/xref", JPATH_LIBRARIES);
JLoader::import("/techjoomla/media/tables/files", JPATH_LIBRARIES);
JLoader::import("/techjoomla/media/tables/xref", JPATH_LIBRARIES);
JLoader::import('components.com_tmt.models.answer', JPATH_ADMINISTRATOR);
JLoader::import('components.com_tmt.models.answers', JPATH_ADMINISTRATOR);
/**
 * Tmt model.
 *
 * @since  1.0.0
 */
class TmtModelquestion extends AdminModel
{
	public $item = null;

	public $getAnswers = 1;

	public $questionMediaClient = 'tjlms.question';

	public $answerMediaClient = 'tjlms.answer';

	public $tjLmsParams;

	public $defaultMimeTypes = array(
		'image/jpeg',
		'image/gif',
		'image/png',
		'image/bmp',
		'image/x-ms-bmp',
		'application/msword',
		'application/excel',
		'application/pdf',
		'application/powerpoint',
		'application/vnd.ms-powerpoint',
		'text/plain',
		'application/x-zip',
		'application/zip',
		'application/vnd.openxmlformats-officedocument.presentationml.presentation',
		'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
		'application/vnd.ms-excel',
		'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
		'audio/wav',
		'audio/aac',
		'audio/mp3',
		'audio/x-m4a',
		'audio/mpeg',
		'audio/x-hx-aac-adts',
		'audio/mp4',
		'audio/x-wav'
	);

	public $defaultImageExtensions = array ('bmp', 'gif', 'jpg', 'png');

	/**
	 * Constructor
	 *
	 * @since 1.0
	 */
	public function __construct()
	{
		$this->comtjlmsHelper   = new comtjlmsHelper;
		$this->techjoomlacommon = new TechjoomlaCommon;
		$this->tjLmsParams      = ComponentHelper::getParams('com_tjlms');
		parent::__construct();
	}

	/**
	 * Method to get an ojbect.
	 *
	 * @param   integer  $id  The id of the object to get.
	 *
	 * @return	mixed	Object on success, false on failure.
	 */
	public function getItem($id = null)
	{
		if ($this->item = parent::getItem($id))
		{
			if (empty($id))
			{
				$id = $this->getState('question.id');
			}

			// Set blank values to media properties
			$this->item->media_type = '';

			// Get answer options for this question
			if ($id)
			{
				$category = Table::getInstance('Category', 'JTable', array('dbo', $this->_db));
				$category->load($this->item->category_id);
				$this->item->cat_title = $category->title;

				// Get Media details
				$mediaDetails = $this->getMediaDetails($id, $this->questionMediaClient);

				if (!empty($mediaDetails))
				{
					$this->item->media_source        = $mediaDetails->source;
					$this->item->media_id            = $mediaDetails->media_id;
					$this->item->original_media_type = $mediaDetails->type;
					$this->item->original_filename   = $mediaDetails->original_filename;

					if (strpos($mediaDetails->type, 'video') !== false)
					{
						$this->item->media_type = 'video';
						$this->item->media_url  = $mediaDetails->original_filename;
					}
					elseif(strpos($mediaDetails->type, 'image') !== false)
					{
						$this->item->media_type = 'image';
					}
					elseif(strpos($mediaDetails->type, 'audio') !== false)
					{
						$this->item->media_type = 'audio';
					}
					else
					{
						$this->item->media_type = 'file';
					}
				}

				/*check if question is attempted*/
				$this->item->isQuestionAttempted = $this->isQuestionAttempted($id);

				// Get answer options for this question
				if ($this->getAnswers == 1)
				{
					$query = $this->_db->getQuery(true);
					$query->select('a.*');
					$query->from($this->_db->qn('#__tmt_answers', 'a'));
					$query->where($this->_db->qn('a.question_id') . '=' . (int) $id);

					if ($this->getState('question.correct_answers') == 1)
					{
						$query->where($this->_db->qn('a.is_correct') . " = 1");
					}

					$query->order('a.order', 'ASC');
					$this->_db->setQuery($query);
					$a_data              = $this->_db->loadObjectList();
					$this->item->answers = $a_data;
				}
			}
		}

		return $this->item;
	}

	/**
	 * Method to check in an item.
	 *
	 * @param   integer  $type    The id of the row to check out.
	 * @param   integer  $prefix  The id of the row to check out.
	 * @param   integer  $config  The id of the row to check out.
	 *
	 * @return	boolean		True on success, false on failure.
	 *
	 * @since	1.6
	 */
	public function getTable($type = 'Question', $prefix = 'TmtTable', $config = array())
	{
		$this->addTablePath(JPATH_COMPONENT_ADMINISTRATOR . '/tables');

		return Table::getInstance($type, $prefix, $config);
	}

	/**
	 * Method to check in an item.
	 *
	 * @param   integer  $id  The id of the row to check out.
	 *
	 * @return	boolean		True on success, false on failure.
	 *
	 * @since	1.6
	 */
	public function checkin($id = null)
	{
		// Get the id.
		$id = (!empty($id)) ? $id : (int) $this->getState('question.id');

		if ($id)
		{
			// Initialise the table
			$table = $this->getTable();

			// Attempt to check the row in.
			if (method_exists($table, 'checkin'))
			{
				if (!$table->checkin($id))
				{
					$this->setError($table->getError());

					return false;
				}
			}
		}

		return true;
	}

	/**
	 * Method to check out an item for editing.
	 *
	 * @param   integer  $id  The id of the row to check out.
	 *
	 * @return  boolean		True on success, false on failure.
	 *
	 * @since	1.6
	 */
	public function checkout($id = null)
	{
		// Get the user id.
		$id = (!empty($id)) ? $id : (int) $this->getState('question.id');

		if ($id)
		{
			// Initialise the table
			$table = $this->getTable();

			// Get the current user object.
			$user = Factory::getUser();

			// Attempt to check the row out.
			if (method_exists($table, 'checkout'))
			{
				if (!$table->checkout($user->get('id'), $id))
				{
					$this->setError($table->getError());

					return false;
				}
			}
		}

		return true;
	}

	/**
	 * Method to get the profile form.
	 *
	 * The base form is loaded from XML
	 *
	 * @param   array    $data      An optional array of data for the form to interogate.
	 * @param   boolean  $loadData  True if the form is to load its own data (default case), false if not.
	 *
	 * @return	JForm	A JForm object on success, false on failure
	 *
	 * @since	1.6
	 */
	public function getForm($data = array(), $loadData = true)
	{
		// Get the form.
		$form = $this->loadForm('com_tmt.question', 'question', array('control' => 'jform', 'load_data' => $loadData));

		if (empty($form))
		{
			return false;
		}

		if ($this->item->id)
		{
			$isQuestionAttempted = $this->isQuestionAttempted($this->item->id);

			// If attempted, then allow only title & description to edit, reset will be readonly
			if ($isQuestionAttempted)
			{
				$form->setFieldAttribute('marks', 'readonly', 'true');
				$form->setFieldAttribute('type', 'readonly', 'true');
				$form->setFieldAttribute('gradingtype', 'readonly', 'true');
				$form->setFieldAttribute('alias', 'readonly', 'true');
				$form->setFieldAttribute('ideal_time', 'readonly', 'true');
				$form->setFieldAttribute('level', 'readonly', 'true');
			}
		}

		return $form;
	}

	/**
	 * Method to get the data that should be injected in the form.
	 *
	 * @return  mixed  The data for the form.
	 *
	 * @since	1.6
	 */
	protected function loadFormData()
	{
		$data = Factory::getApplication()->getUserState('com_tmt.edit.question.data', array());

		if (empty($data))
		{
			$data = $this->getItem();
		}

		return $data;
	}

	/**
	 * Method to save the form data.
	 *
	 * @param   array  $data  The form data.
	 *
	 * @return	mixed		The user id on success, false on failure.
	 *
	 * @since	1.6
	 */
	public function save($data)
	{
		$table = $this->getTable();
		$key   = $table->getKeyName();

		if (!$data['id'])
		{
			$data['created_on'] = Factory::getDate()->toSql();
			$data['state'] = 1;
			$data['id'] = 0;
		}

		if ($data['gradingtype'] != "quiz")
		{
			$data['marks'] = 0;
		}

		if (empty($data['checked_out']))
		{
			$data['checked_out'] = 0;
		}

		if (empty($data['ideal_time']))
		{
			$data['ideal_time'] = 0;
		}

		if (!empty($data['params']))
		{
			$data['params'] = array_map('htmlspecialchars', $data['params']);
			$data['params'] = json_encode($data['params']);
		}
		else
		{
			$data['params'] = '';
		}

		$data['alias'] = (!empty($data['alias'])) ? $data['alias'] : 0;
		
		if (isset($data['mediaFiles']))
		{
			$mediaFileArray = $data['mediaFiles'];
		}

		// DPE HACK CAN GO IN CORE
		$data['company_id'] = ($data['company_id'] )?$data['company_id'] :0;
		$data['qztype'] = ($data['qztype'] )?$data['qztype'] :0; 

		// Save questions data
		if (parent::save($data) === true)
		{
			$id = (!empty($data['id'])) ? $data['id'] : (int) $this->setState($this->getName() . '.id', $table->$key);

			// Save question media - start
			if (isset($data['media_type']))
			{
				if ($data['media_type'] == 'video')
				{
					$mediaDetails = $this->getMediaDetails($id, $this->questionMediaClient);

					$uploadVideoUrl = false;

					if (!empty($mediaDetails))
					{
						if ($mediaDetails->original_filename != $data['media_url'])
						{
							$uploadVideoUrl = true;
						}
					}
					else
					{
						$uploadVideoUrl = true;
					}

					if ($uploadVideoUrl && $data['media_url'] != '')
					{
						$mediaData         = array();
						$mediaData['name'] = $data['media_url'];

						$urlType = $this->getVideoHost($data['media_url']);

						switch ($urlType['host'])
						{
							case 'www.youtube.com':
							case 'youtu.be':
								$mediaData['type'] = 'youtube';
								break;

							case 'vimeo.com':
							case 'player.vimeo.com':
								$mediaData['type'] = 'vimeo';
								break;
						}

						$uploadedMedia = $this->uploadLink($mediaData);

						if (!empty($uploadedMedia))
						{
							// Save Media Xref
							$this->saveMediaXref($uploadedMedia['id'], $id, $this->questionMediaClient);
						}
						else
						{
							$this->setError(Text::_("COM_TMT_QUESTION_MEDIA_UPLOAD_ERROR"));

							return false;
						}
					}
				}
				elseif ($data['media_type'] == 'file' || $data['media_type'] == 'image' || $data['media_type'] == 'audio')
				{
					$mediaFile[] = $mediaFileArray['media_' . $data['media_type']];

					if ($mediaFile[0]['error'] == UPLOAD_ERR_OK)
					{
						$uploadedMedia = $this->uploadFile($mediaFile);

						if (!empty($uploadedMedia[0]))
						{
							// Save Media Xref
							$this->saveMediaXref($uploadedMedia[0]['id'], $id, $this->questionMediaClient);
						}
						else
						{
							return false;
						}
					}
				}
			}
			// Save question media - end

			if ($data['type'] == 'file_upload')
			{
				return $id;
			}

			if ($data['gradingtype'] == 'feedback' && in_array($data['type'], array("text", "textarea")))
			{
				return $id;
			}

			$count = count($data['answers_text']);

			// Get all existing answer ids posted from form
			$form_answers = $data['answer_id_hidden'];

			if (!empty($form_answers))
			{
				$countFormAnswer = count($form_answers);
				$existingAnswers = array();

				for ($i = 0; $i < $countFormAnswer; $i++)
				{
					$form_answers[$i] = (int) $form_answers[$i];

					$adminModel = BaseDatabaseModel::getInstance('Answers', 'TmtModel', array('ignore_request' => true));
					$adminModel->setState('filter.question_id', $id);

					$existingAnswers = $adminModel->getItems();
				}

				$existingAnswerIds = array_map(
					function($e)
					{
						return is_object($e) ? $e->id : $e['id'];
					},
					$existingAnswers
				);

				$deletedAnswers = array_values(array_diff($existingAnswerIds, $form_answers));

				foreach ($deletedAnswers as $key => $deleteId)
				{
					$answerModel = BaseDatabaseModel::getInstance('answer', 'TmtModel', array('ignore_request' => true));

					$answerModel->delete($deleteId);
				}

				if ($data['type'] == 'objtext')
				{
					if (strpos($data['answers_text'][0], ",") !== false)
					{
						$explodedAnswer          = explode(',', $data['answers_text'][0]);
						$dataTrim                = array_map('trim', $explodedAnswer);
						$data['answers_text'][0] = implode(',', $dataTrim);
					}
					else
					{
						$data['answers_text'][0] = trim($data['answers_text'][0]);
					}
				}

				// Now, save all answer options
				for ($i = 0;$i < $count;$i++)
				{
					$answer = array();
					$answer['id'] = 0;
					$answer['question_id'] = $id;

					if (!empty($data['answer_id_hidden'][$i]))
					{
						$answer['id'] = $data['answer_id_hidden'][$i];
					}

					// Flag
					$saveAnswer = 1;

					// Do not save answer text if it is an empty answer
					if ($data['type'] == 'text' || $data['type'] == 'textarea')
					{
						if (empty($data['answers_text'][$i]))
						{
							$saveAnswer = 0;
						}
					}

					if ($saveAnswer == 1)
					{
						if (isset($data['answers_text'][$i]))
						{
							// Set answer text
							$answer['answer'] = $data['answers_text'][$i];
						}

						if (isset($data['answers_comments'][$i]))
						{
							// Set answer comments
							$answer['comments'] = $data['answers_comments'][$i];
						}

						// Set ordering and correct answer for MCQs
						if ( $data['type'] == 'radio' || $data['type'] == 'checkbox')
						{
							// Check if answers_marks array is posted
							if (!empty($data['answers_marks']))
							{
								$answer['marks'] = $data['answers_marks'][$i];
							}
							else
							{
								$answer['marks'] = 0;
							}

							$answer['is_correct'] = $data['answers_iscorrect_hidden'][$i];
							$answer['order']      = $i + 1;
						}
						elseif ($data['type'] == 'rating')
						{
							$answer['answer'] = (float) $answer['answer'];
							$answer['order']  = $i + 1;
						}
						else
						{
							$answer['marks'] = 0;

							// @TODO might need to change values here to NULL
							$answer['is_correct'] = 0;
							$answer['order']      = 0;
						}
					}

					$answerModel = BaseDatabaseModel::getInstance('answer', 'TmtModel', array('ignore_request' => true));

					$answer['answer']   = InputFilter::getInstance(array(), array(), 1, 1)->clean($answer['answer'], 'html');

					if (isset($answer['comments']) && !empty($answer['comments']))
					{
						$answer['comments'] = InputFilter::getInstance(array(), array(), 1, 1)->clean($answer['comments'], 'html');
					}

					$saveResult = $answerModel->save($answer);

					$answerId = '';

					if ($saveResult)
					{
						$answerId = (!empty($answer['id'])) ? $answer['id'] : (int) $answerModel->getState($answerModel->getName() . '.id');
					}

					// Save answer media - start
					if (isset($data['answer_media_type'][$i]) && $data['answer_media_type'][$i] == 'video')
					{
						$ansMediaDetails = '';
						$ansMediaDetails = $this->getMediaDetails($id, $this->answerMediaClient);

						$uploadAnsVideoUrl = false;

						if (!empty($ansMediaDetails))
						{
							if ($ansMediaDetails->original_filename != $data['answer_media_video'][$i])
							{
								$uploadAnsVideoUrl = true;
							}
						}
						else
						{
							$uploadAnsVideoUrl = true;
						}

						if ($uploadAnsVideoUrl)
						{
							$ansMediaData         = array();
							$ansMediaData['name'] = $data['answer_media_video'][$i];

							$ansUrlType = $this->getVideoHost($data['answer_media_video'][$i]);

							switch ($ansUrlType['host'])
							{
								case 'www.youtube.com':
								case 'youtu.be':
									$ansMediaData['type'] = 'youtube';
									break;

								case 'vimeo.com':
								case 'player.vimeo.com':
									$ansMediaData['type'] = 'vimeo';
									break;
							}

							$uploadedAnsMedia = $this->uploadLink($ansMediaData);

							if (!empty($uploadedAnsMedia))
							{
								// Save Media Xref
								$this->saveMediaXref($uploadedAnsMedia['id'], $answerId, $this->answerMediaClient);
							}
							else
							{
								$this->setError(Text::_("COM_TMT_QUESTION_MEDIA_UPLOAD_ERROR"));

								return false;
							}
						}
					}
					elseif (isset($data['answer_media_type'][$i]) &&
							($data['answer_media_type'][$i] == 'file' || $data['answer_media_type'][$i] == 'image' || $data['answer_media_type'][$i] == 'audio'))
					{
						$mediaFile   = array();
						$mediaFile[] = $data['answer_media_' . $data['answer_media_type'][$i]][$i];

						if ($mediaFile[0]['error'] == UPLOAD_ERR_OK)
						{
							$uploadedAnsMedia = $this->uploadFile($mediaFile);

							if (!empty($uploadedAnsMedia[0]))
							{
								// Save Media Xref
								$this->saveMediaXref($uploadedAnsMedia[0]['id'], $answerId, $this->answerMediaClient);
							}
							else
							{
								return false;
							}
						}
					}
					// Save answer media - end
				}
			}

			// Return question id
			return $id;
		}
		else
		{
			return false;
		}
	}

	/**
	 * Method to test whether a record state can be edited.
	 *
	 * @param   object  $record  A record object.
	 *
	 * @return  boolean  True if allowed to change the state of the record. Defaults to the permission for the component.
	 *
	 * @since   12.2
	 */
	protected function canEditState($record)
	{
		$canEdit = TmtHelper::canManageQuestion($record->id, null, $record->created_by);

		return $canEdit;
	}

	/**
	 * Method to test whether a record can be deleted.
	 *
	 * @param   object  $record  A record object.
	 *
	 * @return  boolean  True if allowed to delete the record. Defaults to the permission for the component.
	 *
	 * @since   12.2
	 */
	protected function canDelete($record)
	{
		$canDelete = TmtHelper::canManageQuestion($record->id, null, $record->created_by);

		return $canDelete;
	}

	/**
	 * Method to check whether a question is attempted in any test.
	 *
	 * @param   integer  $questionId  Question Id
	 *
	 * @return  boolean  True if question is attempted.
	 *
	 * @since   1.3.5
	 */
	public function isQuestionAttempted($questionId)
	{
		$query = $this->_db->getQuery(true);

		$query->select($this->_db->quoteName(array('ta.id')));
		$query->from($this->_db->quoteName('#__tmt_tests_answers', 'ta'));
		$query->where($this->_db->quoteName('ta.question_id') . ' = ' . (int) $questionId);
		$query->setLimit('1');
		$this->_db->setQuery($query);

		if (!empty($this->_db->loadResult()))
		{
			return true;
		}

		return false;
	}

	/**
	 * Method to upload the file (image/PDF/Audio)
	 *
	 * @param   ARRAY|Object  $files   fileData
	 *
	 * @param   Integer       $access  access for uploading
	 *
	 * @return	array
	 *
	 * @since   2.0
	 */
	public function uploadFile($files, $access = null)
	{
		$mediaMaxSize          = $this->tjLmsParams->get('tjlms_media_size', '2');
		$mediaMimeType         = $this->tjLmsParams->get('tjlms_media_mime_type', '');
		$mediaAllowedExtension = $this->tjLmsParams->get('tjlms_media_extension', '');

		if (!empty($mediaAllowedExtension))
		{
			$mediaAllowedExtension = array_map('trim', explode(',', $mediaAllowedExtension));
			$mediaAllowedExtension = array_map('strtolower', $mediaAllowedExtension);
		}

		if (!empty($mediaMimeType))
		{
			$mediaMimeType = array_map('trim', explode(',', $mediaMimeType));
		}
		else
		{
			$mediaMimeType = $this->defaultMimeTypes;
		}

		$config                     = array();
		$config['saveData']         = 1;
		$config['state']            = '0';
		$config['size']             = $mediaMaxSize;
		$config['type']             = $mediaMimeType;
		$config['allowedExtension'] = $mediaAllowedExtension;

		if ($access !== null)
		{
			$config['auth'] = $access;
		}

		$config['imageResizeSize']                            = array();
		$config['imageResizeSize']['small']['small_width']    = $this->tjLmsParams->get('small_width', '128');
		$config['imageResizeSize']['small']['small_height']   = $this->tjLmsParams->get('small_height', '128');
		$config['imageResizeSize']['medium']['medium_width']  = $this->tjLmsParams->get('medium_width', '240');
		$config['imageResizeSize']['medium']['medium_height'] = $this->tjLmsParams->get('medium_height', '240');
		$config['imageResizeSize']['large']['large_width']    = $this->tjLmsParams->get('large_width', '400');
		$config['imageResizeSize']['large']['large_height']   = $this->tjLmsParams->get('large_height', '400');

		$mediaLib   = TJMediaStorageLocal::getInstance($config);

		$returnData = $mediaLib->upload($files);

		if (!$returnData)
		{
			$this->setError(Text::_($mediaLib->getError()));

			return false;
		}
		else
		{
			return $returnData;
		}
	}

	/**
	 * Method to upload video URL
	 *
	 * @param   array  $data  post data
	 *
	 * @return	array
	 *
	 * @since   2.0
	 */
	public function uploadLink($data)
	{
		$config   = array();
		$mediaLib = TJMediaStorageLocal::getInstance($config);

		return $mediaLib->uploadLink($data);
	}

	/**
	 * Method to check media xref existence
	 *
	 * @param   INT     $clientId  clientId
	 *
	 * @param   STRING  $client    client
	 *
	 * @return	array
	 *
	 * @since   2.0
	 */
	public function checkMediaXrefExistence($clientId, $client)
	{
		$tjmediaXrefTable = Table::getInstance('Xref', 'TJMediaTable');

		$tjmediaXrefTable->load(array('client_id' => (int) $clientId, 'client' => $client));

		return $tjmediaXrefTable;
	}

	/**
	 * Method to save media xref
	 *
	 * @param   INT     $mediaId    mediaId
	 *
	 * @param   INT     $clientId   clientId
	 *
	 * @param   STRING  $client     client
	 *
	 * @param   INT     $isGallery  isGallery
	 *
	 * @return	object
	 *
	 * @since   2.0
	 */
	public function saveMediaXref($mediaId, $clientId, $client, $isGallery = 0)
	{
		$tjmediaXrefTable = $this->checkMediaXrefExistence($clientId, $client);

		if (!empty($tjmediaXrefTable->id))
		{
			// Delete existing xref
			$oldMediaId = $tjmediaXrefTable->media_id;

			$tjmediaXrefTable->delete();

			// Check if any other xref media not present then delete media file also
			$oldTjmediaXrefTable = Table::getInstance('Xref', 'TJMediaTable');
			$oldTjmediaXrefTable->load(array('media_id' => $oldMediaId));

			if (!$oldTjmediaXrefTable->id)
			{
				$storagePath = TJMediaStorageLocal::getInstance();

				$filetable   = Table::getInstance('Files', 'TJMediaTable');

				// Load the object based on the id or throw a warning.
				$filetable->load($oldMediaId);

				$mediaConfig = array('id' => $oldMediaId, 'uploadPath' => $storagePath->mediaUploadPath);

				$mediaLib    = TJMediaStorageLocal::getInstance($mediaConfig);

				if ($mediaLib->id)
				{
					if (!$mediaLib->delete())
					{
						$this->setError(Text::_($mediaLib->getError()));

						return false;
					}
				}
				else
				{
					$this->setError(Text::_($mediaLib->getError()));

					return false;
				}
			}
		}

		$mediaXref               = array();
		$mediaXref['id']         = '';
		$mediaXref['media_id']   = $mediaId;
		$mediaXref['client_id']  = $clientId;
		$mediaXref['client']     = $client;
		$mediaXref['is_gallery'] = $isGallery;

		$mediaModelXref          = TJMediaXref::getInstance();

		$mediaModelXref->bind($mediaXref);
		$mediaModelXref->save();
	}

	/**
	 * Method to video URL Host
	 *
	 * @param   string  $url  Video url
	 *
	 * @return	array
	 *
	 * @since   2.0
	 */
	public function getVideoHost($url)
	{
		if (!empty($url))
		{
			return parse_url($url);
		}
	}

	/**
	 * Method to get a media details.
	 *
	 * @param   INT  $clientId    event id
	 *
	 * @param   INT  $clientName  clientName
	 *
	 * @param   INT  $isGallery   isGallery
	 *
	 * @return Array.
	 *
	 * @since	2.0
	 */
	public function getMediaDetails($clientId, $clientName, $isGallery = 0)
	{
		$db    = Factory::getDbo();
		$query = $db->getQuery(true);
		$query->select($db->qn(array('xref.id', 'xref.media_id', 'mf.source', 'mf.original_filename', 'mf.type')));
		$query->from($db->qn('#__tj_media_files_xref', 'xref'));
		$query->join('INNER', $db->qn('#__tj_media_files', 'mf') . ' ON (' . $db->qn('xref.media_id') . ' = ' . $db->qn('mf.id') . ')');
		$query->where($db->qn('xref.client_id') . '=' . (int) $clientId);
		$query->where($db->qn('xref.is_gallery') . '=' . (int) $isGallery);
		$query->where($db->qn('xref.client') . '=' . $db->quote($clientName));
		$db->setQuery($query);

		return $db->loadObject();
	}

	/**
	 * Method delete the file and the record from the table
	 *
	 * @param   Integer  $mediaId      media Id of files table
	 *
	 * @param   STRING   $storagePath  file path from params in config
	 *
	 * @param   STRING   $client       client(example -'tjlms.question')
	 *
	 * @param   Integer  $clientId     clientId
	 *
	 * @return	boolean
	 *
	 * @since   2.4.0
	 */
	public function deleteMedia($mediaId, $storagePath, $client, $clientId)
	{
		$tableXref = Table::getInstance('Xref', 'TJMediaTable');

		$checkMediaXrefExistForClient = 0;

		// User allowed to delete only self added media. Check here media is present.
		if (!empty($clientId) && !empty($client) && !empty($mediaId))
		{
			$data = array('client_id' => $clientId, 'client' => $client, 'media_id' => $mediaId);
			$checkMediaXrefExistForClient = $tableXref->load($data);
		}

		// If the media is present against the client, then delete the media and record form xref.
		if ($checkMediaXrefExistForClient)
		{
			// Delete record form xref table
			if (!$tableXref->delete())
			{
				$this->setError(Text::_($tableXref->getError()));
			}
			else
			{
				// Check if any other xref media not present then delete media file also
				$tableXrefPresent = Table::getInstance('Xref', 'TJMediaTable');
				$tableXrefPresent->load(array('media_id' => $mediaId));

				if (!$tableXrefPresent->id)
				{
					$filetable = Table::getInstance('Files', 'TJMediaTable');

					// Load the object based on the id or throw a warning.
					$filetable->load($mediaId);

					$mediaConfig = array('id' => $mediaId, 'uploadPath' => $storagePath);

					$mediaLib = TJMediaStorageLocal::getInstance($mediaConfig);

					if ($mediaLib->id)
					{
						if (!$mediaLib->delete())
						{
							$this->setError(Text::_($mediaLib->getError()));

							return false;
						}
					}
					else
					{
						$this->setError(Text::_($mediaLib->getError()));

						return false;
					}
				}

				return true;
			}
		}
		else
		{
			$this->setError(Text::_('COM_TMT_QUESTION_MEDIA_DELETE_ERROR'));

			return false;
		}
	}

	/**
	 * Method to validate the form data.
	 *
	 * @param   \JForm  $form   The form to validate against.
	 * @param   Array   $data   The data to validate.
	 * @param   string  $group  The name of the field group to validate.
	 *
	 * @return  array|boolean  Array of filtered data if valid, false otherwise.
	 *
	 * @since   _DEPLOY_VERSION_
	 */
	public function validate($form, $data, $group = null)
	{
		if (!empty($data['id']) && !(is_numeric($data['id'])))
		{
			$this->setError(Text::_("COM_TMT_CSV_IMPORT_INVALID_ID_MSG"));

			if ($data['is_csv'])
			{
				Log::add(Text::sprintf('COM_TMT_CSV_IMPORT_INVALID_ID_MSG_LOG', $data['title'], $data['gradingtype']), Log::ERROR, 'com_tmt');
			}

			return false;
		}

		$questionType = array('radio', 'checkbox', 'textarea', 'text', 'file_upload', 'rating', 'objtext');

		if (!in_array($data['type'], $questionType))
		{
			$this->setError(Text::_("COM_TMT_QUESTION_TYPE_ERROR"));

			return false;
		}

		$gradingType = array('quiz', 'exercise', 'feedback');

		if (!in_array($data['gradingtype'], $gradingType))
		{
			$this->setError(Text::_("COM_TMT_QUESTION_GRADING_TYPE_ERROR"));

			return false;
		}

		$difficultyLevel = array('easy', 'medium', 'hard');

		if (!in_array($data['level'], $difficultyLevel))
		{
			$this->setError(Text::_("COM_TMT_QUESTION_DIFFICULTY_LEVEL_ERROR"));

			return false;
		}

		if (!empty($data['category_id']))
		{
			$db       = Factory::getDbo();
			$category = Table::getInstance('Category', 'JTable', array('dbo', $db));
			$category->load(array('id' => $data['category_id']));

			$catid = $category->id ? $category->id : 0;

			if (empty($catid))
			{
				$this->setError(Text::sprintf('COM_TMT_CSV_IMPORT_INVALID_CAT_MSG', htmlentities($data['category_id'])));

				if ($data['is_csv'])
				{
					Log::add(Text::sprintf('COM_TMT_CSV_IMPORT_INVALID_CAT_MSG', $data['category_id']), Log::ERROR, 'com_tmt');
				}

				return false;
			}
		}

		if ($data['marks'] <= 0 && $data['gradingtype'] == 'quiz')
		{
			$this->setError(Text::_("COM_TMT_CSV_IMPORT_QUESTION_MARKS_QUIZ_FIELD_EMPTY"));

			if ($data['is_csv'])
			{
				Log::add(Text::sprintf('COM_TMT_CSV_IMPORT_QUESTION_MARKS_QUIZ_FIELD_LOG', $data['title'], $data['gradingtype']), Log::ERROR, 'com_tmt');
			}

			return false;
		}

		if ($data['type'] == 'objtext')
		{
			$data['answers_text'] = array_map('trim', $data['answers_text']);

			if ($data['answers_text'] == "")
			{
				$this->setError(Text::_("COM_TMT_QUESTION_NO_ANSWER_ERROR"));

				return false;
			}
		}

		if (($data['type'] == 'radio' || $data['type'] == 'checkbox' || $data['type'] == 'rating') && empty($data['answers_text']))
		{
			$this->setError(Text::_("COM_TMT_CSV_IMPORT_FAIL_ANSWER"));

			if ($data['is_csv'])
			{
				Log::add(Text::sprintf('COM_TMT_CSV_IMPORT_FAIL_ANSWER_LOG', $data['title'], $data['gradingtype']), Log::ERROR, 'com_tmt');
			}

			return false;
		}

		if ($data['gradingtype'] == 'quiz' && $data['type'] == 'checkbox' && isset($data['answers_marks'])
			&& array_sum($data['answers_marks']) != $data['marks'])
		{
			$this->setError(Text::_("COM_TMT_CSV_IMPORT_FAIL_MARKS_MISMATCH"));

			if ($data['is_csv'])
			{
				Log::add(Text::sprintf('COM_TMT_CSV_IMPORT_FAIL_MARKS_MISMATCH_LOG', $data['title'], $data['gradingtype']), Log::ERROR, 'com_tmt');
			}

			return false;
		}

		if ($data['gradingtype'] == 'quiz' && $data['type'] == 'radio'
			&& isset($data['answers_marks']) && !in_array($data['marks'], $data['answers_marks']))
		{
			$this->setError(Text::_("COM_TMT_CSV_IMPORT_FAIL_MARKS_MISMATCH_MCQ"));

			if ($data['is_csv'])
			{
				Log::add(Text::sprintf('COM_TMT_CSV_IMPORT_FAIL_MARKS_MISMATCH_MCQ_LOG', $data['title'], $data['gradingtype']), Log::ERROR, 'com_tmt');
			}

			return false;
		}

		if ($data['gradingtype'] == 'quiz'
			&& count(array_filter(array($data['answers_iscorrect_hidden']))) != 0 && !in_array(1, $data['answers_iscorrect_hidden']))
		{
			$this->setError(Text::_("COM_TMT_QUESTION_NO_CORRECT_ANS_MSG"));

			if ($data['is_csv'])
			{
				Log::add(Text::sprintf('COM_TMT_QUESTION_NO_CORRECT_ANS_MSG_LOG', $data['title'], $data['gradingtype']), Log::ERROR, 'com_tmt');
			}

			return false;
		}

		if (isset($data['answers_text']) && count(array_unique($data['answers_text'])) < count($data['answers_text']))
		{
			$this->setError(Text::_("COM_TMT_CSV_IMPORT_FAIL_DUPLICATE_ANSWER"));

			if ($data['is_csv'])
			{
				Log::add(Text::sprintf('COM_TMT_CSV_IMPORT_FAIL_DUPLICATE_ANSWER_LOG', $data['title'], $data['gradingtype']), Log::ERROR, 'com_tmt');
			}

			return false;
		}

		if (($data['type'] == 'radio' || $data['type'] == 'checkbox' || $data['type'] == 'rating') && $data['gradingtype'] == 'quiz'
			&& !empty($data['answers_marks']) && !empty($data['answers_iscorrect_hidden']))
		{
			foreach ($data['answers_marks'] as $key => $value)
			{
				if (empty($data['answers_marks'][$key]) && !empty($data['answers_iscorrect_hidden'][$key] == 1))
				{
					$this->setError(Text::_("COM_TMT_Q_FORM_NO_MARK_FOR_CORRECT_ANSWER"));

					if ($data['is_csv'])
					{
						Log::add(Text::sprintf('COM_TMT_Q_FORM_NO_MARK_FOR_CORRECT_ANSWER_LOG', $data['title'], $data['gradingtype']), Log::ERROR, 'com_tmt');
					}

					return false;
				}

				if (!empty($data['answers_marks'][$key]) && !empty($data['answers_iscorrect_hidden'][$key] == 0))
				{
					$this->setError(Text::_("COM_TMT_QUESTION_MARKS_FOR_NOTCORRECT_ANSWER"));

					if ($data['is_csv'])
					{
						Log::add(Text::sprintf('COM_TMT_QUESTION_MARKS_FOR_NOTCORRECT_ANSWER_LOG', $data['title'], $data['gradingtype']), Log::ERROR, 'com_tmt');
					}

					return false;
				}
			}
		}

		if ($data['gradingtype'] == 'exercise' && !empty($data['marks']) && !empty($data['answers_iscorrect_hidden']))
		{
			$this->setError(Text::_("COM_TMT_QUESTION_GRADING_TYPE_EXERCISE_ANSWERS_MARKS_NOT_EMPTY"));

			if ($data['is_csv'])
			{
				Log::add(
					Text::sprintf('COM_TMT_QUESTION_GRADING_TYPE_EXERCISE_ANSWERS_MARKS_NOT_EMPTY_LOG', $data['title'], $data['gradingtype']),
					Log::ERROR, 'com_tmt'
				);
			}

			return false;
		}

		if ($data['type'] == 'rating')
		{
			if ($data['answers_text'][0] > $data['answers_text'][1])
			{
				$this->setError(Text::_("COM_TMT_QUESTION_RATING_TYPE_VALIDATION"));

				if ($data['is_csv'])
				{
					Log::add(Text::sprintf('COM_TMT_QUESTION_RATING_TYPE_VALIDATION_LOG', $data['title'], $data['gradingtype']), Log::ERROR, 'com_tmt');
				}

				return false;
			}

			// If spaces present in rating label then on frontend for rating type question number label not getting shown.
			//  To remove spaces of rating label we used below.
			$data['params']['rating_label'] = trim($data['params']['rating_label']);

			if (!empty($data['params']['rating_label']))
			{
				$ratingLables     = explode(',', $data['params']['rating_label']);
				$totalNoOfRatings = count(range($data['answers_text'][0], $data['answers_text'][1]));

				if ($totalNoOfRatings !== count($ratingLables))
				{
					$this->setError(Text::sprintf("COM_TMT_QUESTION_RATING_NO_OF_LABEL_ERROR", $db->htmlentities($totalNoOfRatings)));

					if ($data['is_csv'])
					{
						Log::add(Text::sprintf('COM_TMT_QUESTION_RATING_NO_OF_LABEL_ERROR', $totalNoOfRatings), Log::ERROR, 'com_tmt');
					}

					return false;
				}
			}
		}

		if (isset($data['mediaFiles']))
		{
			$mediaFileArray = $data['mediaFiles'];
		}

		if (isset($data['media_type']))
		{
			// Check Media Validation - Start
			if ($data['media_type'] == 'video' && $data['media_url'] != '')
			{
				$mediaUrlType = $this->getVideoHost($data['media_url']);

				$allowedMediaURlArray = array ("www.youtube.com", "youtu.be", "vimeo.com", "player.vimeo.com");

				if (!in_array($mediaUrlType['host'], $allowedMediaURlArray))
				{
					$this->setError(Text::_("COM_TMT_QUESTION_MEDIA_UPLOAD_ERROR"));

					return false;
				}
			}
			elseif ($data['media_type'] == 'file' || $data['media_type'] == 'image' || $data['media_type'] == 'audio')
			{
				if ($mediaFileArray['media_' . $data['media_type']]['error'] == UPLOAD_ERR_OK)
				{
					// Check for allowed file extension - start
					$mediaAllowedExtension = $this->tjLmsParams->get('tjlms_media_extension', '');

					if (!empty($mediaAllowedExtension))
					{
						$mediaAllowedExtension = array_map('trim', explode(',', $mediaAllowedExtension));
						$mediaAllowedExtension = array_map('strtolower', $mediaAllowedExtension);
					}

					$fileDetails = pathinfo($mediaFileArray['media_' . $data['media_type']]['name']);

					if (!isset($fileDetails['extension']) || !in_array(strtolower($fileDetails['extension']), $mediaAllowedExtension))
					{
						$this->setError(Text::_("COM_TMT_QUESTION_MEDIA_INVALID_FILE_TYPE_ERROR"));

						return false;
					}
					// Check for allowed file extension - end

					// Get legal image extensions
					$legalImageExtensions = $this->tjLmsParams->get('image_extensions', '');

					if (!empty($legalImageExtensions))
					{
						$legalImageExtensions = array_map('trim', explode(',', $legalImageExtensions));
					}
					else
					{
						$legalImageExtensions = $this->defaultImageExtensions;
					}

					$isImage = false;

					// Check if file is image
					if (in_array(strtolower($fileDetails['extension']), $legalImageExtensions))
					{
						$isImage = true;
					}

					// Check for allowed mime types - start
					$mediaMimeType = $this->tjLmsParams->get('tjlms_media_mime_type', '');

					if (!empty($mediaMimeType))
					{
						$mediaMimeType = array_map('trim', explode(',', $mediaMimeType));
					}
					else
					{
						$mediaMimeType = $this->defaultMimeTypes;
					}

					$mediaLibObj = TJMediaStorageLocal::getInstance();

					$getMimeType = $mediaLibObj->getMimeType($mediaFileArray['media_' . $data['media_type']]['tmp_name'], $isImage);

					if (!in_array($getMimeType, $mediaMimeType))
					{
						$this->setError(Text::_("COM_TMT_QUESTION_MEDIA_INVALID_FILE_TYPE_ERROR"));

						return false;
					}
				}
			}
		}

		return parent::validate($form, $data, $group);
	}

	/**
	 * Check if question is beign used.
	 *
	 * @param   object  $id  Question Id.
	 *
	 * @return  boolean  True/False.
	 *
	 * @since   1.3.31
	 */
	public function isUsed($id)
	{
		$db = Factory::getDBO();

		$query = $db->getQuery(true);

		$query->select($db->quoteName(array('tq.id')));
		$query->from($db->quoteName('#__tmt_tests_questions', 'tq'));
		$query->where($db->quoteName('tq.question_id') . ' = ' . (int) $id);
		$db->setQuery($query);
		$db->execute();

		$numRows = $db->getNumRows();

		return $numRows;
	}
}
