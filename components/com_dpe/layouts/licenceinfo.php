<?php
/**
 * @package     Joomla.Site
 * @subpackage  Layout
 *
 * @copyright   Copyright (C) 2021 - 2022 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */
defined('JPATH_BASE') or die;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;

JLoader::import('components.com_sla.includes.sla', JPATH_ADMINISTRATOR);
$items = $displayData['licences'];

// Remove below commented code if want to show sla and activities

/*
$clusterXrefsTable = SlaFactory::table("slaclusterxrefs");
$slaModel = SlaFactory::model('Sla', array('ignore_request' => true));
*/

?>
<?php foreach ($items as $key => $item) {
		// Remove below commented code if want to show sla and activities

		/*
		$clusterXrefsTable->load(array('license_id' => $item->id));
		$clusterXrefsTable->sla_id;
		$activities = $slaModel->getSavedSlaActivityTypeHtml($item->id, $clusterXrefsTable->sla_id, true);
		*/
	?>
	<?php if ($item->state == 1) { ?>
		<strong><?php echo Text::_('COM_DPE_ACTIVE_LICENCE'); ?> <?php echo $key+1; ?></strong>
	<?php }else{ ?>
		<strong><?php echo Text::_('COM_DPE_FUTURE_LICENCE'); ?> <?php echo $key+1; ?></strong>
	<?php } ?>
<div class="controls">
	<span><?php echo Text::_('COM_DPE_START_DATE'); ?> : <?php echo Factory::getDate($item->start_date)->format(Text::_('COM_DPE_DATE_D_M_Y')); ?></span>, 
	<span><?php echo Text::_('COM_DPE_END_DATE'); ?> : <?php echo Factory::getDate($item->end_date)->format(Text::_('COM_DPE_DATE_D_M_Y')); ?></span>
</div>
<?php } ?>
