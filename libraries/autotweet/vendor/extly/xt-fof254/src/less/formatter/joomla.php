<?php
/**
 * @package     XT Transitional Package from FrameworkOnFramework
 * @subpackage  less
 *  @copyright   Copyright (c)2012-2020 Extly, CB. All rights reserved. / Based on FrameworkOnFramework of Akeeba
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */
// Protect from unauthorized access
defined('XTF0F_INCLUDED') or die;

/**
 * This class is taken verbatim from:
 *
 * lessphp v0.3.9
 * http://leafo.net/lessphp
 *
 * LESS css compiler, adapted from http://lesscss.org
 *
 * Copyright 2012, Leaf Corcoran <leafot@gmail.com>
 * Licensed under MIT or GPLv3, see LICENSE
 *
 * @package  FrameworkOnFramework
 * @since    2.1
 */
class XTF0FLessFormatterJoomla extends XTF0FLessFormatterClassic
{
	public $disableSingle = true;

	public $breakSelectors = true;

	public $assignSeparator = ": ";

	public $selectorSeparator = ",";

	public $indentChar = "\t";
}
