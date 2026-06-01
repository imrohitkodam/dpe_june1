<?php
/**
* @package RSSeo!
* @copyright (C) 2016 www.rsjoomla.com
* @license GPL, http://www.gnu.org/copyleft/gpl.html
*/

defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\Language\Text;
?>
<?php if (is_array($this->general)) { ?>
<fieldset class="options-form">
	<legend><?php echo Text::_('COM_RSSEO_GA_GENERAL'); ?></legend>
	<table class="table table-striped table-bordered">
		<tbody>
		<?php if (!empty($this->general)) { ?>
		<?php foreach ($this->general as $result) { ?>
			<tr>
				<td class="hasTooltip" title="<?php echo $result->descr; ?>" style="text-align:right;"><?php echo $result->title; ?></td>
				<td class="key" style="text-align:left;"><?php echo $result->value; ?></td>
			</tr>
		<?php } ?>
		<?php } ?>
		</tbody>
	</table>
</fieldset>
<?php } else echo $this->general; ?>