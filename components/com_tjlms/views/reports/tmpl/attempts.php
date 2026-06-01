<?php

/**
 * @package     TJLms
 * @subpackage  com_shika
 *
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (C) 2009 - 2021 Techjoomla. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access.
defined('_JEXEC') or die;

use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Language\Text;
use Joomla\CMS\HTML\HTMLHelper;

jimport('joomla.html.pane');
jimport( 'joomla.html.html' );
HTMLHelper::_('bootstrap.renderModal');

if (!$this->user_id)
{
?>
	<div class="alert alert-warning">
		<?php echo $msg = Text::_('COM_TJLMS_MESSAGE_LOGIN_FIRST');?>
	</div>
<?php
	return;
}

$attempt_details = '';

if ($this->attempts_report)
{
	$attempt_details = $this->attempts_report;
}

$tjlmsdbhelperObj	=	new tjlmsdbhelper();

?>

<div class="<?php echo COM_TJLMS_WRAPPER_DIV; ?>">
	<div class="container-fluid">
           <?php

				if(!empty($attempt_details))
				{
						?>
						<table class="table table-bordered table-striped table-condensed">
								<thead>
									<tr>
										<th width="20%" class="center border-top-blue greyish"><?php echo Text::_( 'COM_TJLMS_AT_NO' ); ?></th>
										<th class="center border-top-red greyish"><?php echo Text::_( 'COM_TJLMS_START_DATE' ); ?></th>
										<th class="center border-top-green greyish"><?php echo Text::_( 'COM_TJLMS_LAST_ACCESS' ); ?></th>
									<?php if( $this->format == 'scorm' || $this->format == 'quiz' || $this->format == 'exercise'){ ?>
										<th width="20%" class="center border-top-red greyish"><?php echo Text::_( 'COM_TJLMS_SCORE' ); ?></th>
									<?php } ?>
										<th class="center border-top-red greyish"><?php echo Text::_( 'COM_TJLMS_CERTIFICATES_TIME_SPENT' ); ?></th>
										<th width="20%" class="center border-top-blue greyish"><?php echo Text::_( 'COM_TJLMS_LESSON_STATUS' ); ?></th>
									</tr>
								</thead>
								<tbody>

						<?php
							$k=1;

							foreach($attempt_details as $attempt)
							{
								if ($this->quizType == 'set')
								{
									$this->test_id = $this->tjlmsdbhelperObj->get_records('test_id', 'tmt_tests_attendees',
									array('invite_id' => (int) $attempt->id ), '', 'loadResult');
								}
										?>
											<tr>
												<td class=center><?php echo $attempt->attempt?></td>
												<td class=center>
													<?php

													$comtjlmsHelper = new comtjlmsHelper;

														echo $date = HTMLHelper::date($attempt->timestart , 'F - j - Y g:i a', true);
													?>
												</td>
												<td class=center>
													<?php
														echo $date = HTMLHelper::date($attempt->last_accessed_on , 'F - j - Y g:i a', true);
													?>
												</td>
			<?php

				if( $this->format == 'scorm' || $this->format == 'quiz' || $this->format == 'exercise')
				{ ?>
					<td class=center>
					<?php if (($this->showAnswerSheet == 1) &&  ($this->format == 'quiz' || $this->format == 'exercise')  && ($attempt->lesson_status != 'started' &&  $attempt->lesson_status != 'incomplete'))
					{	?>
						<?php $link = Route::_(Uri::base() . 'index.php?option=com_tmt&view=answersheet&fromAttempt=1&tmpl=component&course_id=' . $this->course_id . '&id='.$this->test_id.'&ltId='.$attempt->id.'&candid_id='.$attempt->user_id,false); ?>
							
						<a class="user-score" onclick="openAnswersheet('<?php echo $link; ?>');" style="cursor: pointer;">
							<?php echo ($attempt->lesson_status == 'AP') ? Text::_("COM_TJLMS_QUIZ_WAIT_FOR_ASSESSMENT_PENDING") : $attempt->score;?>
						</a>
			<?php 	}
					else
					{
						echo ($attempt->lesson_status == 'AP') ? Text::_("COM_TJLMS_QUIZ_WAIT_FOR_ASSESSMENT_PENDING") : $attempt->score;

					} ?>
					</td>
	<?php		}	?>

											<td class=center><?php echo $attempt->time_spent; ?></td>

											<td class=center><?php echo ucfirst($attempt->lesson_status);?></td>
										</tr>

										<?php
								$k++;
							}?>
								</tbody>
						</table>
					<?php
				}
				else
				{
				?>
					<div class="alert alert-warning">
						<?php echo Text::_("COM_TJLMS_NO_DATA");?>
					</div>
				<?php
				}
				?>
	</div>
</div>
<script>
var oldWindow;
function openAnswersheet(rlink)
{
	closeOldWindow();

	if (jQuery(window).width() < 768) {
		var wwidth = jQuery(window).width();
		var wheight = jQuery(window).height();
		SqueezeBox.open(rlink, {
			handler: 'iframe',
			size: {x: wwidth, y: wheight},
			/*iframePreload:true,*/
			sizeLoading: { x: wwidth, y: wheight },
		});
	}
	else
	{
		oldWindow = window.open(rlink, 'mywin', 'left=20,top=20,width=1200,height=800,toolbar=1,resizable=0'); 
	}
}

function closeOldWindow(){
	if(typeof oldWindow != 'undefined'){
		oldWindow.close();
	}
}
</script>
