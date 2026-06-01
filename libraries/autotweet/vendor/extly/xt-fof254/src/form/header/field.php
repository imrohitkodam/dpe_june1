<?php
/**
 * @package    FrameworkOnFramework
 * @subpackage form
 *  @copyright   Copyright (c)2012-2020 Extly, CB. All rights reserved. / Based on FrameworkOnFramework of Akeeba
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */
// Protect from unauthorized access
defined('XTF0F_INCLUDED') or die;

/**
 * Generic field header, without any filters
 *
 * @package  FrameworkOnFramework
 * @since    2.0
 */
class XTF0FFormHeaderField extends XTF0FFormHeader
{
	/**
	 * Get the header
	 *
	 * @return  string  The header HTML
	 */
	protected function getHeader()
	{
		$sortable = ($this->element['sortable'] != 'false');

		$label = $this->getLabel();

		if ($sortable)
		{
			$view = $this->form->getView();

			return JHtml::_('grid.sort', $label, $this->name,
				$view->getLists()->order_Dir, $view->getLists()->order,
				$this->form->getModel()->task
			);
		}
		else
		{
			return JText::_($label);
		}
	}
}
