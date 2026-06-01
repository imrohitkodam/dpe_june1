<?php
/**
 * @package Tjlms
 * @copyright Copyright (C) 2009 -2010 Techjoomla, Tekdi Web Solutions . All rights reserved.
 * @license GNU GPLv2 <http://www.gnu.org/licenses/old-licenses/gpl-2.0.html>
 * @link     http://www.com
 */
defined('_JEXEC') or die('Restricted access');
use Joomla\CMS\Factory;
use Joomla\CMS\Session\Session;
use Joomla\CMS\Language\Text;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Uri\Uri;

$source = (isset($lesson->source)) ? $lesson->source : '';
$preview_class = "tjlms_display_none";
$html_src = '';
$user_id = Factory::getUser()->id;
$token = Session::getFormToken();

$subformat = $source_plugin = $source_option = '';

/*Check subformat is empty or not*/
if (!empty($lesson->sub_format))
{
	$subformat = $lesson->sub_format;

	$subformat_source_options = explode('.', $subformat);
	$source_plugin = $subformat_source_options[0];
	$source_option = $subformat_source_options[1];
}
$newArticleLink = JURI::base() . 'index.php?option=com_content&view=article&layout=edit';
?>
<div class="control-group"></div>
<?php
if ($articleCnt == 0 && empty($subformat))
{?>
<div class="alert">
	<span><?php echo Text::sprintf('PLG_TJTEXTMEDIA_JC_CREATE_NEW_ARTICLE', $newArticleLink);?></span>
</div>
<?php
}
else
{
?>
<div class="control-group">
	<div class="control-label">
		<label for="jform_request_id_id" title="<?php echo Text::_("PLG_TJTEXTMEDIA_JC_SELECT_ARTICLE_TITLE");?>" >
		<?php echo JText::_("PLG_TJTEXTMEDIA_JC_SELECT_ARTICLE");?> <span class="star">&nbsp;*</span> </label>
	</div>
	<div class="controls">
		<span class="input-append">
		<?php
		if (!empty($source_option) && $source_plugin == 'joomlacontent')
		{
				$source = trim($lesson->source);
				$path = $lesson->source;
				$params = json_decode($lesson->media['params']);
				$previewArticle = $this->articleIsPublished($params->contentid);
		?>
			<input type="text" class="input-large" id="selected_article" value="<?php echo $params->contentnm;?>" disabled="disabled">
			<a href="#modalArticlejform_request_id" id="openJoomlaContent" data-bs-target="#openJoomlaContent" class="btn" role="button" data-bs-toggle="modal"
				title="<?php echo Text::_('PLG_TJTEXTMEDIA_JC_CHANGE_ARTICLE');?>"
				data-original-title="Select article" onclick="openArticleContent(this,'edit','openJoomlaContent')">
				<?php echo Text::_("PLG_TJTEXTMEDIA_JC_CHANGE");?>
			</a>
	<?php   if (is_int($previewArticle))
			{	?>
				<a class="btn btn-primary" onclick="tjlmsAdmin.lesson.preview('previewContent', <?php echo $lesson_id; ?>);"
					title="<?php echo Text::_('PLG_TJTEXTMEDIA_JC_PREVIEW_TITLE');?>" role="button" >
					<?php echo Text::_('PLG_TJTEXTMEDIA_JC_PREVIEW');?>
				</a>
				<?php
					$link = Uri::root() . "index.php?option=com_tjlms&view=lessons&tmpl=component&lesson_id=" .  $lesson_id . "&mode=preview&ptype=" .  $lesson->format . "&isAdmin=1";

					echo HTMLHelper::_(
						'bootstrap.renderModal',
						'previewContent' . $lesson_id,
						array(
							'url'        => $link,
							'width'      => '800px',
							'height'     => '300px',
							'modalWidth' => '80',
							'bodyHeight' => '70'
						)
					);?>
			<?php
			}
			else
			{	?>
				<a class="btn btn-disabled" title="<?php echo $previewArticle;?>">
					<i rel="popover" class="icon-lock" ></i><span class="lesson_attempt_action">
						<?php echo Text::_('PLG_TJTEXTMEDIA_JC_PREVIEW');?>
					</span>
				</a>
<?php		}
		?>
<?php	}else{ ?>

			<input type="text" class="input-large" id="jform_request_id_name" value="Select an Article" disabled="disabled">
			<a href="#modalArticlejform_request_id" id="#openJoomlaContent" data-bs-target="#openJoomlaContent" class="btn hasTooltip btn-primary btn-file" role="button"
				data-bs-toggle="modal" title="<?php echo Text::_('PLG_TJTEXTMEDIA_JC_SELECT_ARTICLE');?>"
				data-original-title="Select article" onclick="openArticleContent(this,'add','openJoomlaContent')">
				<?php echo Text::_("PLG_TJTEXTMEDIA_JC_SELECT");?>
			</a>
<?php	}
		?>

		</span>
	</div>
</div>
<!--	Modal	-->

<div class="modal fade" id="openJoomlaContent" tabindex="-1" aria-labelledby="openJoomlaContent" aria-hidden="true">
			<div class="modal-dialog">
			<div class="modal-content">
				<div class="modal-header">
					<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
				</div>
				<div class="modal-body"></div>
			</div>
			</div>
		</div>
	<input type="hidden" id="subformatoption" name="lesson_format[joomlacontent][subformatoption]" value="url"/>
	<input type="hidden" id="joomlacontent_url" name="lesson_format[joomlacontent][url]">
	<input type="hidden" id="joomlacontent_params" name="lesson_format[joomlacontent][params]" />
<?php
}
?>

