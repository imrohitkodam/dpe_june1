<?php
/**
 * @version    SVN: <svn_id>
 * @package    Com_Dpe
 * @author     Techjoomla <extensions@techjoomla.com>
 * @copyright  Copyright (c) 2009-2019 TechJoomla. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access
defined('_JEXEC') or die('Restricted access');
use Joomla\CMS\Factory;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Language\Text;

// Load jlike lang. file
$lang = Factory::getLanguage();
$lang->load('com_jlike', JPATH_SITE, $lang->getTag(), true);

// Load APiKey Helper
require_once JPATH_SITE . '/components/com_jlike/helpers/apikey.php';

$document=Factory::getDocument();
$document->addScript(Uri::root(true).'/media/jui/js/jquery.min.js');
$document->addScript(Uri::root(true).'/media/com_jlike/vendors/ajaxq.js');
$document->addScript(Uri::root(true).'/components/com_jlike/comments/js/jquery-comments.js');
$document->addScript(Uri::root(true).'/components/com_jlike/assets/scripts/annotations.jQuery.js');


$document->addScript(Uri::root(true).'/media/com_jlike/vendors/ajaxq.js');
$document->addScript(Uri::root(true).'/components/com_jlike/comments/js/jquery-comments.js');
$document->addScript(Uri::root(true).'/components/com_jlike/assets/scripts/annotations.jQuery.js');
// $document->addScript(JUri::root(true).'/components/com_jlike/assets/scripts/hybridtodo.jQuery.js');
$document->addScript(Uri::root(true).'/components/com_jlike/assets/scripts/moment.js');
$document->addStylesheet(Uri::root(true).'/components/com_jlike/comments/css/jquery-comments.css');

// Mentions
$document->addScript(Uri::root(true).'/components/com_jlike/assets/scripts/comment.mention.js');

$reqURI = Uri::root();
$ApikeyHelper = new ApikeyHelper();
$apiKey= $ApikeyHelper->getApiKey($this->userInfo->id);

// Add Language Constant
JText::script('COM_JLIKE_SELECT_ASSIGN_USER');
JText::script('COM_JLIKE_COMMENT');
?>
<script type="text/javascript">
passData = {'displayErrClass':'public-error', 'requestFrom':'<?php echo $this->accessByApiTask; ?>'}
window.onload=function(){
	<?php
	// Check if public comment is enabled
	if ($this->publicComment)
	{
	?>
		jQuery('#publicCommentsDiv').annotations(passData);
	<?php
	}
	// Check if private comment is enabled
	if ($this->privateComment)
	{
	?>
		passData.displayErrClass = 'private-error';
		jQuery('#privateCommentsDiv').annotations(passData);
	<?php
	}
	?>
}
function changeContext(contentOwner, userId)
{
	let contextVal = contentOwner + ':' + userId;
	if(typeof contextVal != undefined  && contextVal != '')
	{
		let users = contextVal.split(':');
		if(users[1] == 'undefined' || users[1] == '')
		{
			jQuery('.private-error').show();
			setTimeout(function(){ jQuery('.private-error').fadeOut();}, 3000);
			jQuery('.private-error').html(Joomla.Text._('COM_JLIKE_SELECT_ASSIGN_USER'));
			jQuery('div#privateCommentsDiv').attr("data-jlike-context", contextVal);
			return false;
		}
	}
	jQuery('div#privateCommentsDiv').attr("data-jlike-context", contextVal);
	jQuery('#privateCommentsDiv').annotations(passData);
}
</script>
<ul class="nav nav-tabs  mb-15">
	<?php

	// Check if public comment is enabled
	if ($this->publicComment)
	{
	?>
	<li class="active">
		<a data-toggle="tab" href="#public_comment"><?php echo Text::_('COM_DPE_JLIKE_PUBLIC_COMMENT'); ?></a>
	</li>
	<?php
	}
	else
	{
		$addClass =' in active ';
	}

	// Check if private comment is enabled
	if ($this->privateComment)
	{
	?>
		<li class="<?php echo $addClass; ?>" >
			<a data-toggle="tab" href="#private_comment"><?php echo Text::_('COM_DPE_JLIKE_PRIVATE_COMMENT'); ?></a>
		</li>
	<?php
	}
	?>
</ul>
<div class="tab-content">
	<?php

	// Check if public comment is enabled
	if ($this->publicComment)
	{
	?>
    <div id="public_comment" class="tab-pane fade in active">
		<span class="public-error alert-error"></span>
	<?php
		$publicView = $this->dpeMainHelper->getViewPath('jlike','public_comments');
		ob_start();
			include($publicView);
			$html = ob_get_contents();
		ob_end_clean();
		echo $html;
	?>
    </div>
    <?php
	}

	// Check if private comment is enabled
    if ($this->privateComment)
	{
	?>
    <div id="private_comment" class="tab-pane fade <?php echo $addClass; ?>">
    <span class="private-error alert-error"></span>
	<?php
		$privateView = $this->dpeMainHelper->getViewPath('jlike','private_comments');
		ob_start();
			include($privateView);
			$html = ob_get_contents();
		ob_end_clean();
		echo $html;
	?>
    </div>
    <?php
	}
	?>
</div>

