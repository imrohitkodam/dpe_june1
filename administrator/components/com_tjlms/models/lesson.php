<?php
/**
 * @version    SVN: <svn_id>
 * @package    Com_Tjlms
 * @copyright  Copyright (C) 2005 - 2014. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * Shika is free software. This version may have been modified pursuant
 * to the GNU General Public License, and as distributed it includes or
 * is derivative of works licensed under the GNU General Public License or
 * other free or open source software licenses.
 */

// No direct access
defined('_JEXEC') or die;
use Joomla\CMS\MVC\Model\AdminModel;
use Joomla\CMS\Factory;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Table\Table;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Form\Form;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Filesystem\Folder;
use Joomla\CMS\Filesystem\File;

jimport('joomla.application.component.modeladmin');
jimport('joomla.filesystem.folder');
jimport('techjoomla.common');

JLoader::import("/techjoomla/media/storage/local", JPATH_LIBRARIES);
JLoader::import("/techjoomla/media/xref", JPATH_LIBRARIES);
JLoader::import("/techjoomla/media/tables/files", JPATH_LIBRARIES);
JLoader::import("/techjoomla/media/tables/xref", JPATH_LIBRARIES);

/**
 * Tjlms model.
 *
 * @since  1.0.0
 */
class TjlmsModelLesson extends AdminModel
{
	protected $text_prefix = 'COM_TJLMS';

	public $lessonImageClient = 'tjlms.lesson';

	public $defaultMimeTypes = array(
		'image/jpeg',
		'image/gif',
		'image/png',
	);

	public $defaultImageExtensions = array ('gif', 'jpg', 'png');

	protected $event_after_delete = 'onAfterLessonDelete';

	/**
	 * Constructor.
	 *
	 * @see     JControllerLegacy
	 *
	 * @since   1.0.0
	 *
	 * @throws  Exception
	 */

	public function __construct()
	{
		JLoader::register('TjlmsHelper', JPATH_ADMINISTRATOR . '/components/com_tjlms/helpers/tjlms.php');
		JLoader::load('TjlmsHelper');

		$this->tjlmsHelper   = new TjlmsHelper;
		$this->tjlmsdbhelper = new tjlmsdbhelper;
		$this->ComtjlmsHelper   = new ComtjlmsHelper;
		$this->techjoomlacommon = new TechjoomlaCommon;
		$this->user = Factory::getUser();
		$this->tjLmsParams = ComponentHelper::getParams('com_tjlms');
		parent::__construct();
	}

	/**
	 * Returns a reference to the a Table object, always creating it.
	 *
	 * @param   type    $type    The table type to instantiate
	 * @param   string  $prefix  A prefix for the table class name. Optional.
	 * @param   array   $config  Configuration array for model. Optional.
	 *
	 * @return    JTable    A database object
	 *
	 * @since    1.6
	 */
	public function getTable($type = 'Lesson', $prefix = 'TjlmsTable', $config = array())
	{
		$this->addTablePath(JPATH_ADMINISTRATOR . '/components/com_tjlms/tables');

		return Table::getInstance($type, $prefix, $config);
	}

	/**
	 * Method to auto-populate the model state.
	 * Note. Calling getState in this method will result in recursion.
	 *
	 * @param   String  $ordering   optional Ordering
	 *
	 * @param   String  $direction  optional direction
	 *
	 * @return  void
	 *
	 * @since	1.0
	 */
	protected function populateState($ordering = null, $direction = null)
	{
		// Initialise variables.
		$app = Factory::getApplication('administrator');

		// Load the filter state.
		$search = $app->getUserStateFromRequest('com_tjlms.lesson' . '.filter.search', 'filter_search');
		$this->setState('filter.search', $search);

		$published = $app->getUserStateFromRequest('com_tjlms.lesson' . '.filter.state', 'filter_published', '', 'string');
		$this->setState('filter.state', $published);

		// List state information.
		parent::populateState($ordering, $direction);
	}

	/**
	 * Method to get the record form.
	 *
	 * @param   array    $data      An optional array of data for the form to interogate.
	 * @param   boolean  $loadData  True if the form is to load its own data (default case), false if not.
	 *
	 * @return    JForm    A JForm object on success, false on failure
	 *
	 * @since    1.0.0
	 */
	public function getForm($data = array(), $loadData = true)
	{
		// Get the form.
		Form::addFormPath(JPATH_ADMINISTRATOR . '/components/com_tjlms/models/forms');
		Form::addFieldPath(JPATH_ADMINISTRATOR . '/components/com_tjlms/models/fields');

		// Initialise variables.
		$app = Factory::getApplication();

		// Get the form.
		$form = $this->loadForm('com_tjlms.lesson', 'lesson', array(
				'control' => 'jform',
				'load_data' => $loadData
				)
			);

	// DPE - Hack start for frontend lesson form

		if ($app->isClient('site'))
		{
			$form->setFieldAttribute('catid', 'required', 'false');
			$form->setFieldAttribute('attempts_grade', 'default', 3);
		}

		// DPE - Hack end for frontend lesson form

		if (empty($form))
		{
			return false;
		}

		return $form;
	}

	/**
	 * Allows preprocessing of the JForm object.
	 *
	 * @param   JForm   $form   The form object
	 * @param   array   $data   The data to be merged into the form object
	 * @param   string  $group  The plugin group to be executed
	 *
	 * @return  void
	 *
	 * @since   3.0
	 */
	protected function preprocessForm(Form $form, $data, $group = 'content')
	{
		$post = Factory::getApplication()->input->post;
		$dataArray = $post->get('jform', '', 'ARRAY');
		$format = '';

		if (is_array($dataArray) && !empty($dataArray) && array_key_exists('gradingtype', $dataArray))
		{
			$format = $dataArray['gradingtype'];
		}

		if ($data->id)
		{
			$format = $data->format;
		}

		if ($format == 'exercise')
		{
			$form->loadFile(JPATH_ADMINISTRATOR . '/components/com_tjlms/models/forms/assessment.xml', true);

			if (!empty($data->set_id))
			{
				$data->add_assessment = 1;
				$form->setValue('add_assessment', 1);
			}
		}

		$form->setFieldAttribute('total_marks', 'readonly', 'true');
		// Interaction option here - DPE - Hack - Will get merge into core soon
		// Get plugin jlike_tjlmslesson of type 'content'
		$jlikeTjlmsLesson = PluginHelper::getPlugin('content', 'jlike_tjlmslesson');

		if (!empty($jlikeTjlmsLesson))
		{
			$form->loadFile(JPATH_ROOT . '/plugins/content/jlike_tjlmslesson/forms/jlike_tjlmslesson.xml', true);
		}

		parent::preprocessForm($form, $data, $group);
	}

