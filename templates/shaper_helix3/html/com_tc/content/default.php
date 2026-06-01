<?php
/**
 * @version    SVN: <svn_id>
 * @package    Com_Tc
 * @author     Techjoomla <extensions@techjoomla.com>
 * @copyright  Copyright (c) 2016-2017 TechJoomla. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die('Restricted access');
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;

JHtml::addIncludePath(JPATH_COMPONENT . '/helpers/');


$document = Factory::getDocument();

// Add styles
$style = " .tc-box {
  //border-top-style: ridge;
  border-right-style: hidden;
  border-bottom:1px solid;
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
			alert('<?php echo Text::_("COM_TC_LATEST_TERMSANDCONDITIONS_NOT_ACCETED_ERROR"); ?>');
			return false;
		}
	}
</script>

<?php
if (!empty($this->tc_id))
{
	?>
	<div class="well well-condensed p-0 bg-none termscondition-section bs">
		<div class="title-section p-15">
			<h2 class="center mt-0">
				<?php echo $this->termsandconditions->title; ?>
			</h2>
			<div class="center">
				<strong>
					<?php echo Text::_("COM_TC_LATEST_TERMSANDCONDITIONS_VERSION") . $this->termsandconditions->version; ?>&nbsp;&nbsp;
				</strong>
			</div>
		</div>

		<br>
		<div class="p-15">
			<div class="tc-box pb-10">
				<?php echo nl2br($this->termsandconditions->content); ?>
			</div>

			<?php
			$app       = Factory::getApplication();
			$input     = $app->input;
			$returnURL = $input->get('return', '', 'STRING');
			?>

			<div class="">
				<form action="" method="post" name="form" onsubmit="return validateform(this)">
					<div class="checkbox">
						<label class="padded-l-0">
							<input id="agree" type="checkbox" name="accept" value="1">
							<?php echo Text::_('COM_TC_LATEST_TERMSANDCONDITIONS_AGREE');?>
						</label>

						<div class="pull-right">
							<button class="btn btn-primary" type="submit" value="Submit" name="Submit">
								<?php echo Text::_('COM_TC_ACCEPT_TERMSANDCONDITIONS_BUTTON'); ?>
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
						echo Text::_("COM_TC_LATEST_TERMSANDCONDITIONS_UPDATED_DATE") .
						HTMLHelper::_('date', $this->termsandconditions->modified_on, $this->dateFormat, 'UTC');?>
					</strong>
				</form>
			</div>
		</div>
	</div>

	<?php
}