<script type="text/javascript" language="javascript">

	jQuery(document).ready(function()
	{
		validArticle=function(id,title,catid,uk1,url,uk2,uk3)
		{
			var source = {contentid: id, contentnm: title};
			var jsonString = JSON.stringify(source);

			/*	Set Dynamic value to textarea after selection of article */
			jQuery("#joomlacontent_params").val(jsonString);
			document.getElementById("joomlacontent_url").value =url;
			jQuery('#jform_request_id_name').val(title);
			jQuery('#selected_article').val(title);
			var myModalEl = document.getElementById('openJoomlaContent');
			var modal = bootstrap.Modal.getInstance(myModalEl)
			modal.hide();
		}
    });

	function openArticleContent(thislink,action,modalId = 'openJoomlaContent')
	{
		var format_form	=	jQuery(thislink).closest(".lesson-format-form");
		var format_form_id	=	jQuery(format_form).attr("id");
		var form_id	=	format_form_id.replace("lesson-format-form_","")

		var lesson_id	=	jQuery("[data-js-id='id']",format_form).val();

		var content_link = "<?php echo JUri::root();?>" + "administrator/index.php?option=com_tjlms&view=lessons&layout=modal&tmpl=component&function=validArticle&filter_published=1&" + "<?php echo $token; ?>" + "=1";

		// var wwidth = jQuery(window).width()-10;
		// var wheight = jQuery(window).height()-10;

		jQuery.ajax({
			url: content_link,
			type: "GET",
			cache: false,
			success: function(response)
			{
				jQuery('#' + modalId + " .modal-dialog");
				jQuery('#' + modalId + " .modal-content .modal-body").html(response);
				jQuery('.joomla-script-options').addClass('d-none');
			}
		});
	}

	function validatetextmediajoomlacontent(formid,format,subformat,media_id)
	{
		var res = {check: 1, message: ""} ,format_lesson_form ,newContent = oldContent = '';
		format_lesson_form = jQuery("#lesson-format-form_"+ formid);
		newContent = jQuery("#lesson_format #" + format + " #jform_request_id_name",format_lesson_form).val()
		oldContent = jQuery("#lesson_format #" + format + " #selected_article",format_lesson_form).val()

		/*Check both newContent and oldContent are empty then return error media id if it is zero than return error*/
		if( (oldContent == undefined || oldContent == '' ) && newContent == "Select an Article" )
		{
			res.check = '0';
			res.message = "<?php echo Text::_('PLG_TJTEXTMEDIA_JC_URL_MISSING');?>";
		}

		return res;
	}
</script>