	/**
	 * Function used to get all sub formats
	 *
	 * @param   string  $format_name  name of the format
	 *
	 * @return select list of sub formats
	 *
	 * @since  1.0.0
	 */
	public function getallSubFormats($format_name)
	{
		$format_name = 'tj' . $format_name;
		PluginHelper::importPlugin($format_name);
		$formats = Factory::getApplication()->triggerEvent('onGetSubFormat_' . $format_name . 'ContentInfo');

		if (!empty($formats))
		{
			return $formats;
		}
		else
		{
			return '';
		}

		$subformat = array();

		foreach ($formats as $format)
		{
			$subformat[] = JHTML::_('select.option', $format['id'], $format['name']);
		}

		$subformat_options = JHTML::_('select.genericlist', $subformat, "lesson_format[" . $format_name . "_subformat]",
							'class="class_' . $format_name . '_subformat" onchange="getVideosubFormat(this);"', "value", "text");

		return $subformat_options;
	}

	/**
	 * Method to get the data that should be injected in the form.
	 *
	 * @return   mixed  The data for the form.
	 *
	 * @since   1.0.0
	 */
	protected function loadFormData()
	{
		// Check the session for previously entered form data.
		$data = Factory::getApplication()->getUserState('com_tjlms.edit.lesson.data', array());

		if (empty($data))
		{
			$data = $this->getItem();
		}

		return $data;
	}

