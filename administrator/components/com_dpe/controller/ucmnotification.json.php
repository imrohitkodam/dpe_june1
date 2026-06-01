<?php
/**
 * @version    SVN: <svn_id>
 * @package    Com_Dpe
 * @copyright  Copyright (c) 2009-2018 TechJoomla. All rights reserved.
 * @license    GNU General Public License version 2 or later.
 */

// No direct access
defined('_JEXEC') or die;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\FormController;
use Joomla\CMS\Response\JsonResponse;
use Joomla\CMS\Session\Session;
use Joomla\CMS\Table\Table;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;

require_once JPATH_ADMINISTRATOR . '/components/com_tjfields/helpers/tjfields.php';


class DpeControllerUcmnotification extends FormController
{


   /**
    * Save the configuration for notification in ucm
    *
    * @return  ChangeSet|boolean
    */
    public function saveUcmConfig()
    {

        $app           = Factory::getApplication();
        $input         = $app->input;
        $inputData     = $input->get('jform', array(), 'array');


        $tjFieldsHelper= new TjfieldsHelper;
        $optionsData   = $tjFieldsHelper->getOptions($inputData['parentId']); 
        $optionIds     =[];

        foreach($optionsData as $data)
        {
            $optionIds[] = $data->id;
        }

        $fieldDataToStore = array();

        foreach($optionIds as $key => $optionId)
        {   
            // Convert subform key to parent if ther is single row present for the submenu
            if (count($inputData['fieldoption_'.$optionId]) == 1)
            {   
               $fieldKey  = key($inputData['fieldoption_'.$optionId]);

               if ($fieldKey != 'fieldoption0')
               {
                 $inputData['fieldoption_'.$optionId]['fieldoption0'] = $inputData['fieldoption_'.$optionId][$fieldKey];
                 unset($inputData['fieldoption_'.$optionId][$fieldKey]);
               }
            }
            $fieldDataToStore[$key]['ucm_field_id'] = $inputData['parentId'];
            $fieldDataToStore[$key]['ucm_field_config']     = json_encode($inputData['fieldoption_'.$optionId]);
            $fieldDataToStore[$key]['ucm_field_option_id']  = $optionId;
        }          
      
        Table::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_dpe/tables');
        $configDetails = Table::getInstance('NotificationConfig', 'DpeTable');
        $configDetails->load(array('ucm_field_id' => $inputData['parentId']));

        if($configDetails->show_notification == 0)
        {
            $configDetails->save(array('ucm_field_id' => $inputData['parentId'],'show_notification'=> '1'));
        }
        
        $model = $this->getModel('Ucmnotification','DpeModel');
        $result = $model->save($fieldDataToStore);


        if ($result)
        {
            $msg = Text::_('COM_DPE_TJUCM_CONFIG_SAVE_SUCCESFULL');
            $result = true;
        }
        else
        {
            $msg = Text::_('COM_DPE_TJUCM_CONFIG_SAVE_UNSUCCESFULL');
            $result = false;
        }

        $returnData  =  array('result'=>$result,'msg'=>$msg);
        
        echo new JsonResponse($returnData);
        $app->close();
    }


    /**
     * Method to get a model object, loading it if required.
     *
     * @param   string  $name    The model name. Optional.
     * @param   string  $prefix  The class prefix. Optional.
     * @param   array   $config  Configuration array for model. Optional.
     *
     * @return  BaseDatabaseModel  The model.
     *
     * @since   1.6
     */
    public function getModel($name = '', $prefix = '', $config = ['ignore_request' => true])
    {
            return parent::getModel($name, $prefix, $config);
    }

     /**
     * Method to get a notification
     *
     * @param   string  $name    The model name. Optional.
     * @param   string  $prefix  The class prefix. Optional.
     * @param   array   $config  Configuration array for model. Optional.
     *
     * @return  BaseDatabaseModel  The model.
     *
     * @since   1.6
     */
    public function getNotificationIdByUniqueKey()
    {
        $app      = Factory::getApplication();
        $input    = $app->input;
        $inputData     = $input->get('uniqueKey', array(), 'array');
        $result = '';
        $url = '';

        Table::addIncludePath(JPATH_ROOT . '/administrator/components/com_tjnotifications/tables');
        $tjnotificationTable = Table::getInstance('Notification', 'TjnotificationTable');
        $tjnotificationTable->load(array('key'=>$inputData[0]));
        $uniqueKeyId = $tjnotificationTable->id;

        if($uniqueKeyId)
        {
            $url = 'index.php?option=com_tjnotifications&view=notification&layout=edit&id='.$uniqueKeyId.'&from=ucmnotification';
            $result = true;
        }
        else
        {
            $result = false;;
        }

        $returnData  =  array('url'=>$url,'success'=> $result);
        
        echo new JsonResponse($returnData);
        $app->close();
    }
}

