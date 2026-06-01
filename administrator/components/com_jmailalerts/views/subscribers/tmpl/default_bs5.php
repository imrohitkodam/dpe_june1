<?php
/**
 * @package     JMailAlerts
 * @subpackage  com_jmailalerts
 *
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (C) 2009 - 2022 Techjoomla. All rights reserved.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 */

// Do not allow direct access
defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;

$user      = Factory::getUser();
$userId    = $user->get('id');
$listOrder = $this->state->get('list.ordering');
$listDirn  = $this->state->get('list.direction');
$canOrder  = $user->authorise('core.edit.state', 'com_jmailalerts');
$saveOrder = $listOrder == 'a.ordering';

if ($saveOrder)
{
	$saveOrderingUrl = 'index.php?option=com_jmailalerts&task=subscribers.saveOrderAjax&tmpl=component';
	HTMLHelper::_('sortablelist.sortable', 'subscriberList', 'adminForm', strtolower($listDirn), $saveOrderingUrl);
}

?>

<?php
// Add Javascript
$doc = Factory::getDocument();
$doc->addScriptDeclaration('
	Joomla.orderTable = function() {
		table = document.getElementById("sortTable");
		direction = document.getElementById("directionTable");
		order = table.options[table.selectedIndex].value;
		if (order != "' . $listOrder . ' ") {
			dirn = "asc";
		} else {
			dirn = direction.options[direction.selectedIndex].value;
		}
		Joomla.tableOrdering(order, dirn, "");
	}

	function preview(uid, sdate, aid, rid, email) {
		let flag = 0;
		let link= "' . Uri::base() . '" + "index.php?option=com_jmailalerts&task=subscriber.preview&tmpl=component&send_mail_to_box=admin@admin.com&flag=1&userid=";

		link = link + "&user_id=" + uid + "&select_date_box=" + sdate + "&alert_id=" + aid + "&email_id=" + email;

		document.getElementById(rid).setAttribute("href", link);
	}'
);
?>

<?php
if (!empty($this->extra_sidebar))
{
	$this->sidebar .= $this->extra_sidebar;
}
?>

