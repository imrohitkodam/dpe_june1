use Joomla\CMS\Language\Text;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Uri\Uri;
<?php
if(!empty($this->assignUserArray))
{
	if(empty($this->defaultUser))
	{
		echo "<div class='alert alert-warning'>" . Text::_('COM_DPE_NOT_ASSIGNED_USER') . "</div>";
	}
?>
	<div class="row mt-20 mb-20">
		<div class="col-md-4 mt-5">
			<?php echo Text::_('COM_DPE_CONTENT_ASSIGN_USER'); ?>
		</div>
		<div class="col-md-6">
		<?php
			echo HTMLHelper::_('select.genericlist', $this->assignUserArray, 'jform[assign_users]', array('class'=>'required chzn-container changecomment', 'onChange'=> 'changeContext('.$this->lessonData->created_by.', this.value)'), 'value', 'text',$this->defaultUser);
		?>
		</div>
	</div>
<?php
}
?>

<div id="privateCommentsDiv" role="tabpanel" class="tab-pane" style=""
	data-jlike-url="<?php echo $this->lessonUrl; ?>"
	data-jlike-site-domain="<?php echo Uri::root(); ?>"
	data-jlike-key="<?php echo $apiKey;?>"
	data-jlike-type="annotations"
	data-jlike-client="com_tjlms.lesson"
	data-jlike-cont-id="<?php echo $this->urldata->cont_id; ?>"
	data-jlike-title=""
	data-jlike-limitstart="0"
	data-jlike-limit="5"
	data-jlike-userid="<?php echo $this->userInfo->id; ?>"
	data-jlike-ordering="annotation_date"
	data-jlike-contentid="<?php echo $this->contentId; ?>"
	data-jlike-context="<?php echo $this->context; ?>"
	data-jlike-direction="DESC"
>
</div>
