<?php
/**
 * @package     XT Transitional Package from FrameworkOnFramework
 * @subpackage  hal
 *  @copyright   Copyright (c)2012-2020 Extly, CB. All rights reserved. / Based on FrameworkOnFramework of Akeeba
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */
defined('XTF0F_INCLUDED') or die;

/**
 * Interface for HAL document renderers
 *
 * @package  FrameworkOnFramework
 * @since    2.1
 */
interface XTF0FHalRenderInterface
{
	/**
	 * Render a HAL document into a representation suitable for consumption.
	 *
	 * @param   array  $options  Renderer-specific options
	 *
	 * @return  void
	 */
	public function render($options = array());
}
