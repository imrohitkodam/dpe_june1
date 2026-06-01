<?php
/**
 * @copyright	Copyright (C) 2012-2020 Cedric KEIFLIN alias ced1870
 * https://www.joomlack.fr
 * Module Maximenu CK
 * @license		GNU/GPL
 * */

// no direct access
defined('_JEXEC') or die('Restricted access');
?>
<!-- debut maximenu CK -->
<select name="maximenuckdropselect" id="<?php echo $params->get('menuid', 'maximenuck'); ?>" style="width: auto" onchange="if (this.options[this.selectedIndex].value) top.location.href=this.options[this.selectedIndex].value" class="<?php echo $params->get('class_sfx'); ?>">
	<option value="">- <?php echo \Joomla\CMS\Language\Text::_('MAXIMENUCK_SELECT_ITEM') ?> -</option>
	<?php
	foreach ($items as $i => &$item) {
		$selected = isset($item->current) && $item->current ? ' selected="selected"' : '';
		echo "<option " . $selected . "value=\"" . $item->flink . "\">" . str_repeat("- ", $item->level - 1) . $item->ftitle . "</option>";
	}
	?>
</select>
<!-- fin maximenuCK -->
