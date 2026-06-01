use Joomla\CMS\Uri\Uri;
<div id="publicCommentsDiv" role="tabpanel" class="tab-pane" style=""
	data-jlike-url="<?php echo $this->lessonUrl; ?>"
	data-jlike-site-domain="<?php echo Uri::root(); ?>"
	data-jlike-key="<?php echo $apiKey;?>"
	data-jlike-type="annotations"
	data-jlike-subtype="public"
	data-jlike-client="com_tjlms.lesson"
	data-jlike-cont-id="<?php echo $this->urldata->cont_id; ?>"
	data-jlike-title=""
	data-jlike-limitstart="0"
	data-jlike-limit="5"
	data-jlike-userid="<?php echo $this->userInfo->id; ?>"
	data-jlike-ordering="annotation_date"
	data-jlike-contentid=""
	data-jlike-context=""
	data-jlike-direction="DESC"
	>
</div>
