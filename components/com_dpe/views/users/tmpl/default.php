<?php
/**
 * @package    Com_Dpe
 * @author     Techjoomla <extensions@techjoomla.com>
 * @copyright  Copyright (c) 2009-2019 TechJoomla. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */
// No direct access
defined('_JEXEC') or die;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Factory;
use Joomla\CMS\Router\Route;

use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\Table\Table;

jimport( 'joomla.html.html.select' );
HTMLHelper::_('bootstrap.framework');
HTMLHelper::_('bootstrap.tooltip');
HTMLHelper::_('behavior.multiselect');
HTMLHelper::_('behavior.formvalidator');
HTMLHelper::_('formbehavior.chosen', 'select');
HTMLHelper::_('stylesheet','modal.css');

JLoader::import("/components/com_subusers/includes/rbacl", JPATH_ADMINISTRATOR);

Text::script('COM_DPE_CONFIRMATION_TO_ASSIGN');
$document = Factory::getDocument();
$document->addStyleSheet('templates/shaper_helix3/css/bootstrap.min.css');
$document->addStyleSheet('templates/shaper_helix3/css/custom.css');
$listOrder  = $this->state->get('list.ordering');
$listDirn   = $this->state->get('list.direction');

$redirectUrl = 'index.php?option=com_dpe&view=users&tmpl=component&title=' . $this->title . '&element_id=' . $this->lessonId;
$contentUrl = 'index.php?option=com_tjlms&view=lesson&lesson_id=' . $this->lessonId;

$dateFormat = (String) DPE::config()->get('dateFormat', 'Y-m-d');
?>
<div id="system-message" class="hide assign-popup">
	
	<div class="assign-response">
	</div>
	<a class="close" data-dismiss="alert" style="float: right;margin-top: -24px;">×</a>
</div>
<div class="height-100vh overflow-hidden">
<form action="<?php echo Route::_('index.php?option=com_dpe&view=users&id='. $this->lessonId); ?>" method="post" name="adminForm" id="adminForm" class="assign-userfrm form-validate">
	<div class="cust-table">
		<div class="modal-header">
			<h3><?php echo Text::sprintf('COM_DPE_ASSIGN_USER_HEAD', ucwords($this->escape($this->title)));?></h3>
			<button type="button" class="close" onclick="closeAssignRecommendPopups();">&times;</button>
		</div>
		<ul  class="nav nav-tabs">
			<li id="userList" class="active">
				<a href="#select_user" data-toggle="tab"><span class="tab-count">1</span><?php echo Text::_('COM_DPE_SELECT_USER_TAB');?></a>
			</li>
			<li id="dateTab">
				<a href="#assign_due_date" data-toggle="tab"><span class="tab-count">2</span><?php echo Text::_('COM_DPE_DUE_DATE_TAB');?></a>
			</li>
		</ul>
		<div class="tab-content clearfix">
			<div class="tab-pane fade in active" id="select_user">
			<div class="row">
						<div class="col-xs-12 mb-10">
						<?php
							echo LayoutHelper::render('joomla.searchtools.default',
							array('view' => $this));
						?>
						</div>
					</div>
				<div class="modal-body">
					<?php
						if (!empty($this->items))
						{
						?>
						<div class="row user-popup table-responsive">
						<table class="table" id="multiagencyList">
							<thead class="thead-fixed w-100">
								<tr>
									<th width="1%">
										<?php
											$disableCheck = '';
											if (empty($this->items)):
												$disableCheck = 'disabled'; ?>
											<?php endif;?>
										<input type="checkbox" name="checkall-toggle" value=""
											title="<?php echo Text::_('JGLOBAL_CHECK_ALL'); ?>"
											onclick="Joomla.checkAll(this)" <?php echo $disableCheck;?>/>
									</th>
									<th width="35%">
										<?php echo HTMLHelper::_( 'grid.sort', 'COM_DPE_ENROLMENT_USERs', 'a.name', $listDirn, $listOrder); ?>
									</th>
									<th width="30%">
										<?php echo HTMLHelper::_( 'grid.sort', Text::sprintf('COM_DPE_SCHOOL', Text::_('COM_MULTIAGENCY_ORGANISATION')), 'c.title', $listDirn, $listOrder); ?>
									</th>
									<th width="20%">
										<?php echo HTMLHelper::_( 'grid.sort', 'COM_DPE_ENROLMENT_DESIGNATION', 'r.name', $listDirn, $listOrder); ?>
									</th>
									<!-- <th class=''>
										<?php echo HTMLHelper::_( 'grid.sort', 'COM_DPE_ENROLMENT_USERID', 'b.user_id', $listDirn, $listOrder); ?>
									</th> -->
								</tr>
							</thead>
							<tbody class="tbody-fixed w-100" id="search-tool">
								<?php foreach ($this->items as $i => $item) : ?>
								<td class="center" width="1%">
											<?php echo HTMLHelper::_('grid.id', $i, $item->id, false, 'uid'); ?>
										</td>
									<td class="user-name" width="35%"><?php echo $item->name; ?> </td>
									<td width="30%"><?php echo $this->escape($item->title); ?> </td>
									<td width="20%">										<?php
										// Get subusers actions mapp
										$coreRoleId = RBACL::getCoreRoleByUser($item->id, 'com_multiagency', $item->client_id);

										if ($coreRoleId[0])
										{
											$tjUcmRoleTable = RBACL::table('role');
											$tjUcmRoleTable->load(array('id' => $coreRoleId[0]));
										}

										if (property_exists($tjUcmRoleTable, 'name'))
										{
											echo $this->escape($tjUcmRoleTable->name);
										}
										?>
									</td>
									<!-- <td><?php echo $item->id; ?> </td> -->

								</tr>
								<?php endforeach; ?>
							</tbody>
						</table>
						</div>
						<div class="row fixed next-btn">
							<div class="col-xs-12 text-center assign-btn">
								<a class="btn btn-blue enroll-btn pull-right" name="next" onclick="nxtBtnValidation();" value=""><?php echo Text::_('COM_DPE_NEXT_BTN'); ?></a>
							</div>
						</div>

						<?php
						}
						else
						{
							?>
							<div class="clearfix">&nbsp;</div>
							<div class="alert alert-info"><?php echo Text::_("COM_DPE_NO_RECORDS_FOUND");?></div>
							<?php
						}
						?>
					<input type="hidden" name="task" id="task" value="" />
					<input type="hidden" name="option" id="option" value="com_dpe" />
					<input type="hidden" name="element_id" value="<?php echo $this->lessonId;?>" />
					<input type="hidden" name="title" value="<?php echo $this->escape($this->title);?>" />
					<input type="hidden" name="client" value="com_tjlms.lesson" />
					<input type="hidden" name="notify" value="1" />
					<input type="hidden" name="type" value="assign" />
					<input type="hidden" name="url" value="<?php echo $contentUrl;?>" />
					<input type="hidden" name="redirect_url" class="redirect_url" value="<?php echo Route::_($redirectUrl, false);?>" />
					<input type="hidden" name="start_date" id="start_date" value="" />
					<input type="hidden" name="due_date" id="due_date" value="" />
					<input type="hidden" name="boxchecked" value="0" />
					<input type="hidden" name="tmpl" value="component" />
					<input type="hidden" name="filter_order" value="<?php echo $listOrder; ?>" />
					<input type="hidden" name="filter_order_Dir" value="<?php echo $listDirn; ?>" />
					<input type="hidden" name="clusterId" value="<?php echo $item->clusterId?>"/>

					<?php echo HTMLHelper::_( 'form.token'); ?>
				</div>
			</div>
			<div class="tab-pane fade ss" id="assign_due_date">
				<div class="modal-body">
					<div class="input-custom">
