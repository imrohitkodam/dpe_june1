<?php
/**
 * @package     Shika
 * @subpackage  com_tjlms
 *
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (C) 2009 - 2020 Techjoomla. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Form\FormHelper;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\Registry\Registry;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Factory;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;

HTMLHelper::addIncludePath(JPATH_COMPONENT . '/helpers/html');
HTMLHelper::_('bootstrap.tooltip');
HTMLHelper::_('behavior.multiselect');
HTMLHelper::_('formbehavior.chosen', '.multipleAuthors', null, array('placeholder_text_multiple' => Text::_('JOPTION_SELECT_AUTHOR')));
HTMLHelper::_('formbehavior.chosen', 'select');

$document	= Factory::getDocument();
$document->addStyleSheet(Uri::root() . '/media/jui/css/jquery.searchtools.css');
$document->addStyleSheet(Uri::root() . '/media/jui/css/chosen.css');

$document->addScript(Uri::root() . '/media/jui/js/chosen.jquery.min.js');

$listOrder      = $this->state->get('list.ordering');
$listDirn       = $this->escape($this->state->get('list.direction'));
$filter_state	= $this->state->get('filter.state');
$filter_format	= $this->state->get('filter.format');
$saveOrder      = $listOrder == 'a.ordering';

$modelLesson = BaseDatabaseModel::getInstance('Lesson', 'TjlmsModel');

$user        = Factory::getUser();
$canDelete   = $user->authorise('core.delete', 'com_tjlms');
$jlikeDelete = $user->authorise('core.delete', 'com_jlike'); 
$deleteDocMessage='';

if ($saveOrder)
{
	$saveOrderingUrl = 'index.php?option=com_tjlms&task=managelessons.saveOrderAjax&tmpl=component';
	HTMLHelper::_('sortablelist.sortable', 'lessonsList', 'adminForm', strtolower($listDirn), $saveOrderingUrl);
}

$app                    = Factory::getApplication();
$menu                   = $app->getMenu();
$itemId                 = $menu->getActive()->id;
$uploadDocLink          = 'index.php?option=com_tjlms&view=lessonform&layout=edit&ptype=document';
$tjlmsparams            = ComponentHelper::getParams('com_tjlms');
$launchLessonFullScreen = $tjlmsparams->get('launch_full_screen');
$target                 = ($launchLessonFullScreen == 'tab') ? 'target="_blank"' : "";
$jlikeTjlmslessonPlugin    = PluginHelper::getPlugin('content', 'jlike_tjlmslesson');
$jlikeTjlmslessonPluginObj = new Registry($jlikeTjlmslessonPlugin->params);

$clusterId    = $this->state->get('filter.clusters', '', 'INT');

JLoader::import("/components/com_cluster/includes/cluster", JPATH_ADMINISTRATOR);

$clusterUserModel = ClusterFactory::model('ClusterUser', array('ignore_request' => true));
$clusters         = $clusterUserModel->getUsersClusters($user->id);


// Check for Can view Compliance Manager
if (ComponentHelper::getComponent('com_subusers', true)->enabled)
{
	JLoader::import("/components/com_subusers/includes/rbacl", JPATH_ADMINISTRATOR);

	if (!$user->authorise('core.manageall', 'com_cluster'))
	{
		$canAccessCM = null;

		foreach ($clusters as $cluster)
		{
			if (!$canAccessCM)
			{
				$canAccessCM = RBACL::check($user->id, 'com_cluster', 'core.ViewComplianceManager', 'com_multiagency', $cluster->cluster_id);

				if ($canAccessCM && (empty($clusterId)))
				{
					$clusterId = $cluster->cluster_id;
				}
			}
		}

		if (!$canAccessCM)
		{
			$app->enqueueMessage(Text::_('JERROR_ALERTNOAUTHOR'), 'error');
			$app->setHeader('status', 403, true);

			return;
		}
	}
}
// Get create Agency list menu
$mainframe         = Factory::getApplication();
$menu              = $mainframe->getMenu();
$createDocMenuItem = $menu->getItems('link', 'index.php?option=com_tjlms&view=lessonform&layout=edit', true );

// Get Current url for notification manager widget

$extraParams = Uri::getInstance()->toString(array('query'));
$extraParams = str_replace('?', '&', $extraParams);
$input       = $mainframe->input;

$currentUrl =  'index.php?option=' . $input->get('option') . '&view=' . $input->get('view') . $extraParams .'&Itemid=' . $input->get('Itemid');
$complianceListId = ComponentHelper::getParams('com_dpe')->get('compliance_list');
?>

<div class="<?php echo COM_TJLMS_WRAPPER_DIV; ?> tjBs3  manage-doc">
<!--
	<div class="row">
		<h2><?php echo Text::_("COM_TJLMS_MANAGELESSONS_VIEW_DEFAULT_TITLE") ; ?></h2>
	</div>
-->
<form action="<?php echo Route::_('index.php?option=com_tjlms&view=managelessons'); ?>" method="post" name="adminForm" id="adminForm" class="compliancemanager">
	<div class="row">
		<div class="col-md-6 upload-doc">
			<!-- DPE  -- Hack Cluster Filter Start-->
			<?php

			// Get plugin dpe_tjlms_cluster of type 'system'
			$plgSystemTjlmsCluster = PluginHelper::getPlugin('system', 'dpe_tjlms_cluster');

			if (!empty($plgSystemTjlmsCluster))
			{
				FormHelper::addFieldPath(JPATH_ADMINISTRATOR . '/components/com_tjfields/models/fields/');
				$cluster      = FormHelper::loadFieldType('cluster', false);
				$clusterArray = $cluster->getOptionsExternally();

				echo HTMLHelper::_('select.genericlist', $clusterArray, 'clusters',
					'class="inputbox mb-2" onchange="this.form.submit();"', 'value', 'text', $clusterId
				);
			}

			$user = Factory::getUser();
			$params     			   = ComponentHelper::getParams('com_multiagency');
			$multiagency_trustee_group = (int) $params->get('multiagency_trustee_group');
			$isTrustee 				   = in_array($multiagency_trustee_group, $user->groups);
			$orgAdminRoleId            = (int) $params->get('multiagency_school_admin_group', '0', 'INT');
			$orgAdminRoleId 		   = in_array($orgAdminRoleId, $user->groups);

			// Hide the Tags filter for non-Super Admin users
			if ($user->authorise('core.manageall', 'com_cluster') || $user->authorise('core.admin'))
			{
				FormHelper::addFieldPath(JPATH_SITE . '/components/com_tjucm/models/fields/');
				$dpeTags = FormHelper::loadFieldType('dpetags', false);
				$dpeTag  = $dpeTags->getOptions(); 

				JLoader::import("/components/com_dpe/includes/dpe", JPATH_SITE);
				$dpeModel = DPE::model('school', array('ignore_request' => true));

				$trusteeTags = ($isTrustee)?$dpeModel->getAgencyTags($multiagency_trustee_group):$dpeModel->getAgencyTags($orgAdminRoleId);
				?>

				<div class="btn-group mr-10 md-w-200px pull-left mb-5">
					<fieldset id="filter-bar">
						<div class="filter-select fltrt">
							<select name="filter_tags[]" id = "filter_tags"  data-placeholder="<?php echo Text::_('COM_DPE_FORM_LBL_TAG'); ?>" class="chosen-select" multiple="multiple" onchange="this.form.submit()">
								<option value=""><?php echo Text::_('COM_DPE_FORM_LBL_TAG'); ?></option>
								<?php if ($user->authorise('core.manageall', 'com_cluster'))
								{
									echo HTMLHelper::_('select.options', $dpeTag, 'value', 'text', $this->state->get('filter.tags'));
								}else{ 
									echo HTMLHelper::_('select.options', $trusteeTags, 'value', 'text', $this->state->get('filter.tags'));
								}?>
							</select>
						</div>
					</fieldset>
				</div>
				<?php
			}
			?>
			<!-- Cluster Filter End -->

			<?php
	// Check for Can Create Document
			if (ComponentHelper::getComponent('com_subusers', true)->enabled)
			{
				JLoader::import("/components/com_subusers/includes/rbacl", JPATH_ADMINISTRATOR);

				if ($user->authorise('core.manageall', 'com_cluster') || RBACL::check($user->id, 'com_cluster', 'core.manage.lessons', 'com_tjlms', $clusterId))
					{?>
						<a href="<?php echo Route::_($uploadDocLink . '&Itemid=' . $createDocMenuItem->id); ?>" class="btn btn-blue btn-small btn-upload">
							<?php echo Text::_('COM_DPE_UPLOAD_DOC'); ?></a><?php
						}
					}?>
				</div>
				<div class="col-md-12 search-field">
					<?php
					$searchTool = LayoutHelper::render('joomla.searchtools.default', array('view' => $this,
						'options' => array('filterButton' => false, 'filtersHidden' => false)
					));
					echo str_replace("icon-search","glyphicon glyphicon-search", $searchTool);
				?> <!--// JHtmlsidebar for menu ends-->
			</div>
		</div>


		<div class="clearfix mb-10"> </div>

		<?php if (empty($this->items)) : ?>
			<div class="alert alert-no-items">
				<?php echo Text::_('JGLOBAL_NO_MATCHING_RESULTS'); ?>
			</div>
		<?php else : ?>

			<div class="table-responsive">
				<table class="table table-striped mb-0" id="lessonsList">
					<thead>
						<tr>
							<th width="30%">
								<?php echo HTMLHelper::_( 'grid.sort', 'COM_TJLMS_MANAGELESSONS_NAME', 'a.title', $listDirn, $listOrder); ?>
							</th>
							<th width="20%">
								<?php echo HTMLHelper::_( 'grid.sort', Text::sprintf('COM_TJLMS_MANAGELESSONS_CLUSTER_HEAD', Text::_('COM_MULTIAGENCY_ORGANISATION')), 'cl.name', $listDirn, $listOrder); ?>
							</th>
							<th width="10%">
								<?php echo HTMLHelper::_( 'grid.sort', 'COM_DPE_USER_NAME', 'user_count', $listDirn, $listOrder); ?>
							</th>
							<th width="30%" class="text-center">
								<?php echo Text::_('COM_TJLMS_MANAGELESSONS_STATUS');?>
							</th>
							<th width="10%">
								&nbsp;
							</th>
						</tr>
					</thead>
					<tbody>
						<?php
						foreach ($this->items as $i => $item)
						{

							// Use the already loaded model and get Document ID
							$lesson = $modelLesson->getlessondata($item->id);
							$lesson_typedata = $modelLesson->getlesson_typedata($item->id, $item->format);
							$params = json_decode($lesson_typedata->params);

							$readPercentage = '';
							$usedPercentage = '';

							$intractions = new Registry($item->params);
							$noInteractions = false;

							if (!$intractions['practice_interaction'] && !$intractions['read_interaction'])
							{
								$noInteractions = true;
							}

							if ($item->user_count <= 0)
							{
								$deleteDocMessage = Text::_('COM_DPE_DELETE_DOCUMENT_MESSAGE');
							}
							else
							{
								$deleteDocMessage = Text::sprintf('COM_DPE_DELETE_ASSIGNED_DOCUMENT_MESSAGE', $item->user_count);
							}

							$createdBy = ($this->showUserOrUsername == 'name')?$item->name:$item->username;
							$canCreate  = $this->canCreate;
							$canEdit    = $this->canEdit;
							$canCheckin = $this->canCheckin;

							$canChange  = $this->canChangeStatus;

							if ($item->user_count > 0)
							{
								// Get interaction percentage
								$readPercentage = ($item->read_count * 100) / $item->user_count;
								$usedPercentage = ($item->used_count * 100) / $item->user_count;
								$consentPercentage = ($item->consented_count * 100) / $item->user_count;
							}

							// Get file extension
							$info = new SplFileInfo($item->source);
							$extension = $info->getExtension();
							?>

							<tr class="row<?php echo $i % 2; ?>" >
								<td>
									<div class="break-word doc-title">
										<a class="hasTooltip"
										href="<?php echo Route::_('index.php?option=com_tjlms&view=lesson&lesson_id=' . $item->id . '&cluster_id=' . $clusterId); ?>"
										<?php echo $target;?>
										title="<?php echo Text::_('COM_DPE_DOCUMENT_LAUNCH_HINT'); ?>">
										<span class="icon pull-left <?php echo $extension;?>"></span>
										<span class="doc-name"><?php echo $this->escape($item->title); ?></span>
									</a>
								</div>

								<div class="fs-12">
									<?php
									$lessonDescCharLimit = 100;

									if (strlen($item->description) > $lessonDescCharLimit)
									{
										echo substr(strip_tags($item->description), 0, $lessonDescCharLimit);?>

										<div class="mid" id="HiddenDiv_<?php echo $i ?>" style="">
											<?php echo substr(strip_tags($item->description), $lessonDescCharLimit, strlen($item->description));?>
										</div>
										<a href="javascript:void(0);" class="manage-lesson-more_<?php echo $i ?>" onclick="tjlms.managelessons.showHide('HiddenDiv_<?php echo $i ?>')">
											<?php echo Text::_('COM_TJLMS_MANAGELESSONS_LESSON_DESCRIPTION_READ_MORE');?>
										</a>
										<a href="javascript:void(0);" class="manage-lesson-less_<?php echo $i ?>" style="display:none" onclick="tjlms.managelessons.showHide('HiddenDiv_<?php echo $i ?>')">
											<?php echo Text::_('COM_TJLMS_MANAGELESSONS_LESSON_DESCRIPTION_READ_LESS');?>
										</a>
										<?php
									}
									else
									{
										echo $this->escape($item->description);
									}
									?>
								</div>

							</td>
								<!--
								DPE - Hack - Start
							-->
							<td class="school-title">
								<?php echo (($item->cluster_name) ? $this->escape($item->cluster_name) : '-'); ?>
							</td>
								<!--
								DPE - Hack - End
							-->
							<td class="user-count">
								<?php
								if ($item->user_count)
								{
									?>
									<a href=<?php echo Route::_('index.php?option=com_tjlms&view=lesson&lesson_id=' . $item->id.'&showusers=1');?>>
										<?php echo $item->user_count;?>
									</a>
									<?php
								}
								else
								{
									echo $item->user_count;
								}
								?>
							</td>
							<td class="doc-status <?php echo $noInteractions ? 'text-center' : 'text-right'?>">
								<?php

								if ($noInteractions)
								{
									echo Text::_('COM_DPE_NOT_APLLICABLE');
								}

								if ($jlikeTjlmslessonPluginObj->get('read_interaction') == '1' && $intractions['read_interaction'])
								{
									?>
									<span class="d-block">
										<span><?php echo Text::_('COM_DPE_DOCUMENT_LIST_INTERACTION_READ_UNDERSTOOD');?></span>
										<div class="progress d-inline-block mx-5" width="150">
											<div class="progress-bar progress-bar-info progress-green " role="progressbar" data-toggle="tooltip" title="<?php echo Text::_('COM_DPE_DOCUMENT_LIST_INTERACTION_READ_UNDERSTOOD');?>" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100" style="width:0%" data-original-title="0/4"></div>
											<div class="progress-bar " role="progressbar" data-toggle="tooltip" title="<?php echo Text::_('COM_DPE_DOCUMENT_LIST_INTERACTION_READ_UNDERSTOOD');?>" aria-valuenow="25" aria-valuemin="0" aria-valuemax="100" style="width:<?php echo $readPercentage;?>%" data-original-title="1/4"></div>
										</div>
										<span class="count"><?php echo Text::sprintf('COM_DPE_DOCUMENT_LIST_INTERACTION_COUNT', $item->read_count, $item->user_count);?></span>
									</span>
									<?php
								}

								if ($jlikeTjlmslessonPluginObj->get('practice_interaction') == '1' && $intractions['practice_interaction'])
								{
									?>
									<span class="d-block">
										<span><?php echo Text::_('COM_DPE_DOCUMENT_LIST_INTERACTION_USED');?></span>
										<div class="progress d-inline-block mx-5" width="150">
											<div class="progress-bar progress-bar-info progress-green " role="progressbar" data-toggle="tooltip" title="<?php echo Text::_('COM_DPE_DOCUMENT_LIST_INTERACTION_USED');?>" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100" style="width:0%" data-original-title="0/4"></div>
											<div class="progress-bar " role="progressbar" data-toggle="tooltip" title="<?php echo Text::_('COM_DPE_DOCUMENT_LIST_INTERACTION_USED');?>" aria-valuenow="25" aria-valuemin="0" aria-valuemax="100" style="width:<?php echo $usedPercentage;?>%" data-original-title="1/4"></div>
										</div>
										<span class="count"><?php echo Text::sprintf('COM_DPE_DOCUMENT_LIST_INTERACTION_COUNT', $item->used_count, $item->user_count);?></span>
									</span>
									<?php
								}

								if ($jlikeTjlmslessonPluginObj->get('consent_interaction') == '1')
								{
									?>
									<span class="d-block">
										<span><?php echo Text::_('COM_DPE_DOCUMENT_LIST_INTERACTION_CONSENT');?></span>
										<div class="progress d-inline-block mx-5" width="150">
											<div class="progress-bar progress-bar-info progress-green " role="progressbar" data-toggle="tooltip" title="<?php echo Text::_('COM_DPE_DOCUMENT_LIST_INTERACTION_CONSENT');?>" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100" style="width:0%" data-original-title="0/4"></div>
											<div class="progress-bar " role="progressbar" data-toggle="tooltip" title="<?php echo Text::_('COM_DPE_DOCUMENT_LIST_INTERACTION_CONSENT');?>" aria-valuenow="25" aria-valuemin="0" aria-valuemax="100" style="width:<?php echo $consentPercentage;?>%" data-original-title="1/4"></div>
										</div>
										<span class="count"><?php echo Text::sprintf('COM_DPE_DOCUMENT_LIST_INTERACTION_COUNT', $item->consented_count, $item->user_count);?></span>
									</span>
									<?php
								}
								?>
							</td>
							<td class="text-center doc-assign">
								<?php

								if (ComponentHelper::getComponent('com_subusers', true)->enabled)
								{
									JLoader::import("/components/com_subusers/includes/rbacl", JPATH_ADMINISTRATOR);

									if ($user->authorise('core.manageall', 'com_cluster') || RBACL::check($user->id, 'com_cluster', 'core.assign.lesson', 'com_tjlms', $clusterId))
									{
										$assignLink = Route::_('index.php?option=com_dpe&view=users&tmpl=component&title=' . $this->escape($item->title) . '&element_id=' . $item->id . '&cluster_id=' . $clusterId, false); ?>
										<a class="d-inline-block mr-4" href="javascript:void(0);" onclick="openUserAssignRecommendPopups('<?php echo addslashes($assignLink);?>')" id="assign-modal-link" title="<?php echo Text::_('COM_DPE_LESSON_ASSIGNMENT');?>" >
											<i class="fa fa-user-plus" aria-hidden="true"></i>
										</a>
									<?php } ?>
								<?php } ?>
								<?php if ($jlikeDelete) {

									if (ComponentHelper::getComponent('com_subusers', true)->enabled)
									{
										JLoader::import("/components/com_subusers/includes/rbacl", JPATH_ADMINISTRATOR);

										if ($user->authorise('core.manageall', 'com_cluster') || RBACL::check($user->id, 'com_cluster', 'core.deassign.lesson', 'com_tjlms', $clusterId))
										{
											$deassignLink = Route::_('index.php?option=com_dpe&view=users&layout=users&tmpl=component&action=deassign&title=' . $this->escape($item->title) . '&element_id=' . $item->id . '&cluster_id=' . $clusterId, false);?>
											<a class="d-inline-block mr-4" href="javascript:void(0);" onclick="openUserAssignRecommendPopups('<?php echo addslashes($deassignLink);?>')" id="assign-modal-link" title="<?php echo TEXT::_('COM_DPE_DEASSIGNMENT_BTN');?>" >
												<i class="fa fa-user-times" aria-hidden="true"></i>
											</a>
										<?php } ?>
									<?php } ?>
								<?php } ?>
										<?php
										if (ComponentHelper::getComponent('com_subusers', true)->enabled)
										{
											JLoader::import("/components/com_subusers/includes/rbacl", JPATH_ADMINISTRATOR);

											if ($user->authorise('core.manageall', 'com_cluster') || RBACL::check($user->id, 'com_cluster', 'core.createDocument', 'com_multiagency', $clusterId))
												{?>
													<a class="d-inline-block mr-4" href="<?php echo Route::_($uploadDocLink . '&id=' . $item->id . '&cluster_id=' . $clusterId . '&Itemid=' . $createDocMenuItem->id); ?>" title="<?php echo Text::_('JACTION_EDIT'); ?>">
														<i class="fa fa-pencil-square-o" aria-hidden="true"></i>
													</a>
												<?php } ?>
											<?php } ?>
											<?php if ($jlikeDelete) { ?>
												<?php
												if (ComponentHelper::getComponent('com_subusers', true)->enabled)
												{
													JLoader::import("/components/com_subusers/includes/rbacl", JPATH_ADMINISTRATOR);

													if ($user->authorise('core.manageall', 'com_cluster') || RBACL::check($user->id, 'com_cluster', 'core.delete.lesson', 'com_multiagency', $clusterId))
														{?>
															<a class="d-inline-block mr-4" onclick="deleteItem('<?php echo $item->id; ?>',this)" data-message="<?php echo $item->user_count <= 0 ? Text::_('COM_DPE_DELETE_DOCUMENT_MESSAGE'): Text::sprintf('COM_DPE_DELETE_ASSIGNED_DOCUMENT_MESSAGE', $item->user_count);?>" class="btn btn-mini delete-button" type="button"><i class="icon-trash" ></i></a>
															<!-- Add Embed Code Button -->
														<?php } ?>
												<?php } 

													if (!empty($intractions) && isset($intractions['publicly_interaction']) && $intractions['publicly_interaction'] === "1") : ?>
														<a class="d-inline-block mr-4 btn btn-mini embed-button"
														onclick="showEmbedCode('<?php echo $item->id; ?>', '<?php echo $params->document_id; ?>', this)"
														data-message="<?php echo Text::_('COM_DPE_EMBED_COPIED_MESSAGE'); ?>"
														type="button">
															<i class="icon-code"></i>
														</a>
													<?php endif; 
													
													
													}?>
								<!--
									<a class="d-inline-block">
										<i class="fa fa-trash-o" aria-hidden="true"></i>
									</a>
								-->

							</td>
						</tr>

						<?php
					}
					?>
				</tbody>
			</table>
		</div>
	<?php endif; ?>

	<div class="pager" id="pagination">
		<?php echo $this->pagination->getPagesLinks(); ?>
		<!-- <hr class="hr hr-condensed"/> -->
	</div><!--row-fluid-->

	<input type="hidden" name="task" value="" />
	<input type="hidden" name="boxchecked" value="0" />
	<input type="hidden" name="filter_order" value="<?php echo $listOrder; ?>" />
	<input type="hidden" name="filter_order_Dir" value="<?php echo $listDirn; ?>" />

	<!-- DPE Hack start to add hidden fields to create content for notification manager -->
	<input type="hidden" name="url" id="url" value="<?php echo $currentUrl;?>"/>
	<input type="hidden" name="element" id="element" value="com_tjlms.lesson"/>
	<input type="hidden" name="element_id" id="element_id" value="<?php echo $complianceListId;?>"/>
	<input type="hidden" name="cluster_id" id="cluster_id" value=""/>
	<!-- DPE Hack end -->

	<?php echo HTMLHelper::_('form.token'); ?>
</form>
</div>


<script type="text/javascript">

	<!-- Following js added to set cluster dropdown value to hidden 'cluster_id' field -->

	if (jQuery('#filter_agencies').val() != "all" )
	{
		jQuery('#cluster_id').val(jQuery('#clusters').val());
	}

	// Function to show embed code modal
	function showEmbedCode(lessonId, documentId, element) 
	{
		const iframeSrc = '<?php echo JURI::base(); ?>index.php?option=com_tjlms&view=lesson&lesson_id=' + lessonId;
		const iframeHtml = '<iframe src="' + iframeSrc + '" width="100%" height="600" frameborder="0" allowfullscreen></iframe>';

		// Copy to clipboard
		const tempInput = document.createElement('textarea');
		tempInput.value = iframeHtml;
		document.body.appendChild(tempInput);
		tempInput.select();
		tempInput.setSelectionRange(0, 99999);

		try {
			document.execCommand('copy');
			redirectAfterCopy(lessonId);
		} catch (err) {
			console.error('Copy failed:', err);
		}
		document.body.removeChild(tempInput);
	}


	function redirectAfterCopy(lessonId) {
		const itemId = '<?php echo $itemId; ?>';
		const clusterId = '<?php echo $clusterId; ?>';

		if (!lessonId) return;

		const redirectURL = Joomla.getOptions('system.paths').base + 
			'/index.php?option=com_dpe&task=embedCopyRedirect&id=' + lessonId +
			'&Itemid=' + itemId + '&clusterId=' + clusterId;

		window.location.href = redirectURL;
	}




	function deleteItem(lessonId,params)
	{
		let itemId = '<?php echo $itemId;?>';
		let id = parseInt(lessonId);
		let clusterId = '<?php echo $clusterId;?>';

		if(isNaN(id) || id =='')
		{
			return false;
		}

		let redirectURL = Joomla.getOptions('system.paths').base + '/index.php?option=com_dpe&task=deleteDocument&id='+id+'&Itemid='+itemId+'&clusterId='+clusterId;

		if (!confirm(jQuery(params).data("message")))
		{
			return false;
		}
		window.location.href = redirectURL;
	}
//DPE HACK
jQuery( document ).ready(function() {
	jQuery('#list_fullordering_chosen').hide();
});
</script>
<script>
	jQuery(document).ready(function() {
		
		
		jQuery('#filter_tags').change(function(e) {    
			var selectData = jQuery("#filter_tags").chosen().val(); 
    	// Check the tag filter is set or not  and set accordingly
    	if ((selectData == null )|| (selectData == ''))
    	{ 
    		jQuery("#filter_tags").val(jQuery("#filter_tags option:first").val());
    	}
    }); 

   		// check dpe admin
   		var isDpeAdminOrTrustee = "<?php echo ($user->authorise('core.manageall', 'com_cluster') || $isTrustee || $orgAdminRoleId); ?>";

   		if (!isDpeAdminOrTrustee)
   		{
   			jQuery('#filter_tags_chosen').hide();
   		}

   		var tagselectData = jQuery("#filter_tags").chosen().val(); 
   		if (tagselectData)
   		{
   			jQuery("#clusters").val(jQuery("#clusters option:first").val());
   			jQuery("#clusters").trigger("liszt:updated");
   		}

   		jQuery('#filter_tags').on('change', function() {
   			jQuery("#clusters").val(jQuery("#clusters option:first").val());			
   		});
   		jQuery('#clusters').on('change', function() {	
   			jQuery("#filter_tags").val(jQuery("#filter_tags option:first").val());
   		});

   	})
   </script>
</body>
</html>