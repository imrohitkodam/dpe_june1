<?php
/**    
 * SEO Glossary Glossary Controller
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
     /**
     * Method to save a user's profile data.
     *
     * @return	void
     * @since	1.6
     */
    public function save($key = NULL, $urlVar = NULL) {
        // Check for request forgeries.
        JSession::checkToken() or jexit(JText::_('JINVALID_TOKEN'));

        // Initialise variables.
        $app = JFactory::getApplication();
        $model = $this->getModel('glossary', 'SeoglossaryModel');

        // Get the user data.
       $data = JFactory::getApplication()->input->post->get('jform', array(), 'array');
       $catid=$data['catid'];
        // Redirect back to the edit screen.
           $Itemid = SeoglossaryHelper::getGlossaryMenu($catid);
				
				$item_id="";
				if($Itemid)
				{
					$item_id='&Itemid='.$Itemid;
					
				}
				$link = JRoute::_('index.php?option=com_seoglossary&view=glossaries&catid=' . $catid.$item_id);
             
        // Validate the posted data.
        $form = $model->getForm();
        if (!$form) {
            JFactory::getApplication()->enqueueMessage($model->getError(),'error');
            return false;
        }

        // Validate the posted data.
        $data = $model->validate($form, $data);

        // Check for errors.
        if ($data === false) {
            // Get the validation messages.
            $errors = $model->getErrors();

            // Push up to three validation messages out to the user.
            for ($i = 0, $n = count($errors); $i < $n && $i < 3; $i++) {
                if ($errors[$i] instanceof Exception) {
                    $app->enqueueMessage($errors[$i]->getMessage(), 'warning');
                } else {
                    $app->enqueueMessage($errors[$i], 'warning');
                }
            }

           

           $this->setRedirect($link);
            return false;
        }
        // Attempt to save the data.
        $return = $model->save($data);
   
        // Check for errors.
        if ($return === false) {
            // Save the data in the session.
           

            $this->setMessage(JText::sprintf('COM_SEOGLOSSARY_EROR', $model->getError()), 'warning');
            $this->setRedirect($link);
            return false;
        }
        // Redirect to the list screen.
        $this->setMessage(JText::_('COM_SEOGLOSSARY_COMPONENT_SAVE'));
        $this->setRedirect($link);

    }


}