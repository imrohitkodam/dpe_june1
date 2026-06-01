<?php
/**
 * @version     1.0.0
 * @package     com_advsearch
 * @copyright   Copyright (C) 2012. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @author      amol <amol_p@tekdi.net> - http://tekdi.net
 */


// no direct access
defined('_JEXEC') or die;


JHtml::_('bootstrap.tooltip');
JHTML::_('script','system/multiselect.js',false,true);
// Import CSS
$document = JFactory::getDocument();
$document->addStyleSheet('components/com_advsearch/assets/css/advsearch.css');
JLoader::import('AdvsearchHelper', JPATH_ROOT . '/components/com_advsearch/helpers/advsearch.php');
$document->addScript('https://ajax.googleapis.com/ajax/libs/jquery/1.8.0/jquery.min.js');
$document->addScript('components/com_advsearch/assets/js/advsearch_edit.js');

$user	= JFactory::getUser();
$userId	= $user->get('id');
$listOrder	= $this->state->get('list.ordering');
$listDirn	= $this->state->get('list.direction');
$canOrder	= $user->authorise('core.edit.state', 'com_advsearch');
$saveOrder	= $listOrder == 'a.ordering';
?>



<form method="post" id="adminForm" name="adminForm" class="form-validate" action="">
	<div class="span12">
		<h3>Stat Details</h3>
		<hr/>

		<div class="span12">
			<div class="span2"><strong>Select Plugin</strong></div>
			<div class="span6"><?php echo $this->Plugins; ?></div>
		</div>

		<div class="span12 types" id="types"></div>
		<div   id="fields" class="fields"></div>


	</div>


	<input type="hidden" name="option" value="com_advsearch" />
	<input type="hidden" name="task" value="createindexer.saveIndexer" />
<?php echo JHtml::_('form.token'); ?>


</form>
<?php
$AdvsearchHelper  = new AdvsearchHelper;
echo $AdvsearchHelper->getFooterNote(); ?>
<script>

$("#delete_field").live("click", function()
{
	if(!confirm('Are you sure you want to delete?'))
	{
		ev.preventDefault();
		return false;
	}
	else
	{
		$(this).parent().parent().remove();
	}
});

function deleteMe(row_id)
{
	if(!confirm('Are you sure you want to delete this field?'))
	{
		ev.preventDefault();
		return false;
	}
	else
	{
		$('#my_row_'+row_id).remove();
	}
}
</script>


