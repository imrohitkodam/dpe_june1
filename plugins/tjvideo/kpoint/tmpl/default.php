<?php
/**
 * @package     Tjlms.Plugin
 * @subpackage  Tjlms,TJVideo,kpoint
 *
 * @copyright   Copyright (C) 2005 - 2020 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die('Restricted access');
jimport('joomla.client.http');

use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;

if (is_array($vars) && array_key_exists('totalcount', $vars))
{
	if ((int) $vars['totalcount'] < 0)
	{
		echo Text::_('PLG_TJVIDEO_KPOINT_LIST_NOT_FOUND');
	}
	else
	{
		$input     = Factory::getApplication()->input;
		$lesson_id = $input->get('lesson_id', '0', 'INT');
		$form_id   = $input->get('form_id', ' ', 'STRING');
		$maxSearch = $this->params->get('max_search');
		?>

		<script>
			var VarsameLessonName = 0;
			jQuery(document).ready(function () {
			jQuery("#filter_search").keypress(function (event) {
					var keycode = (event.keyCode ? event.keyCode : event.which);
						if (keycode == "13")
						{
							var filter_search = jQuery("#filter_search").val();
							jQuery.ajax({
							url:"<?php echo Uri::root() .'index.php?option=com_tjlms&task=callSysPlgin&plgType=tjvideo&callType=1&plgName=kpoint&plgtask=getHtmlAjax&lesson_id=' . $lesson_id. '&form_id=' . $form_id . '&qtext=' ?>" + filter_search,
									type: "post",
									datatype : "json",
									success:function(data)
									{
										jQuery("#appendHtml<?php echo $lesson_id;?>").html(data);
										jQuery("#filter_search").val(filter_search);
									},
									error : function(data){
									}
								});
						}
				});

			    jQuery('#sbox-content').scroll(function() {
					var $this   = jQuery(this);
					var results = jQuery("#appendHtml<?php echo $lesson_id;?>");

					if ($this.scrollTop() + $this.height() == results.height())
					{
						loadVideoList();
					}
			 	});
			});

			function getHtml(param)
			{
				if (param == "search")
				{
					jQuery("tbody").hide();
					jQuery("tfoot").show();
					jQuery("button").prop("disabled","disabled");
					jQuery("input").prop("disabled","disabled");
					var filter_search = jQuery("#filter_search").val();

					jQuery.ajax({
					url:"<?php echo Uri::root() .'index.php?option=com_tjlms&task=callSysPlgin&plgType=tjvideo&callType=1&plgName=kpoint&plgtask=getHtmlAjax&lesson_id='.$lesson_id.'&form_id='.$form_id. '&qtext=' ?>" + filter_search,
							type: "post",
							datatype : "json",
							success:function(data)
							{
								jQuery("tfoot").hide();
								jQuery("input").removeAttr("disabled");
								jQuery("button").removeAttr("disabled");
								jQuery("#appendHtml<?php echo $lesson_id;?>").html(data);
								jQuery("#filter_search").val(filter_search);
							},
							error : function(data){
								jQuery("tfoot").hide();
								jQuery("input").removeAttr("disabled");
								jQuery("button").removeAttr("disabled");
								jQuery("#modal-header").hide();
							}
						});
				}
				else if (param == "clear")
				{
					jQuery("tbody").hide();
					jQuery("tfoot").show();
					jQuery("button").prop("disabled","disabled");
					jQuery("input").prop("disabled","disabled");
					jQuery.ajax({
					url:"<?php echo Uri::root() .'index.php?option=com_tjlms&task=callSysPlgin&plgType=tjvideo&callType=1&plgName=kpoint&plgtask=getHtmlAjax&lesson_id='.$lesson_id.'&form_id='.$form_id; ?>",
							type: "post",
							datatype : "json",
							success:function(data)
							{
								jQuery("tfoot").hide();
								jQuery("input").removeAttr("disabled");
								jQuery("button").removeAttr("disabled");
								jQuery("#appendHtml<?php echo $lesson_id;?>").html(data);
							},
							error : function(data){
								jQuery("tfoot").hide();
								jQuery("input").removeAttr("disabled");
								jQuery("button").removeAttr("disabled");
							}
						});
				}
				else
				{
					jQuery("tbody").hide();
					jQuery("tfoot").show();
					jQuery("button").prop("disabled","disabled");
					jQuery("input").prop("disabled","disabled");
					var limit = jQuery("#list_limit").val();
					var endlimit = parseInt(limit) + 1;

					jQuery.ajax({
					url:"<?php echo Uri::root() .'index.php?option=com_tjlms&task=callSysPlgin&plgType=tjvideo&callType=1&plgName=kpoint&plgtask=getHtmlAjax&lesson_id='.$lesson_id.'&form_id='.$form_id.'&qtext=&first=' ?>"+limit + "&max=" +endlimit,
							type: "post",
							datatype : "json",
							success:function(data)
							{
								jQuery("tfoot").hide();
								jQuery("input").removeAttr("disabled");
								jQuery("button").removeAttr("disabled");
								jQuery("#appendHtml<?php echo $lesson_id;?>").html(data);
							},
							error : function(data){
								jQuery("tfoot").hide();
								jQuery("input").removeAttr("disabled");
								jQuery("button").removeAttr("disabled");
							}
						});
				}
			}

			function handleClick(sameLessonName)
			{
				if (sameLessonName.checked == true)
				{
					VarsameLessonName = 1;
				}
				else
				{
					VarsameLessonName = 0;
				}
			}

			function loadVideoList()
			{
				var first     = Number(jQuery('#limit').val());
				var listCount = Number(jQuery('#totalcount').val());
				var max       = <?php echo $maxSearch; ?>;

				max = max > 25 ? 25 : max
				var firstCount = 1;
				var limitValue = first + max;

				first = first + firstCount;

				if (first <= listCount)
				{
					var url = "<?php echo Uri::root() .'index.php?option=com_tjlms&task=callSysPlgin&plgType=tjvideo&plgName=kpoint&plgtask=loadMore&callType=1&lesson_id='.$lesson_id.'&form_id='.$form_id ?>";

					jQuery.ajax({
						url: url + '&first=' + first + '&max=' + max,
						type: "get",
						success: function(response){
							jQuery(".kpoint_video_tr:last").after(response).show().fadeIn("slow");
							jQuery('#limit').val(limitValue);
						}
					});
				}
			}
		</script>

		<div id="appendHtml<?php echo $lesson_id;?>">
			<div class="modal-header" id="modal-header">
				<button type="button" class="close" onclick="window.parent.SqueezeBox.close();" data-dismiss="modal" aria-hidden="true">×</button>
				<h3><?php echo Text::_('PLG_TJVIDEO_KPOINT_LIST'); ?>
			</div>

			<table class="table table-striped" id="categoryList" style="position: relative;">
				<thead>
				<tr><td colspan="2"><div class="btn-wrapper input-append">
					<input type="text" name="filter" id="filter_search" value="" placeholder="Search">

					<button type="submit" class="btn hasTooltip" title="" data-original-title="Search" onclick="getHtml('search');">
						<i class="icon-search"></i>
					</button>

					<button type="submit" class="btn" onclick="getHtml('clear');">
						<i class="icon-remove"></i>
					</button>
				</div>
				</td>
				</thead>
				<!-- <tbody> -->

			<?php if (empty($vars['list'])) { ?>

				<tr>
					<td colspan="4">Record not found</td>
				</tr>

			<?php }
				else{
				?>
			<!-- </tbody> -->
			<tfoot style="display:none;">
			<tr><td colspan="4"><img src="<?php  echo Uri::root() . 'components/com_tjlms/assets/images/loading_squares.gif';?>"></td></tr></tfoot>
			</table>
				<?php

				foreach ($vars['list'] as $key => $value)
				{
					?>
					<div class="controls controls-row kpoint_video_tr">
						<div class="span2 video_img">
							<a href="javascript:void(0)" onclick="parent.bindKpoint('<?php echo $form_id;?>','<?php echo $value['kapsule_id'];?>','<?php echo $value['displayname']; ?>','<?php echo $value['thumbnail_url']; ?>','<?php echo $value['description']; ?>', '<?php echo $value['owner_displayname']; ?>')"><img src="<?php echo $value['images']['thumb'];?>" class="img-polaroid" height="200" width="200"></a>
						</div>
						<div class="span4">
							<div class="video_link">
								<strong><?php echo $value['displayname'];?></strong>
							</div>
							<div class="video_desc">
								<i class="icon-user pull-left"></i>
								<?php echo $value['owner_displayname'];?>
							</div>
						</div>
					</div>

					<?php
				}
					?>
				<input type="hidden" id="totalcount" value="<?php echo $vars['counts']['videos']; ?>">
				<input type="hidden" id="limit" value="<?php echo count($vars['list']); ?>">
				<?php
			}
			?>
		</div>
		<?php
	}
}
else
{
	if (isset($vars['error']))
	{
		echo '<div class="alert alert-danger" role="alert">'.$vars['error']['message'].'</div>';
	}
	else if(is_string($vars))
	{
		echo '<div class="alert alert-danger" role="alert">'.$vars.'</div>';
	}
	else
	{
		if ($vars == 1)
		{
			echo '<div class="alert alert-danger" role="alert">
  <span class="glyphicon glyphicon-exclamation-sign" aria-hidden="true"></span>
  <span class="sr-only">'.Text::_('COM_TJLMS_VIDEO_KPINT_PARAMETER_NOT_FOUND').'</span></div>';
		}
		else
		{
			echo '<div class="alert alert-danger" role="alert">
  <span class="glyphicon glyphicon-exclamation-sign" aria-hidden="true"></span>
  <span class="sr-only">'.Text::_('COM_TJLMS_VIDEO_KPINT_API_NOT_FOUND').'</div>';
		}
	}
}
?>

