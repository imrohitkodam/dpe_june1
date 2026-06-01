<?php
/**
 * @version     1.0.0
 * @package     com_tmt
 * @copyright   Copyright (C) 2013. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @author      Techjoomla <contact@techjoomla.com> - http://techjoomla.com
 */
// no direct access
defined('_JEXEC') or die;

JHtml::_('behavior.keepalive');
JHtml::_('behavior.tooltip');
JHtml::_('behavior.formvalidation');

//Load admin language file
$lang = JFactory::getLanguage();
$lang->load('com_tmt', JPATH_ADMINISTRATOR);
$document=JFactory::getDocument();
$document->addStylesheet(JUri::root().'components/com_tmt/assets/css/tmt.css');
$app = JFactory::getApplication();
$test_id = $app->input->get('test_id');
$candi_id = $app->input->get('candi_id');
$tmtTestsHelper=new tmtTestsHelper();
$test_name=$tmtTestsHelper->getDisplayTestnm($test_id);
$candidate_name=$tmtTestsHelper->getDisplayCandinm($candi_id);

//print_r($this->item);
?>
<script>
    function enable(i)
    {
		jQuery("#candi_marks"+i).removeAttr("readonly");
		var que_marks = jQuery('#que_marks'+i).val();
		jQuery('#candi_marks'+i).attr("value", que_marks);
	}
	function disable(i)
	{
		jQuery('#candi_marks'+i).attr("value", "0");
		jQuery('#candi_marks'+i).attr("readonly", "readonly");
	}
	function validmarks(i)
	{
		var que_marks = jQuery('#que_marks'+i).val();
		var can_marks = jQuery('#candi_marks'+i).val();
		if(que_marks < can_marks)
		{
			alert('Please enter less than or equal to marks for allocated marks. ');
			jQuery('#candi_marks'+i).val("");
		}
		/*if(can_marks != 0 || can_marks != '')
		{
			alert('Please enter marks for allocated marks. ');
			jQuery('#candi_marks'+i).val("");
		}*/
	}
</script>
<script type="text/javascript">
Joomla.submitbutton = function(task)
{
	if(task=='singlepaper.save')
	{
		jQuery('#tmt_singlepaper .btn').prop('disabled', true);
		Joomla.submitform(task);
	}
}

</script>
<style>
fieldset {
	background-color: #F5F5F5;
	border-top: 1px solid #E5E5E5;
	padding: 17px 20px 18px;
	border-color: #DDDDDD #DDDDDD #DDDDDD -moz-use-text-color;
	border-style: solid solid solid none;
	border-width: 1px 1px 1px 0;
}
</style>
<div id="tmt_singlepaper" class="row-fluid"><!--1-->
	<div class="span12"><!--2-->
		<h2 class="componentheading"><?php echo JText::_('COM_TMT_SINGLEPAPER');?></h2>
		<hr class="hr hr-condensed"/>
		<form method="post" action=""  name="adminForm" id="adminForm" class="form-validate">
					<div class="row-fluid"><!--5-->
						<div class="span12"><!--6-->
							<h3><?php echo JText::_('COM_TMT_SINGLEPAPER_TEST_NAME'); ?><?php echo $test_name; ?></h3>
						</div><!--6-->
					</div><!--5-->

					<div class="row-fluid"><!--7-->
						<div class="span12"><!--8-->
							<h3><?php echo JText::_('COM_TMT_SINGLEPAPER_CANDIDATE_NAME'); ?><?php echo $candidate_name; ?></h3>
						</div><!--8-->
					</div><!--7-->

					<div class="row-fluid"><!--10-->
						<div class="span12"><!--11-->
							<h6><i class="icon-pencil"></i>&#160;<?php echo JText::_('COM_TMT_SINGLEPAPER_CANDIDATE_ANSWER'); ?></h6>
						</div><!--11-->
					</div>

					<div class="row-fluid"><!--10-->
						<div class="span12"><!--12-->
							<h6><i class="icon-ok"></i>&#160;<?php echo JText::_('COM_TMT_SINGLEPAPER_CANDIDATE_EXPECTED_ANWSER'); ?></h6>
						</div><!--12-->
					</div><!--10-->

			<hr class="hr hr-condensed"/>
			<div class="row-fluid"><!--13-->
				<div class="span12"><!--14--><?php
					//print_r($this->item->test_id);
					$n=count( $this->item );
					$k=1;
					for($i=0; $i < $n ; $i++)
					{
						$row=$this->item[$i];
						$well_style = "";
//						print_r($row);die;
						if($row->is_correct)
							$well_style = "alert alert-success";
						else
							$well_style = "alert alert-error";
						//print_r($row->is_correct);?>
						<div class="well <?php echo $well_style; ?>">
							<div class="control-group"><!--15-->
								<h6>
									<?php //echo $k.".  " . htmlentities($row->que_title);?>
									<?php echo $k.".  " . htmlspecialchars($row->que_title, ENT_QUOTES, "UTF-8");?>
									<input type="hidden" name="test_ans_id<?php echo $i;?>" id="test_ans_id<?php echo $i;?>" value="<?php echo $row->test_ans_id; ?>" >
									<input type="hidden" name="test_que<?php echo $i;?>" id="test_que<?php echo $i;?>" value="<?php echo $row->question_id; ?>" >
									<span class="pull-right"><?php echo $row->marks;?></span>
								</h6>
							</div><!--15-->
							<?php
							//echo"shiv".$row->question_id;
							?>

							<?php

							 $path =tmtTestsHelper::get_q_image($row->question_id); ?>
									<div class="com-tmt-img-width">
											<?php if($path):?>
												<img  width="100%" height="auto" src="<?php echo $path?>">
											<?php else:?>
											<?php endif;?>
									</div>
							<hr class="hr hr-condensed"/>
							<div class="control-group"><!--16-->
								<i class="icon-pencil"></i>&#160;
								<?php //echo htmlentities($row->candidate_ans); ?>
								<?php
								$candidate_ans = tmtQuestionsHelper::getAnswersTitle(json_decode($row->candidate_ans));
								echo htmlspecialchars(implode(",",$candidate_ans), ENT_QUOTES, "UTF-8"); ?>
							</div><!--16-->
							<div class="control-group"><!--17-->
								<i class="icon-ok"></i>&#160;
								<?php //echo htmlentities($row->answer); ?>
								<?php echo htmlspecialchars($row->answer, ENT_QUOTES, "UTF-8"); ?>
							</div><!--17-->
							<hr class="hr hr-condensed"/>

						</div>
						<!--<div class="control-group">&nbsp;</div>21 - commented by Himangi--><?php
						$k++;
					}?><input type="hidden" id="count" name="count" value=<?php echo $i;?>>

				</div><!--span12--><!--14-->
			</div><!--row-fluid--><!--13-->
			<input type="hidden" name="option" value="com_tmt" />
			<input type="hidden" name="controller" value="" />
			<input type="hidden" name="task" value="singlepaper.save" />
			<?php echo JHTML::_( 'form.token' ); ?>
		</form>
	</div><!--span12--><!--2-->
</div><!--row-fluid--><!--1-->
