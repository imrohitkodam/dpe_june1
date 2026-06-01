<?php
/**
 * @package    DOCman
 * @copyright   Copyright (C) 2011 Timble CVBA (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        http://www.joomlatools.com
 */

defined('_JEXEC') or die;

JFormHelper::loadFieldClass('groupedlist');

class JFormFieldDocmanmultidownload extends JFormFieldRadio
{
    protected $type = 'Docmanpages';

    /**
     * Wraps the output in com_docman class for Bootstrap and removes Chosen
     */
    protected function getInput()
    {
        if (!class_exists('Koowa')) {
            return '';
        }

        $html = parent::getInput();

        if (!class_exists('\ZipArchive')) {
            $html .= $this->_getNotice();
        }

        return $html;
    }

    protected function _getNotice()
    {
        $manager = KObjectManager::getInstance();
        $translator = $manager->getObject('translator');
        $translator->load('com://admin/docman');

        $string = "
            <fieldset><br />
                <label class=\"alert alert-warning no_menu_items_layout\">" . $translator->translate('You need to ask your hosting company to enable PHP ZipArchive extension to use this feature') . "</label>
            </fieldset>
            ";

        return $string;
    }
}
