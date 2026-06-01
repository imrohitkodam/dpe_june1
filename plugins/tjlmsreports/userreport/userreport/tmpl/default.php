<?php
/**
 * @package    Shika
 * @author     TechJoomla | <extensions@techjoomla.com>
 * @copyright  Copyright (C) 2005 - 2014. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * Shika is free software. This version may have been modified pursuant
 * to the GNU General Public License, and as distributed it includes or
 * is derivative of works licensed under the GNU General Public License or
 * other free or open source software licenses.
 */
// No direct access

defined('_JEXEC') or die('Restricted access');
jimport( 'joomla.application.application' );
$document = JFactory::getDocument();
$document->addScript(JURI::base(true).'/components/com_tjlms/assets/js/tjlms_admin.js');
$mainframe = JFactory::getApplication('admin');
?>

<div class="user-report">
	<div id="tjlms-reports-div-container">
		<table id="report-table" class="table table-striped left_table">
			<thead>
				<tr>
					<?php
							foreach ($colToshow as $eachColumn)
							{
								$icon = '';
								$class = '';

								$sortCol = $mainframe->getUserState( "com_tjlms.userreport_table_sortCol", '' );
								$sortOrder = $mainframe->getUserState( "com_tjlms.userreport_table_sortOrder", '' );

								if ($sortCol == $eachColumn && !empty($sortCol) && !empty($sortOrder) )
								{
									if ($sortOrder != 'desc')
									{
										$class = 'hearderSorted';
										$icon = '<span class="icon-arrow-up-3"></span>';
									}
									else
									{
										$class = '';
										$icon = '<i class="icon-arrow-down-3"></i>';
									}
								}
									?>
									<th class="th_<?php echo $eachColumn; ?> left <?php echo $class; ?>">
										<?php $calHeading = strtoupper($eachColumn); ?>
										<?php $calHeading = 'PLG_TJLMSREPORTS_USERREPORT_' . $calHeading; ?>

										<a href="#" onclick="sortColumns(this); return false;" class="hasTooltip" title="<?php echo JText::_('PLG_TJLMSREPORTS_USERREPORT_SORT'); ?>" data-value="<?php echo $eachColumn; ?>"><?php echo JText::_($calHeading); ?><?php echo $icon; ?></a>

										<?php
										if (in_array($eachColumn,$showSearchToCol))
										{
											if ($eachColumn == 'user_group')
											{
												// Initialize JavaScript field attributes.
												$attr = ' class="filter-input input-medium"';
												$attr .= 'onchange="getFilterdata()"';

												echo JHtml::_('access.usergroup', "search-filter-". $eachColumn, isset($filters[$eachColumn]) ? $filters[$eachColumn] : '', $attr, array('1'), "search-filter-". $eachColumn);

											}
											else
											{
											?>
												<input type="text" class="input input-mini filter-input" onKeyUp="getFilterdata(-1, event,'search'); return false;" id="search-filter-<?php echo $eachColumn; ?>" value="<?php echo isset($filters[$eachColumn]) ? $filters[$eachColumn] : ''; ?>" />
											<?php
											}
										}
										?>
									</th>
									<?php
							}

							?>

				</tr>
			</thead>
			<tbody>

				<?php
					if (empty($userReportData))
					{
						?>
							<tr>
								<td colspan='<?php echo count($colToshow); ?>'>
									<div class="alert alert-warning"><?php echo JText::_('JGLOBAL_NO_MATCHING_RESULTS'); ?></div>
								</td>
							</tr>
						<?php
						return;
					}

					foreach ($userReportData as $key => $value)
					{
						?>
							<tr >
								<?php foreach ($value as $index => $data): ?>
									<td class="th_<?php echo $index; ?>">
										<?php echo $data;	?>
									</td>
								<?php endforeach; ?>
							</tr>
						<?php
					}
				?>
			</tbody>
		</table>
	</div>
</div>
