<?php

/**
 * @version    SVN: <svn_id>
 * @package    Tjlms
 * @author     Techjoomla <extensions@techjoomla.com>
 * @copyright  Copyright (c) 2009-2015 TechJoomla. All rights reserved.
 * @license    GNU General Public License version 2 or later.
 */

defined('_JEXEC') or die('Restricted access');


jimport('joomla.html.pane');
JHTML::_('behavior.modal', 'a.tjmodal');
?>

<div class="techjoomla-bootstrap">
	<div class="com_tjlms_content">
		<table class="table table-bordered tjlms-table" width="100%">
			<thead>
				<tr>
					<th class="border-top-blue greyish center"><?php echo JText::_( 'COM_TJLMS_COURSE_LESSON' ); ?></th>
					<th class="border-top-red greyish center"><?php echo JText::_( 'COM_TJLMS_ATTEMPTS' ); ?></th>
					<th class="border-top-green greyish center"><?php echo JText::_( 'COM_TJLMS_SCORE' ); ?></th>
					<th class="border-top-blue greyish center"><?php echo JText::_( 'COM_TJLMS_LESSON_STATUS' ); ?></th>
				</tr>
			</thead>
			<?php
			if(!empty($this->row))
			{
					$link =	'index.php?option=com_tjlms&view=reports&layout=attempts&tmpl=component';
					?>
					<?php

						foreach($this->row as $lesson_row)
						{
							$link .= '&lesson_id='.$lesson_row['id'];
							$detailed_attempts_report_link = $this->comtjlmsHelper->tjlmsRoute($link,false);
							?>
							<tr>
								<td width="25%"><?php echo $lesson_row['name']?></td>
								<td class=center>
									<?php if( $lesson_row['attempts']>0){?>
									<a href="<?php echo $detailed_attempts_report_link; ?>" rel="{size: {x: 700, y: 500}, handler:'iframe'}"  class="tjmodal">
										<?php echo $lesson_row['attempts']; ?>
									</a>
									<?php }
									else
									{
										echo $lesson_row['attempts'];
									}	?>
								</td>
								<td class=center><?php echo $lesson_row['score'];?></td>
								<td class=center><?php
								if($lesson_row['attempts']==0)
								{
									$lesson_row['lesson_status']='Not Attempted';
									echo $lesson_row['lesson_status'];
								}
								else
								{
									echo $lesson_row['lesson_status'];
								}?></td>
							</tr>
							<?php /*foreach($lesson_row['attempts'] as $attempt_row){ ?>
							<tr>
								<td> </td>
								<td class=center>
										<?php echo $attempt_row->attempt?>
								</td>
								<td class=center><?php echo $attempt_row->score;?></td>
								<td class=center><?php echo $attempt_row->lesson_status;?></td>

							</tr>
							<?php } */?>
							<?php
						}?>


				<?php
			}
			else
			{
				echo "<tr><td colspan='4' class='center'>".JText::_('COM_TJLMS_COURSE_REPORT_NOT_FOUND')."</td></tr>";
			}
			?>
		</table>
	</div>
</div>


