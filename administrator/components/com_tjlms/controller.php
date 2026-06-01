<?php
/**
 * @version    SVN: <svn_id>
 * @package    Tjlms
 * @author     Techjoomla <extensions@techjoomla.com>
 * @copyright  Copyright (c) 2009-2015 TechJoomla. All rights reserved.
 * @license    GNU General Public License version 2 or later.
 */

// No direct access.
defined('_JEXEC') or die;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Factory;
use Joomla\CMS\Filesystem\File;

/**
 * Class for Tjlms main controller
 *
 * @since  1.0
 */
class TjlmsController extends BaseController
{
	/**
	 * Method to display a view.
	 *
	 * @param   boolean  $cachable   If true, the view output will be cached
	 * @param   array    $urlparams  An array of safe url parameters and their variable types, for valid values see {@link JFilterInput::clean()}.
	 *
	 * @return  JController  This object to support chaining.
	 *
	 * @since	1.5
	 */
	public function display($cachable = false, $urlparams = false)
	{
		require_once JPATH_COMPONENT . '/helpers/tjlms.php';
		$view	= Factory::getApplication()->input->getCmd('view', 'dashboard');

		Factory::getApplication()->input->set('view', $view);

		$layout = Factory::getApplication()->input->getCmd('layout', 'default');
		Factory::getApplication()->input->set('layout', $layout);

		/*parent::display($cachable, $urlparams);*/
		parent::display();

		return $this;
	}

	/**
	 * This uploads the files
	 *
	 * @return  void
	 *
	 * @since   1.0
	 */
	public function upload_files_store()
	{
		$TjlmsHelper = new TjlmsHelper;

		$input = Factory::getApplication()->input;
		$post = $input->post;
		$files	=	$input->files;
		$course_id = $input->get('course_id', 0, 'INT');
		$mod_id = $input->get('mod_id', 0, 'INT');
		$lesson_id = $input->get('lesson_id', 0, 'INT');
		$lesson_id_srting = '';

		if ($lesson_id)
		{
			$lesson_id_srting = " &lesson_idd=" . $lesson_id;
		}

		$store_files = $TjlmsHelper->upload_files_store($post, $files);
		$store_files = json_encode($store_files);
		$red_link = 'index.php?option=com_tjlms&view=lesson&layout=add_files&course_id=' . $course_id;
		$red_link .= '&mod_id=' . $mod_id . '&tmpl=component' . $lesson_id_srting . '&store_files=' . $store_files;
		$this->setRedirect($red_link);
	}

	/**
	 * This prints the html to show the version is outdated
	 *
	 * @return  void
	 *
	 * @since   1.0
	 */
	public function scormfix()
	{
		$db    = Factory::getDBO();

		$query = $db->getQuery(true);

		// Select all records from the user profile table where key begins with "custom.".
		// Order it by the ordering field.
		$query->select('*');
		$query->from($db->quoteName('jdh_tjlms_scorm'));
		$query->where($db->quoteName('lesson_id') . " IN(SELECT id FROM `jdh_tjlms_lessons` WHERE `format` LIKE 'scorm')");

		$db->setQuery($query);

		// Load the results as a list of stdClass objects (see later for more options on retrieving data).
		$scormres = $db->loadObjectList();

		foreach ($scormres as $se)
		{
				$mData  = new stdClass;
				$mData->format = 'scorm';
				$mData->sub_format = 'nativescorm.upload';
				$mData->org_filename  = $mData->source = $se->package;
				$mData->source  = $se->package;
				$mData->storage  = 'local';
				$db->insertObject('jdh_tjlms_media', $mData);

				// Id of the inserted media
				$media_id = $db->insertid();

				$ldata = new stdClass;
				$ldata->id = $se->lesson_id;
				$ldata->media_id = $media_id;

				$db->updateObject('jdh_tjlms_lessons', $ldata, 'id');
		}
	}

	/**
	 * Download log on import users.
	 *
	 * @return  mixed
	 *
	 * @since   1.2.10
	 */
	public function downloadLog()
	{
		jimport('joomla.filesystem.file');
		$prefix   = Factory::getApplication()->input->getVar('prefix');
		$session  = Factory::getSession();
		$config   = Factory::getConfig();

		$filename = $session->get($prefix . '_filename');

		$file = $config->get('log_path') . '/' . $filename;

		if (!empty($filename) && File::exists($file))
		{
			header('Content-Description: File Transfer');
			header('Content-Type: application/octet-stream');
			header('Content-Disposition: attachment; filename="' . basename($file) . '"');
			header('Expires: 0');
			header('Cache-Control: must-revalidate');
			header('Pragma: public');
			header('Content-Length: ' . filesize($file));
			readfile($file);
			jexit();
		}
		else
		{
			header("Location: " . $_SERVER["HTTP_REFERER"]);
		}
	}
}
