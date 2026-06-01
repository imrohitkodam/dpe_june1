<?php
/**
 * SEO Glossary Glossary View Edit layout
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
defined( '_JEXEC' ) or die ;
if (version_compare(JVERSION, '4.0', 'ge'))
				{}
				else{
JHtml::_( 'behavior.tooltip' );
				}
JHtml::_( 'behavior.formvalidator' );
$app = JFactory::getApplication( );
$document = JFactory::getDocument( );
$user = JFactory::getUser();
$canDo = SeoglossaryHelper::getActions( );
$id = JFactory::getApplication()->input->getInt( 'id' );
$myglossdata = new SeoglossaryModelglossaries;
$catid = JFactory::getApplication()->input->getInt('catid', null);

if($catid)
{
	$Itemid = SeoglossaryHelper::getGlossaryMenu($catid);
				
				$item_id="";
				if($Itemid)
				{
					$item_id='&Itemid='.$Itemid;
					
				}
				$link = JRoute::_('index.php?option=com_seoglossary&view=glossaries&catid=' . $catid.$item_id);
}
else
{
				$link = JRoute::_('index.php?option=com_users&view=login');
	
}
if ( !empty( $id ) )
{
	if ( !($canDo->get( 'core.edit' )) )
	{
		$msg = JText::_( 'COM_SEOGLOSSARY_EDIT_NOT_AUTHORIZED' );
		  $app->enqueueMessage($msg);
		$app->redirect( $link);
	}
}
else
if ( !($canDo->get( 'core.create' )) )
{
	$msg = JText::_( 'COM_SEOGLOSSARY_ADD_NOT_AUTHORIZED' );
	  $app->enqueueMessage($msg);
	$app->redirect( $link );
}
?>
<script type="text/javascript">
	<?php if (!version_compare(JVERSION, '4.0', 'ge')){ ?>
	Joomla.submitbutton = function(task)
{
if (task == 'glossary.cancel' || document.formvalidator.isValid(document.getElementById('glossary-form'))) {
Joomla.submitform(task, document.getElementById('glossary-form'));
}
else {
alert('<?php echo $this->escape( JText::_( 'JGLOBAL_VALIDATION_FORM_FAILED' ) );?>
	');
	return false;
	}
	}
	<?php } ?>
</script>
<form action="<?php echo $link; ?>" method="post" name="adminForm" id="glossary-form" class="form-validate">
	<div class="glossary row">
		<div id="toolbar" class="toolbar-list span12 col-md-12">
			<ul>
				<li id="toolbar-save" class="button">
					<a class="toolbar btn btn-small btn-success" onclick="javascript:Joomla.submitbutton('glossary.save')" > <span class="icon-apply icon-white"> </span> <?php echo JText::_( 'JSave' ); ?> </a>
				</li>
				<li id="toolbar-cancel" class="button">
					 <?php if(!empty($_SERVER['HTTP_REFERER'])) {?><a class="toolbar btn btn-small btn-danger" href="<?php echo $_SERVER['HTTP_REFERER'];?>" > <span class="icon-unpublish"> </span> <?php echo JText::_( 'JCancel' ); ?> </a><?php } ?>
				</li>
			</ul>
			<div class="clr"></div>
		</div>
		<fieldset class="adminform  span12 col-md-12">
			<ul class="adminformlist">
				<li <?php if ($catid !== null) { ?>style="display:none;"<?php } ?>>
					<?php 
						if ($catid !== null) {
							echo '<input type="hidden" name="jform[catid]" id="jform_catid" value="'.$catid.'" />'."\n";
						} else {
					?>
					<?php echo $this->form->getLabel( 'catid' );?>
					<?php echo $this->form->getInput( 'catid' );?>
					<?php } ?>
				</li>
				<li style="display:none;">
					<?php echo $this->form->getLabel( 'id' );?>
					<?php echo $this->form->getInput( 'id' );?>
				</li>
				<?php if ($user->guest): ?>
				<li>
					<?php echo $this->form->getLabel( 'tname' );?>
					<?php echo $this->form->getInput( 'tname' );?>
				</li>
				<li>
					<?php echo $this->form->getLabel( 'tmail' );?>
					<?php echo $this->form->getInput( 'tmail' );?>
				</li>
				<?php endif; ?>
				<li>
					<?php echo $this->form->getLabel( 'tterm' );?>
					<?php echo $this->form->getInput( 'tterm' );?>
				</li>
                <li>
                    <?php echo $this->form->getLabel( 'tsynonyms' );?>
                    <?php echo $this->form->getInput( 'tsynonyms' );?>
                </li>
				<li>
					<div class="clr"></div><?php echo $this->form->getLabel( 'tdefinition' );?>
					<div class="clr"></div><?php echo $this->form->getInput( 'tdefinition' );?>
				</li>
				<li>
					<div class="clr"></div><?php echo $this->form->getLabel( 'tmore' );?>
					<div class="clr"></div><?php echo $this->form->getInput( 'tmore' );?>
				</li>
				<?php if($this->params->get('auto_publish')==0) {
				?>

				<input type="hidden" name="jform[state]" value="0" />
				<?php } else {?>
				<input type="hidden" name="jform[state]" value="1" />
				<?php }?>
				<li>
					<?php echo $this->form->getLabel( 'checked_out' );?>
					<?php echo $this->form->getInput( 'checked_out' );?>
				</li>
				<li>
					<?php echo $this->form->getLabel( 'checked_out_time' );?>
					<?php echo $this->form->getInput( 'checked_out_time' );?>
				</li>
			</ul>
		</fieldset>
	</div>
	<input type="hidden" name="task" value="" />
	<input type="hidden" name="view" value="glossaries" />
	<input type="hidden" name="catid" value="<?php echo @$catid; ?>" />
	<input type="hidden" name="id" value="<?php echo $id; ?>" />
	<input type = "hidden" name = "option" value = "com_seoglossary" />
	<?php echo JHtml::_( 'form.token' );?>

	<div class="clr"></div>
</form>