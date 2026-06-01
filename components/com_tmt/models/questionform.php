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
use Joomla\CMS\Object\CMSObject;

use Joomla\CMS\Factory;
use Joomla\CMS\Table\Table;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Filesystem\Path;
use Joomla\CMS\Filesystem\File;
use Joomla\Utilities\ArrayHelper;
use Joomla\CMS\Filesystem\Folder;
use Joomla\CMS\MVC\Model\FormModel;

/**
 * Tmt questionform model.
 *
 * @since  1.0
 */
class TmtModelQuestionForm extends FormModel
{
	public $item = null;

	public $withAnswers = 1;

	/**
	 * Method to auto-populate the model state.
	 *
	 * Note. Calling getState in this method will result in recursion.
	 *
	 * @return   void
	 *
	 * @since    1.6
	 */
	protected function populateState()
	{
		$app   = Factory::getApplication();
		$input = $app->input;

		// Load state from the request userState on edit or from the passed variable on default
		if ($input->get('view') == 'questionform')
		{
			$id = $input->get('id');
			$app->setUserState('com_tmt.edit.question.id', $id);
			$this->setState('question.id', $id);
		}
	}

	/**
	 * Method to get an ojbect.
	 *
	 * @param   integer  $id  The id of the object to get.
	 *
	 * @return  mixed  Object on success, false on failure.
	 */
	public function getItem($id = null)
	{
		if ($this->item === null)
		{
			$this->item = false;

			if (empty($id))
			{
				$id = $this->getState('question.id');
			}

			if (!$this->item)
			{
				// Get a level row instance.
				$table = $this->getTable();

				// Attempt to load the row.
				if ($table->load($id))
				{
					$user = Factory::getUser();

					// Get current record id - this is important
					$id = $table->id;

					// Convert the Table to a clean JObject.
					$properties = $table->getProperties(1);
					$this->item = ArrayHelper::toObject($properties, 'JObject');

					// Get answer options for this question
					if ($id && $this->withAnswers)
					{
						try
						{
							$db    = $this->getDbo();
							$query = $db->getQuery(true);
							$query->select('a.*');
							$query->from($db->quoteName('#__tmt_answers', 'a'));

							if ($this->getState('question.correct_answers') == 1)
							{
								$query->where($db->quoteName('a.is_correct') . " = 1");
							}

							$query->where($db->quoteName('a.question_id') . '=' . (int) $id);
							$query->order('a.order', 'ASC');
							$db->setQuery($query);
							$a_data = $db->loadObjectList();

							$this->item->answers = $a_data;
						}
						catch (Exception $e)
						{
							$this->setError($e->getMessage());

							return false;
						}
					}
				}
				elseif ($error = $table->getError())
				{
					$this->setError($error);
				}
			}
		}

		return $this->item;
	}

	/**
	 * Method to getTable
	 *
	 * @param   integer  $type    The id of the row to check out.
	 * @param   integer  $prefix  The id of the row to check out.
	 * @param   integer  $config  The id of the row to check out.
	 *
	 * @return  boolean  True on success, false on failure.
	 *
	 * @since   1.6
	 */
	public function getTable($type = 'Question', $prefix = 'TmtTable', $config = array())
	{
		$this->addTablePath(JPATH_ADMINISTRATOR . '/components/com_tmt/tables');

		return Table::getInstance($type, $prefix, $config);
	}

