<?php
/**
* @version	 1.0.0
* @package	 com_tmt
* @copyright   Copyright (C) 2013. All rights reserved.
* @license	 GNU General Public License version 2 or later; see LICENSE.txt
* @author	  Techjoomla <contact@techjoomla.com> - http://techjoomla.com
*/
// no direct access
defined('_JEXEC') or die;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;

HTMLHelper::_('behavior.keepalive');
HTMLHelper::_('bootstrap.tooltip');
HTMLHelper::_('behavior.formvalidation');
HTMLHelper::_('jquery.framework');

	//JText::_('COM_TMT_UPLOAD_FILE_BROWSE');
?>


<div class="container">
	<div class='row-fluid'>
		<h3 class='text-center'>
			<?php echo Text::_('COM_TJLMS_QUIZ_REVIEW_THANKS'); ?>
		</h3>
	</div>
</div>
