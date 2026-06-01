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
// @deprecated  1.3.32 Use TJCertificate certificate view instead
// No direct access
defined('_JEXEC') or die;

if (!$this->userid)
{
?>
	<div class="alert alert-warning">
		<?php echo $msg = JText::_('COM_TJLMS_MESSAGE_LOGIN_FIRST');?>
	</div>
	<?php
	return;
}

if ($this->html['html'])
{
?>
<div class="techjoomla-bootstrap">
	<div class="table-responsive">
		<table cellpadding="5">
			<tr>
				<td>
					<?php
					$printlink = 'index.php?option=com_tjlms&view=certificate&layout=pdf_gen&user_id=' . $this->userid . '&course_id=' . $this->course_id;
					?>
					<input type="button" class="btn btn-blue" onclick="printcertificate('certificatrediv')" value="<?php echo JText::_('COM_TJLMS_PRINT');?>" />
				</td>
				<td>
					<a  class="btn btn-primary btn-medium"
					href="<?php echo $this->comtjlmsHelper->tjlmsRoute($printlink, false);?>">
						<?php
							echo JText::_('COM_TJLMS_PRINT_PDF');
						?>
					</a>
				</td>
			</tr>
		</table>
	</div>
<div>

	<div id="certificatrediv">
		<?php
		echo $this->html['html'];
		?>
	</div>
<?php
}
