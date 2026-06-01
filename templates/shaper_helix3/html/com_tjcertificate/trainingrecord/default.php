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
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Factory;
use Joomla\CMS\Router\Route;

// Check elearning tool action for DPE
if (!$this->user->authorise('core.manageall', 'com_cluster'))
{
	JLoader::import('/components/com_subusers/includes/rbacl', JPATH_ADMINISTRATOR);

	if (!RBACL::check($this->user->id, 'com_cluster', 'core.viewShika', 'com_tjlms'))
	{
		$app = Factory::getApplication();

		$app->enqueueMessage(Text::_('JERROR_ALERTNOAUTHOR'));

		return;
	}
}

$backLink = 'index.php?option=com_tjcertificate&view=certificates&layout=my';
$backLink = Route::_($backLink);

$certificateStatusArr = array(Text::_('COM_TJCERTIFICATE_CERTIFICATE_PENDING_TEXT') => -1, Text::_('JUNPUBLISHED') => 0, Text::_('JPUBLISHED') => 1);

?>
<div class="row mr-5">
	<div id="backBtn">
		<a id="certificate_back" class="pull-right fs-16 font-600 cursor-pointer" href="<?php echo $backLink;?>"><i class="fa fa-arrow-left mr-10" aria-hidden="true"></i><?php echo Text::_('COM_TJCERTIFICATE_CERTIFICATE_BACK_BUTTON');?></a>
	</div>
</div>
<fieldset id="users-profile-core" class="trainingrecord-view">
	<h3 class="mt-0 mb-20 pb-20">
		<?php echo Text::_('COM_TJCERTIFICATE_EXTERNAL_CERTIFICATE_DETAIL_VIEW_HEAD'); ?>
	</h3>
	<dl class="dl-horizontal">
		<dt>
			<?php echo Text::_('COM_TJCERTIFICATE_FORM_LBL_CERTIFICATE_NAME'); ?>
		</dt>
		<dd>
			<?php echo $this->escape($this->item->name); ?>
		</dd>
		<?php if ($this->item->cert_url) { ?>
		<dt>
			<?php echo Text::_('COM_TJCERTIFICATE_LBL_CERTIFICATE_URL'); ?>
		</dt>
		<dd>
			<a href="<?php echo $this->escape($this->item->cert_url); ?>" target="_blank"><?php echo $this->escape($this->item->cert_url); ?></a>
		</dd>
		<?php } ?>
		<dt>
			<?php echo Text::_('COM_TJCERTIFICATE_FORM_LBL_ISSUE_ORG'); ?>
		</dt>
		<dd>
			<?php echo $this->escape($this->item->issuing_org); ?>
		</dd>
		<dt>
			<?php echo Text::_('COM_TJCERTIFICATE_CERTIFICATE_FORM_LBL_CERTIFICATE_STATUS'); ?>
		</dt>
		<dd>
			<?php echo ucfirst($this->escape($this->item->status)); ?>
		</dd>

		<dt>
			<?php echo Text::_('COM_TJCERTIFICATE_CERTIFICATE_CERTIFICATE_STATUS_TEXT'); ?>
		</dt>
		<dd>
			<?php

			 echo $certificateStatus = array_search($this->item->state, $certificateStatusArr); ?>
		</dd>


		<dt>
			<?php echo Text::_('COM_TJCERTIFICATE_CERTIFICATE_FORM_LBL_CERTIFICATE_ISSUED_DATE'); ?>
		</dt>
		<dd>
			<?php echo $this->certificate->getFormatedDate($this->item->issued_on);?>
		</dd>
		<?php   if ($this->item->expired_on != "0000-00-00 00:00:00" && !empty($this->item->expired_on)) { ?>
		<dt>
			<?php echo Text::_('COM_TJCERTIFICATE_CERTIFICATE_FORM_LBL_CERTIFICATE_EXPIRY_DATE'); ?>
		</dt>
		<dd>
			<?php  echo $this->certificate->getFormatedDate($this->item->expired_on);?>
		</dd>
		<?php } ?>
		<?php if ($this->item->cert_file) { ?>
		<dt>
		</dt>
		<dd>
			<?php
			if ($this->item->mediaData[0])
			{
				$downloadAttachmentLink = Uri::root() . 'index.php?option=com_tjcertificate&task=trainingrecord.downloadAttachment&id=' . $this->item->mediaData[0]->media_id . '&recordId=' . $this->item->id;

				if ($this->item->mediaData[0]->type === "image")
				{
				?>
				<img src="<?php echo $this->item->mediaData[0]->path . '/' . $this->item->mediaData[0]->source;?>">
				<?php
				}
				?>
				<?php // echo $this->item->mediaData[0]->title;?>

				<!-- Downlaod option available for who have manage permission and record owner -->
				<?php if ($this->manage || $this->item->user_id == $this->user->id) { ?>
					<a
						class="p-10 mt-15 mb-10 btn btn-primary"
						href="<?php echo $downloadAttachmentLink;?>"
						target=""
						title="<?php echo $this->escape(strip_tags($this->item->mediaData[0]->title)); ?>">
						Download
						<i class="fa fa-download" aria-hidden="true"></i>

					</a>
				<?php } ?>
		<?php } ?>
		</dd>
		<?php } ?>
		<?php if ($this->item->comment) { ?>
		<dt>
			<?php echo Text::_('COM_TJCERTIFICATE_CERTIFICATE_FORM_LBL_CERTIFICATE_COMMENT'); ?>
		</dt>
		<dd>
			<?php echo $this->escape($this->item->comment); ?>
		</dd>
		<?php } ?>
	</dl>
</fieldset>
