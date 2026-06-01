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

if (!empty($this->sub_format))
{

		$attempt = $this->attempt;

		$pageToView	= 0;
		if(!empty($this->lastattempttracking_data))
			$pageToView = $this->lastattempttracking_data->current_position;

		if ($pageToView == 0)
		{
			$pageToView = 1;
		}
	?>
	<div id="main_doc_container" class="main_doc_container">
		<?php

			$config	= array();
			$config['lesson_id'] = $this->lesson_id;
			$config['attempt'] = $this->attempt;
			$config['current'] = $pageToView;

			if (!empty($this->params->document_id))
			{
				$config['document_id'] = $this->params->document_id;
			}

			if (!empty($this->source))
			{
				$config['source'] = $this->source;
			}

			$config['user_id'] = $this->user_id;

			PluginHelper::importPlugin('tjdocument', $this->pluginToTrigger);
			$result = Factory::getApplication()->triggerEvent('on' . ucfirst($this->pluginToTrigger) . 'renderPluginHTML',array($config));

			echo $result[0];
		?>
	</div>
<?php } ?>
