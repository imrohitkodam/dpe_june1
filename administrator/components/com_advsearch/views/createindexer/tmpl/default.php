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
$document->addScript('https://ajax.googleapis.com/ajax/libs/jquery/1.8.0/jquery.min.js');
$document->addScript('components/com_advsearch/assets/js/advsearch_edit.js');
JLoader::import('components.com_advsearch.helpers.advsearch', JPATH_ADMINISTRATOR);
$user	= JFactory::getUser();
$userId	= $user->id;
$listOrder	= $this->state->get('list.ordering');
$listDirn	= $this->state->get('list.direction');
$canOrder	= $user->authorise('core.edit.state', 'com_advsearch');
$saveOrder	= $listOrder == 'a.ordering';
?>




<form name="adminForm" id="adminForm" action="" method="post" class="form-validate" >
<fieldset class="adminform">
	<legend>Indexer Details</legend>
	    <table border="0" width="100%" class="adminlist">
		<tbody>
			<tr> </tr>
			<tr> </tr>
			<tr>
				<td align="left" width="10%"><strong>Select Plugin</strong></td>

				<td align="left" >

					<?php echo $this->Plugins; ?>

				</td>
			</tr>
		    <tr id="types" class="types">
				<!--td align="left" width="20%"><strong>Select Type</strong></td>
				<td align="left" width="20%" -->
				</td>
			</tr>
		    <tr>
				<td align="left"  id="fields" class="fields" colspan="2" />
				</td>
			</tr>
			  <tr>
				<td align="left" id="fields" class="fields" colspan="2" />
				</td>
			</tr>

			</tbody></table>
</fieldset>
	<input type="hidden" name="option" value="com_advsearch" />
	<input type="hidden" name="task" value="createindexer.saveIndexer" />
	<?php echo JHtml::_('form.token'); ?>

</form>

<?php
$AdvsearchHelper  = new AdvsearchHelper;

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

