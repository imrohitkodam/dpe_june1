<?php
/**
 * @package     TJCertificate
 * @subpackage  com_tjcertificate
 *
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (C) 2009 - 2020 Techjoomla. All rights reserved.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 */

// No direct access to this file
defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\Language\Text;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\HTML\HTMLHelper;

HTMLHelper::_('jquery.token');

$view   = $displayData['view'];
$notify = $displayData['notify'];

?>
<div id="importcsv" class="modal hide fade" role="dialog">
	<div class="modal-dialog">
		<button type="button" class="close" data-dismiss="modal" style="width: 40px;opacity: 0.7;">&times;</button>
		<!-- Modal content-->
		<div class="modal-content">
			<div class="modal-header">
				<h4 class="modal-title"><?php echo Text::_('COM_TJCOMPETENCY_IMPORT_CSV'); ?></h4>
				<p class="alert alert-info" style="display:none;" id="show-info"></p>
				<p class="alert alert-info" style="display:none;" id="show-log"></p>
				<div class="alert alert-error" id="show-error" style="display:none;"></div>
				<div class="form-group" id="process" style="display:none;">
		        <div class="progress progress-striped">
		         <div class="bar" role="progressbar" aria-valuemin="0" aria-valuemax="100">
		          <span id="process_data">0</span><!--  - --> <span style="display:none;" id="total_data">0</span>
		         </div>
		        </div>
		       </div>
			</div>
			<div class="modal-body">
				<form id="uploadForm" class="form-inline center" name="uploadForm" method="post" enctype="multipart/form-data">
					<table>
						<tr>&nbsp;</tr>
						<tr>
							<fieldset id="upload-noflash" class="actions">
								<label for="upload-file" class="control-label"><?php echo Text::_('COM_TJCOMPETENCY_UPLOADE_FILE'); ?></label>
								<input type="file" name="csvfile" id="csvfile" accept=".csv"/>
								<button type="submit" class="btn btn-primary" id="upload-submit">
									<i class="icon-upload icon-white"></i><?php echo Text::_('COM_TJCOMPETENCY_IMPORT_CSV'); ?>
								</button>

								<?php
								if ($notify)
								{
									?>
									&nbsp;
									<input value="1" id="notify_user_import" type="checkbox" name="notify_user_import" /> Notify User
									<?php
								}
								?>

								<hr class="hr hr-condensed">
								<div class="alert alert-warning" role="alert"><i class="icon-info"></i>
										<?php
										$link = '<a href="' . Uri::root() . 'media/com_tjcompetency/samplecsv/' . $view . '_import.csv' . '">here</a>';
									echo Text::sprintf('COM_TJCOMPETENCY_SAMPLE_CSV', $link);
									?>
								</div>
							</fieldset>
						</tr>
					</table>
					<?php echo HTMLHelper::_('form.token'); ?>
				</form>
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
			</div>
		</div>
	</div>
</div>

<script type="text/javascript">
	jQuery(document).ready(function () {

		jQuery('#importcsv').on('hidden.bs.modal', function () {
			location.reload();
		});

		var clearTimer;

		jQuery(document).on('submit', '#uploadForm', function (e) {
			e.preventDefault();

			jQuery.ajax({
				url:"<?php echo Uri::base(); ?>index.php?option=com_tjcompetency&task=<?php echo $view;?>.csvImport&tmpl=component&format=html",
			    method:"POST",
			    data: new FormData(this),
			    dataType:"json",
			    contentType:false,
			    cache:false,
			    processData:false,
			    beforeSend:function() {
				    jQuery('#upload-submit').attr('disabled', 'disabled');
				    jQuery('#csvfile').attr('disabled', 'disabled');
				    jQuery('#upload-submit').html('<i class="icon-upload icon-white"></i><?php echo Text::_('COM_TJCOMPETENCY_IMPORTING_CSV'); ?>');
			    },
			    success:function(data) {
					if(data.success)
					{
						jQuery('#total_data').text(data.total_line);

						csvProcess();

						clearTimer = setInterval(csvProgress, 2000);
					}
					else if(data.error)
					{
						jQuery('#show-info').css('display', 'block');
						jQuery('#show-info').html(data.error);
						jQuery('#csvfile').attr('disabled', false);
						jQuery('#upload-submit').attr('disabled', false);
						jQuery('#upload-submit').html('<i class="icon-upload icon-white"></i><?php echo Text::_('COM_TJCOMPETENCY_IMPORT_CSV'); ?>');
					}
			    }
			});
		});

		function csvProcess()
		{
			jQuery('#process').css('display', 'block');

			jQuery.ajax({
				url:"<?php echo Uri::base(); ?>index.php?option=com_tjcompetency&task=<?php echo $view;?>.csvProcess",
				dataType:"json",
				success:function(data)
				{
					if(data.log)
					{
						jQuery('#show-info').css('display', 'block');
						jQuery('#show-info').html('<?php echo Text::_('COM_TJCOMPETENCY_CSV_DATA_IMPORTED'); ?>');

						jQuery('#show-log').css('display', 'block');
						jQuery('#show-log').html(data.log);
					}
					
					if(data.error)
					{
						clearInterval(clearTimer);
						csvFileDelete();

						setTimeout(function(){ jQuery('#process').css('display', 'none'); }, 3000);

						jQuery('#show-error').css('display', 'block');
						jQuery('#show-error').html(data.error);

						jQuery('#csvfile').val('');
						jQuery('#csvfile').attr('disabled', false);
						jQuery('#upload-submit').attr('disabled', false);
						jQuery('#upload-submit').html('<i class="icon-upload icon-white"></i><?php echo Text::_('COM_TJCOMPETENCY_IMPORT_CSV'); ?>');
					}
				}
			});
		}

		function csvProgress()
		{
			jQuery.ajax({
				url:"<?php echo Uri::base(); ?>index.php?option=com_tjcompetency&task=<?php echo $view;?>.csvProgress",
				dataType:"json",
				success:function(data)
				{
					jQuery('#process').css('display', 'block');

					var proCount  = parseInt(data.progress_count);
					var totalData = parseInt(jQuery('#total_data').text());
					var width     = Math.round((proCount / totalData) * 100);

					jQuery('#process_data').text(width + '%');

					if (width >= 1)
					{
						jQuery('.bar').css('width', width + '%');
					}

					if (width >= 100)
					{
						clearInterval(clearTimer);
						csvFileDelete();

						setTimeout(function(){ jQuery('#process').css('display', 'none'); }, 3000);

						setTimeout(function(){
							jQuery('#show-info').css('display', 'block');
							jQuery('#show-info').html('<?php echo Text::_('COM_TJCOMPETENCY_CSV_DATA_IMPORTED'); ?>');
						}, 500);

						jQuery('#csvfile').val('');
						jQuery('#csvfile').attr('disabled', false);
						jQuery('#upload-submit').attr('disabled', false);
						jQuery('#upload-submit').html('<i class="icon-upload icon-white"></i><?php echo Text::_('COM_TJCOMPETENCY_IMPORT_CSV'); ?>');
					}
				}
			});
		}

		function csvFileDelete()
		{
			jQuery.ajax({
				url:"<?php echo Uri::base(); ?>index.php?option=com_tjcompetency&task=<?php echo $view;?>.csvFileDelete",
				dataType:"json",
				success:function(data)
				{
				}
			});
		}
	});
</script>
