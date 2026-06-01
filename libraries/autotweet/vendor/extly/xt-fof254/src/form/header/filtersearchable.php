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
 * Generic filter, text box entry with optional buttons
 *
 * @package  FrameworkOnFramework
 * @since    2.0
 */
class XTF0FFormHeaderFiltersearchable extends XTF0FFormHeaderFieldsearchable
{
	/**
	 * Get the header
	 *
	 * @return  string  The header HTML
	 */
	protected function getHeader()
	{
		return '';
	}
}
