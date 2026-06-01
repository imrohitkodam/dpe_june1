<?php
/**
 * @package     TjCompetency
 * @subpackage  com_tjcompetency
 *
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (C) 2009 - 2021 Techjoomla. All rights reserved.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 */

JFormHelper::loadFieldClass('list');
use Joomla\CMS\Language\Text;
use Joomla\CMS\Factory;

JLoader::import('components.com_tjcompetency.includes.tjcompetency', JPATH_ADMINISTRATOR);

/**
 * Custom field to list all public and logged-in user's private competency contents
 *
 * @since  1.0.0
 */
class JFormFieldCompetencyContents extends JFormFieldList
{
	/**
	 * The supported form contexts
	 *
	 * @var    array
	 * @since  1.0.0
	 */
	protected $supportedContext = array(
		'com_tjcompetency.skillcontentmaps',
		'com_tjcompetency.skillcontentusermaps',
		'com_tjreports.reports',
	);

	/**
	 * Method to get a list of options for a list input.
	 *
	 * @return	array		An array of JHtml options.
	 *
	 * @since   11.4
	 */
	public function getOptions()
	{
		$options = array();

		if (!$this->multiple)
		{
			$options[] = JHtml::_('select.option', '', Text::_('COM_COMPETENCY_COMPETENCY_SKILLCONTENTMAP_CONTENT_FIELD_SELECT'));
		}

		$app             = JFactory::getApplication();
		$jinput          = $app->input;
		$option          = $jinput->get('option');
		$view            = $jinput->get('view');
		$listViewContext = $option . '.' . $view;

		if (in_array($listViewContext, $this->supportedContext) && $this->element['dependant'])
		{
			if ($listViewContext != 'com_tjreports.reports')
			{
				$client = $app->getUserStateFromRequest($listViewContext . '.filter.client', 'client');

				if (empty($client))
				{
					return $options;
				}
			}
		}

		$db    = Factory::getDbo();
		$query = $db->getQuery(true);
		$query->select('DISTINCT a.id, a.client, a.client_id, state');
		$query->from($db->quoteName('#__tjcompetency_skill_content_map', 'a'));
		$query->group('a.client, a.client_id');

		if (!is_array($client) && !empty($client))
		{
			$query->where('a.client = ' . $db->quote($client));
		}
		elseif (is_array($client))
		{
			$client = array_map(array($db, 'quote'), $client);
			$client = implode(',', $client);
			$query->where('a.client IN (' . $client . ')');
		}

		$db->setQuery($query);
		$contentList = $db->loadObjectList();

		if (!empty($contentList))
		{
			foreach ($contentList as $frm)
			{
				$title = TjCompetency::SkillContentMap()::getContentName($frm->client, $frm->client_id);
				$title = $title . ' (' . ucfirst($frm->client) . ')';

				if ($frm->state != 1)
				{
					$options[] = JHtml::_('select.option', $frm->client_id, '[' . $title . ']');
				}
				else
				{
					$options[] = JHtml::_('select.option', $frm->client_id, $title);
				}
			}
		}

		return $options;
	}
}
