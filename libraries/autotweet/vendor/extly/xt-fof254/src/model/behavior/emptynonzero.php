<?php
/**
 * @package     XT Transitional Package from FrameworkOnFramework
 * @subpackage  model
 *  @copyright   Copyright (c)2012-2020 Extly, CB. All rights reserved. / Based on FrameworkOnFramework of Akeeba
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */
// Protect from unauthorized access
defined('XTF0F_INCLUDED') or die;

/**
 * FrameworkOnFramework model behavior class
 *
 * @package  FrameworkOnFramework
 * @since    2.1
 */
class XTF0FModelBehaviorEmptynonzero extends XTF0FModelBehavior
{
	/**
	 * This event runs when we are building the query used to fetch a record
	 * list in a model
	 *
	 * @param   XTF0FModel        &$model  The model which calls this event
	 * @param   XTF0FDatabaseQuery  &$query  The query being built
	 *
	 * @return  void
	 */
	public function onBeforeBuildQuery(&$model, &$query)
	{
		$model->setState('_emptynonzero', '1');
	}
}
