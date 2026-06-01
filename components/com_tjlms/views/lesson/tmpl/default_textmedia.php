<?php

/**
 * @package Tjlms
 * @copyright Copyright (C) 2009 -2010 Techjoomla, Tekdi Web Solutions . All rights reserved.
 * @license GNU GPLv2 <http://www.gnu.org/licenses/old-licenses/gpl-2.0.html>
 * @link     http://www.techjoomla.com
*/
defined('_JEXEC') or die('Restricted access');
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Factory;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Plugin\CMSPlugin;

include_once JPATH_ROOT.DS.'administrator/components/com_tjlms/js_defines.php';

$reqURI = Uri::root();

// If host have wwww, but Config doesn't.
if (isset($_SERVER['HTTP_HOST']))
{
	if ((substr_count($_SERVER['HTTP_HOST'], "www.") != 0) && (substr_count($reqURI, "www.") == 0))
	{
		$reqURI = str_replace("://", "://www.", $reqURI);
	}
	elseif ((substr_count($_SERVER['HTTP_HOST'], "www.") == 0) && (substr_count($reqURI, "www.") != 0))
	{
		// Host do not have 'www' but Config does
		$reqURI = str_replace("www.", "", $reqURI);
	}
}

?>
<script>
var root_url = "<?php echo $reqURI;?>"
</script>
<?php

$input = Factory::getApplication()->input;

$sub_layout = $input->get('sub_layout','','STRING');
$config = array();
if (empty($sub_layout))
{
	if (!empty($this->sub_format))
	{
		$config['file'] = $this->source;
		$config['lesson_id'] = $this->lesson_id;
		$config['attempt'] = $this->attempt;
		$config['current'] = 1;
		$config['source'] = $this->lesson_typedata->source;
		$config['user_id'] = Factory::getUser()->id;
	}
}
else
{
	$this->pluginToTrigger = $input->get('pluginToTrigger','0','STRING');
	$lesson_id = $input->get('lesson_id','0','INT');
	$form_id = $input->get('form_id','0','STRING');
	$config['user_id'] = $input->get('user_id','0','INT');
	$config['lesson_id'] = $lesson_id;
	$config['form_id'] = $form_id;
	$config['current'] = 1;
	$config['sub_layout'] = 'creator';

	$this->lesson_data = $this->model->getlessondata($lesson_id);
	$this->lesson_typedata = $this->model->getlesson_typedata($lesson_id, 'tjtextmedia');
	if (isset($this->lesson_typedata->source))
	{
		$config['source'] = $this->lesson_typedata->source;
	}

	if (isset($this->lesson_data->media_id))
	{
		$config['media_id'] = $this->lesson_data->media_id;
	}

	//die;
	/*if ($input->get('action', '', 'string') == 'edit' || $input->get('action', '', 'string') == 'add')
	{
		if (isset ($this->lesson_typedata->source))
		{
			$config['source'] = $this->lesson_typedata->source;
		}
	}
	if ($lesson_id)
	{
		if (isset($this->lesson_data->media_id))
		{
			$config['media_id'] = $this->lesson_data->media_id;
		}
	}*/
}

// Trigger all sub format  video plugins method that renders the video player
PluginHelper::importPlugin('tjtextmedia', $this->pluginToTrigger);
$result = Factory::getApplication()->triggerEvent('on' . $this->pluginToTrigger . 'renderPluginHTML', array($config));

echo $result[0];