	/**
	 * Method to check in an item.
	 *
	 * @param   integer  $id  The id of the row to check out.
	 *
	 * @return  boolean  True on success, false on failure.
	 *
	 * @since   1.6
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
	 * @return  boolean  True on success, false on failure.
	 *
	 * @since  1.6
	 */
	public function checkout($id = null)
	{
		// Get the user id.
		$id = (!empty($id)) ? (int) $id : (int) $this->getState('question.id');

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
	 * @return  JForm   A JForm object on success, false on failure
	 *
	 * @since	1.6
	 */
	public function getForm($data = array(), $loadData = true)
	{
		// Get the form.
		$form = $this->loadForm('com_tmt.question', 'questionform', array('control' => 'jform', 'load_data' => $loadData));

		if (empty($form))
		{
			return false;
		}

		return $form;
	}

	/**
	 * Method to get the data that should be injected in the form.
	 *
	 * @return	mixed	The data for the form.
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
	 * @return  id
	 *
	 * @since	1.6
	 */
	public function save($data)
	{
		$db       = Factory::getDBO();
		$id       = (!empty($data['id'])) ? $data['id'] : (int) $this->getState('question.id');
		$state    = (!empty($data['state'])) ? 1 : 0;
		$user     = Factory::getUser();
		$q_id_del = $data['imgdel1'];

		// Del if img is removed from table
		if ($q_id_del)
		{
			$query = $db->getQuery(true);
			$query->delete($db->qn('#__tmt_questions_image'));
			$query->where($db->qn('q_id') . ' = ' . $db->q((int) $q_id_del));
			$db->setQuery($query);
			$db->execute();
		}

		$table = $this->getTable();

		// For new record.
		if (!$id)
		{
			// Add created time.
			$data['created_on'] = Factory::getDate()->toSql();

			// Publish new record.
			$data['state'] = 1;
		}

		// Save questions data
		if ($table->save($data) === true)
		{
			try
			{
				$count   = count($data['answers_text']);
				$m_files = $data['m_files1'];

				// Get current record id - this is important
				$id = $table->id;

				// Get all existing answer ids posted from form
				$form_answers = $data['answer_id_hidden'];
				$form_answers = implode(',', $db->q($form_answers));

				// Get all existing answer ids from db
				$query = $db->getQuery(true);
				$query->select('id')
					->from($db->qn(' #__tmt_answers'))
					->where($db->qn('question_id') . '=' . $db->q($id))
					->where(' id IN(' . $form_answers . ')');
				$db->setQuery($query);
				$existing_answers = $db->loadObjectList();

				$existing_answer_ids = array();

				// Convert it to #__tests_answers_id => #__tests_answers_id format
				foreach ($existing_answers as $ea)
				{
					$existing_answer_ids[$ea->id] = $ea->id;
				}

				// First, delete existing answer options which are removed by user
				$query = $db->getQuery(true);
				$query->delete($db->qn('#__tmt_answers'));
				$query->where($db->qn('question_id') . ' = ' . $db->q((int) $id));
				$query->where(' id NOT IN(' . $form_answers . ')');
				$db->setQuery($query);
				$db->execute();

				// First, delete existing answer img options which are removed by user
				$query = $db->getQuery(true);
				$query->delete($db->qn('#__tmt_answers_image'));
				$query->where($db->qn('q_id') . ' = ' . $db->q((int) $id));
				$query->where(' id NOT IN(' . $form_answers . ')');
				$db->setQuery($query);
				$db->execute();

				// Now, save all answer options
				for ($i = 0; $i < $count; $i++)
				{
					$updateFlag = 'insert';

					// Let's represent data as needed by answers table
					$newdata              = new stdClass;
					$newdata->id          = '';
					$newdata->question_id = $table->id;

					// Check if the current record is already present in db
					if (array_key_exists($data['answer_id_hidden'][$i], $existing_answer_ids))
					{
						$newdata->id = $data['answer_id_hidden'][$i];
						$updateFlag  = 'update';
						unset($data['answer_id_hidden'][$i]);

						if (($newdata->id) && (!empty($m_files[$i]['name'])))
						{
							self::upload_img($m_files[$i]['name'], $m_files[$i]['type'], $m_files[$i]['tmp_name'], $newdata->id, $newdata->question_id);
						}
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
						// Set answer text
						$newdata->answer = $data['answers_text'][$i];

						// Set ordering and correct answer for MCQs
						if ($data['type'] == 'radio' || $data['type'] == 'checkbox')
						{
							// Check if answers_marks array is posted
							if (isset($data['answers_marks']))
							{
								$newdata->marks = $data['answers_marks'][$i];
							}
							else
							{
								$newdata->marks = 0;
							}

							$newdata->is_correct = $data['answers_iscorrect_hidden'][$i];
							$newdata->order      = $i++;
						}
						else
						{
							$newdata->marks = 0;

							// @TODO might need to change values here to NULL
							$newdata->is_correct = 0;
							$newdata->order      = 0;
						}
					}

					switch ($updateFlag)
					{
						case 'insert':
							if (!$db->insertObject('#__tmt_answers', $newdata, 'id'))
							{
								echo $db->stderr();

								return false;
							}

							$a_id = $db->insertid();

							if (($a_id) && (!empty($m_files[$i]['name'])))
							{
								self::upload_img($m_files[$i]['name'], $m_files[$i]['type'], $m_files[$i]['tmp_name'], $a_id, $newdata->question_id);
							}
						break;

						case 'update':
							if (!$db->updateObject('#__tmt_answers', $newdata, 'id'))
							{
								echo $db->stderr();

								return false;
							}
						break;
					}
				}

				// Return question id
				return $id;
			}
			catch (Exception $e)
			{
				$this->setError($e->getMessage());

				return false;
			}
		}
		else
		{
			return false;
		}
	}

	/**
	 * Method to upload an image against question
	 *
	 * @param   integer  $img_name      The id of the row to check out.
	 * @param   integer  $img_type      The id of the row to check out.
	 * @param   integer  $img_tmp_name  The id of the row to check out.
	 * @param   integer  $id            The id of the row to check out.
	 * @param   integer  $question_id   The id of the row to check out.
	 *
	 * @return	boolean		True on success, false on failure.
	 *
	 * @since	1.6
	 */
	public function upload_img($img_name, $img_type, $img_tmp_name, $id, $question_id)
	{
		$allowed = array('png', 'jpg', 'jpeg');
		$ext     = pathinfo($img_name, PATHINFO_EXTENSION);
		$db      = Factory::getDBO();

		if (in_array($ext, $allowed))
		{
			if (strpos($img_type, 'image') !== false)
			{
				if ($img_name != null)
				{
					if (!Folder::exists(JPATH_SITE . '/media/com_tmt/a_image/' . $id))
					{
						Folder::create(JPATH_SITE . '/media/com_tmt/a_image/' . $id);
					}

					$filename = rand() . '_' . $img_name;
					$filepath = Path::clean(JPATH_SITE . '/media/com_tmt/a_image/' . $id . '/' . $filename);

					// Do the upload
					jimport('joomla.filesystem.file');

					if (File::upload($img_tmp_name, $filepath))
					{
						try
						{
							$query = $db->getQuery(true);
							$query->select('id')
								->from($db->qn(' #__tmt_answers_image'))
								->where($db->qn('a_id') . '=' . $db->q((int) $id));
							$db->setQuery($query);

							if ($res = $db->loadResult())
							{
								$answers_image = new stdClass;

								// Must be a valid primary key value.
								$answers_image->id        = $res;
								$answers_image->img_title = $img_name;
								$answers_image->img_path  = 'media/com_tmt/a_image/' . $id . '/' . $filename;
								$db->updateObject('#__tmt_answers_image', $answers_image, 'id');
							}
							else
							{
								$answers_image            = new stdClass;
								$answers_image->id        = null;
								$answers_image->a_id      = (int) $id;
								$answers_image->q_id      = (int) $question_id;
								$answers_image->img_title = $img_name;
								$answers_image->img_path  = 'media/com_tmt/a_image/' . $id . '/' . $filename;
								$db->insertObject('#__tmt_answers_image', $answers_image);
							}
						}
						catch (Exception $e)
						{
							$this->setError($e->getMessage());

							return false;
						}
					}
					else
					{
						// Redirect and throw an error message
						$app->enqueueMessage(Text::_('ERROR_IN_UPLOAD'), 'error');
					}
				}
			}
		}
		else
		{
			$app               = Factory::getApplication();
			$tmtFrontendHelper = new tmtFrontendHelper;
			$itemid            = $tmtFrontendHelper->getItemId('index.php?option=com_tmt&view=questions');
			$app->enqueueMessage(Text::_('COM_TMT_ITEM_WRONG_IMG_FORMAT'), 'warning');
			$app->redirect('index.php?option =com_tmt&view=questionform&id=' . $question_id . '&Itemid=' . $itemid);
		}
	}

	/**
	 * Method to check no of files user has uploaded against a file upload type quetion
	 *
	 * @param   INT  $ltId  the id of the lesson track row
	 * @param   INT  $qId   the id of question id
	 *
	 * @return	INT
	 *
	 * @since	DEPLOY_VERSION
	 */
	public function getNoOfFilesuploadedforAns($ltId, $qId)
	{
		$query = $this->_db->getQuery(true);
		$query->select('answer')
			->from(' #__tmt_tests_answers')
			->where(' invite_id =' . (int) $ltId)
			->where(' question_id =' . (int) $qId);

		$this->_db->setQuery($query);
		$ans = $this->_db->loadResult();

		if (!empty($ans))
		{
			$answerArray = json_decode($ans);

			return count($answerArray);
		}

		return 0;
	}
}