<div class="<?php echo JMAILALERTS_WRAPPER_CLASS; ?>" id="jmailalerts-subscribers">
	<form action="<?php echo Route::_('index.php?option=com_jmailalerts&view=subscribers'); ?>" method="post" name="adminForm" id="adminForm">
		<div class="row">
			<div class="col-md-12">
				<div id="j-main-container" class="j-main-container">
					<?php echo LayoutHelper::render('joomla.searchtools.default', array('view' => $this)); ?>

					<div class="clearfix"> </div>

					<?php
					if (empty($this->items))
					{
						?>
						<div class="alert alert-no-items">
							<?php echo Text::_('JGLOBAL_NO_MATCHING_RESULTS'); ?>
						</div>
						<?php
					}
					else
					{
						?>
						<table class="table table-striped" id="subscriberList">
							<thead>
								<tr>
									<?php
									if (isset($this->items[0]->ordering))
									{
										?>
										<th width="1%" class="nowrap center hidden-phone">
											<?php
											echo HTMLHelper::_('searchtools.sort', '', 'a.ordering', $listDirn, $listOrder, null, 'asc', 'JGRID_HEADING_ORDERING', 'icon-menu-2');
											?>
										</th>
										<?php
									}
									?>

									<th width="1%" class="hidden-phone">
										<input type="checkbox" name="checkall-toggle" value="" title="<?php echo Text::_('JGLOBAL_CHECK_ALL'); ?>" onclick="Joomla.checkAll(this)" />
									</th>

									<?php
									if (isset($this->items[0]->state))
									{
										?>
										<th width="1%" class="nowrap center">
											<?php echo HTMLHelper::_('searchtools.sort', 'JSTATUS', 'a.state', $listDirn, $listOrder); ?>
										</th>
										<?php
									}
									?>

									<th class='left'>
										<?php echo HTMLHelper::_('searchtools.sort', 'COM_JMAILALERTS_SUBSCRIBERS_USER_ID', 'a.user_id', $listDirn, $listOrder); ?>
									</th>

									<th class='left'>
										<?php echo HTMLHelper::_('searchtools.sort', 'COM_JMAILALERTS_SUBSCRIBERS_ALERT_ID', 'a.alert_id', $listDirn, $listOrder); ?>
									</th>

									<th class='left'>
										<?php echo HTMLHelper::_('searchtools.sort', 'COM_JMAILALERTS_SUBSCRIBERS_FREQUENCY', 'a.frequency', $listDirn, $listOrder); ?>
									</th>

									<th class='left'>
										<?php echo HTMLHelper::_('searchtools.sort', 'COM_JMAILALERTS_SUBSCRIBERS_DATE', 'a.date', $listDirn, $listOrder); ?>
									</th>
									<th class='left'>
										<?php 		// DPE hack to show the organisation name for the guest users
										echo Text::_('COM_JMAILALERTS_SUBSCRIBERS_ORGANISATION'); ?>
									</th>
<!-- 
									<th>
										<?php //echo Text::_('COM_JMAILALERTS_PREVIEW'); ?>
									</th> -->

									<?php
									if (isset($this->items[0]->id))
									{
										?>
										<th width="1%" class="nowrap center hidden-phone">
											<?php echo HTMLHelper::_('searchtools.sort', 'JGRID_HEADING_ID', 'a.id', $listDirn, $listOrder); ?>
										</th>
										<?php
									}
									?>
								</tr>
							</thead>

							<tfoot>
								<?php
								if (isset($this->items[0]))
								{
									$colspan = count(get_object_vars($this->items[0]));
								}
								else
								{
									$colspan = 10;
								}
								?>
								<tr>
									<td colspan="<?php echo $colspan; ?>">
										<?php echo $this->pagination->getListFooter(); ?>
									</td>
								</tr>
							</tfoot>

							<tbody>
								<?php
								foreach ($this->items as $i => $item)
								{
									$ordering   = ($listOrder == 'a.ordering');
									$canCreate  = $user->authorise('core.create', 'com_jmailalerts');
									$canEdit    = $user->authorise('core.edit', 'com_jmailalerts');
									$canCheckin = $user->authorise('core.manage', 'com_jmailalerts');
									$canChange  = $user->authorise('core.edit.state', 'com_jmailalerts'); ?>

									<tr class="row<?php echo $i % 2; ?>">
										<?php
										if (isset($this->items[0]->ordering))
										{
											?>
											<td class="nowrap center hidden-phone">
												<?php
												if ($canChange)
												{
													$disableClassName = '';
													$disabledLabel	   = '';

													if (!$saveOrder)
													{
														$disabledLabel    = Text::_('JORDERINGDISABLED');
														$disableClassName = 'inactive tip-top';
													}
													?>

													<span class="sortable-handler hasTooltip <?php echo $disableClassName; ?>"
														title="<?php echo $disabledLabel; ?>">
														<i class="icon-menu"></i>
													</span>

													<input type="text" style="display:none" name="order[]" size="5" value="<?php echo $item->ordering; ?>" class="width-20 text-area-order " />
													<?php
												}
												else
												{
													?>
													<span class="sortable-handler inactive" >
														<i class="icon-menu"></i>
													</span>
													<?php
												}
												?>
											</td>
											<?php
										}
										?>

										<td class="center hidden-phone">
											<?php echo HTMLHelper::_('grid.id', $i, $item->id); ?>
										</td>

										<?php
										if (isset($this->items[0]->state))
										{
											?>
											<td class="center">
												<?php echo HTMLHelper::_('jgrid.published', $item->state, $i, 'subscribers.', $canChange, 'cb'); ?>
											</td>
											<?php
										}
										?>

										<td>
											<?php
											if (isset($item->checked_out) && $item->checked_out)
											{
												?>
												<?php echo HTMLHelper::_('jgrid.checkedout', $i, $item->editor, $item->checked_out_time, 'subscribers.', $canCheckin); ?>
												<?php
											}
											?>

											<?php
											if ($canEdit)
											{
												?>
												<a href="<?php echo Route::_('index.php?option=com_jmailalerts&task=subscriber.edit&id=' . (int) $item->id); ?>">
													<?php
													echo $this->escape($item->name);
													echo'<br/>' . $item->user_id; ?>
												</a>
												<?php
											}
											else
											{
												?>
												<?php
												echo $this->escape($item->name);
												echo'<br>' . $item->user_id;
												?>
												<?php
											}
											?>
										</td>

										<td>
											<?php 
											echo $item->alert_name;
											echo '<span class="small"> (' . $item->alert_id . ')</span>'; ?>
										</td>

										<td>
											<?php		// DPE hack to show the organisation name for the guest users
												 echo Text::_($item->frequencyname); ?>
										</td>

										<td>
											<?php echo ($item->date == 	"0000-00-00 00:00:00")?$item->date : HTMLHelper::_('date', $item->date, 'Y-m-d H:i:s'); ?>
										</td>
										<td>
											<?php echo ($item->orgName)?$item->orgName:''; ?>
										</td>

										<!-- <td> -->
											<?php
											// if ($item->state)
											// {
											// 	$link = Route::_('index.php?option=com_jmailalerts&task=subscriber.preview&tmpl=component&send_mail_to_box=admin@admin.com&flag=1&userid=&user_id=' . $item->user_id . '&select_date_box=' . $item->date . '&alert_id=' . $item->alert_id . '&email_id=' . $item->email_id);

											// 	echo HTMLHelper::_(
											// 		'bootstrap.renderModal',
											// 		'preview' . (int) $item->id . 'Modal',
											// 		array(
											// 			'url'         => $link,
											// 			'title'       => Text::_('COM_JMAILALERTS_PREVIEW'),
											// 			'height'      => '700px',
											// 			'width'       => '600px',
											// 			'bodyHeight'  => '70',
											// 			'modalWidth'  => '80',
											// 			'closeButton' => false,
											// 			'backdrop'    => 'static',
											// 			'keyboard'    => false,
											// 			'footer'      => '<button class="btn btn-secondary" type="button" data-bs-dismiss="modal">' . Text::_('JLIB_HTML_BEHAVIOR_CLOSE') . '</button>',
											// 		)
											// 	);
												?>

												<!-- <a id ="<?php //echo (int) $item->id; ?>"
													onclick="document.getElementById('preview<?php //echo (int) $item->id; ?>Modal').open();"
													href="javascript:void(0);">
													<?php //echo Text::_('COM_JMAILALERTS_PREVIEW'); ?>
												</a>
 -->
												<?php
											//}
											?>
										<!-- </td> -->

										<?php
										if (isset($this->items[0]->id))
										{
											?>
											<td class="center hidden-phone">
												<?php echo (int) $item->id; ?>
											</td>
											<?php
										}
										?>
									</tr>
									<?php
								}
								?>
							</tbody>
						</table>
						<?php
					}
					?>
				</div>
			</div>
		</div>

		<input type="hidden" name="task" value="" />
		<input type="hidden" name="boxchecked" value="0" />
		<input type="hidden" name="list[fullorder]" value="<?php echo $listOrder . ' ' . $listDirn; ?>" />
		<?php echo HTMLHelper::_('form.token'); ?>
	</form>
</div>