<!--
						<h5><u><?php echo Text::_('COM_DPE_DOCUMENT_ASSIGNMENT'); ?></u></h5>
-->
						<div class="row">
							<div class="col-md-4 col-sm-6 hide">
								<label class="col-md-4 assign-startdate"><?php echo Text::_('COM_DPE_ASSIGNMENT_START_DATE'); ?></label>
								<?php
									echo HTMLHelper::calendar(HTMLHelper::date('now', $dateFormat, true),'lesson_start_date','lesson_start_date', Text::_("COM_DPE_GEN_DATE_FORMAT") ,array('placeholder'=>Text::_("COM_DPE_COMPLIANCE_ASSIGN_USER_START_DATE") , 'validate'=>'nopastdate', 'class'=>'validate-datetime col-md-4','onChange'=>'setDate(this)'));
								?>
							</div>
						</div>
						<div class="row">
							<div class="col-sm-1 col-12">
								<label><?php echo Text::_('COM_DPE_ASSIGNMENT_DUE_DATE'); ?></label>
							</div>

							<div class="col-sm-3 col-12">
							<?php
								echo HTMLHelper::calendar(HTMLHelper::date('now +1 month', $dateFormat, true),'lesson_due_date','lesson_due_date', Text::_("COM_DPE_GEN_DATE_FORMAT"), array('placeholder'=>Text::_("COM_DPE_COMPLIANCE_ASSIGN_USER_DUE_DATE") , 'class'=>'validate-datetime validate-graterdateverify col-md-4 w-100', 'onChange'=>'setDate(this)'));
							?>
							</div>
						</div>
					</div>
					<div class="row fixed next-btn assign-btn">
						<div class="col-xs-12 text-center position-relative">
							<a class="btn btn-blue enroll-btn pull-right" name="assign" id="assign"><?php echo Text::_('COM_DPE_ASSIGNMENT_BTN'); ?></a>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
</form>
</div>

<script type="text/javascript">
	var schoolId = '<?php echo $this->agenciesId;?>';

	jQuery('#userList').click(function(){

		jQuery('#dateTab').removeClass('active');
		jQuery('#assign_due_date').removeClass('active');
		jQuery('#userList').addClass('active');
		jQuery('#select_user').addClass('active');
	})
</script>
