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
 * Custom field to list all public and logged-in user's private competency scalesets
 *
 * @since  1.0.0
 */
class JFormFieldCompetencyScalesets extends JFormFieldList
{
	/**
	 * Method to get a list of options for a list input.
	 *
	 * @return	array		An array of JHtml options.
	 *
	 * @since   11.4
	 */
	protected function getOptions()
	{
		$options   = array();
		$options[] = JHtml::_('select.option', '', Text::_('COM_COMPETENCY_COMPETENCY_SCALESET_FIELD_SELECT'));

		// Get scaleset model
		$model    = TjCompetency::model('Scalesets', array('ignore_request' => true));
		$scalesetList = $model->getItems();

		if (!empty($scalesetList))
		{
			foreach ($scalesetList as $frm)
			{
				if ($frm->state != 1)
				{
					$options[] = JHtml::_('select.option', $frm->id, '[' . $frm->title . ']');
				}
				else
				{
					$options[] = JHtml::_('select.option', $frm->id, $frm->title);
				}
			}
		}

		return $options;
	}
}
