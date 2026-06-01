<?php
/**
 * @version    SVN: <svn_id>
 * @package    Com_Dpe
 * @author     Techjoomla <extensions@techjoomla.com>
 * @copyright  Copyright (c) 2009-2019 TechJoomla. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;
use Joomla\CMS\Factory;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\MVC\View\HtmlView;

require_once JPATH_ADMINISTRATOR . '/components/com_tjfields/helpers/tjfields.php';


/**
 * HTML View class for the Content component
 *
 * @since  1.5
 */
class DpeViewucmnotification extends HtmlView
{
    public function display($tpl = null)
    {

       $input          = Factory::getApplication()->getInput();  
       $model          = $this->getModel();
       $this->item     = $this->get('Item');

       $this->form     = $this->get('Form');
       $this->parentId = $input->get('id');
       $this->client   = $input->get('client');
       $this->fields   = $model->getFieldByClient($this->client);

       $tjFieldsHelper = new TjfieldsHelper;
       $this->optionsData = $tjFieldsHelper->getOptions($input->get('id'));

       parent::display($tpl);
   }
}
