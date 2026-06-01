<?php
/**
 * @package     TMT
 * @subpackage  com_shika
 *
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (C) 2009 - 2019 Techjoomla. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 *
 * @deprecated  1.4.0  This file will be removed and replacements will be provided in utilities
 */
// No direct access
defined('_JEXEC') or die;
use Joomla\CMS\Language\Text;

$tmtConfig = array();
$tmtConfig['difficulty_levels']["easy"] = Text::_('COM_TMT_DIFF_LEVEL_EASY');
$tmtConfig['difficulty_levels']["medium"] = Text::_('COM_TMT_DIFF_LEVEL_MEDIUM');
$tmtConfig['difficulty_levels']["hard"] = Text::_('COM_TMT_DIFF_LEVEL_HARD');
