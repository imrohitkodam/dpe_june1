<?php
/**
 * @package com_api
 * @copyright Copyright (C) 2009 2014 Techjoomla, Tekdi Technologies Pvt. Ltd. All rights reserved.
 * @license GNU GPLv2 <http://www.gnu.org/licenses/old-licenses/gpl-2.0.html>
 * @link http://techjoomla.com
 * Work derived from the original RESTful API by Techjoomla (https://github.com/techjoomla/Joomla-REST-API) 
 * and the com_api extension by Brian Edgerton (http://www.edgewebworks.com)
*/

defined('_JEXEC') or die( 'Restricted access' );
use Joomla\CMS\Factory;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Session\Session;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Table\Table;

jimport('joomla.application.component.controller');

class ApiControllerKeys extends ApiController {


	public function display($cachable = false, $urlparams = array()) {
		parent::display();
	}

	private function checkAccess() {
		$user	= Factory::getUser();

		if ($user->get('gid') == 25) :
			return true;
		endif;

		$params	= ComponentHelper::getParams('com_api');

		if (!$params->get('key_registration')) :
			return false;
		endif;

		$access_level = $params->get('key_registration_access');

		if ($user->get('gid') < $access_level) :
			return false;
		endif;

		return true;
	}

	public function cancel() {

		//JRequest::checkToken() or jexit(JText::_("COM_API_INVALID_TOKEN"));
		 Session::checkToken() or jexit(Text::_("COM_API_INVALID_TOKEN"));

		$this->setRedirect(Route::_('index.php?option=com_api&view=keys', FALSE));
	}

	public function save() {

		Session::checkToken('default') or jexit(Text::_("COM_API_INVALID_TOKEN"));

		//vishal - for j3.2
		$app = Factory::getApplication();
		$id 	= $app->input->post->get('id',0,'INT');

		if (!$id && !$this->checkAccess()) :
			Factory::getApplication()->redirect('index.php', Text::_('COM_API_NOT_AUTH_MSG'));
			exit();
		endif;

		//$domain	= JRequest::getVar('domain', '', 'post', 'string');
		$domain	= $app->input->post->get('domain','','STRING');

		$data	= array(
			'id'		=> $id,
			'domain'	=> $domain,
			'user_id'	=> Factory::getUser()->get('id'),
			'enabled'	=> 1
		);

		$model	= JModel::getInstance('Key', 'ApiModel');

		if ($model->save($data) === false) :
			$this->setRedirect($_SERVER['HTTP_REFERER'], $model->getError(), 'error');
			return false;
		endif;

		$this->setRedirect(Route::_('index.php?option=com_api&view=keys'), Text::_('COM_API_KEY_SAVED'));

	}

	public function delete() {

		//vishal - for j3.2
    	$app = Factory::getApplication();

		//$key = $app->input->get('key');
		//JRequest::checkToken('request') or jexit(JText::_("COM_API_INVALID_TOKEN"));
		Session::checkToken('default') or jexit(Text::_("COM_API_INVALID_TOKEN"));

		if (!$this->checkAccess()) :
			Factory::getApplication()->redirect('index.php', Text::_('COM_API_NOT_AUTH_MSG'));
			exit();
		endif;

		$user_id	= Factory::getUser()->get('id');
		//$id 		= JRequest::getInt('id', 0);
		$id 		= $app->input->get('id','','INT');

		$table 	= Table::getInstance('Key', 'ApiTable');
		$table->load($id);

		if ($user_id != $table->user_id) :
			$this->setRedirect($_SERVER['HTTP_REFERER'], Text::_("COM_API_UNAUTHORIZED_DELETE_KEY"), 'error');
			return false;
		endif;

		$table->delete($id);

		$this->setRedirect($_SERVER['HTTP_REFERER'], Text::_("COM_API_SUCCESSFUL_DELETE_KEY"));

	}

}
