<?php
/**
 * Joomlatools DOCman
 *
 * @package     DOCman
 * @copyright   Copyright (C) 2020 Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        https://github.com/joomlatools/docman for the canonical source repository
 */

class ComDocmanJoomlaModelDocument extends Joomla\CMS\MVC\Model\AdminModel
{
    /**
     * Method to get the record form.
     *
     * @param   array    $data      Data for the form.
     * @param   boolean  $loadData  True if the form is to load its own data (default case), false if not.
     *
     * @return  Form|boolean  A Form object on success, false on failure
     *
     * @since   1.6
     */
    public function getForm($data = [], $loadData = true)
    {
        Joomla\CMS\Form\Form::addFormPath(JPATH_ADMINISTRATOR . '/components/com_docman/joomla/form');

        $form = $this->loadForm('com_docman.document', 'document', ['control' => 'jform', 'load_data' => $loadData]);

        if (empty($form)) $form = false;

        return $form;
    }
}
