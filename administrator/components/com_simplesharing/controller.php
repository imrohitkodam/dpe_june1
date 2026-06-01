<?php

/**
 * @version     1.0.2
 * @package     com_simplesharing
 * @copyright   Copyright (C) 2014. All rights reserved.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 * @author      NYC HelpDesk.co LLC <support@nychelpdesk.co> - nychelpdesk.co
 */

// No direct access
defined('_JEXEC') or die;

class SimplesharingController extends JControllerLegacy {

    /**
     * Method to display a view.
     *
     * @param	boolean			$cachable	If true, the view output will be cached
     * @param	array			$urlparams	An array of safe url parameters and their variable types, for valid values see {@link JFilterInput::clean()}.
     *
     * @return	JController		This object to support chaining.
     * @since	1.5
     */
    public function display($cachable = false, $urlparams = false) {
        require_once JPATH_COMPONENT . '/helpers/simplesharing.php';
        require_once JPATH_COMPONENT . '/helpers/rest.php';

        $view = JFactory::getApplication()->input->getCmd('view', 'slavewebsites');
        JFactory::getApplication()->input->set('view', $view);

        parent::display($cachable, $urlparams);

        return $this;
    }

}
