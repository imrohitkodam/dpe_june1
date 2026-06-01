<?php
/**
 * @version    SVN: <svn_id>
 * @package    Com_Tjlms
 * @copyright  Copyright (C) 2005 - 2014. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * Shika is free software. This version may have been modified pursuant
 * to the GNU General Public License, and as distributed it includes or
 * is derivative of works licensed under the GNU General Public License or
 * other free or open source software licenses.
 */

defined('_JEXEC') or die('Unauthorized Access');
use Joomla\CMS\Language\Text;
?>
<div id="version-widget-content">
	<h4 class="center">
		<span><?php echo Text::_('COM_TJLMS_VERSION_HEADER_UPDATE_REQUIRED');?></span>
	</h4>
	<hr />
	<p>
		<?php echo Text::_('COM_TJLMS_VERSION_OUTDATED_VERSION_INFO');?>
	</p>

	<table class="table table-striped">
		<tr>
			<td>
				<?php echo Text::_('COM_TJLMS_VERSION_INSTALLED_VERSION');?>
			</td>
			<td>
				<?php echo $installed;?>
			</td>
		</tr>
		<tr>
			<td>
				<?php echo Text::_('COM_TJLMS_VERSION_LATEST_VERSION');?>
			</td>
			<td>
				<?php echo $latest;?>
			</td>
		</tr>
	</table>

	<div class="center">
		<a href="index.php?option=com_installer&view=update" target="_blank" class="btn btn-es-success btn-sm">
			<?php echo Text::_('COM_TJLMS_GET_UPDATES_BUTTON');?>
		</a>
	</div>
</div>
