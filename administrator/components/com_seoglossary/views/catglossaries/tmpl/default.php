<?php
/**
 * SEO Glossary view
 *
 * We developed this code with our hearts and passion.
 * We hope you found it useful, easy to understand and change.
 * Otherwise, please feel free to contact us at contact@joomunited.com
 *
 * @package 	SEO Glossary
 * @copyright 	Copyright (C) 2012 JoomUnited (http://www.joomunited.com). All rights reserved.
 * @license 	GNU General Public License version 2 or later; http://www.gnu.org/licenses/gpl-2.0.html
 */

// No direct access
defined('_JEXEC') or die;
require_once(JPATH_SITE . '/components/com_seoglossary/helpers/compat.php');
if (defined('SEOGJ3')) {
    require_once('default.j3x.php');
} else {
    require_once('default.j25.php');
}