<?php
/**
* @package RSSeo!
* @copyright (C) 2016 www.rsjoomla.com
* @license GPL, http://www.gnu.org/copyleft/gpl.html
*/

defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\Language\Text;
?>

<?php if (!empty($this->dates)) { ?>
<button id="rsseoAllBtn" onclick="RSSeo.importAllKeywordData();" class="btn <?php echo RSSeoAdapterGrid::fdirection('pull-left'); ?> btn-primary" type="button">
	<i id="rsseoAll" style="display:none;" class="fa fa-spinner fa-pulse fa-fw"></i> 
	<span><?php echo Text::_('COM_RSSEO_GKEYWORD_RUN_ALL'); ?></span>
</button>
<?php } ?>

<div id="rsseo_import_message">&nbsp;</div>