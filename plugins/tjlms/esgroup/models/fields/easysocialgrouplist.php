<?php
/**
 * @package     Joomla.Plugin
 * @subpackage  tjlms.esgroup
 *
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (C) 2009 - 2020 Techjoomla. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access
defined('_JEXEC') or die('Restricted access');
use Joomla\CMS\Form\FormField;
use Joomla\CMS\Form\FormHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\CMS\Form\Field\ListField;

FormHelper::loadFieldClass('list');

if (JVERSION < '3.0')
{
	HTMLHelper::_('formbehavior.chosen', 'select');
}

/**
 * Supports an HTML select list
 *
 * @since  1.0.0
 */
class FormFieldEasysocialGroupList extends ListField
{
	/**
	 * The form field type.
	 *
	 * @var   string
	 * @since 2.4.0
	 */
	protected $type = 'easysocialgrouplist';

	/**
	 * Method to get a list of options for a list input.
	 *
	 * @return array An array of HTMLHelper options.
	 *
	 * @since  2.4.0
	 */
	protected function getInput()
	{
		$input       = Factory::getApplication()->input;
		$courseId    = $input->get('id');
		BaseDatabaseModel::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_tjlms/models');
		$courseModel = BaseDatabaseModel::getInstance('course', 'TjlmsModel');
		$courseInfo  = $courseModel->getItem($courseId);
		$esgroups    = '';

		if (!empty($courseInfo->params['esgroup']->onAfterEnrollEsGroups))
		{
			$esgroups = $courseInfo->params['esgroup']->onAfterEnrollEsGroups;
		}

		if (empty($this->value) && !empty($courseId))
		{
			$this->value = $esgroups;
		}

		$db    = Factory::getDbo();
		$user  = Factory::getUser();
		$query = $db->getQuery(true);

		// Select the required fields from the table.
		$query->select('sc.id, sc.title, scc.title as cat_title, scc.id as cat_id');
		$query->from($db->qn('#__social_clusters', 'sc'));
		$query->where($db->qn('sc.state') . '=' . $db->q('1'));
		$query->where($db->qn('sc.cluster_type') . '=' . $db->q('group'));
		$query->join('LEFT', $db->qn('#__social_clusters_categories', 'scc') . ' ON (' . $db->qn('scc.id') . ' = ' . $db->qn('sc.category_id') . ')');

		$query->order($db->escape('scc.id'));

		$db->setQuery($query);

		$allGroups = $db->loadObjectList();

		$HTMLCats    = array();
		$category_id = 0;
		$tempGroups  = array();
		$i           = 0;

		foreach ($allGroups as $group)
		{
			$catId                          = $group->cat_id;
			$tempGroups[$catId]['title']    = $group->cat_title;
			unset($group->cat_id);
			unset($group->cat_title);
			$tempGroups[$catId]["groups"][] = $group;
		}

		foreach ($tempGroups as $ind => $catgroup)
		{
			$cat                   = new stdClass;
			$cat->id               = $ind;
			$cat->title            = $catgroup['title'];
			$HTMLCats[$i]['id']    = $ind;
			$HTMLCats[$i]['text']  = $catgroup['title'];
			$HTMLCats[$i]['items'] = array();

			foreach ($catgroup['groups'] as $group)
			{
				$g                       = new stdClass;
				$g->id                   = $group->id;
				$g->title                = $group->title;
				$HTMLCats[$i]['items'][] = HTMLHelper::_('select.option', $group->id, $group->title);
			}

			$i++;
		}

		// Compute the current selected values

		$selected = $this->value;
		$html     = $options = array();
		$html[]   = HTMLHelper::_('select.groupedlist',  $HTMLCats, $this->name,
			array('id' => $this->id, 'group.id' => 'id', 'list.attr' => 'multiple=true', 'list.select' => $selected)
			);

		return implode($html);
	}
}
