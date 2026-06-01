<?php
/**
 * SEO Glossary Compatibility helper
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
use Joomla\CMS\Version;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\MVC\View\HtmlView;

jimport( 'joomla.version' );
$version = new Version();

jimport('joomla.application.component.controller');
jimport('joomla.application.component.view');
jimport('joomla.application.component.model');

if ($version->isCompatible('3.0.0')) {
    define('SEOGJ3', 1);
    class SeogModel extends BaseDatabaseModel {}
    class SeogController extends BaseController {}
    class SeogView extends HtmlView {}
} else {
    define('SEOGJ25', 1);
    class SeogModel extends JModel {}
    class SeogController extends JController {}
    class SeogView extends JView {}
}