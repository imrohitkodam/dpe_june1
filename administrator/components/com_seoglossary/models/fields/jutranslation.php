<?php
/**    
 * SEO Glossary Glossary field
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

defined( '_JEXEC' ) or die;

jimport( 'joomla.form.formfield' );

/**
 * Form Field class for the Joomla Framework.
 */
class JFormFieldJutranslation extends JFormField {

    /**
     * Ju Translation input
     */
    protected function getInput() {
        include_once(JPATH_ADMINISTRATOR . DIRECTORY_SEPARATOR . 'components' . DIRECTORY_SEPARATOR . 'com_seoglossary' . DIRECTORY_SEPARATOR . 'helpers' . DIRECTORY_SEPARATOR . 'jutranslation.php');
        return Jutranslation::getInput();
    }

}