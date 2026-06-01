<?php
/**
 * @version    SVN: <svn_id>
 * @package    TMT
 * @author     Techjoomla <contact@techjoomla.com>
 * @copyright  Copyright (C) 2012-2013 Techjoomla. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access
defined('_JEXEC') or die;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Factory;

jimport('joomla.application.component.controller');

/**
 * TMT controller Class.
 *
 * @since  1.0
 */
class TmtController extends BaseController
{

	function test(){
				$db = Factory::getDBO();
		$query="SELECT id,rules FROM `#__assets` WHERE  `name` = 'com_tmt' ";
		$db->setQuery($query);
		$result=$db->loadobject();

		//print_r($result);die;
		if(strlen(trim($result->rules))<=3)
		{

			$obj=new Stdclass();

			$obj->id=$result->id;
$obj->rules='{"core.admin":[],"core.manage":{"6":1,"2":1},"core.create":{"6":1,"2":1},"core.delete":{"6":1,"2":1},"core.edit":{"6":1,"2":1},"core.edit.state":{"6":1,"2":1},"core.edit.own":{"6":1,"2":1}}';

			if(!$db->updateObject('#__assets',$obj,'id'))
			{
				$app = Factory::getApplication();
				$app->enqueueMessage($db->stderr(), 'error');
			}
		}
	}
}
