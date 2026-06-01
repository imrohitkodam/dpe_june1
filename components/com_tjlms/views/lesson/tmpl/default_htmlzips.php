<?php
/**
 * @package InviteX
 * @copyright Copyright (C) 2009 -2010 Techjoomla, Tekdi Web Solutions . All rights reserved.
 * @license GNU GPLv2 <http://www.gnu.org/licenses/old-licenses/gpl-2.0.html>
 * @link     http://www.techjoomla.com
 */

defined('_JEXEC') or die('Restricted access');
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Factory;

jimport('joomla.html.pane');
?>
<style>
	iframe{margin:0px;padding:0px;height:100%;}
	iframe{display:block;}
</style>
<script type="text/javascript">
	var root_url	=	"<?php echo JURI::base();?>";
</script>
<?php
if (!empty($this->sub_format))
{
	$config = array();
	$lesson_entry_file = Uri::base().'media/com_tjlms/lessons/'.$this->lesson_id.'/index.html';
	$config['file']	= $lesson_entry_file;
	$config['lesson_id'] = $this->lesson_id;
	$config['attempt'] = $this->attempt;
	$config['current'] = 1;
	$config['lesson_data'] = $this->lesson;

	PluginHelper::importPlugin('tjhtmlzips', $this->pluginToTrigger);
	$result = Factory::getApplication()->triggerEvent('on' . $this->pluginToTrigger . 'renderPluginHTML', array($config));

	echo $result[0];
}
?>
