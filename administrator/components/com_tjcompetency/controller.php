<?php
/**
 * @package     TjCompetency
 * @subpackage  com_tjcompetency
 *
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (C) 2009 - 2021 Techjoomla. All rights reserved.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 */

// No direct access to this file
defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Controller\BaseController;

\JLoader::load(JPATH_COMPONENT_ADMINISTRATOR . '/includes/tjcompetency');
\JLoader::register('TjCompetencyHelper', __DIR__ . '/helpers/tjcompetency.php');

/**
 * Class TjCompetencyController
 *
 * @since  1.0.0
 */
class TjCompetencyController extends BaseController
{
	/**
	 * Method to display a view.
	 *
	 * @param   boolean  $cachable   If true, the view output will be cached
	 * @param   mixed    $urlparams  An array of safe url parameters and their variable types, for valid values see {@link JFilterInput::clean()}.
	 *
	 * @return  JController   This object to support chaining.
	 *
	 * @since    1.0.0
	 */
	public function display($cachable = false, $urlparams = false)
	{
		$app  = Factory::getApplication();
		$view = $app->input->getCmd('view', 'frameworks');
		$app->input->set('view', $view);

		return parent::display($cachable, $urlparams);
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
		$prefix   = JFactory::getApplication()->input->getVar('prefix');
		$session  = JFactory::getSession();
		$config   = JFactory::getConfig();

		$filename = $session->get($prefix . '_filename');

		$file = $config->get('log_path') . '/' . $filename;

		if (!empty($filename) && JFile::exists($file))
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
