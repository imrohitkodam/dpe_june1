<?php
/**
 * Search Module SEO Glossary module helper
 *
 * We developed this code with our hearts and passion.
 * We hope you found it useful, easy to understand and change.
 * Otherwise, please feel free to contact us at contact@joomunited.com
 *
 * @package 	SEO Glossary
 * @copyright 	Copyright (C) 2014 JoomUnited (http://www.joomunited.com). All rights reserved.
 * @license 	GNU General Public License version 2 or later; http://www.gnu.org/licenses/gpl-2.0.html
 */

// no direct access
defined('_JEXEC') or die;
require_once(JPATH_SITE . '/components/com_seoglossary/helpers/compat.php');

SeogModel::addIncludePath(JPATH_SITE . '/components/com_seoglossary/models', 'SeoglossaryModel');

// import the Joomla! arrayhelper library
jimport('joomla.utilities.arrayhelper');

class modSearchseoglossaryHelper
{
    public static function getSearchform($params)
    {
        return $str;
    }
}