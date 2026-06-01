<?php
/**
 * @package    Com_Tjucm
 * @author     Techjoomla <extensions@techjoomla.com>
 * @copyright  Copyright (c) 2009-2019 TechJoomla. All rights reserved.
 * @license    GNU General Public License version 2 or later.
 */

// No direct access
defined('_JEXEC') or die;
use Joomla\CMS\Table\Table;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\CMS\Layout\FileLayout;

use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Session\Session;
use Joomla\Registry\Registry;

HTMLHelper::_('jquery.token');
HTMLHelper::_('bootstrap.tooltip');
HTMLHelper::_('behavior.multiselect');
HTMLHelper::_('formbehavior.chosen', 'select');
HTMLHelper::_('bootstrap.renderModal');

$noRecordClass = 'hide';

BaseDatabaseModel::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_tjfields/models');

JLoader::import('field', JPATH_ADMINISTRATOR . '/components/com_tjfields/tables');
$tjfieldsTablefield = Table::getInstance('field', 'TjfieldsTable', array());

JLoader::import('clusters', JPATH_ADMINISTRATOR . '/components/com_cluster/tables');
$clusterTableClusters = Table::getInstance('clusters', 'ClusterTable', array());

// Get document type id
Table::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_tjucm/tables');
$typeInstance = Table::getInstance('type', 'TjucmTable');
$documentInstance = Table::getInstance('document', 'TjucmTable');
$typeInstance->load(array('unique_identifier' => $this->client));
$documentInstance->load(array('ucm_type' => $typeInstance->id, 'state' => 1));

// Get Documents of type single
BaseDatabaseModel::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_tjucm/models');
$UCMDocumentsModel = BaseDatabaseModel::getInstance('Documents', 'TjucmModel', array('ignore_request' => true));
$UCMDocumentsModel->setState('filter.document_type', 0);
$UCMDocumentsModel->setState('filter.state', 1);
$documents = $UCMDocumentsModel->getItems();

$ucmTypeParams = json_decode($typeInstance->params);
$loggedUser = Factory::getUser();
$this->ucmTypeParams = json_decode($typeInstance->params);


// Check ROP is generic process and User not having permission to manage all cluster
if ($this->state->get('filter.process') == 'generic')
{
	if (!Factory::getUser()->authorise('core.manageall', 'com_cluster'))
	{
		$this->canEditOwn = false;
		$this->canDeleteOwn = false;
	}
}


// Is DPE Admin
$isDPEAdmin = 0;

if ($loggedUser->authorise('core.manageall', 'com_cluster'))
{
	$isDPEAdmin = 1;
}

$tjUcmFrontendHelper = new TjucmHelpersTjucm;

// DPE - Hack  - Start
JLoader::import("/components/com_dpe/includes/dpe", JPATH_SITE);
$params = DPE::config();
// DPE - Hack  - End
$statusColumnWidth = 0;
$fieldsData = array();

$user = Factory::getUser();
$canCopyItem        = $user->authorise('core.type.copyitem', 'com_tjucm.type.' . $ucmTypeId);

$input    = Factory::getApplication()->input;
$listLayout = $input->get('reverselist', 'coredatalist', 'STRING');

?>

<script>
function openVisualizationPopup(url)
{
	var wwidth = jQuery(window).width() - 50;
	var wheight = jQuery(window).height() - 50;

	SqueezeBox.open(url, {
		handler: 'iframe',
		closable: true,
		size: {
			x: wwidth,
			y: wheight
        },
        classWindow: 'tjucm-rop-doc',
	});
}
function openDocument(url, UcmId)
{
	openDocumentPopup(url);
}

var selector = '.nav li';

jQuery(selector).on('click', function(){
    jQuery(selector).removeClass('active');
    jQuery(this).addClass('active');
    jQuery('#ucmdataid').val(jQuery(this).attr('data-ucm-id'));
    jQuery('#recordIds').val(jQuery(this).attr('data-ucm-id'));
    jQuery('#clutername').val(jQuery(this).attr('data-org-name'));
});

</script>
<?php

if (!empty($this->items))
{
	?>
	<ul class="coredata nav">
		<?php

		if (!empty($this->listcolumn))
		{
			foreach ($this->listcolumn as $fieldId => $col_name)
			{
				if (isset($fieldsData[$fieldId]))
				{
					$tjFieldsFieldTable = $fieldsData[$fieldId];
				}
				else
				{
					Table::addIncludePath(JPATH_ROOT . '/administrator/components/com_tjfields/tables');
					$tjFieldsFieldTable = Table::getInstance('field', 'TjfieldsTable');
					$tjFieldsFieldTable->load($fieldId);
					$fieldsData[$fieldId] = $tjFieldsFieldTable;
				}
			}
		}
		?>

			<?php
			$xmlFileName = explode(".", $this->client);
			$xmlFilePath = JPATH_SITE . "/administrator/components/com_tjucm/models/forms/" . $xmlFileName[1] . "_extra" . ".xml";
			$formXml = simplexml_load_file($xmlFilePath);

			$view = explode('.', $this->client);
			JLoader::import('components.com_tjucm.models.itemform', JPATH_SITE);
			$itemFormModel    = BaseDatabaseModel::getInstance('ItemForm', 'TjucmModel');
			$formObject = $itemFormModel->getFormExtra(
				array(
					"clientComponent" => 'com_tjucm',
					"client" => $this->client,
					"view" => $view[1],
					"layout" => 'edit')
					);

			foreach($this->items as $item)
			{
				$editRecordLink = 'index.php?option=com_tjucm&task=itemform.edit&id=' . $item->id . '&client=' . $this->client . '&cluster_id=' . $item->cluster_id;

				// Check document generate permission
				$isDocumentGenerate = true;

				if (!$loggedUser->authorise('core.manageall', 'com_cluster'))
				{
					$isDocumentGenerate = RBACL::check($loggedUser->id, 'com_cluster', 'document.generate', 'com_multiagency', $item->cluster_id);
				}
				?>
				<?php

					// Call the JLayout to render the fields in the details view
					$layout = new FileLayout('list.' . $listLayout, JPATH_ROOT . '/components/com_tjucm/');
					echo $layout->render(
						array(
							'itemsData' => $item,
							'created_by' => $this->created_by,
							'client' => $this->client,
							'xmlFormObject' => $formXml,
							'ucmTypeId' => $this->ucmTypeId,
							'ucmTypeParams' => $this->ucmTypeParams,
							'fieldsData' => $fieldsData,
							'formObject' => $formObject,
							'statusColumnWidth' => $statusColumnWidth,
							'listcolumn' => $this->listcolumn,
							'documents' => $documents
						)
					);
			}
		?>
</ul>
<?php
}
else
{
	$noRecordClass = '';
}
?>
<div class="alert alert-no-items no-items-result <?php echo $noRecordClass;?>">
	<?php echo Text::_('JGLOBAL_NO_MATCHING_RESULTS'); ?>
</div>
<?php
