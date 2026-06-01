<?php
/**
 * @package     DPE
 * @subpackage  com_dpe
 *
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (C) 2009 - 2019 Techjoomla. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access
defined('_JEXEC') or die;
use Joomla\CMS\Uri\Uri;

use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Session\Session;

$document = Factory::getDocument();
$document->addScript(Uri::root() . 'media/com_dpe/js/dpe.js');
$document->addStyleSheet('templates/shaper_helix3/css/bootstrap.min.css');
$document->addStyleSheet('templates/shaper_helix3/css/custom.css');

HTMLHelper::_('bootstrap.tooltip');
HTMLHelper::_('behavior.multiselect');
HTMLHelper::_('formbehavior.chosen', 'select');

$path = "";

switch ($this->client) {
	case "com_tjucm.ithardware":
	$path  = Uri::root() . 'media/com_dpe/samplecsv/HardwareImport.csv';
	break;

	case "com_tjucm.software":
	$path  = Uri::root() . 'media/com_dpe/samplecsv/SoftwareImport.csv';
	break;

	case "com_tjucm.role":
	$path  = Uri::root() . 'media/com_dpe/samplecsv/RoleImport.csv';
	break;

	case "com_tjucm.ropvendors":
	$path  = Uri::root() . 'media/com_dpe/samplecsv/VendorImport.csv';
	break;
}
?>

<div class="mt-20 ml-20 mr-20">
	<form action="<?php echo Uri::root(); ?>index.php?option=com_dpe&task=csvImport&tmpl=component&format=html" id="uploadForm" name="uploadForm" method="post" enctype="multipart/form-data">
	<?php echo $this->form->renderField('cluster_id'); ?>
		<table>
			<tr>&nbsp;</tr>
			<tr>
				<div id="uploadform">
					<fieldset id="upload-noflash" class="actions">
						<label for="upload-file" class="control-label"><?php echo Text::_('COM_DPE_UPLOADE_FILE'); ?></label>
						<input type="file" required id="upload-file" name="csvfile" id="csvfile" onchange="validate_import(this)" />
						<button class="btn btn-primary" id="upload-submit" onClick="validate_import(this)">
							<i class="icon-upload icon-white"></i>
							<?php echo Text::_('COM_DPE_IMPORT_CSV'); ?>
						</button>
						<hr class="hr hr-condensed">
						<div class="alert alert-warning" role="alert"><i class="icon-info"></i>
							<?php
								$link = '<a href="' . $path . '">' . Text::_("COM_DPE_CSV_SAMPLE") . '</a>';
								echo Text::sprintf('COM_DPE_CSVHELP', $link);
							?>
						</div>
					</fieldset>
				</div>
			</tr>
		</table>
		<input type="hidden" name="client" value="<?php echo $this->client;?>"/>
		<?php echo HTMLHelper::_('form.token'); ?>
	</form>
</div>

<script type="text/javascript">

// Close popup if data saved successfully

	jQuery(document).ready(function(){
		if(jQuery('#system-message div').hasClass('alert alert-success'))
		{
			jQuery("#system-message-container").fadeTo(2000, 500, function(){
				window.parent.document.location.reload(true);
				window.parent.SqueezeBox.close();
			});
		}
	});
</script>
