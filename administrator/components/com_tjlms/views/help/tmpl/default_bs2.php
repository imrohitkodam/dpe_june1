<?php
/**
 * @version     1.0.0
 * @package     com_tjlms
 * @copyright   Copyright (C) 2014. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @author      TechJoomla <extensions@techjoomla.com> - www.techjoomla.com
 */

// no direct access
defined('_JEXEC') or die;

JHTML::_('behavior.tooltip');
JHTML::_('behavior.framework');
JHTML::_('behavior.modal');
// Load jQuery.
if (JVERSION >= '3.0')
{
	JHtml::_('jquery.framework');
}

//$helperobj = new comquick2cartHelper;

if (JVERSION < '3.0')
{
	$strapperClass = 'techjoomla-bootstrap';
}
else
{
	$strapperClass = '';
}


?>
<div class="<?php echo COM_TJLMS_WRAPPER_DIV ?>">

		<?php
			ob_start();
			include JPATH_BASE . '/components/com_tjlms/layouts/header.sidebar.php';
			$layoutOutput = ob_get_contents();
			ob_end_clean();
			echo $layoutOutput;
		?> <!--// JHtmlsidebar for menu ends-->

		<div class="clearfix">&nbsp;</div>

		<!--HEADER LEARNING STORE DASHBOARD BACKEND-->
		<div class="page-header">
			<h3><?php echo JText::_('COM_TJLMS_BACKEND_HELP_HEADING'); ?></h3>
		</div>



		<div class="row-fluid">
			<?php if (JVERSION >= '3.0') :?>
					<?php
						echo JHtml::_('bootstrap.startTabSet', 'myTab', array('active' => 'aboutproduct'));

							/*TAB 1-- ABOUT COMPONENT*/
							echo JHtml::_('bootstrap.addTab', 'myTab', 'aboutproduct', JText::_('COM_TJLMS_ABOUT_TECHJOOMLA', true));
							?>
								<div class="row-fluid">
									<div class="span8">
										<div class="row-fluid">
											<div class="span2">
												<?php
												$imagePath = 'media/com_tjlms/images/default/';
												$imagePath = JRoute::_(JUri::root() . $imagePath . 'integrations-lms.png', false);
												?>
												<img class="img-polaroid tjlmslogo" src="<?php echo $imagePath;?>" >
											</div>
											<div class="span10">
												<h1><?php echo JText::_('COM_TJLMS'); ?></h1>
											</div>
										</div>
											<div>
												<h3><?php echo JText::_('COM_TJLMS_LEARNING_MGMT_SYSTEM'); ?></h3>
											</div>
											<div class="clearfix">&nbsp;</div>
											<hr class="hr hr-condensed"/>

											<div class="">
												<?php echo JText::_('COM_TJLMS_ABOUT_LMS'); ?>
											</div>

									</div>
									<div class="span4">
										<!--<div class="row-fluid well">
											<a href="" target="_blank">
												<?php
												if(JVERSION >= '3.0')
													echo '<i class="icon-quote"></i>';
												else
													echo '<i class="icon-bullhorn"></i>';
												?> <?php echo JText::_('COM_TJLMS_LEAVE_JED_FEEDBACK'); ?></a>
										</div>-->
										<div class="row-fluid">
											<div class="span12 ">
												<p class="pull-right">
													<span class="label label-info"><?php echo JText::_('COM_TJLMS_STAY_TUNNED'); ?></span>
												</p>
											</div>
										</div>
										<div class="row-striped">
											<div class="row-fluid">
												<div class="span4"><?php echo JText::_('COM_TJLMS_FACEBOOK'); ?></div>
												<div class="span8">
													<!-- facebook button code -->
													<div id="fb-root"></div>
													<script>(function(d, s, id) {
													  var js, fjs = d.getElementsByTagName(s)[0];
													  if (d.getElementById(id)) return;
													  js = d.createElement(s); js.id = id;
													  js.src = "//connect.facebook.net/en_US/all.js#xfbml=1";
													  fjs.parentNode.insertBefore(js, fjs);
													}(document, 'script', 'facebook-jssdk'));</script>
													<div class="fb-like" data-href="https://www.facebook.com/techjoomla" data-send="true" data-layout="button_count" data-width="250" data-show-faces="false" data-font="verdana"></div>
												</div>
											</div>

											<div class="row-fluid">
												<div class="span4"><?php echo JText::_('COM_TJLMS_TWITTER'); ?></div>
												<div class="span8">
													<!-- twitter button code -->
													<a href="https://twitter.com/techjoomla" class="twitter-follow-button" data-show-count="false">Follow @techjoomla</a>
													<script>!function(d,s,id){var js,fjs=d.getElementsByTagName(s)[0];if(!d.getElementById(id)){js=d.createElement(s);js.id=id;js.src="//platform.twitter.com/widgets.js";fjs.parentNode.insertBefore(js,fjs);}}(document,"script","twitter-wjs");</script>
												</div>
											</div>

											<div class="row-fluid">
												<div class="span4"><?php echo JText::_('COM_TJLMS_GPLUS'); ?></div>
												<div class="span8">
													<!-- Place this tag where you want the +1 button to render. -->
													<div class="g-plusone" data-annotation="inline" data-width="300" data-href="https://plus.google.com/102908017252609853905"></div>
													<!-- Place this tag after the last +1 button tag. -->
													<script type="text/javascript">
													(function() {
													var po = document.createElement('script'); po.type = 'text/javascript'; po.async = true;
													po.src = 'https://apis.google.com/js/plusone.js';
													var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(po, s);
													})();
													</script>
												</div>
											</div>
										</div>
										<div class="row-fluid">
											<div class="span12 center">
												<?php
												$logo_path='<img src="'.JURI::root().'media/com_tjlms/images/default/techjoomla.png" alt="TechJoomla" class="jbolo_vertical_align_top"/>';
												?>
												<a href='http://techjoomla.com/' target='_blank'>
													<?php echo $logo_path;?>
												</a>
											</div>
										</div>
									</div>
								</div>
							<?php
							echo JHtml::_('bootstrap.endTab');/*TAB 1-- ABOUT COMPONENT ENDS*/

							/*TAB 2-- DOCUMENT LINKS AND SUPPORT*/
							echo JHtml::_('bootstrap.addTab', 'myTab', 'docs-and-support', JText::_('COM_TJLMS_DOCS_AND_SUPPORT', true));
							?>
									<!--DOCUMENTATION LINKS-->
									<div class="row-fluid">
										<div class="span12">
											<h4><?php echo JText::_('COM_TJLMS_DOCS_HEADER');?></h4>
										</div>
										<div class="span12">
											<a href="//techjoomla.com/table/extension-documentation/shika-lms-for-joomla-documentation/" target="_blank"><i class="icon-file"></i> <?php echo JText::_('COM_TJLMS_DOCS');?></a>
										</div>
									</div>
									<!--FAQ's-->
									<div class="row-fluid">
										<div class="span12">
											<h4><?php echo JText::_('COM_TJLMS_FAQS_HEADER');?></h4>
										</div>
										<div class="span12">
											<a href="//techjoomla.com/documentation-for-shika-lms-for-joomla/shika-faqs" target="_blank">
												<?php
												if(JVERSION >= '3.0')
													echo '<i class="icon-help"></i>';
												else
													echo '<i class="icon-question-sign"></i>';
												?>
												<?php echo JText::_('COM_TJLMS_FAQS');?>
											</a>
										</div>
									</div>

									<!--SUPPORT LINKS-->
									<div class="row-fluid">
										<div class="span12">
											<h4><?php echo JText::_('COM_TJLMS_SUPPORT_HEADER');?></h4>
										</div>
										<div class="span12">
											<a href="//techjoomla.com/forums/categories/shika.html" target="_blank">
												<?php
													if(JVERSION >= '3.0')
														echo '<i class="icon-help"></i>';
													else
														echo '<i class="icon-question-sign"></i>';

													echo JText::_('COM_TJLMS_SUPPORT');?>
											</a>
										</div>
									</div>
							<?php
							echo JHtml::_('bootstrap.endTab');/*TAB 1-- ABOUT COMPONENT ENDS*/



						echo JHtml::_('bootstrap.endTabSet');/*BOOTSTRAP TABSET ENDS*/
					endif;
			?>
		</div><!--row-fluid ends-->
	</div><!--row-fluid ends-->
</div><!--BOOTSTRAP DIV-->
