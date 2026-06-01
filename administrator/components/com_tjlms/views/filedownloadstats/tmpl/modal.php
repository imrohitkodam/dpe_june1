<?php
/**
 * @package     TJLms
 * @subpackage  com_shika
 *
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (C) 2009 - 2020 Techjoomla. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Router\Route;
use Joomla\Registry\Registry;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Session\Session;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Layout\LayoutHelper;

$params     = Tjlms::config();
$dateFormat = $params->get('date_format_show', 'Y-m-d H:i:s');
$fileId     = Factory::getApplication()->input->get('fileId');
HTMLHelper::_('script', 'administrator/components/com_tjlms/assets/js/tjlms_admin.js');
?>
<div class="row existing-tests-modal" id="userStatusModal">
	<form action="<?php echo Route::_('index.php?option=com_tjlms&view=filedownloadstats&layout=modal&tmpl=component'); ?>" method="post" name="adminForm" id="adminForm">

		<div class="top-heading">
			<!-- set componentheading -->
			<h2 class="componentheading" style="margin-bottom: 47px;">
					<?php echo Text::_('COM_TJLMS_HEADING_USER_TIME_STATS');?>
			</h2>
			<?php
			// Search tools bar
			echo LayoutHelper::render('joomla.searchtools.default', array('view' => $this));
			?>

		</div>

			<div class="row">
				<table class="category table table-striped table-bordered table-hover">
					<thead>
						<tr>
							<th>
								<?php echo Text::_('COM_TJLMS_TITLE_FILE_DOWNLAOD_STATUS_USERNAME'); ?>
							</th>
							<th>
								<?php echo Text::_('COM_TJLMS_TITLE_FILE_DOWNLAOD_STATUS_TIME'); ?>
							</th>
						</tr>
					</thead>
					<tbody>
						<?php
						foreach ($this->items as $item)
						{
							$dateTime = json_decode($item->downloads, true);
						?>
							<tr>
								<td><?php echo Factory::getUser($item->user_id)->name; ?></td>
								<td><?php echo HTMLHelper::date($dateTime['dateTime'], $dateFormat); ?></td>
							</tr>
						<?php
						}?>
					</tbody>
				</table>
			</div><!--row-fluid-->

			<div class="row">
					<div class="pagination">
						<?php echo $this->pagination->getListFooter(); ?>
					</div>
					<hr class="hr hr-condensed"/>
			</div><!--row-fluid-->
			<input type="hidden" name="fileId" value=" <?php echo $fileId; ?>" />
			<input type="hidden" name="task" value="" />
			<input type="hidden" name="<?php echo Session::getFormToken();?>" value="1" data-js-id="form-token"/>
		</form>
</div><!--row-fluid-->
