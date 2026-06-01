<?php
/**
 * @version     1.0.0
 * @package     com_advsearch
 * @copyright   Copyright (C) 2012. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @author      Komal Mane <komal_m@tekditechnologies.com> - http://tekdi.net
 */
// No direct access
defined('_JEXEC') or die;

JHtml::_('bootstrap.tooltip');
JHtml::_('behavior.formvalidator');
// JHtml::_('formbehavior.chosen', 'select');
JHtml::_('behavior.keepalive');

// Import CSS
JHtml::stylesheet(JUri::root(). 'administrator/components/com_advsearch/assets/css/advsearch.css' );
// JHtml::script('http://ajax.googleapis.com/ajax/libs/jquery/1.8.0/jquery.min.js' );
JHtml::script( JUri::root().'administrator/components/com_advsearch/assets/js/mapping.js' );

 //~ echo"<pre>"; print_r($this->item); echo"</pre>";
$selectedValues = '';

//require_once JPATH_COMPONENT.'/helpers/advsearch.php';
JLoader::import('AdvsearchHelper', JPATH_ROOT . '/components/com_advsearch/helpers/advsearch.php');

$user      = JFactory::getUser();
$userId    = $user->get('id');
$listOrder = $this->state->get('list.ordering');
$listDirn  = $this->state->get('list.direction');
$canOrder  = $user->authorise('core.edit.state', 'com_advsearch');
$saveOrder = $listOrder == 'a.ordering';

$mapping_id = '';
$mapping_name = '';
$primary_index_id = '';
$secondary_index_id = '';
$disabled = '';

if ($this->item)
{
	$mapping_id = $this->item[0]->mapping_id;
	$mapping_name = $this->item[0]->mapping_name;
	$primary_index_id = $this->item[0]->primary_index_id;
	$secondary_index_id = $this->item[0]->secondary_index_id;
	$disabled = 'disabled="disabled"';
}
?>
<script>

</script>
<form name="adminForm" id="adminForm" action="" method="post" class="form-validate">
	<fieldset class="adminform">
		<legend>Indexer Details</legend>
		<table border="0" width="100%" class="adminlist">
			<tbody>
				<tr>
					<td>Enter mapping name : <input type="text" name="mapping_name" id="mapping_name" value="<?php echo $mapping_name ;?>"></td>
				</tr>
				<tr>
					<td><?php echo JHTML::_('select.genericlist', $this->Indexer,  'indexer_1', 'class="required"'.$disabled.'', 'text', 'value', $primary_index_id ); ?></td>
				</tr>
				<tr id="indexer_1_data" class="indexer_1_data"></tr>
			</tbody>
		</table>

		<table border="0" width="100%" class="adminlist">
			<tbody>
				<tr>
					<td><?php echo JHTML::_('select.genericlist', $this->Indexer,  'indexer_2', 'class="required" '.$disabled.'', 'text', 'value', $secondary_index_id); ?></td>
				</tr>
				<tr id="indexer_2_data" class="indexer_2_data"></tr>
			</tbody>
		</table>
	</fieldset>
	<input type="hidden" name="option" value="com_advsearch" />
	<input type="hidden" name="task" value="createmapping.saveMapping" />
	<input type="hidden" name="id" value="<?php echo $mapping_id;?>" />
	<?php echo JHtml::_('form.token'); ?>
</form>

<?php $AdvsearchHelper  = new AdvsearchHelper;
echo $AdvsearchHelper->getFooterNote(); ?>
<script>
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
