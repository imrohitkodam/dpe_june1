<?php
/**
 * @version    SVN: <svn_id>
 * @package    Com_Tc
 * @author     Techjoomla <extensions@techjoomla.com>
 * @copyright  Copyright (c) 2016-2017 TechJoomla. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die('Restricted access');

JHtml::addIncludePath(JPATH_COMPONENT . '/helpers/');

use Joomla\CMS\HTML\HTMLHelper;

$document = JFactory::getDocument();

// Add styles
$style = " .tc-box {
  border-top-style: ridge;
  border-right-style: hidden;
  border-bottom-style: ridge;
  border-left-style: hidden;
  overflow-y: auto;
  max-height: 300px;
  overflow-x: hidden;
}";

$document->addStyleDeclaration($style);
?>

<script>
	function validateform (form) {
		var tc_id = <?php echo (int) $this->tc_id; ?>;
		var user_id = <?php echo (int) $this->user_id; ?>;

		if (jQuery("#agree").is(':checked') === true){
			return true;
		}
		else {
			alert('<?php echo JText::_("COM_TC_LATEST_TERMSANDCONDITIONS_NOT_ACCETED_ERROR"); ?>');
			return false;
		}
	}
</script>

<?php
if (!empty($this->tc_id))
{
	?>
	<div class="well well-condensed">
		<div class="">
			<h1 class="center">
				<?php echo $this->termsandconditions->title; ?>
			</h1>
			<div class="center">
				<strong>
					<?php echo JText::_("COM_TC_LATEST_TERMSANDCONDITIONS_VERSION") . $this->termsandconditions->version; ?>&nbsp;&nbsp;
				</strong>
			</div>
		</div>

		<br>

		<div class="tc-box">
			<?php echo nl2br($this->termsandconditions->content); ?>
		</div>

		<?php
		$app       = JFactory::getApplication();
		$input     = $app->input;
		$returnURL = $input->get('return', '', 'STRING');
		?>

		<div class="">
			<form action="" method="post" name="form" onsubmit="return validateform(this)">
				<div class="checkbox">
					<label class="padded-l-0">
						<input id="agree" type="checkbox" name="accept" value="1">
						<?php echo JText::_('COM_TC_LATEST_TERMSANDCONDITIONS_AGREE');?>
					</label>

					<div class="pull-right">
						<button class="btn btn-primary" type="submit" value="Submit" name="Submit">
							<?php echo JText::_('COM_TC_ACCEPT_TERMSANDCONDITIONS_BUTTON'); ?>
						</button>
					</div>
				</div>

				<input type="hidden" name="option" value="com_tc">
				<input type="hidden" name="task" value="content.accept()">
				<input type="hidden" name="user_id" value="<?php echo $this->user_id; ?>">
				<input type="hidden" name="tc_id" value="<?php echo $this->tc_id; ?>">
				<input type="hidden" name="return_url" value="<?php echo $returnURL; ?>">

				<strong>
					<?php
					echo JText::_("COM_TC_LATEST_TERMSANDCONDITIONS_UPDATED_DATE") .
					HTMLHelper::_('date', $this->termsandconditions->modified_on, $this->dateFormat, 'UTC');?>
				</strong>
			</form>
		</div>
	</div>

	<?php
}
