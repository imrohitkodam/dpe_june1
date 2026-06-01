<?php
/**
 * @package     XT Transitional Package from FrameworkOnFramework
 * @subpackage  form
 *  @copyright   Copyright (c)2012-2020 Extly, CB. All rights reserved. / Based on FrameworkOnFramework of Akeeba
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */
// Protect from unauthorized access
defined('XTF0F_INCLUDED') or die;

/**
 * Generic interface that a XTF0F form field class must implement
 *
 * @package  FrameworkOnFramework
 * @since    2.0
 */
interface XTF0FFormField
{
	/**
	 * Get the rendering of this field type for static display, e.g. in a single
	 * item view (typically a "read" task).
	 *
	 * @return  string  The field HTML
	 *
	 * @since 2.0
	 */
	public function getStatic();

	/**
	 * Get the rendering of this field type for a repeatable (grid) display,
	 * e.g. in a view listing many item (typically a "browse" task)
	 *
	 * @return  string  The field HTML
	 *
	 * @since 2.0
	 */
	public function getRepeatable();
}
