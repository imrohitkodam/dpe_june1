<?php
/**
 * @package     Shika
 * @subpackage  com_tmt
 *
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (C) 2009 - 2019 Techjoomla. All rights reserved.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 */

// No direct access to this file
defined('_JEXEC') or die('Restricted access');
use Joomla\CMS\Filter\InputFilter;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;

use Joomla\CMS\Factory;

$language = Factory::getLanguage();
$language->load('com_tmt');

/**
 * Questions Api.
 *
 * @package     Joomla.Administrator
 * @subpackage  com_api
 *
 * @since       1.3.22
 */
class LmsApiResourceQuestions extends ApiResource
{
	protected $items = array();

	protected $showCorrectAnswer = 0;

	/**
	 * Function to fetch questions record.
	 *
	 * @return void
	 */
	public function get()
	{
		$input                   = Factory::getApplication()->input;
		$filter                  = InputFilter::getInstance();
		$filtersArray            = $input->get('filters', array(), 'ARRAY');
		$this->showCorrectAnswer = $input->get('show_correct_answer', 0, 'INT');
		$fields                  = $input->get('fields', '', 'STRING');

		if (!empty($fields))
		{
			$fieldsArray = explode(",", $fields);
		}

		JLoader::import('components.com_tmt.models.questions', JPATH_ADMINISTRATOR);
		$questionsModel = BaseDatabaseModel::getInstance('questions', 'TmtModel', array('ignore_request' => true));

		if (isset($fieldsArray) && !empty($fieldsArray))
		{
			foreach ($fieldsArray as $field)
			{
				$field = $filter->clean($field, 'TRIM');

				switch ($field)
				{
					case 'media.*':
						$questionsModel->setState('filter.media', $field);
						break;

					case 'answers.*':
						$questionsModel->setState('filter.answers', $field);
						break;

					case 'answers.is_correct':
						$questionsModel->setState('filter.show_correct_answer', $field);
						break;

					default:
						break;
				}
			}
		}

		if (isset($filtersArray['search']))
		{
			$search = $filter->clean($filtersArray['search'], 'STRING');
			$questionsModel->setState('filter.search', $search);
		}

		if (isset($filtersArray['level']))
		{
			$level = $filter->clean($filtersArray['level'], 'STRING');
			$questionsModel->setState('filter.level', $level);
		}

		if (isset($filtersArray['category']))
		{
			$category = $filter->clean($filtersArray['category'], 'INT');
			$questionsModel->setState('filter.category', $category);
		}

		if (isset($filtersArray['category_title']))
		{
			$categoryTitle = $filter->clean($filtersArray['category_title'], 'STRING');
			$questionsModel->setState('filter.category_title', $categoryTitle);
		}

		if (isset($filtersArray['type']))
		{
			$type = $filter->clean($filtersArray['type'], 'STRING');
			$questionsModel->setState('filter.type', $type);
		}

		if (isset($filtersArray['gradingtype']))
		{
			$gradingType = $filter->clean($filtersArray['gradingtype'], 'STRING');
			$questionsModel->setState('filter.gradingtype', $gradingType);
		}

		if (isset($filtersArray['created_by']))
		{
			$createdBy = $filter->clean($filtersArray['created_by'], 'INT');
			$questionsModel->setState('filter.created_by', $createdBy);
		}

		if (isset($filtersArray['state']))
		{
			$state = $filter->clean($filtersArray['state'], 'INT');
			$questionsModel->setState('filter.state', $state);
		}

		if (isset($filtersArray['order']))
		{
			$order = $filter->clean($filtersArray['order'], 'STRING');
			$questionsModel->setState('list.ordering', $order);
		}

		if (isset($filtersArray['order_Dir']))
		{
			$orderDir = $filter->clean($filtersArray['order_Dir'], 'STRING');
			$questionsModel->setState('list.direction', $orderDir);
		}

		$limit = $input->getInt('limit', 0);
		$questionsModel->setState('list.limit', $limit);

		$limitstart = $input->getInt('limitstart', 0);
		$questionsModel->setState('list.start', $limitstart);

		$this->items = $questionsModel->getItems();

		// Get the validation messages.
		$errors = $questionsModel->getErrors();

		if (!empty($errors))
		{
			$msg = array();

			// Push up to three validation messages out to the user.
			for ($i = 0, $n = count($errors); $i < $n && $i < 3; $i++)
			{
				if ($errors[$i] instanceof Exception)
				{
					$msg[] = $errors[$i]->getMessage();
				}
				else
				{
					$msg[] = $errors[$i];
				}
			}

			ApiError::raiseError("400", implode("\n", $msg), 'APIValidationException');
		}

		$result = new stdClass;

		$result->result    = $this->items;
		$result->total     = $questionsModel->getTotal();
		unset($this->items);
		$this->plugin->setResponse($result);

		return;
	}
}
