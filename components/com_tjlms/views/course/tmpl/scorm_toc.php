<?php
/**
 * @version    SVN: <svn_id>
 * @package    Tjlms
 * @author     Techjoomla <extensions@techjoomla.com>
 * @copyright  Copyright (c) 2009-2015 TechJoomla. All rights reserved.
 * @license    GNU General Public License version 2 or later.
 */

// No direct access.
defined('_JEXEC') or die;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
?>
<div class="tjlms_scorm_toc" id="tjlms_scorm_toc">
	<table class="table no-margin no-padding tjlms-table tjlms_toc_panel">
	<?php
		$i = 0;
		foreach($toc_tree['scoes'] as $org_item)
		{
			foreach($org_item->children as $parent_item)
			{
				?>
						<tr data-depth="0" class="<?php echo  ($i == 0) ? 'tjexpanded' : 'tjcollapsed' ?> "  onclick="toggleexpand(this);" parentfor=<?php echo $parent_item->id?>>
							<td class='tjlms_node_header noleft-border' colspan=4 >
									<?php if($parent_item->launch !='') { ?>

										<a class="sco-obj" style="cursor:pointer;" title="<?php echo $parent_item->url;?>"><?php echo $parent_item->title; ?></a>

									<?php }
									else{ ?>
											<i class=' <?php echo  ($i == 0) ? 'icon-folder-open' : 'icon-folder-close' ?>'></i><?php echo $parent_item->title;?>
								<?php } ?>
							</td>
						</tr>
			<?php
				if(isset($parent_item->children))
				{
					$launch_full_screen = 1;
					if($this->tjlmsparams->get('launch_full_screen') != 1)
						$launch_full_screen=0;
					?>
					<?php
						foreach($parent_item->children as $item)
						{


							//$onclick = "open_scoforattempt('" . $item->id ."','". $m_lesson->id ."','".$m_lesson->attempts_done."','".$launch_full_screen."')";

							$disabled = $hovertitle = '';
							$active_btn_class = 'btn-small btn-primary';
							//$onclick = '';
							/*$onclick=	"open_scoforattempt('" . $item->id ."','".$m_lesson->id ."','" . $item->attemptsdonebyuser ."','" . $m_lesson->no_of_attempts. "','".$item->statusDetails->lastAttemptStatus ."','".$this->launch_lesson_full_screen ."');";*/

							$sco_url = $this->tjlmshelperObj->tjlmsRoute("index.php?option=com_tjlms&view=lesson&lesson_id=" . $m_lesson->id . "&lessonscreen=1&sco_id=" . $item->id . "&tmpl=component", false);

							$onclick=	"open_lessonforattempt('" . addslashes(htmlspecialchars($sco_url)) . "','" . $this->launch_lesson_full_screen ."');";
							$lock_icon	=	'';

							if(strtotime($m_lesson->start_date) <= strtotime(Factory::getDate()->toSql()) || $m_lesson->start_date == '0000-00-00 00:00:00')
							{
								if ($m_lesson->free_lesson == 1)
								{
									$hovertitle = '';
									$active_btn_class = 'btn-small btn-primary';
								}
								else
								{
									if($m_lesson->eligible_toaceess != 1)
									{
										$active_btn_class = 'btn-small btn-disabled';
										$onclick="";
										$lock_icon="<i class='icon-lock'></i>";

										$m_lesson->eligibilty_lessons = implode(',', $m_lesson->eligibilty_lessons);
										if($m_lesson->format == "tmtQuiz")
										{
											$type=Text::_("COM_TJLMS_TYPE_QUIZ");
										}
										else
										{
											$type=Text::_("COM_TJLMS_TYPE_LESSON");
										}

										$hovertitle	=	' rel="popover" ' . 'data-original-content="' . Text::sprintf( 'COM_TJLMS_NOT_COMPLETED_PREREQUISITES_TOOLTIP', $type ,$m_lesson->eligibilty_lessons) . '"';
									}

									if( $m_lesson->no_of_attempts > 0 && ( $m_lesson->attemptsdonebyuser >= $m_lesson->no_of_attempts && $m_lesson->completed_last_attempt	== '1' ) )
									{
										$active_btn_class = 'btn-small btn-disabled';

										$onclick="";

										$hovertitle	=	" rel='popover' data-original-content='" . Text::_("COM_TJLMS_ATTEMPTS_EXHAUSTED_TOOLTIP") . " ' ";

									}

									if($m_lesson->format == 'tmtQuiz' && !$this->oluser_id)
									{
										$active_btn_class = 'btn-small btn-disabled';
										$onclick = "";
										$hovertitle	=	" rel='popover' data-original-content='" . Text::_("COM_TJLMS_GUEST_NOATTEMPT_QUIZ") . " ' ";
									}

									if ($this->checkifuserenroled != 1  && $this->usercanAccess == 0)
									{
										$onclick = "";
										$active_btn_class = 'btn-small btn-disabled';
										$hovertitle	=	" rel='popover' data-original-content='" . Text::_("COM_TJLMS_LOGIN_TO_ACCESS") . " ' ";
									}
								}

							}

							if (strtotime($m_lesson->start_date) > strtotime(Factory::getDate()->toSql()) && $m_lesson->start_date != '0000-00-00 00:00:00')
							{

								$temp = "COM_TJLMS_NOT_PUBLISHED_YET_".strtoupper($m_lesson->format);
								$hovertitle	=	Text::_($temp);

								$hovertitle	=	" rel='popover' data-original-content='" . $hovertitle . " ' ";
								$active_btn_class = 'btn-small btn-disabled';

								$lock_icon="<i class='icon-lock'></i>";
								$onclick="";
							}
							elseif (strtotime($m_lesson->end_date) < strtotime(Factory::getDate()->toSql()) && $m_lesson->end_date != '0000-00-00 00:00:00')
							{
								$temp = "COM_TJLMS_EXPIRED_".strtoupper($m_lesson->format);
								$hovertitle	=	Text::_($temp);

								$hovertitle	=	" rel='popover' data-original-content='" . $hovertitle . " ' ";
								$active_btn_class = 'btn-small btn-disabled';

								$lock_icon="<i class='icon-lock'></i>";
								$onclick="";
							}


						?>
							<tr data-depth="1" childof="<?php echo $parent_item->id?>">
								<td class="noleft-border">
									<div class="tjlms_lesson-title">
										<div class="lesson-progress-container ng-scope">
											<span title="<?php echo $item->statusicon?>" class="lesson-progress-mask">

											</span>
										</div>
										<a class="sco-obj" title="<?php echo $item->url;?>"><?php echo $item->title; ?></a>

									</div>
								</td>
								<td width="35%">
										<?php if (!empty($item->statusdetails))
										{
											//print_r($item->statusdetails);
										?>
											<div><?php echo Text::_("COM_TJLMS_USER_STARTED_LESSON_ON") . ': ' . Factory::getDate($item->statusdetails->started_on)->Format('jS F Y'); ?></div>
											<div><?php echo Text::_("COM_TJLMS_USER_LAST_ACCESSED_LESSON_ON") . ': '.Factory::getDate($item->statusdetails->last_accessed_on)->Format('jS F Y');?></div>
											<div><?php if (Factory::getDate($item->statusdetails->total_time_spent)->format('i') == '00')
											{
												$min = 0;
											}
											else
											{
												$min = Factory::getDate($item->statusdetails->total_time_spent)->format('i');
											}

											if (Factory::getDate($item->statusdetails->total_time_spent)->format('s') == '00')
											{
												$sec = 0;
											}
											else
											{
												$sec = Factory::getDate($item->statusdetails->total_time_spent)->format('s');
											}

											if (Factory::getDate($item->statusdetails->total_time_spent)->format('H') != '00')
											{
												$timetaken = Text::_("COM_TJLMS_USER_TOTAL_TIME_ON_LESSON") . ': ' . Factory::getDate($item->statusdetails->total_time_spent)->format('H');
												$timetaken .= ' hours ' . $min . ' min ' . $sec . ' secs' . "\n";
											}
											else
											{
												$timetaken = Text::_("COM_TJLMS_USER_TOTAL_TIME_ON_LESSON") . ': ' . $min . ' min ' . $sec . ' secs' . "\n";
											}
											echo $timetaken;
											?>
											</div>
											<div>
											<?php
											/*$attempts_done_by_available = $item->attemptsdonebyuser;

											if ($m_lesson->no_of_attempts > 0)
											{
												$attempts_done_by_available .= " / " . $m_lesson->no_of_attempts;
											}
											else
											{
												$attempts_done_by_available .= " / " . Text::_("COM_TJLMS_LABEL_UNLIMITED_ATTEMPTS");
											}

											if ($m_lesson->attemptsdonebyuser > 0)
											{
												$report_link .= '&lesson_id=' . $m_lesson->id;

												if ($m_lesson->no_of_attempts == 0)
												{
													$m_lesson->no_of_attempts = Text::_('COM_TJLMS_LABEL_UNLIMITED_ATTEMPTS');
												}

												$attemptool = Text::sprintf("COM_TJLMS_ATTEMPTS_DONE_TOOLTIP", $m_lesson->completed_atttempts, $m_lesson->no_of_attempts);



												$popover_con = "<div>Completed attempts:".$m_lesson->completed_atttempts."</div><div>Total attempt:".$m_lesson->attemptsdonebyuser."</div>";


												$detailed_attempts_report_link = "<a class='modal attempt_report' href='" . $this->tjlmshelperObj->tjlmsRoute($report_link, false)."' bpl='popover' data-placement='right' data-original-content='". $popover_con."'>". $attempts_done_by_available ." </a>";

												echo Text::_("ATTEMPTS") . ': ' . $statusattpt = $detailed_attempts_report_link;
											}
											else
											{*/
												echo $statusattpt = $attempts_done_by_available;
											/*}*/
											?>
											</div>

											<div>
												<?php echo Text::_("COM_TJLMS_LESSON_STATUS") . ': ' . ucfirst($item->statusdetails->status); ?>
											</div>
										<?php
										}
										else
										{
											echo Text::_("COM_TJLMS_LESSON_NOT_ACCESSED");
										}
										?>
									<?php //echo $item->statusDetails;?>
								</td>
								<td width="20%">
									<?php if($item->launch !='') { ?>
										<button title="<?php echo $hovertitle; ?>" class="btn <?php echo $active_btn_class; ?>" <?php echo $disabled;?> onclick="<?php echo $onclick?>"><span class="lesson_attempt_action">Launch</span></button>
									<?php } ?>
								</td>
							</tr>
							<?php
							}
							?>
						<?php
				}
				?>

				<?php
				$i++;
			}
	}

				?>
		</table>
</div>
