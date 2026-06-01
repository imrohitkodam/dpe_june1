<?php
/**
 * @version     1.0.0
 * @package     com_tjreports
 * @copyright   Copyright (C) 2014. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @author      TechJoomla <extensions@techjoomla.com> - http://www.techjoomla.com
*/

// no direct access
defined('_JEXEC') or die;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Filter\OutputFilter;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Plugin\PluginHelper;

HTMLHelper::_('formbehavior.chosen', 'select');
HTMLHelper::addIncludePath(JPATH_COMPONENT . '/helpers/html');
JLoader::import('components.com_subusers.includes.rbacl', JPATH_ADMINISTRATOR);
JLoader::import('components.com_cluster.includes.cluster', JPATH_ADMINISTRATOR);
HTMLHelper::script(Uri::root().'media/com_timelog/js/timelog.js');
HTMLHelper::script(Uri::root().'media/com_dpe/js/tjreportaddtodo.js');


jimport( 'joomla.database.databasequery' );

$emailColmClass = 'td-sendemail';
$addTodoClass = "td-addtodo";

$app             = Factory::getApplication();
$headerLevel     = $this->headerLevel;
$this->listOrder = $this->state->get('list.ordering');
$this->listDirn  = $this->state->get('list.direction');
$totalCount      = 0;
foreach ($this->colToshow as $key=>$data)
{
	if (is_array($data))
	{
		$totalCount = $totalCount + count($data);
	}
	else
	{
		$totalCount++;
	}
}

// Fetch content Id for compliance manager report
if (isset($this->filterValues['lession_id']))
{
	PluginHelper::importPlugin('dpeaddtodo');
	
	try
	{
	    $contentId = Factory::getApplication()->triggerEvent('onGetContentId',array('lesson_id'=>$this->filterValues['lession_id']));
	    
	}
	catch (Exception $e)
	{
	    $results = $e;
	}
}

$input                = Factory::getApplication()->input;
$displayFilters       = $this->userFilters;
$totalHeadRows        = count($displayFilters);
$reportId             = $app->getUserStateFromRequest('reportId', 'reportId', '');
$user                 = Factory::getUser();
$userAuthorisedExport = $user->authorise('core.export', 'com_tjreports.tjreport.' . $reportId);


