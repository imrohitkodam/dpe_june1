<?php
/**
 * @version    CVS: 1.0.0
 * @package    Com_Test123
 * @author     Parth Lawate <contact@techjoomla.com>
 * @copyright  2017 Parth Lawate
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */
// No direct access
defined('_JEXEC') or die;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Router\Route;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\CMS\Form\FormHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Uri\Uri;
HTMLHelper::_('bootstrap.tooltip');
HTMLHelper::_('behavior.multiselect');
HTMLHelper::_('formbehavior.chosen', 'select');

// Get Process Addtion form Itemid
$document      = Factory::getDocument();
$document->addScript(Uri::root() . 'media/com_dpe/js/tjucmroplist.js');
$document->addScript(Uri::root() . 'media/system/js/messages.min.js');

$menu          = Factory::getApplication()->getMenu();
$itemId        = $menu->getActive()->id;
$input         = Factory::getApplication()->input;
$filterProcess = $input->get('filter_process', '', STRING);
$selected      = $input->get('filter_process', 'myprocess', STRING);
$check         = ($selected == 'generic') ? 'checked': '';
//$document->addStyleSheet(Uri::root() . 'media/com_dpe/css/roplist.css');

// Get Process Addtion form Itemid
$link = 'index.php?option=com_tjucm&view=items&client=com_tjucm.rop';
JLoader::import("/components/com_tjucm/helpers/tjucm", JPATH_SITE);
$tjucmHelper = new TjucmHelpersTjucm;
$itemId      = $tjucmHelper->getItemId($link);

$createRopLink = Route::_('index.php?option=com_dpe&view=ropform&tmpl=component&client=com_tjucm.rop&Itemid=' . $itemId);
$orgFilter = $this->state->get('filter.cluster_id', 0, 'INT');
$link = $orgFilter ? $link.'&cluster='.$orgFilter : $link;

// Get business function
$ropbusinessFieldValue = $input->get('business_function', 'commercial', 'STRING');
?>
<script>
jQuery(document).ready(function() {
	var menuItemId = "<?php echo $itemId;?>";

	jQuery("#ropProcessCheck").click(function() {
		var url = Joomla.getOptions('system.paths').base + '/index.php?option=com_dpe&view=roplist';
		var business_function = jQuery('#business_function').val();

		if (jQuery(this).prop("checked") == true) {
			window.location = url + '&business_function=' + business_function +'&filter_process=generic&Itemid=' + menuItemId;

		} else {
			window.location = url + '&business_function=' + business_function + '&Itemid=' + menuItemId;
		}
	});
});
</script>

<div class="tj-page checklist_dashboard print-view">
	<div class ="row">
		<div class="col-xs-12">
			<form action="<?php echo Route::_('index.php?option=com_dpe&view=roplist'); ?>" method="post" name="roplist" id="roplist" class="rop-list-view">
				<!--Filterbar start-->

				<div class="p-5 mt-30 mb-40">
					<div class="pull-right">
						<!--Generic Checkbox-->
						<div class="d-inline-block mr-20">
							<input class="" type="checkbox" name="ropProcessCheck" id="ropProcessCheck" <?php echo $check;?>>
							<label for="ropProcessCheck" class=""><?php echo Text::_('COM_TJUCM_ROP_GENERIC_PROCESSES');?></label>
						</div>

						<?php
						// Check if com_cluster component is installed
						if (ComponentHelper::getComponent('com_cluster', true)->enabled && !$filterProcess)
						{
							FormHelper::addFieldPath(JPATH_ADMINISTRATOR . '/components/com_tjfields/models/fields/');
							$cluster           = FormHelper::loadFieldType('cluster', false);
							$this->clusterList = $cluster->getOptionsExternally();
							?>
							<div class="agency-drop-down btn-group d-inline-block mr-15 md-w-300px">
								<?php
									if (count($this->clusterList) > 1)
									{
									//	unset($this->clusterList[0]);
									}
									echo HTMLHelper::_('select.genericlist', $this->clusterList, "filter[cluster_id]", 'class="input-medium" size="1" onchange="this.form.submit();"', "value", "text", $this->state->get('filter.cluster_id', '', 'INT'));
								?>
							</div>
							<div class="d-inline-block">
								<a href="javascript:void(0);" onclick="tjucm.itmes.openRopPopups('<?php echo addslashes(Route::_($createRopLink));?>')"
								class=" btn btn-add add-process-btn btn-small ml-10">
									<i class="icon-plus"></i><?php echo Text::_('COM_TJUCM_ROP_ADD_PROCESS_ITEM'); ?>
								</a>
							</div>


							<?php
						}
						?>
					</div>
				</div>

				<div class="container">
					<div class="row mt-30 d-flex flex-wrap">
					<?php
						$count       = 1;

						foreach ($this->items as $item)
						{
							$inProgress = $item->progressData->inprogress ? $item->progressData->inprogress:0;
							$completed = $item->progressData->Completed ? $item->progressData->Completed:0;
							$businessFunctionFilter = '&business_function=' . $item->options;
							$boxLink = Route::_($link . $businessFunctionFilter . '&Itemid=' . $itemId, false)

							?>

								<div class="col-lg-3 col-md-3 col-sm-4 col-xs-12 mb-30 d-flex">
									<div class="process-div">
										<a class="text-white title-link-74" href="<?php echo $boxLink; ?>">

										<div class="circle circle-<?php echo $count; ?>">
											<h3 class="process-acr-name name<?php echo $count;?>">
												<?php echo $item->options[0] ?>
											</h3>
										</div>
										<div class="process-name">
											<h3>
												<?php echo $item->options; ?>
											</h3>
										</div>
										<?php
										if (!$orgFilter  && $selected !== 'generic') : ?>
										<div class="organization-count">
											<h3>
												<?php
												$orgText = ($item->cluster_ids_cnt > 1 ? 1 : 0) > 1 ? Text::_('COM_DPE_ROP_LIST_BOX_LAYOUT_ORGANISATIONS_TEXT') : Text::_('COM_DPE_ROP_LIST_BOX_LAYOUT_ORGANISATION_TEXT');
												echo $item->cluster_ids_cnt.' '. $orgText;
												?>
											</h3>
										</div>
										<?php endif; ?>
										<div class="process-state mt-30">
											<div class="inprogress-state d-inline-block state-width">
												<p>
													<?php echo Text::sprintf('COM_DPE_ROP_LIST_BOX_LAYOUT_INPROGRESS_TEXT', $inProgress);?>
												</p>
											</div>
											<div class="complete-state d-inline-block ml-10 state-width">
												<p>
													<?php echo Text::sprintf('COM_DPE_ROP_LIST_BOX_LAYOUT_COMPLETE_TEXT', $completed);?>
												</p>
											</div>
										</div>
										</a>
									</div>
										<div class="line"></div>
								</div>
							<?php
							$count++;
						}
					?>
					</div>
				  </div>
			</form>
		</div>
	</div>
</div>
