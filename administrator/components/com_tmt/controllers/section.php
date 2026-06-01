<?php
/**
 * @package    Com_Tmt
 * @copyright  Copyright (C) 2009 -2015 Techjoomla, Tekdi Web Solutions . All rights reserved.
 * @license    GNU GPLv2 <http://www.gnu.org/licenses/old-licenses/gpl-2.0.html>
 * @link       http://www.techjoomla.com
 */
// No direct access
defined('_JEXEC') or die;
use Joomla\CMS\MVC\Controller\FormController;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Response\JsonResponse;

/**
 * Test controller class.
 *
 * @since  1.0
 */
class TmtControllerSection extends FormController
{
	/**
	 * Constructor
	 *
	 * @since 1.0.0
	 */
	public function __construct()
	{
		$this->view_list = 'tests';
		parent::__construct();
	}

	/**
	 * Method to get questions based on rules posted using ajax
	 *
	 * @param   MIXED  $key     key
	 * @param   MIXED  $urlVar  urlVar
	 *
	 * @return  void
	 *
	 * @since 1.0
	 */
	public function save($key = null, $urlVar = null)
	{
		$input = Factory::getApplication()->input;
		$post = $input->post;
		$model = $this->getModel('section');
		$data = $post->get('tjlms_section', '', 'ARRAY');

		// Save the module and the course ID along with that
		$savedSection = $model->save($data);

		if ($savedSection)
		{
			$row = $model->getItem($savedSection);
			$model->unique = $data['lesson_id'];
			$model->questions_count = $data['questions_count'];
			$html = $model->renderHTML($data, $row);

			echo $html;
		}
		else
		{
			$html = '<strong>' . Text::_('COM_TJLMS_ERROR_MSG') . '</strong>';
			echo $html;
		}

		Factory::getApplication()->close();
	}

	/**
	 * Function used to delet the module of a particular course
	 *
	 * @return true/false
	 *
	 * @since  1.0.0
	 **/
	public function delete()
	{
		$input    = Factory::getApplication()->input;
		$test_id = $input->get('test_id', 0, 'INT');
		$section_id = $input->get('section_id', 0, 'INT');
		$model    = $this->getModel('section');
		$deletedSection = $model->delete($section_id);

		echo new JsonResponse($deletedSection);
		jexit();
	}
}