if ($app->isClient('site'))
{	
	Text::script('PLG_SYSTEM_ADDTODO_BTN');
	Text::script('PLG_SYSTEM_ADDTODO_BTN_VALIDATION');



	$siteUrl = Uri::root();
	$message = array();
	$message['success']    = Text::_("COM_TJREPORTS_EXPORT_FILE_SUCCESS");
	$message['error']      = Text::_("COM_TJREPORTS_EXPORT_FILE_ERROR");
	$message['inprogress'] = Text::_("COM_TJREPORTS_EXPORT_FILE_NOTICE");
	$message['text']       = Text::_("COM_TJREPORTS_CSV_EXPORT");

	HTMLHelper::script(Uri::base() . 'libraries/techjoomla/assets/js/tjexport.js');
	$document = Factory::getDocument();
	$csv_url = 'index.php?option=' . $input->get('option') . '&view=' . $input->get('view') . '&format=csv';

	$document->addScriptDeclaration("var csv_export_url='{$csv_url}';");
	$document->addScriptDeclaration("var csv_export_success='{$message['success']}';");
	$document->addScriptDeclaration("var csv_export_error='{$message['error']}';");
	$document->addScriptDeclaration("var csv_export_inprogress='{$message['inprogress']}';");
	$document->addScriptDeclaration("var tj_csv_site_root='{$siteUrl}';");

	// DPE Hack to check user have elearning action to access elearning reports
	if (!$user->authorise('core.manageall', 'com_cluster'))
	{
		$clusterUserModel = ClusterFactory::model('ClusterUser', array('ignore_request' => true));
		$clusters         = $clusterUserModel->getUsersClusters($user->id);
		$elearningReports = ComponentHelper::getParams('com_dpe')->get('elearningReports');
		$elearningAccess  = array();

		if (in_array($this->reportData->id, $elearningReports))
		{
			foreach ($clusters as $cluster)
			{
				$elearningAccess[] = RBACL::check($user->id, 'com_cluster', 'core.viewShika', 'com_tjlms', $cluster->cluster_id);
			}

			$elearningAccess = array_filter($elearningAccess);

			if (empty($elearningAccess))
			{
				$app = Factory::getApplication();

				$app->enqueueMessage(Text::_('JERROR_ALERTNOAUTHOR'));

				return;
			}
		}
	}
	// DPE Hack end
}
?>
<div id="reports-container">
	<div class="<?php echo COM_TJLMS_WRAPPER_DIV ?> tjBs3">
	<?php
	if (!empty($this->sidebar)):?>
		<div id="j-sidebar-container" class="span2">
			<?php echo $this->sidebar;?>
		</div>
		<!--j-sidebar-container-->
		<div id="j-main-container" class="span10">
	<?php
	else :
	?>
		<div id="j-main-container">
	<?php
	endif;

		if ($app->isClient('site') && isset($this->reportData->title))
		{
		?>
			<h2 class="title mt-0"><?php echo htmlspecialchars($this->reportData->title, ENT_COMPAT, 'UTF-8'); ?></h2>
		<?php
		}
		?>
			<form class="manage-reports ucm-form-styling" action="<?php echo Route::_('index.php?option=com_tjreports&view=reports'); ?>" method="post" name="adminForm" id="adminForm" onsubmit="return tjrContentUI.report.submitForm();">
				<!--html code-->
				<div class="dp-pagination-dropdown pt-10 d-block">
					<div class="d-inline-block me-2">
						<div class="form-group md-w-220 d-inline-block">
							<select class="form-control" id="report-select" onchange="tjrContentUI.report.loadReport(this,'<?php echo $this->client; ?>');">
							<?php
								foreach ($this->enableReportPlugins as $eachPlugin) :
								$this->model->loadLanguage($eachPlugin['plugin']);
								$selected = ' ';

								if ($this->reportId == $eachPlugin['reportId'])
								{
									$selected = 'selected="selected"';
								}

								$pluginName = strtoupper($eachPlugin['plugin']);
								$langConst = "PLG_TJREPORTS_" . $pluginName;
							?>
								<option value="<?php echo $eachPlugin['plugin'];?>" <?php echo $selected; ?> data-reportid="<?php echo $eachPlugin['reportId']; ?>">
									<?php echo $eachPlugin['title']; ?>
								</option>
							<?php
								endforeach;
							?>
							</select>
						</div>
						<!--form-group-->
					</div>
					<?php
						if (!$app->isClient('administrator') && $userAuthorisedExport && $user)
						{
						?>
							<div class="d-inline-block me-2">
								<a onclick="tjexport.exportCsv(0)" class="btn btn-small btn-default export">
									<i class='fa fa-download'></i>&nbsp;<?php echo Text::_('COM_TJREPORTS_CSV_EXPORT'); ?>
								</a>
								<!-- <span id="sendEmail">
								</span> -->
							</div>
							<div class="clearfix"></div>
					  <?php
						}
						?>
					<!--col-md-3-->
					<div class="d-inline-block me-2 pull-right pr-0">
						<div id="reportPagination" class="pull-right ">
							<?php
							if (!$app->isClient('administrator'))
							{
								echo $this->pagination->getPaginationLinks('joomla.pagination.links', array('showPagesLinks' => false,'showLimitStart' => false));
							}
							else
							{
								echo $this->pagination->getLimitBox();
							}
							?>
						</div>
						<div class="clearfix"></div>
					</div>
					<!--/col-md-1-->
				</div>
				<div class="report-top-bar mt-0 pb-10 d-block">
				<?php
					if (!empty($this->savedQueries))
					{
					?>
						<div class="d-inline-block me-2">
							<?php	echo HTMLHelper::_('select.genericlist', $this->savedQueries, "queryId", 'class="" size="1" onchange="tjrContentUI.report.getQueryResult(this.value);" name="filter_saveQuery"', "value", "text", $this->queryId);
							?>
							<?php
							if ($this->queryId)
							{
							?>
								<a class="btn btn-default" onclick="tjrContentUI.report.deleteThisQuery();">
									<i class="fa fa-trash"></i>
								</a>
							<?php
							}
							?>
						</div>
					<!--/col-md-4-->
					<?php
					}

					if ($app->isClient('site'))
					{
						if ($this->isExport)
						{
						}
					?>
						<div class="d-inline-block me-2 w-auto">
							<span id="btn-cancel">
								<input type="text" name="queryName" autocomplete="off" placeholder="Title for the Query"  id="queryName"/>
							</span>
							<a class="btn btn-primary  saveData" type="button" id="saveQuery"
							onclick="tjrContentUI.report.saveThisQuery();">
								<?php echo Text::_('COM_TJREPORTS_SAVE_THIS_QUERY'); ?>
							</a>
							<button class="btn btn btn-default cancel-btn " type="button" style="display:none;" onclick="tjrContentUI.report.cancel();">Cancel</button>
						</div>
					<?php
					}
					?>

					<!--/col-md-2-->
							<div class="show-hide-cols d-inline-block me-2 w-auto">
								<input type="button" id="show-hide-cols-btn" class="btn btn-success" onclick="tjrContentUI.report.getColNames(); return false;" value="<?php echo Text::_('COM_TJREPORTS_HIDE_SHOW_COL_BUTTON'); ?>" />
								<ul id="ul-columns-name" class="ColVis_collection">
									<?php
									foreach ($this->showHideColumns as $colKey)
									{
										$checked 	= '';

										if (isset($this->columns[$colKey]['title']))
										{
											$colTitle = $this->columns[$colKey]['title'];
										}
										else
										{
											$colTitle = 'PLG_TJREPORTS_' . strtoupper($this->pluginName . '_' . $colKey . '_TITLE');
										}

										if (in_array($colKey, $this->colToshow))
										{
											$checked 	= 'checked="checked"';
										}
									?>
										<li>
											<label>
												<input onchange="tjrContentUI.report.submitTJRData('showHide');" type="checkbox" value="<?php echo $colKey;	?>" <?php echo $checked; ?> name="colToshow[<?php echo $colKey;	?>]" id="<?php echo $colKey;	?>">
												<span><?php echo Text::_($colTitle);?></span>
											</label>
										</li>
									<?php
									}
								?>
								</ul>
							</div>
							<!-- <div class="d-inline-block me-2">
							<span id="sendEmail">
								</span>
							</div> -->
						
						<div class="d-inline-block me-2">
							<span id="addtodobtn">
								</span>
							</div>
							
					<!--/col-md-2-->
					 
					<!--/col-md-2-->
					<!--/row-->
					</div>
					<div class="js-stools-container-list hidden-phone hidden-table row">
						<div class="ordering-select hidden-phone show-tools col-md-12" id="topFiltersShow">
							<?php
							if ($totalHeadRows > 1)
							{
								$this->filters  = array_pop($displayFilters);
								$this->filterLevel = 1;
								echo $this->loadTemplate('filters');

								if ($this->srButton)
								{
								?>
									<div class="btn-group filter-btn-block control-group pt-10 pull-left">
									<?php
										if ($this->srButton !== -1)
										{
										?>
											<button class="btn btn-primary hasTooltip br-0 mr-5" onclick="tjrContentUI.report.submitTJRData(); return false;" title="<?php echo Text::_('JSEARCH_FILTER_SUBMIT')?>">
												<i class="fa fa-search"></i>
											</button>
										<?php
										} ?>
										<button class="btn hasTooltip" type="button" title="<?php echo Text::_('JSEARCH_FILTER_CLEAR')?>" onclick="tjrContentUI.report.resetSubmitTJRData('reset', '#topFiltersShow'); return false;">
											<!-- <i class="fa fa-remove"></i>--><?php echo Text::_('JSEARCH_FILTER_CLEAR')?> 
										</button>
									</div>
							 <?php
								}
							}
							?>
						</div>
					<!--/col-md-12-->
					</div >
					<?php // if add todo plugin enable set the filter to show the addtodo button 
						if (PluginHelper::isEnabled('system', 'dpeaddtodo'))	
						{ ?>
						<input type='checkbox' id='filters_allUser' name='filters[allUser]' value='' onchange="getalluser(this)"><span class= 'fill_all_user fw-bold'> <?php echo Text::_('PLG_SYSTEM_ADDTODO_BTN_CHECKALL');?></span>
						<br>
					<?php }?>
					

					<!--/row-->
					<div class="clearfix"></div>
					<!-- js-stools-container-list hidden-phone hidden-tablet span4 -->
					<div id="report-containing-div" class="row">

						<div class="col-xs-12">
							<div class="table-responsive report-tbl mt-3">
								<table id="report-table" class="table table-striped left_table ">
									<thead>
										<?php
										jimport('joomla.filter.output');
										$filters = array();

										if (!empty($displayFilters))
										{
											$filters = array_pop($displayFilters);
										}

										for($i = $headerLevel; $i > 0 ; $i--)
										{
											echo '<tr class="report-row">';

											foreach($this->colToshow as $index=>$detail)
											{
												if (!is_array($detail))
												{
													$hasFilter = isset($filters[$detail]);
												}

												if ($i == 1)
												{
													if (strpos($index, '::'))
													{
														$indexArray   = explode('::', $index);
														$contentTitle = $indexArray[0];
														$contentId    = $indexArray[0];

														foreach ($detail as $subKey => $subDetail)
														{
															$keyDetails   = explode('::', $subKey);

															if (!isset($this->columns[$subKey]['title']))
															{
																$subTextTitle = 'PLG_TJREPORTS_' . strtoupper($this->pluginName . '_' . $keyDetails[0] . '_' . $keyDetails[1] . '_TITLE');
															}
															else
															{
																$subTextTitle = $this->columns[$subKey]['title'];
															}

															echo '<th class="subdetails ' . $keyDetails[0] . ' ' . $keyDetails[1] . '">';

															$colTitle = Text::sprintf($subTextTitle, $keyDetails[1]) ;

															if (in_array($subKey, $this->sortable))
															{
																echo $sortHtml = HTMLHelper::_('grid.sort', $colTitle, $subKey, $this->listDirn, $this->listOrder);
															}
															else
															{
																echo '<div class="header_title">' . Text::_($colTitle) . '</div>';
															}

															echo '</th>';
														}
													}
													else
													{
														$colKey = $detail;
														$colKeyClass = OutputFilter::stringURLSafe($colKey);
														if (!isset($this->columns[$colKey]['title']))
														{
															$colTitle = 'PLG_TJREPORTS_' . strtoupper($this->pluginName . '_' . $colKey . '_TITLE');
														}
														else
														{
															$colTitle = $this->columns[$colKey]['title'];
														}

														echo '<th class="' . $colKeyClass  . '">';

														if ($hasFilter)
														{
															echo '<span class="table-heading">';
														}

														if (in_array($colKey, $this->sortable))
														{
															echo $sortHtml = HTMLHelper::_('grid.sort', $colTitle, $colKey, $this->listDirn, $this->listOrder);
														}
														else
														{
															echo '<div class="header_title">' . Text::_($colTitle) . '</div>';
														}
														if ($hasFilter)
														{
															echo '<a href="#" title="search" class="col-search">
																		<i class="fa fa-search"></i>
																	</a></span>';
														}

														if ($hasFilter)
														{
															$this->filterLevel = 2;

															$this->filters  = array($colKey => $filters[$colKey]);
															$this->colKey = $colKey;

															echo $this->loadTemplate('filters');
														}

														echo '</th>';
													}
												}
												elseif ($i == 2)
												{
													if (strpos($index, '::'))
													{
														$indexDetail = explode('::', $index, 2);

														echo '<th class="center" colspan="' . count($detail) . '">' . array_pop($indexDetail) . '</th>';
													}
													else
													{
														echo '<th>&nbsp;</th>';
													}
												}
											}

											echo '</tr>';
										}
										?>
									</thead>
									<tbody>
									<?php
										// Loop through items
										// No Result Found

										if ((empty($this->items) && !$this->items['noaccessmessage']) || count($this->items) < 1 )
										{
											echo '<tr>
													<td class="center" colspan="' . $totalCount . '">No Results Found.</td>
												</tr>';
										}
										elseif($this->items['noaccessmessage'])
										{
											echo '<tr><td class="center" colspan="' . $totalCount . '">' . $this->items['noaccessmessage'] . '</td></tr>';
										}
										else
										{

											foreach($this->items as $itemKey => $item)
											{
												echo '<tr>';

												foreach ($this->colToshow as $arrayKey => $key)
												{
													
													if (is_array($key))
													{
														foreach($key as $subkey => $subVal)
														{
															$keyDetails   = explode('::', $subkey);
															echo '<td class="subdetails ' . $keyDetails[0] . ' ' . $keyDetails[1] . '">' .  $item[$arrayKey][$subkey] .'</td>';
														}
													}
													else
													{			
														$value = 'value ="'.$item['user_id'].'"';

														$isSendEmailClass = ($key == $this->emailColumn) ? $emailColmClass : '';
														
														$isAddTodoClass   = ($key == $this->emailColumn) ? $addTodoClass : '';	

														if ($isAddTodoClass)
														{
															echo "<td ".$value."class=\"{$isAddTodoClass} {$key} {$isSendEmailClass}\" >{$item[$key]}</td>";
														}
														else
														{
															echo "<td class=\"{$isAddTodoClass} {$key} {$isSendEmailClass}\" >{$item[$key]}</td>";
														}
													}
												}

												echo '</tr>';
											}
										}

										// Any message to display
										if (!empty($this->messages))
										{
											echo '
											<tr>
												<td colspan="' . $totalCount . '">
													<div class="alert alert-warning">
														' . implode('<br>', (array) $this->messages) . '
													</div>
												</td>
											</tr>';
										}
									?>
									</tbody>
								</table>
							</div>
						</div>
						<!--/col-md-12-->
					</div>
					<!--report-containing-div-->


					<?php

					if (!$app->isClient('administrator'))
					{
						?>
							<div class="text-center pager" id="pagination">
							<?php
								echo $this->pagination->getPaginationLinks('joomla.pagination.links', array('showLimitBox' => false));
							?>
							</div>
						<?php
					}
					else
					{
						?>
						<div class="text-center pager" id="pagination">
							<?php echo $this->pagination->getListFooter();?>
						</div>
					<?php
					}

					$application = Factory::getApplication();
					$sitemenu = $application->getMenu();
					$mainmenuItems = $sitemenu->getItems(array('unpublish-menu'), array(''));

					
					$actualUrl = Route::_('index.php?option=com_jlike&tmpl=component&view=recommendationform&layout=editreport',false);

					?>

					<input type="hidden" id="filter_order" name="filter_order" value="<?php echo  $this->listOrder; ?>" />
					<input type="hidden" id="filter_order_Dir" name="filter_order_Dir" value="<?php echo  $this->listDirn; ?>" />
					<input type="hidden" id="reportId" name="reportId" value="<?php echo  $this->reportId; ?>" />
					<input type="hidden" id="reportToBuild" name="reportToBuild" value="<?php echo  $this->pluginName; ?>" />
					<input type="hidden" id="task" name="task" value="" />
					<input type="hidden" name="boxchecked" value="0" />
					<input type="hidden" id="noofpage" name="noofpage" value="<?php echo $this->pagination->pagesStop; ?>" />
					<input type="hidden" name="client" id="client" value="<?php echo $this->client; ?>">
					<input type="hidden" name="report_id" id="report_id" value="<?php echo $reportId; ?>">
					<input type="hidden" name="contentId" id="contentId" value="<?php echo $contentId[0]; ?>">
					<input type='hidden' id="courseUrl" name="courseUrl" value = "<?php echo $this->items[0]['url']?>" >
					<input type="hidden" id='todourl'name="" value="<?php echo $actualUrl;?>">

		  	
					<?php echo HTMLHelper::_('form.token'); ?>
				<!--report-top-bar row-fluid-->
			</form>
		</div>
		<!--j-main-container-->
		</div>
	<!-- COM_TJLMS_WRAPPER_DIV -->
</div>
<!-- reports-container -->
<script>
jQuery(document).ready(function()
{
	/* It restrict the user for manual input in datepicker field */
	jQuery(document).delegate('.dash-calendar', 'focusin', function(event) {
		event.preventDefault();
		jQuery(this).parent().siblings(':eq(0)').show();
	});

	jQuery(document).delegate('.dash-calendar', 'keydown contextmenu', function() {
			return false;
	});

	if(!jQuery("#tj-addtodo").length)
	{ 
		jQuery('#filters_allUser').hide();
		jQuery('.fill_all_user').hide();
	}

});
	
</script>
