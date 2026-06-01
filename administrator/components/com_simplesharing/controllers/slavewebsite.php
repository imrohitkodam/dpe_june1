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

jimport('joomla.application.component.controllerform');

/**
 * Slavewebsite controller class.
 */
class SimplesharingControllerSlavewebsite extends JControllerForm
{

    function __construct() {
        $this->view_list = 'slavewebsites';
        parent::__construct();
    }
//function allowAdd($data=  array()){
//        $model = $this->getModel('slavewebsites');
//        if($model->checkAdd()) return true;
//        else { 
//            JFactory::getApplication()->enqueueMessage(JText::_('COM_SIMPLESHARING_ERROR_LIMITED_SLAVE_WEBSITES'), 'message');
//            return false;
//        }
//    }
}
