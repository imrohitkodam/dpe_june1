<?php
/**
* @package RSSeo!
* @copyright (C) 2016 www.rsjoomla.com
* @license GPL, http://www.gnu.org/copyleft/gpl.html
*/

defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\Language\Text;
?>

<button class="btn btn-danger" type="button" onclick="if (confirm('<?php echo Text::_('COM_RSSEO_DELETE_LOG_CONFIRM',true); ?>')) Joomla.submitbutton('gkeywords.deletelog')">
	<?php echo Text::_('COM_RSSEO_DELETE_LOG'); ?>
</button>