<?php
/**    
 * SEO Glossary Glossary view Edit layout
 *
 * We developed this code with our hearts and passion.
 * We hope you found it useful, easy to understand and change.
 * Otherwise, please feel free to contact us at contact@joomunited.com
 *
 * @package 	SEO Glossary
 * @copyright 	Copyright (C) 2012 JoomUnited (http://www.joomunited.com). All rights reserved.
 * @license 	GNU General Public License version 2 or later; http://www.gnu.org/licenses/gpl-2.0.html
 */

// no direct access
defined('_JEXEC') or die;
jimport('joomla.filesystem.folder');

JHtml::_('behavior.formvalidator');
JHtml::_('jquery.framework');

$extlink=$this->form->getValue('extlink');
$lang = JFactory::getLanguage()->getTag();
$document = JFactory::getDocument();
$document->addStyleSheet(JURI::root() . '/components/com_seoglossary/assets/css/materialize.css');
$currentlang=explode("-",$lang);
$clang=$currentlang[0];
require_once  realpath(JPATH_ADMINISTRATOR.'/components/com_menus/helpers/menus.php');
$document->addStyleSheet(JURI::base().'components/com_seoglossary/assets/css/bootstrap-tagsinput.css');
$document->addScript(JURI::base().'components/com_seoglossary/assets/js/bootstrap-tagsinput.js');
?>
<script type="text/javascript">
	var copydata="";
	<?php if (!version_compare(JVERSION, '4.0', 'ge')){ ?>
	Joomla.submitbutton = function(task)
	{
		if (task == 'glossary.cancel' || document.formvalidator.isValid(document.getElementById('glossary-form'))) {
			Joomla.submitform(task, document.getElementById('glossary-form'));
		}
		else {
			alert('<?php echo $this->escape(JText::_('JGLOBAL_VALIDATION_FORM_FAILED'));?>');
		}
	}
	<?php } ?>
	function changeLink() {
                                var x = document.getElementById("formtype").value;
								var final_value=document.getElementById("jform_contactlink").value
								final_value=final_value.replace("mailto:","");
								
								if (x=="mail") {
                                document.getElementById("jform_contactlink").value="mailto:"+final_value;
								}
								else if (x=="link") {
									if (!final_value.match(/^[a-zA-Z]+:\/\//))
									{
									    final_value = 'http://' + final_value;
									}
                                     document.getElementById("jform_contactlink").value=final_value;
                                }
								else if (x=="menu") {
								    document.getElementById("cmsmenu_chzn").style.display="block";
									
                                }
                            }
							function changeextLink() {
                                var x = document.getElementById("formexttype").value;
								var final_value=document.getElementById("jform_exturl").value
								final_value=final_value.replace("mailto:","");
								if (x=="mail") {
                                document.getElementById("jform_exturl").value="mailto:"+final_value;
								}
								else if (x=="link") {
									if (!final_value.match(/^[a-zA-Z]+:\/\//))
									{
									    final_value = 'http://' + final_value;
									}
                                     document.getElementById("jform_exturl").value=final_value;
                                }
								else if (x=="menu") {
								    document.getElementById("cmsmenuext_chzn").style.display="block";
									document.getElementById("cmsk2ext_chzn").style.display="none";									
                                }
								else if (x=="k2") {
									document.getElementById("cmsmenuext_chzn").style.display="none";
									document.getElementById("cmsk2ext_chzn").style.display="block";	
                                }
                            }
							function changek2Menu() {
								document.getElementById("jform_exturl").value="";
								 var x = document.getElementById("cmsk2ext").value;
                                document.getElementById("jform_exturl").value="k2:"+x;
                            }
							function changeMenu() {
								 var x = document.getElementById("cmsmenu").value;
                                document.getElementById("jform_contactlink").value="menu:"+x;
                            }
							function changeextMenu() {
								 document.getElementById("jform_exturl").value="";
								 var x = document.getElementById("cmsmenuext").value;
                                document.getElementById("jform_exturl").value="menu:"+x;
                            }
							function updateData(data) {
                                var value=(jQuery(data).val());
								
								if (value==0) {
                                    document.getElementById("extlinkdata").style.display="none";
                                }else
								{
																
									 document.getElementById("extlinkdata").style.display="block";							
								}
                            }
							function showSearchData() {
                  

        jQuery('#target').html('Finding..');
		var tterm=jQuery("#jform_tterm").val();
          jQuery.getJSON("https://<?php echo $currentlang[0];?>.wikipedia.org/w/api.php?format=json&action=query&prop=extracts&exintro&explaintext&origin=*&redirects=1&titles=" + tterm, function(data){
				var obj = data.query.pages;
				var ob = Object.keys(obj)[0];
				if(obj[ob]["extract"])
				{
				jQuery('#target').html(obj[ob]["extract"]);
				copydata=obj[ob]["extract"];
				}
				else
				{
				jQuery('#target').html("<?php echo "<p>".JTEXT::_('COM_SEOGLOSSARY_NORESULT')."</p>";?>");	
				}
			});
    }
	function copySearchData()
	{
		<?php if (version_compare(JVERSION, '4.0', 'ge')){ ?>
		Joomla.editors.instances['jform_tdefinition'].replaceSelection(copydata);
		<?php }else{?>
		jInsertEditorText(copydata, 'jform_tdefinition');
		<?php }?>
	}
</script>

<style type="text/css">
	#cmsmenu_chzn,#cmsmenuext_chzn,#cmsk2ext, #cmsk2ext_chzn
	{
		display:none;
	}
	#editor-xtd-buttons
	{
		width:100%;
	}
	#jform_tdefinition-lbl,#jform_tmore-lbl
	{
		font-weight:bold;
		font-size: 18px;
		margin-top:30px;
		text-transform: capitalize;
	}
	<?php if(!$extlink) {?>
	#extlinkdata
	{
		display:none;														
	}
	<?php } ?>
</style>
<?php
													
																$groups = array();
																$menus = array();
																$menuType =null;
																$published = array();
																$disable=array();
																$items = MenusHelper::getMenuLinks($menuType, 0, 0, $published);
																foreach ($items as $menu)
																	{
																		// Initialize the group.
																		$groups[$menu->menutype] = array();
																		// Build the options array.
																		foreach ($menu->links as $link)
																		{
																			$groups[$menu->menutype][] = JHtml::_('select.option', $link->value, $link->text, 'value', 'text', in_array($link->type, $disable));
																		}
																	}
																	foreach ($groups as $group => $links)
																		{
																		foreach ($links as $link)
																		{
																			$menus[] = $link;
																		}
																		}
																		$output = JHTML::_('select.genericlist', $menus, 'cmsmenu', 'class="inputbox" style="width:220px;" onChange="changeMenu()"', 'value', 'text', '');
																$output2 = JHTML::_('select.genericlist', $menus, 'cmsmenuext', 'class="inputbox" style="width:220px;" onChange="changeextMenu()"', 'value', 'text', '');
																 if ((JFolder::exists(JPATH_SITE . '/components/com_k2/'))) {
																if (JComponentHelper::isEnabled('com_k2', true))
																{
																	require_once(JPATH_ADMINISTRATOR.'/components/com_k2/elements/base.php');
																	$name="cmsk2ext";
																$fieldID = 'fieldID_'.md5($name);
																$fieldName="cmsk2ext";
																 $scope ="items";
																 $view="items";
																 $html = '
																 <script type="text/javascript">
																 jQuery( document ).ready(function() {
																 jQuery(".k2SingleSelect").bind("DOMSubtreeModified",function(){
																	document.getElementById("jform_exturl").value="";
								 var x = jQuery("input[name=\"cmsk2ext\"]").val();
								 if (typeof x != "undefined")
								 {
                                document.getElementById("jform_exturl").value="k2:"+x;
									}
																});
																});
																 </script>
	        <div class="k2SelectorButton k2SingleSelect" id="cmsk2ext_chzn" style="display:none">
				<a data-k2-modal="iframe" class="btn" title="'.JText::_('COM_SEOGLOSSARY_SELECT').'" href="index.php?option=com_k2&view='.$view.'&tmpl=component&context=modalselector&fid='.$fieldID.'&fname='.$fieldName.'">
		        	<i class="fa fa-file-text-o"></i> '.JText::_('COM_SEOGLOSSARY_SELECT').'
		        </a>
	        </div>
	        <div id="'.$fieldID.'" class="k2SingleSelect"></div>
	        ';

																$output2.=$html;
																}
															}		
																																															
													?>
<style type="text/css">
	.seoglossary label
	{
		max-width:150px;
	}
</style>
<div id="ju-form" class="main-card">
<form action="<?php echo JRoute::_('index.php?option=com_seoglossary&layout=edit&id='.(int) $this->item->id); ?>" method="post" name="adminForm" id="glossary-form" class="form-validate">
	<div>
		<fieldset>
			<div class="span12 row-fluid row form-horizontal-desktop">
				
			<legend><?php echo JText::_('COM_SEOGLOSSARY_LEGEND_GLOSSARY'); ?></legend>
           <div class="span8 col-lg-8">
			<?php echo $this->form->renderField('catid'); ?> 
			<?php /* 
			<p><?php echo $this->form->getLabel('id'); ?>
			<?php echo $this->form->getInput('id'); ?></p>
			*/ ?>

            
			<div class="control-group">
			<div class="control-label"><?php echo $this->form->getLabel('tterm'); ?></div>
			
			<div class="input-append input-group controls"><?php echo $this->form->getInput('tterm'); ?><button onclick="showSearchData()" type="button" class="btn"><?php echo Jtext::_('COM_SEOGLOSSARY_LBL_HINT')?></button><button onclick="copySearchData()" type="button" class="btn"><?php echo Jtext::_('COM_SEOGLOSSARY_LBL_COPYTEXT');?></button></div>
			<br><span id="target" class="span12 col-md-12" style="font-weight:bold;"></span></div>
			
			<?php echo $this->form->renderField('alias'); ?>
			
			<div class="control-group" >
                            <div class="control-label"><?php echo $this->form->getLabel('extlink'); ?></div>
                            <div class="controls">
                               <?php echo str_replace('<input','<input onchange="updateData(this)" ',$this->form->getInput('extlink')); ?>
</div>
                        </div>	
                        <div class="control-group" id="extlinkdata">
                            <div class="control-label"><?php echo $this->form->getLabel('exturl'); ?></div>
                            <div class="controls">
                                <?php echo $this->form->getInput('exturl'); ?><br/>
							<select size="1" class="inputbox chzn-done"  id="formexttype" onChange="changeextLink();">
														<option selected="selected" value="link">Link</option>
														<option value="menu">Menu</option>
														<option value="k2">k2</option>
														</select>
															<?php
																	echo  $output2;
															?>
														
							</div>
                        </div>	
			<?php echo str_replace('input ','input data-role="tagsinput" ',$this->form->renderField('tsynonyms')); ?>
						<?php echo $this->form->renderField('tdefinition'); ?>
						
						<?php echo $this->form->renderField('tmore'); ?>			
			</div>
			<div class="span3 col-lg-3">
				<?php echo $this->form->renderField('icon'); ?>
			<?php echo $this->form->renderField('hits'); ?>
			
			
				<?php echo $this->form->renderField('nofollow'); ?>
				<?php echo $this->form->renderField('target'); ?>
				<?php echo $this->form->renderField('metakey'); ?>
				<?php echo $this->form->renderField('metadesc'); ?>
				<?php echo $this->form->renderField('tags'); ?>
				<?php echo $this->form->renderField('created_by'); ?>
				<?php echo $this->form->renderField('tname'); ?>
				<?php echo $this->form->renderField('tmail'); ?>
				<?php echo $this->form->renderField('state'); ?>
				<?php echo $this->form->renderField('created'); ?>
				<?php echo $this->form->renderField('publish_up'); ?>
				<?php echo $this->form->renderField('publish_down'); ?>	
					<?php /* Hidden fields */ ?>
					<?php echo $this->form->getLabel('checked_out'); ?>
                    <?php echo $this->form->getInput('checked_out'); ?>
					<?php echo $this->form->getLabel('checked_out_time'); ?>
                    <?php echo $this->form->getInput('checked_out_time'); ?>
			</div>
		</div>
		</fieldset>
	</div>


	<input type="hidden" name="task" value="" />
	<?php echo JHtml::_('form.token'); ?>
	<div class="clr"></div>
</form>
</div>