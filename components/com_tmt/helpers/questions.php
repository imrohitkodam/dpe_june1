<?php
/**
 * @package     TMT
 * @subpackage  com_shika
 *
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (C) 2009 - 2019 Techjoomla. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\HTML\HTMLHelper;

/**
 * Tmt question helper class
 *
 * @since       1.0.0
 *
 * @deprecated  1.4.0  This class will be removed and replacements will be provided in utilities, question library & questions, answers model
 *
 */
class TmtQuestionsHelper
{
	/**
	 * Method to check if a test can be deleted.
	 * If test has valid invitations or other data, it can't be deleted.
	 *
	 * @param   int  $aid  test id
	 *
	 * @return    boolean  true/false
	 *
	 * @since    1.0
	 */
	public static function getAnswersTitle($aid = null)
	{
		if ($aid)
		{
			$db = Factory::getDBO();
			$query = $db->getQuery(true);
			$query->select('answer')
				->from('#__tmt_answers')
				->where('id IN(' . implode(",", $db->quote($aid)) . ')');
			$db->setQuery($query);
			$ans = $db->loadColumn();

			return $ans;
		}

		return false;
	}

	/**
	 * Method to validate if logged in user is author if id is not null
	 *
	 * @param   int    $id            Category id
	 * @param   array  $companyUsers  Array of company users
	 *
	 * @return   boolean  true/false
	 *
	 * @since    1.0
	 */
	public function checkCreator($id = null, $companyUsers = null)
	{
		if ($id)
		{
			$db = Factory::getDBO();
			$query = $db->getQuery(true);
			$query->select('created_by')
				->from('#__tmt_questions')
				->where('id = ' . $db->quote($id));
			$db->setQuery($query);
			$creator = $db->loadResult();

			if (is_array($companyUsers))
			{
				if (in_array($creator, $companyUsers))
				{
					return true;
				}
			}
			else
			{
				if ($creator == Factory::getUser()->id)
				{
					return true;
				}
			}
		}

		return false;
	}

	/**
	 * Method to check if a question can be deleted.
	 * If question is used in 1 or more tests, it can't be deleted.
	 *
	 * @param   int  $id  question id
	 *
	 * @return   boolean  true/false
	 *
	 * @since    1.0
	 */
	public function canBeDeleted($id = null)
	{
		$db = Factory::getDBO();

		if ($id)
		{
			// Create a new query object.
			$query = $db->getQuery(true);

			$query->select($db->quoteName(array('tq.id')));
			$query->from($db->quoteName('#__tmt_tests_questions', 'tq'));
			$query->where($db->quoteName('tq.question_id') . ' = ' . (int) $id);

			$db->setQuery($query);
			$test_questions = $db->loadObjectlist();

			if (count($test_questions))
			{
				// Don't allow delete, if questions are present.
				return false;
			}
			else
			{
				// Allow deleting.
				return true;
			}
		}

		return false;
	}

	/**
	 * Method to get options for difficulty levels list filter/dropdown
	 *
	 * @return    array    $options    id and title for difficulty levels list filter/dropdown
	 *
	 * @since    1.0
	 */
	public function getDifficultyLevelOptions()
	{
		// Include defines.php
		include JPATH_SITE . '/components/com_tmt/defines.php';
		$options   = array();
		$options[] = HTMLHelper::_('select.option', '', Text::_('COM_TMT_SELECT_LEVEL'));

		foreach ($tmtConfig['difficulty_levels'] as $key => $val)
		{
			$options[] = HTMLHelper::_('select.option', $key, $val);
		}

		return $options;
	}

	/**
	 * Method to get options for question types list filter/dropdown
	 *
	 * @return    array    $options    id and title for question types list filter/dropdown
	 *
	 * @since    1.0
	 */
	public function getQTypesOptions()
	{
		$options           = array();
		$options[]         = HTMLHelper::_('select.option', '', Text::_('COM_TMT_SELECT_QTYPE'));
		$options[1]        = new stdClass;
		$options[1]->value = "radio";
		$options[1]->text  = Text::_('COM_TMT_QTYPE_MCQ_SINGLE');
		$options[2]        = new stdClass;
		$options[2]->value = "checkbox";
		$options[2]->text  = Text::_('COM_TMT_QTYPE_MCQ_MULTIPLE');
		$options[3] = new stdClass;
		$options[3]->value = "text";
		$options[3]->text = Text::_('COM_TMT_QTYPE_SUB_TEXT');
		$options[4] = new stdClass;
		$options[4]->value = "textarea";
		$options[4]->text = Text::_('COM_TMT_QTYPE_SUB_TEXTAREA');
		$options[5] = new stdClass;
		$options[5]->value = "file_upload";
		$options[5]->text = Text::_('COM_TMT_QTYPE_ONLY_QUESTION');
		$options[6] = new stdClass;
		$options[6]->value = "rating";
		$options[6]->text = Text::_('COM_TMT_QTYPE_RATING');

		return $options;
	}

	/**
	 * Method to get options for question banks list filter/dropdown
	 *
	 * @return    array    $options    id and title for question banks types list filter/dropdown
	 *
	 * @since    1.0
	 */
	public function getQuestionsBanks()
	{
		$options           = array();
		$options[]         = HTMLHelper::_('select.option', '', Text::_('COM_TMT_SELECT_Q_BANK'));
		$options[1]        = new stdClass;
		$options[1]->value = "stock_qbank";
		$options[1]->text  = Text::_('COM_TMT_QBANK_STOCK');
		$options[2]        = new stdClass;
		$options[2]->value = "my_qbank";
		$options[2]->text  = Text::_('COM_TMT_QBANK_MY');

		return $options;
	}

	/**
	 * Method to get count of questions created & published.
	 *
	 * @return    int    $count    number of questions
	 *
	 * @since    1.0
	 */
	public function getQuestionsCount()
	{
		$db   = Factory::getDbo();
		$query = $db->getQuery(true);
		$query->select('COUNT(q.id) AS count');
		$query->from('`#__tmt_questions` AS q');

		// Get questions from published categories only.
		$query->where('q.state=1');

		$db->setQuery($query);
		$count = $db->loadResult();

		return $count;
	}
}
