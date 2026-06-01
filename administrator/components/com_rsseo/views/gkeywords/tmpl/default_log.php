<?php
/**
* @package RSSeo!
* @copyright (C) 2016 www.rsjoomla.com
* @license GPL, http://www.gnu.org/copyleft/gpl.html
*/

defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\Language\Text;
use Joomla\CMS\Factory;
?>

<div class="container-fluid">
	<?php if ($this->logs) { ?>
	<table class="table table-striped">
	<?php foreach ($this->logs as $log) { ?>
	<tr>
		<td><?php echo Factory::getDate($log->date)->format(rsseoHelper::getConfig('global_dateformat')); ?></td>
		<td><?php echo $log->message; ?></td>
	</tr>
	<?php } ?>
	</table>
	<?php } else { ?>
	<?php echo Text::_('COM_RSSEO_GKEYWORDS_LOG_EMPTY'); ?>
	<?php } ?>
</div>