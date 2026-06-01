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

defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\HTML\HTMLHelper;

jimport('joomla.html.pane');

HTMLHelper::_('stylesheet', 'components/com_tjlms/bootstrap/css/bootstrap.min.css');
HTMLHelper::_('script', 'components/com_tjlms/bootstrap/js/bootstrap.js');

	$attempt_details=$this->attempt_details;
?>

<div class="<?php echo COM_TJLMS_WRAPPER_DIV; ?>">
           <?php
								//print_r($courseEnrolledUsers);die;
								if($attempt_details)
								{
										?>
										<table class="table table-striped table-bordered">
												<thead>
													<tr>
														<th width="1%"><?php echo Text::_( 'AT_NO' ); ?></th>
														<th width="10%"><?php echo Text::_( 'START_DATE' ); ?></th>
														<th width="10%"><?php echo Text::_( 'LAST_ACCESS' ); ?></th>

														<th width="10%"><?php echo Text::_( 'SCORE' ); ?></th>
														<th class="nowrap" width="1%"><?php echo Text::_( 'lesson_status' ); ?></th>
													</tr>
												</thead>
												<tbody>

										<?php
											$k=0;
											foreach($attempt_details as $attempt)
											{
														//$e_user=JFactory::getUser($r->id);
														?>
															<tr>
																<td class=center><?php echo $attempt->attempt?></td>
																<td class=center>
																	<?php
																		if(JVERSION >= '1.6.0')
																			 echo JHTML::Date($attempt->timestart, "F -j - Y H:i:s");
 																	?>
																</td>
																<td class=center>
																		<?php
																			 echo JHTML::Date($attempt->last_accessed_on, "F -j - Y H:i:s");
 																	?>
																</td>
																<td class=center><?php echo $attempt->score;?></td>
																<td class=center><?php echo $attempt->lesson_status;?></td>

															</tr>

														<?php
												$k++;
											}?>
												</tbody>
										</table>
									<?php
								}
								else
									echo "No Data Found";
						?>
          </div>


	</div>


