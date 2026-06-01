<?php
/**    
 * SEO Glossary Glossary controller
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

jimport('joomla.application.component.controllerform');

/**
 * Glossary controller class.
 */
class SeoglossaryControllerGlossary extends JControllerForm
{

    function __construct() {
        $this->view_list = 'glossaries';
        parent::__construct();
    }

}