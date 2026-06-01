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
 * Custom field to list all public and logged-in user's private competency scales
 *
 * @since  1.0.0
 */
class JFormFieldScales extends JFormFieldList
{
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
			$options[] = JHtml::_('select.option', '', Text::_('COM_COMPETENCY_COMPETENCY_SCALE_FIELD_SELECT'));
		}

		// Get scale model
		$model    = TjCompetency::model('Scales', array('ignore_request' => true));
		$scaleList = $model->getItems();

		if (!empty($scaleList))
		{
			foreach ($scaleList as $frm)
			{
				if ($frm->state != 1)
				{
					$options[] = JHtml::_('select.option', $frm->id, '[' . $frm->title . ' (' . $frm->scaleset_title . ') ' . ']');
				}
				else
				{
					$options[] = JHtml::_('select.option', $frm->id, $frm->title . ' (' . $frm->scaleset_title . ') ');
				}
			}
		}

		return $options;
	}
}
