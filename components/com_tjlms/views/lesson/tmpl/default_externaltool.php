<?php
/**
 * @package    LMS_Shika
 * @copyright  Copyright (C) 2009 -2010 Techjoomla, Tekdi Web Solutions . All rights reserved.
 * @license    GNU GPLv2 <http://www.gnu.org/licenses/old-licenses/gpl-2.0.html>
 * @link       http://www.techjoomla.com
 */
// No direct access
defined('_JEXEC') or die('Restricted access');
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Factory;

jimport('joomla.html.pane');

if (!empty($this->sub_format))
{
	$config = array();

	$config['file']	= $this->source;
	$config['lesson_id'] = $this->lesson_id;
	$config['attempt'] = $this->attempt;
	$config['current'] = 1;
	$config['lesson_data'] = $this->lesson;
	$config['course'] =$this->course_info;
	$config['params'] = $this->lesson_typedata->params;
	if (!empty($this->lastattempttracking_data))
	{
		$config['current'] = $this->lastattempttracking_data->current_position;
	}

	// Trigger all sub format  video plugins method that renders the video player
	PluginHelper::importPlugin('tjexternaltool', $this->pluginToTrigger);
	$result = Factory::getApplication()->triggerEvent('on' . $this->pluginToTrigger . 'renderPluginHTML', array($config));

	echo $result[0];
}