	/**
	 * Method to get a single record.
	 *
	 * @param   integer  $pk  The id of the primary key.
	 *
	 * @return   mixed  Object on success, false on failure.
	 *
	 * @since  1.0.0
	 */
	public function getItem($pk = null)
	{	
		// DPE hack as its automatically takes the default menu's id 
		 

		if (empty($pk) && ($this->getState($this->getName() . '.id') == Factory::getApplication()->getMenu()->getDefault()->query['id']))
		{
		   $this->setState('lessonform.id', 0);
		}

		if ($item = parent::getItem($pk))
		{
			// Do any procesing on fields here if needed
			if ($item->id)
			{
				if (!empty($item->media_id))
				{
					$queryForFormat = $this->_db->getQuery(true);
					$queryForFormat->select($this->_db->qn(array('m.sub_format','m.format', 'm.source','m.org_filename')));
					$queryForFormat->from($this->_db->qn('#__tjlms_media', 'm'));
					$queryForFormat->join('LEFT', $this->_db->qn('#__tjlms_lessons', 'l') . '
					ON(' . $this->_db->qn('l.media_id') . '=' . $this->_db->qn('m.id') . ')');
					$queryForFormat->where($this->_db->qn('l.id') . ' = ' . $this->_db->q((int) $item->id));

					$this->_db->setQuery($queryForFormat);
					$res = $this->_db->loadObject();

					$item->sub_format = $item->format = '';

					if (!empty($res))
					{
						$plg_type = 'tj' . $res->format;

						$format_subformat = explode('.', $res->sub_format);
						$plg_name = $format_subformat[0];

						PluginHelper::importPlugin($plg_type);
						$checkFormat = Factory::getApplication()->triggerEvent('onAdditional' . $plg_name . 'FormatCheck', array($item->id, $res));

						if (!empty($checkFormat[0]))
						{
							$format_res = $checkFormat[0];

							if ($format_res)
							{
								$item->sub_format = $format_res->sub_format;
								$item->format = $format_res->format;
								$item->source = $format_res->source;
								$item->org_filename = $format_res->org_filename;
							}
						}
					}
				}

				// DPE Hack start
				$query = $this->_db->getQuery(true);
				$query->select($this->_db->qn('params'));
				$query->from($this->_db->qn('#__jlike_content'));
				$query->where($this->_db->qn('element_id') . ' = ' . $this->_db->q((int) $item->id));
				$this->_db->setQuery($query);

				$results = $this->_db->loadObjectList();

				foreach ($results as $row)
				{
					if (!empty($row->params))
					{
						$item->interaction = json_decode($row->params, true); // Convert JSON to associative array
					}
				}
				// DPE Hack end

				$params = ComponentHelper::getParams('com_tjlms');
				$allowAssocFiles = $params->get('allow_associate_files', '0', 'INT');

				if ($allowAssocFiles == 1)
				{
					$query = $this->_db->getQuery(true);
					$query->select($this->_db->qn('f.org_filename', 'filename'));
					$query->select($this->_db->qn(array('af.id', 'af.media_id', 'f.source' , 'f.storage')));
					$query->from($this->_db->qn('#__tjlms_media', 'f'));
					$query->join('INNER', $this->_db->qn('#__tjlms_associated_files', 'af') . '
					ON (' . $this->_db->qn('af.media_id') . ' = ' . $this->_db->qn('f.id') . ')');
					$query->where($this->_db->qn('af.lesson_id') . ' = ' . $this->_db->q((int) $item->id));

					$this->_db->setQuery($query);
					$item->oldAssociateFiles = $this->_db->loadObjectList();

					if (!empty($item->oldAssociateFiles))
					{
						JLoader::import('components.com_tjlms.libraries.storage', JPATH_SITE);
						$tjStorage = new Tjstorage;

						foreach ($item->oldAssociateFiles as $key => $assocFile)
						{
							if ($assocFile->storage == 'invalid')
							{
								unset($item->oldAssociateFiles[$key]);

								continue;
							}

							$storage 	= $tjStorage->getStorage($assocFile->storage);
							$fileExists = $storage->exists('media/com_tjlms/lessons/' . $assocFile->source);

							if (!$fileExists)
							{
								unset($item->oldAssociateFiles[$key]);
							}
						}
					}
				}

				$query = $this->_db->getQuery(true);
				$query->select('max(attempt) as total');
				$query->from('#__tjlms_lesson_track as lt');
				$query->leftjoin('#__tjlms_lessons as l ON l.id = lt.lesson_id');
				$query->where('lesson_id  = ' . $item->id);
				$this->_db->setQuery($query);
				$item->max_attempt = $this->_db->loadResult();

				$query = $this->_db->getQuery(true);
				$query->select('*');
				$query->from($this->_db->qn('#__tjlms_media'));
				$query->where($this->_db->qn('id') . ' = ' . $this->_db->q((int) $item->media_id));

				$this->_db->setQuery($query);
				$media = $this->_db->loadAssoc();
				$item->media = $media;

				// Get Assessment added against lesson
				require_once JPATH_ADMINISTRATOR . '/components/com_tjlms/models/assessment.php';
				$assessModel = BaseDatabaseModel::getInstance('Assessment', 'TjlmsModel');
				$assessment = $assessModel->getLessonItem($item->id);

				if ($assessment)
				{
					$item->set_id = $assessment->id;
					$item->assessment_title = $assessment->assessment_title;
					$item->assessment_attempts = $assessment->assessment_attempts;
					$item->assessment_attempts_grade = $assessment->assessment_attempts_grade;
					$item->assessment_answersheet = $assessment->assessment_answersheet;
					$item->answersheet_options = $assessment->answersheet_options;
					$item->assessment_params = $assessment->assessment_params;
					$item->assessment_student_name = $assessment->assessment_student_name;

					$subQuery = $this->_db->getQuery(true);
					$subQuery->select('lt.id');
					$subQuery->from($this->_db->quoteName('#__tjlms_lesson_track', 'lt'));
					$subQuery->where($this->_db->quoteName('lt.lesson_id') . " = " . (int) $item->id);

					$query = $this->_db->getQuery(true);
					$query->select('count(*)');
					$query->from($this->_db->quoteName('#__tjlms_assessment_reviews', 'ar'));
					$query->where('ar.lesson_track_id IN(' . $subQuery . ')');
					$query->where($this->_db->quoteName('ar.review_status') . " = " . 1);
					$query->group('ar.lesson_track_id');
					$query->order('count(*)', 'DESC');
					$query->setLimit(1);
					$this->_db->setQuery($query);
					$item->livetrackReviews = (int) $this->_db->loadResult();
				}
				// DPE - Hack This will get in core soon
				// Get lesson interaction data
				$jlikeTjlmslessonPlg = PluginHelper::getPlugin('content', 'jlike_tjlmslesson');

				if ($jlikeTjlmslessonPlg)
				{
					
					PluginHelper::importPlugin('content');
					$lessonInteractionData = Factory::getApplication()->triggerEvent('getLessonInteractionData', array($item->id));

					if (!empty($lessonInteractionData))
					{
						foreach ($lessonInteractionData[0] as $key => $lessonInteraction)
						{
							$item->{$key}     = $key;
						}
					}
				}

				if ($item->image)
				{
					try
					{
						$uploadPath = $this->tjLmsParams->get('lesson_image_upload_path', "/images/com_tjlms/lessons/");
						$mediaObj = TJMediaStorageLocal::getInstance(array("id" => $item->image ,"uploadPath" => $uploadPath));
						$item->image = $mediaObj->media;
					}
					catch (\Exception $e)
					{
						// If we have any kind of error here => false;
					}
				}
			}
		}

		return $item;
	}

	/**
	 * Prepare and sanitise the table prior to saving.
	 *
	 * @param   ARRAY  $table  Table instance
	 *
	 * @return  void
	 *
	 * @since    1.6
	 */
	protected function prepareTable($table)
	{
		jimport('joomla.filter.output');

		if (empty($table->id))
		{
			// Set ordering to the last item if not set
			if (@$table->ordering === '')
			{
				$query = $this->_db->getQuery(true);
				$query->select('MAX(ordering)');
				$query->from($this->_db->qn('#__tjlms_lessons'));

				$max             = $this->_db->loadResult();
				$table->ordering = $max + 1;
			}
		}
	}

	/**
	 * Method to lessons along with course ID and module ID
	 *
	 * @param   ARRAY  $data  Validated lesson jform data Array
	 *
	 * @return  INT
	 *
	 * @since    1.0.0
	 **/
	public function save($data)
	{
		$table = $this->getTable();
		$key   = $table->getKeyName();

		if (!empty($data['image']))
		{
			$files['image'] = $data['image'];
		}

		if (!$data['id'])
		{
			$data['created_by'] = $this->user->id;
			$data['short_desc'] = !empty($data['short_desc']) ? $data['short_desc'] : '';
			$data['description'] = !empty($data['description']) ? $data['description'] : '';
			$data['media_id'] = !empty($data['media_id']) ? $data['media_id'] : '0';
			$data['total_marks'] = !empty($data['total_marks']) ? $data['total_marks'] : '0';
			$data['passing_marks'] = !empty($data['passing_marks']) ? $data['passing_marks'] : '0';
			$data['free_lesson'] = !empty($data['free_lesson']) ? $data['free_lesson'] : '0';
			$data['ordering'] = !empty($data['ordering']) ? $data['ordering'] : '0';
			$data['checked_out_time'] = !empty($data['checked_out_time']) ? $data['checked_out_time'] : '0000-00-00 00:00:00';
			$data['storage'] = !empty($data['storage']) ? $data['storage'] : '';
			$data['ideal_time_config'] = !empty($data['ideal_time_config']) ? $data['ideal_time_config'] : 0;
			$data['catid'] = !empty($data['catid']) ? $data['catid'] : 0;
			$data['no_of_attempts'] = !empty($data['no_of_attempts']) ? $data['no_of_attempts'] : 0;
			$data['attempts_grade'] = !empty($data['attempts_grade']) ? $data['attempts_grade'] : 0;
			$data['consider_marks'] = !empty($data['consider_marks']) ? $data['consider_marks'] : 1;
			$data['eligibility_criteria'] = !empty($data['eligibility_criteria']) ? $data['eligibility_criteria'] : 0;
			$data['params'] = !empty($data['params']) ? $data['params'] : '';
		}

		if (empty($data['start_date']))
		{
			$data['start_date'] = '0000-00-00 00:00:00';
		}

		if (empty($data['end_date']))
		{
			$data['end_date'] = '0000-00-00 00:00:00';
		}

		if (empty($data['checked_out']))
		{
			$data['checked_out'] = '0';
		}

		if (!empty($data['image']) && (!$data['image']['error'] || $data['image']['error'] == UPLOAD_ERR_OK))
		{
			$imageDetails = pathinfo($data['image']['name']);

			// Get legal image extensions
			$legalImageExtensions = $this->tjLmsParams->get('tjlms_image_extension', '');

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
			if (in_array($imageDetails['extension'], $legalImageExtensions))
			{
				$isImage = true;
			}

			// Check for allowed mime types - start
			$imageMimeType = $this->tjLmsParams->get('tjlms_image_mime_type', '');

			if (!empty($imageMimeType))
			{
				$imageMimeType = array_map('trim', explode(',', $imageMimeType));
			}
			else
			{
				$imageMimeType = $this->defaultMimeTypes;
			}

			$mediaLibObj = TJMediaStorageLocal::getInstance();

			$getMimeType = $mediaLibObj->getMimeType($data['image']['tmp_name'], $isImage);

			if (!in_array($getMimeType, $imageMimeType))
			{
				$this->setError(Text::_("COM_TMT_QUESTION_MEDIA_INVALID_FILE_TYPE_ERROR"));

				return false;
			}
			// Check for allowed mime types - end
		}
		
		if (empty($data['id']))
		{
			$data['image'] = '';
		}
		
		if (parent::save($data))
		{
			$id = (!empty($data['id'])) ? $data['id'] : (int) $this->setState($this->getName() . '.id', $table->$key);

			if (!empty($files['image']) && (!$files['image']['error'] || $files['image']['error'] == UPLOAD_ERR_OK))
			{
				$imageFile[] = $files['image'];
				$uploadFolder = JPATH_SITE . $this->tjLmsParams->get('lesson_image_upload_path', "/images/com_tjlms/lessons/");

				$maxSize = $this->tjLmsParams->get('lesson_upload_size');
				$config = array("client_id" => $id, "client" => $this->lessonImageClient, "uploadPath" => $uploadFolder, "size" => $maxSize);

				BaseDatabaseModel::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_tjlms/models');
				$mediaModel = BaseDatabaseModel::getInstance('Media', 'TjlmsModel');
				$uploadedMedia = $mediaModel->uploadImage($imageFile, null, $config);

				if (!$uploadedMedia)
				{
					$this->setError(Text::_($mediaModel->getError()));

					return false;
				}

				$data['id'] = $id;
				$data['image'] = $uploadedMedia['id'];
				parent::save($data);
			}
		}

		// DPE - Hack - Start - Check User Associated With Cluster or Not
		PluginHelper::importPlugin('system', 'dpe_tjlms_cluster');
		Factory::getApplication()->triggerEvent('onAfterLessonSave', array($id, $data));

		// DPE - Hack - End

		return $id;
	}

	/**
	 * Function to update lesson format
	 *
	 * Array
	 * (
	 * [video_subformat] => jwplayer
	 * [jwplayer] => Array
	 * (
	 * [subformatoption] => url
	 * [url] => sdfsd
	 * [upload] =>
	 * )
	 * [upload] =>
	 * [format] => video
	 * [format_id] => 0
	 * [id] => 23
	 * )
	 *
	 * @param   ARRAY  $format_data  Post Array
	 *
	 * @return  boolean
	 *
	 * @since  1.0.0
	 */
	public function updateformat($format_data)
	{
		$lesson_obj         = new stdclass;
		$format_data        = is_object($format_data) ? array($format_data) : $format_data;
		$lesson_obj->id     = $format_data['id'];
		$lesson_obj->format = $format_data['format'];

		$subfomatopt = $format_data['subformat'];

		$media_id = $format_data['media_id'];

		if (!empty($format_data[$subfomatopt]))
		{
			$subformatdata = $format_data[$subfomatopt];

			// If ($subformatdata['subformatoption'] != 'upload')
			{
				$option = $subformatdata['subformatoption'];

				$lessonformat_data             = new stdclass;
				$lessonformat_data->format     = $lesson_obj->format;
				$lessonformat_data->sub_format = $subfomatopt . '.' . $option;

				$formatsource = '';

				if (!empty($subformatdata[$option]))
				{
					$lessonformat_data->source = $formatsource = $subformatdata[$option];
				}

				if (!empty($subformatdata['params']))
				{
					$lessonformat_data->params     = $subformatdata['params'];
				}
				/*else
				{
					$lessonformat_data->params = !empty($format_data['params']) ? $format_data['params'] : ''; 
				}*/

				$lessonformat_data->org_filename = !empty($format_data['org_filename']) ? $format_data['org_filename'] : '';
				$lessonformat_data->saved_filename = !empty($format_data['saved_filename']) ? $format_data['saved_filename'] : ''; 
				$lessonformat_data->created_by = !empty($format_data['created_by']) ? $format_data['created_by'] : 0; 
				$lessonformat_data->path = !empty($format_data['path']) ? $format_data['path'] : ''; 
				$lessonformat_data->storage = !empty($format_data['storage']) ? $format_data['storage'] : ''; 
				
				if (!empty($format_data['media_id']))
				{
					$lessonformat_data->id     = $format_data['media_id'];
					$this->_db->updateObject('#__tjlms_media', $lessonformat_data, 'id');

					// Id of the inserted media
					$lesson_obj->media_id = $media_id = $format_data['media_id'];
				}
				else
				{
					$this->_db->insertObject('#__tjlms_media', $lessonformat_data);

					// Id of the inserted media
					$lesson_obj->media_id = $format_data['media_id'] = $media_id = $this->_db->insertid();
				}
			}

			if (!$this->_db->updateObject('#__tjlms_lessons', $lesson_obj, 'id'))
			{
				echo $this->_db->stderr();

				return false;
			}

			$format = 'tj' . $lesson_obj->format;
			PluginHelper::importPlugin($format);

			$results = Factory::getApplication()->triggerEvent('onAfter' . $subfomatopt . 'FormatUploaded', array($format_data));
		}

		PluginHelper::importPlugin('system');

		// Trigger all "sytem" plugins OnAfterLessonCreation method
		Factory::getApplication()->triggerEvent('onAfterLessonFormatUploaded', array(
															$format_data['id'],
															$format_data['media_id']
														)
							);

		return $media_id;
	}

	/**
	 * Function to upload associate files
	 *
	 * @param   ARRAY  $post  Post Array
	 *
	 * @return  boolean
	 *
	 * @since  1.0.0
	 */
	public function updateassocfiles($post)
	{
		try
		{
			$lesson_format = $post->get('lesson_format', '', 'ARRAY');

			$assocFiles = $post->get('lesson_files', '', 'ARRAY');

			foreach ($assocFiles as $assocFile)
			{
				if (!empty($assocFile['media_id']))
				{
					// Check if already entry exists
					$query = $this->_db->getQuery(true);

					// Select all records from the user profile table where key begins with "custom.".
					// Order it by the ordering field.
					$query->select($this->_db->qn(array('id')));
					$query->from($this->_db->qn('#__tjlms_associated_files'));
					$query->where($this->_db->qn('lesson_id') . ' = ' . $this->_db->q($lesson_format['id']));
					$query->where($this->_db->qn('media_id') . ' = ' . $this->_db->q($assocFile['media_id']));

					// Reset the query using our newly populated query object.
					$this->_db->setQuery($query);

					// Load the results as a list of stdClass objects (see later for more options on retrieving data).
					$mediaFileId = $this->_db->loadResult();

					if (empty($mediaFileId))
					{
						$fileData            = new stdClass;
						$fileData->id        = '';
						$fileData->lesson_id = $lesson_format['id'];
						$fileData->media_id  = $assocFile['media_id'];
						$this->_db->insertObject('#__tjlms_associated_files', $fileData, 'id');
					}
				}
			}

			// Add action logs for lesson edit.
			$lessonObj = Tjlms::lesson($lesson_format['id']);

			if (!empty($lesson_format['id']) && !empty($lessonObj->alias))
			{
				PluginHelper::importPlugin('actionlog');
				Factory::getApplication()->triggerEvent('onAfterLessonEdit', array(
																	$lessonObj->id,
																	$lessonObj->course_id,
																	$lessonObj->created_by,
																	$lessonObj->mod_id,
																	$lessonObj->title
																)
									);
			}

			return true;
		}
		catch (Exception $e)
		{
			$this->setError($e->getMessage());

			return false;
		}
	}

	/**
	 * Check for the format of the lesson if change during edit. And also delete the data related to it.
	 *
	 * @param   INT  $lessonid       Lesson ID
	 * @param   INT  $currentFormat  Current Lesson format
	 *
	 * @return  boolean
	 *
	 * @since  1.0.0
	 */
	public function changeLessonFormat($lessonid, $currentFormat)
	{
		try
		{
			$query = $this->_db->getQuery(true);
			$query->select($this->_db->qn(array('format', 'media_id')));
			$query->from($this->_db->qn('#__tjlms_lessons'));
			$query->where($this->_db->qn('id') . ' = ' . $this->_db->q((int) $lessonid));

			$this->_db->setQuery($query);
			$lessonDetails = $this->_db->loadAssoc();

			// Select repected table
			if ($lessonDetails['format'] == 'scorm' || $lessonDetails['format'] == 'tjscorm')
			{
				$format_table = $this->_db->qn('#__tjlms_scorm');
				$where        = $this->_db->qn('lesson_id') . ' = ' . $this->_db->q((int) $lessonid);
			}
			else
			{
				$format_table = $this->_db->qn('#__tjlms_media');
				$where        = $this->_db->qn('id') . ' = ' . $this->_db->q((int) $lessonDetails['media_id']);
			}

			if ($lessonDetails['format'] != $currentFormat)
			{
				$query->delete($format_table);
				$query->where($where);

				$this->_db->setQuery($query);
				$this->_db->execute();

				return 0;
			}
			else
			{
				return 1;
			}
		}
		catch (Exception $e)
		{
			$this->setError($e->getMessage());

			return false;
		}
	}

	/**
	 * Store associate files for a lesson
	 *
	 * @param   ARRAY  $selected_files  Files ARRAY
	 * @param   INT    $lesson_id       Lesson ID
	 *
	 * @return  void
	 *
	 * @since  1.0.0
	 */
	public function storeAssociateFiles($selected_files, $lesson_id)
	{
		$query = $this->_db->getQuery(true);

		// Get the already attache associate file of the lesson incase of edit
		$original_files = $this->getlessonFiles($lesson_id);
		$files_present  = array();

		foreach ($original_files as $ind => $files)
		{
			$files_present[$ind] = $files->media_id;
		}

		// Array to collect the files which has to be delete
		$delete_files = array();

		// Array to collect the files which has to be keep as it is
		$dont_edit = array();

		foreach ($files_present as $f_p)
		{
			if (!in_array($f_p, $selected_files))
			{
				$delete_files[] = $f_p;
			}
			else
			{
				$dont_edit[] = $f_p;
			}
		}

		foreach ($delete_files as $d_f)
		{
			$query->delete($this->_db->qn('#__tjlms_associated_files'));
			$query->where($this->_db->qn('media_id') . ' = ' . $this->_db->q((int) $d_f));
			$query->where($this->_db->qn('lesson_id') . ' = ' . $this->_db->q((int) $lesson_id));

			$this->_db->setQuery($query);
			$this->_db->execute();
		}

		$insert_file = array();

		foreach ($selected_files as $s_p)
		{
			if (!in_array($s_p, $dont_edit))
			{
				$insert_file[] = $s_p;
			}
		}

		return $insert_file;
	}

	/**
	 * Delete the unused files of the lesson
	 *
	 * @param   ARRAy  $format_data  Data
	 *
	 * @return  void
	 *
	 * @since  1.0.0
	 */
	public function deleteunusedfiles($format_data)
	{
		// Delete previously uploaded scorm or video files
		$directory = JPATH_SITE . '/media/com_tjlms' . '/lessons/' . $lessonid;
		$files     = Folder::files($directory);

		$img = $this->tjlmsdbhelper->get_records('image ', 'tjlms_lessons', array('id' => $lessonid), '', 'loadResult');

		$files_to_delete = array_diff($files, array($img));

		foreach ($files_to_delete as $f)
		{
			File::delete($directory . '/' . $f);
		}
	}

	/**
	 * Function used to get Associate files for a lesson
	 *
	 * @return  Array
	 *
	 * @since  1.0.0
	 */
	public function getselectAllAssociatedFiles()
	{
		try
		{
			$query = $this->_db->getQuery(true);
			$query->select('*');
			$query->from($this->_db->qn('#__tjlms_files'));

			$this->_db->setQuery($query);
			$select_files = $this->_db->loadObjectList('id');

			return $select_files;
		}
		catch (Exception $e)
		{
			$this->setError($e->getMessage());

			return false;
		}
	}

	/**
	 * Function used to get all associate files
	 *
	 * @param   INT  $lessonId  lesson id
	 *
	 * @return  Object list of files
	 *
	 * @since  1.0.0
	 */
	public function getFilestoAssociate($lessonId)
	{
		try
		{
			$query = $this->_db->getQuery(true);

			$subquery = $this->_db->getQuery(true);
			$subquery->select('DISTINCT(taf.media_id)')
						->from($this->_db->qn('#__tjlms_associated_files', 'taf'))
						->where($this->_db->qn('lesson_id') . ' = ' . $this->_db->q((int) $lessonId));

			$query->select($this->_db->qn(array('m.id', 'm.storage', 'm.source')));
			$query->select($this->_db->qn(array('m.org_filename', 'm.source'), array('filename', 'path')));
			$query->from($this->_db->qn('#__tjlms_media', 'm'));
			$query->join('LEFT', $this->_db->qn('#__tjlms_associated_files', 'af') . '
			 ON (' . $this->_db->qn('af.media_id') . ' = ' . $this->_db->qn('m.id') . ')');
			$query->where($this->_db->qn('format') . ' = "associate"');
			$query->where($this->_db->qn('m.id') . ' NOT IN (' . $subquery->__toString() . ')');

			// Filter by search in title
			$search = $this->getState('filter.search');

			if (!empty($search))
			{
				$search = '%' . $this->_db->escape($search, true) . '%';
				$query->where($this->_db->qn('m.org_filename') . ' LIKE ' . $this->_db->q($search, false));
			}

			$this->_db->setQuery($query);

			$select_files = $this->_db->loadObjectList('id');

			if (!empty($select_files))
			{
				JLoader::import('components.com_tjlms.libraries.storage', JPATH_SITE);
				$tjStorage = new Tjstorage;

				foreach ($select_files as $key => $assocFile)
				{
					if ($assocFile->storage == 'invalid')
					{
						unset($select_files[$key]);

						continue;
					}

					$storage 	= $tjStorage->getStorage($assocFile->storage);
					$fileExists = $storage->exists('media/com_tjlms/lessons/' . $assocFile->source);

					if (!$fileExists)
					{
						unset($select_files[$key]);
					}
				}
			}

			return $select_files;
		}
		catch (Exception $e)
		{
			$this->setError($e->getMessage());

			return false;
		}
	}

	/**
	 *  To get the specified details about a lesson
	 *
	 * @param   INT  $lessonId    Lesson ID
	 * @param   INT  $columns     Columns to fetch
	 * @param   INT  $conditions  Conditions to be added while fetching
	 *
	 * @return  Array
	 *
	 * @since  1.0.0
	 */
	public function getLessonDetails($lessonId, $columns=array('*'), $conditions=array())
	{
		$db    = Factory::getDBO();
		$query = $db->getQuery(true);

		$query->select($columns);
		$query->from($db->quoteName('#__tjlms_lessons'));

		if ($conditions)
		{
			$query->where($conditions);
		}

		$query->where($db->quoteName('id') . " = " . $db->quote($lessonId));
		$db->setQuery($query);

		return $db->loadObject();
	}

	/**
	 * Function used to remove old lesson image
	 *
	 * @param   INT  $l_id  Lesson ID
	 *
	 * @return  Array
	 *
	 * @since  1.0.0
	 */
	public function removeLessonImage($l_id)
	{
		try
		{
			$query = $this->_db->getQuery(true);
			$query->select($this->_db->qn(array('image','storage')));
			$query->from($this->_db->qn('#__tjlms_lessons', 'l'));
			$query->where($this->_db->qn('l.id') . ' = ' . $this->_db->q((int) $l_id));
			$this->_db->setQuery($query);
			$lesson = $this->_db->loadAssoc();

			if (empty($lesson))
			{
				return false;
			}

			require_once JPATH_ROOT . '/components/com_tjlms/libraries/storage.php';
			$Tjstorage = new Tjstorage;

			// Get image to be shown for course
			$tjlmsparams  	= ComponentHelper::getParams('com_tjlms');

			if (!empty($lesson['image']) && $lesson['storage'] != 'invalid')
			{
				$lessonImage 	= $lesson['image'];
				$storage   		= $Tjstorage->getStorage($lesson['storage']);
				$imageSizes 	= array('', 'L_', 'M_', 'S_');

				foreach ($imageSizes as $imageSize)
				{
					$storage->delete($tjlmsparams->get('lesson_image_upload_path') . $imageSize . $lesson['image']);
				}
			}
		}
		catch (Exception $e)
		{
			$this->setError($e->getMessage());

			return false;
		}
	}

	/**
	 * Function to get the id of the scorm table
	 *
	 * @param   INT  $lessonid  lessonid
	 *
	 * @return  id of tjlms_scorm
	 *
	 * @since 1.1.4
	 */
	public function removeLastUsedMedia($lessonid)
	{
		$lessonid = (int) $lessonid;

		if ($lessonid)
		{
			$lessonFile = $this->getLessonMedia($lessonid);

			if (!empty($lessonFile) && $lessonFile->mediaid)
			{
				// For safe side check if Media is not used twice
				$used_in = $this->isMediaUsed($lessonFile->mediaid);

				// Not used in more than one lesson
				if ($used_in < 2)
				{
					JLoader::import('components.com_tjlms.models.media', JPATH_ADMINISTRATOR);
					$mediaModel = new TjlmsModelMedia;
					$mediaModel->delete($lessonFile->mediaid);
				}
			}
		}
	}

	/**
	 * Function to get Lesson Media Detail
	 *
	 * @param   INT  $lessonid  lessonid
	 *
	 * @return  Boolean
	 *
	 * @since 1.1.4
	 */
	private function getLessonMedia($lessonid)
	{
		try
		{
			$query = $this->_db->getQuery(true);
			$query->select($this->_db->qn(array('m.*')));
			$query->select($this->_db->qn(array('m.id ', 'l.id'), array('mediaid','lesson_id')));
			$query->from($this->_db->qn('#__tjlms_media', 'm'));
			$query->join('LEFT', $this->_db->qn('#__tjlms_lessons', 'l') . ' ON (' . $this->_db->qn('l.media_id') . ' = ' . $this->_db->qn('m.id') . ')');
			$query->where($this->_db->qn('m.format') . ' != "associate"');
			$query->where($this->_db->qn('l.id') . ' = ' . $this->_db->q((int) $lessonid));
			$this->_db->setQuery($query);
			$lessonFiles = $this->_db->loadObject();

			return $lessonFiles;
		}
		catch (Exception $e)
		{
			$this->setError($e->getMessage());

			return false;
		}
	}

	/**
	 * Function to check if Media is reused
	 *
	 * @param   INT  $media_id  media_id
	 *
	 * @return  Boolean
	 *
	 * @since 1.1.4
	 */
	private function isMediaUsed($media_id)
	{
		$media_id = (int) $media_id;

		try
		{
			if ($media_id)
			{
				$query = $this->_db->getQuery(true);
				$query->select('count(*)');
				$query->from($this->_db->qn('#__tjlms_lessons', 'l'));
				$query->where($this->_db->qn('l.media_id') . ' = ' . (int) $media_id);
				$this->_db->setQuery($query);
				$used_count = $this->_db->loadResult();

				return $used_count;
			}
		}
		catch (Exception $e)
		{
			$this->setError($e->getMessage());

			return false;
		}
	}

	/**
	 * Function used to remove associated files
	 *
	 * @param   string  $lessonId  A prefix for the store id.
	 * @param   string  $mediaId   A prefix for the store id.
	 *
	 * @return  JSON
	 *
	 * @since  1.0.0
	 *
	 */
	public function removeAssociatedFile($lessonId, $mediaId)
	{
		try
		{
			$query = $this->_db->getQuery(true);

		// Delete record condition
		$conditions = array(
			$this->_db->qn('media_id') . '=' . $mediaId,
			$this->_db->qn('lesson_id') . '=' . $lessonId
		);

		$query->delete($this->_db->qn('#__tjlms_associated_files'));
		$query->where($conditions);
		$this->_db->setQuery($query);

			if ($this->_db->execute())
			{
				return 1;
			}
			else
			{
				return 0;
			}
		}
		catch (Exception $e)
		{
			$this->setError($e->getMessage());

			return false;
		}
	}

	/**
	 * Delete all scorm lesson data tracks for the lessons
	 *
	 * @param   INT  $lessonId  lesson id
	 *
	 * @return  void
	 *
	 * @since  1.0.0
	 */
	private function deleteScormData($lessonId)
	{
		try
		{
			$db = Factory::getDbo();

			// Get scorm id
			$query = $db->getQuery(true);
			$query->select(array($db->qn('sc.id', 'scorm_id')));
			$query->from($db->qn('#__tjlms_scorm', 'sc'));
			$query->where($db->qn('sc.lesson_id') . '= ' . (int) $lessonId);
			$db->setQuery($query);
			$scormId = $db->loadResult();

			if ($scormId)
			{
				// Get scorm id
				$query = $db->getQuery(true);
				$query->select(array($db->qn('scos.id', 'scoes_id')));
				$query->from($db->qn('#__tjlms_scorm_scoes', 'scos'));
				$query->where($db->qn('scos.scorm_id') . '= ' . (int) $scormId);
				$db->setQuery($query);
				$scoIds = $db->loadColumn();

				// Delete from SCORM TABLE
				$query = $db->getQuery(true);
				$conditions = array($db->quoteName('lesson_id') . ' = ' . (int) $lessonId);
				$query->delete($db->quoteName('#__tjlms_scorm'));
				$query->where($conditions);
				$db->setQuery($query);
				$db->execute();

				// Entries from Table #_tjlms_scorm_scoes deleted
				$query = $db->getQuery(true);
				$conditions_scormid = array(
					$db->quoteName('scorm_id') . ' = ' . (int) $scormId
				);

				$query->delete($db->quoteName('#__tjlms_scorm_scoes'));
				$query->where($conditions_scormid);
				$db->setQuery($query);
				$db->execute();

				// Delete Sceos table data for this scorm
				$this->deleteScormScoesData($scoIds);
			}
		}
		catch (Exception $e)
		{
			return false;
		}
	}

	/**
	 * Delete all scorm scoes data tracks for the lessons
	 *
	 * @param   ARRAY  $scoesIds  array of scoes Ids
	 *
	 * @return  void
	 *
	 * @since  1.0.0
	 */
	public function deleteScormScoesData($scoesIds)
	{
		try
		{
			$db = Factory::getDbo();
			$scoesIdsString = implode(',', $db->q($scoesIds));

			// Entries from Table #_tjlms_scorm_scoes_data deleted
			$query = $db->getQuery(true);
			$conditions_scoesid = array(
				$db->quoteName('sco_id') . ' IN (' . $scoesIdsString . ')'
			);

			$query->delete($db->quoteName('#__tjlms_scorm_scoes_data'));
			$query->where($conditions_scoesid);
			$db->setQuery($query);
			$db->execute();

			// Entries from  Table #_tjlms_scorm_scoes_track deleted
			$query = $db->getQuery(true);
			$query->delete($db->quoteName('#__tjlms_scorm_scoes_track'));
			$query->where($conditions_scoesid);
			$db->setQuery($query);
			$scormtrack = $db->execute();

			// Table #_tjlms_scorm_seq_mapinfo
			$query = $db->getQuery(true);
			$query->delete($db->quoteName('#__tjlms_scorm_seq_mapinfo'));
			$query->where($conditions_scoesid);
			$db->setQuery($query);
			$db->execute();

			// Table #_tjlms_scorm_seq_objective
			$query = $db->getQuery(true);
			$query->delete($db->quoteName('#__tjlms_scorm_seq_objective'));
			$query->where($conditions_scoesid);
			$db->setQuery($query);
			$db->execute();

			// Table #_tjlms_scorm_seq_rolluprule
			$query = $db->getQuery(true);
			$query->delete($db->quoteName('#__tjlms_scorm_seq_rolluprule'));
			$query->where($conditions_scoesid);
			$db->setQuery($query);
			$db->execute();

			// Table #_tjlms_scorm_seq_rolluprulecond
			$query = $db->getQuery(true);
			$query->delete($db->quoteName('#__tjlms_scorm_seq_rolluprulecond'));
			$query->where($conditions_scoesid);
			$db->setQuery($query);
			$db->execute();

			// Table #_tjlms_scorm_seq_rulecond
			$query = $db->getQuery(true);
			$query->delete($db->quoteName('#__tjlms_scorm_seq_rulecond'));
			$query->where($conditions_scoesid);
			$db->setQuery($query);
			$db->execute();

			// Table #_tjlms_scorm_seq_ruleconds
			$query = $db->getQuery(true);
			$query->delete($db->quoteName('#__tjlms_scorm_seq_ruleconds'));
			$query->where($conditions_scoesid);
			$db->setQuery($query);
			$db->execute();
		}
		catch (Exception $e)
		{
			return false;
		}
	}

	/**
	 * Function used to delete lesson
	 *
	 * @param   INT  $lesson  lesson obj
	 *
	 * @return  JSON
	 */
	public function deleteLesson($lesson)
	{
		BaseDatabaseModel::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_tjlms/models');
		$mediaModel = BaseDatabaseModel::getInstance('Media', 'TjlmsModel');
		$lessonMappingModel = BaseDatabaseModel::getInstance('CourseLessons', 'TjlmsModel');

		require_once JPATH_SITE . '/components/com_tjlms/helpers/tracking.php';
		$trackingHelper = new ComtjlmstrackingHelper;

		try
		{
			$mediaId = $lesson->media_id;
			$courseId = $lesson->course_id;
			$format = $lesson->format;

			// For safe side check if Media is not used twice
			$used_in = $this->isMediaUsed($mediaId);

			// Not used in more than one lesson
			if ($used_in == 0)
			{
				$mediaModel->delete($mediaId);
			}

			if ($format == 'scorm')
			{
				// Remove extracted scorm from local
				jimport('joomla.filesystem.folder');
				$extractedPath = JPATH_SITE . '/media/com_tjlms/lessons/' . $lesson->id;

				if (Folder::exists($extractedPath))
				{
					Folder::delete($extractedPath);
				}

				// Delete scorm entries
				$this->deleteScormData($lesson->id);
			}

			// Delete lesson Track
			$this->deleteLessonTracks($lesson->id);

			// Get all the courses lesson is part of
			$courseId = $lesson->course_id;

			if ($courseId)
			{
				// Update course track
				$trackingHelper->addCourseTrackEntry($courseId);
			}

			$lessonMappingModel->setState("filter.lesson", $lesson->id);

			$result = $lessonMappingModel->getItems();

			if (!empty($result))
			{
				foreach ($result as $ind => $obj)
				{
					$trackingHelper->addCourseTrackEntry($obj->course_id);
				}
			}

			return true;
		}
		catch (Exception $e)
		{
			$this->setError($e->getMessage());

			return false;
		}
	}

	/**
	 * Delete all lesson tracks for the lesson
	 *
	 * @param   ARRAY  $lessonId  lesson id
	 *
	 * @return  void
	 *
	 * @since  1.0.0
	 *
	 */
	public function deleteLessonTracks($lessonId)
	{
		try
		{
			$db = Factory::getDbo();

			// Delete all lesson tracks related to selected course lessons
			$query      = $db->getQuery(true);
			$conditions = array(
				$db->quoteName('lesson_id') . ' = ' . (int) $lessonId
			);

			$query->delete($db->quoteName('#__tjlms_lesson_track'));
			$query->where($conditions);
			$db->setQuery($query);
			$result = $db->execute();

			return $result;
		}
		catch (Exception $e)
		{
			$this->setError($e->getMessage());

			return false;
		}
	}
	/**
	 * Function to add interation option fields against document
	 * This is hack for DPE to save lesson interaction
	 * Todo: Need to check with Shika team to add this function in core
	 *
	 * @param   ARRAY  $post  Post Array
	 *
	 * @return  boolean
	 *
	 * @since  __DEPLOY_VERSION__
	 */
	public function saveInteraction($post)
	{
		$jform = $post->get('jform', array(), 'ARRAY');

		$docInteraction = $post->get('doc_interaction_id', 0, 'INTEGER');
		$pluginObj      = PluginHelper::getPlugin('content', 'jlike_tjlmslesson');
		$pluginParams   = new JRegistry($pluginObj->params);

		// Load contentform model to get content id
		JLoader::import('contentform', JPATH_SITE . '/components/com_jlike/models');
		$jlikeModelContentForm = new JlikeModelContentForm;

		$intParams = array();

		// If "Read & Understood" is not checked then remove "Used in Practice"
		if (!array_key_exists('read_interaction', $jform))
		{
			unset($jform);
		}

		foreach ($jform as $key => $interactionWay)
		{
			if ($pluginParams->get($key))
			{
				$intParams[$key] = $pluginParams->get($key);
			}
		}

		$data = array();
		$data['element']    = 'com_tjlms.lesson';
		$data['url']        = 'index.php?option=com_tjlms&view=lesson&lesson_id=' . $docInteraction;
		$data['element_id'] = $docInteraction;
		$data['title']      = $this->getLessonDetails($docInteraction)->title;
		$data['params']     = (!empty($intParams)) ? json_encode($intParams) : '';
		$contentId          = JlikeModelContentForm::getContentID($data);

		// This will execute in edit case
		$data['id'] = $contentId;
		$contentId  = $jlikeModelContentForm->save($data);

		return $contentId ? $contentId : 0;
	}
}
