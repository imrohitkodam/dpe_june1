<?php
/**
 * @package        	Joomla
 * @subpackage		Event Booking
 * @author  		Tuan Pham Ngoc
 * @copyright    	Copyright (C) 2010 - 2024 Ossolution Team
 * @license        	GNU/GPL, see LICENSE.php
 */

defined('_JEXEC') or die ;

use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Language\Text;

/**
 * Layout variables
 *
 * @var array $speakers
 */

$rootUri               = Uri::root(true);
$config                = EventbookingHelper::getConfig();
$bootstrapHelper       = EventbookingHelperBootstrap::getInstance();
$numberColumns         = $config->get('number_speakers_per_row', 4);
$numberSpeakers        = count($speakers);
$count                 = 0;
$span                  = 'span' . intval(12 / $numberColumns);
$span                  = $bootstrapHelper->getClassMapping($span);
$rowFluidClass         = $bootstrapHelper->getClassMapping('row-fluid');
$imageCircleClass      = $bootstrapHelper->getClassMapping('img-circle');
$speakerContainerClass = $span . ' eb-speaker-container';
?>
<div id="eb-speakers-list" class="<?php echo $rowFluidClass; ?> clearfix speaker-list">
	<div class="speakers-section-wrapper speaker-location-section container-fluid">
	<h2 class="speakers-section-title"><?php echo Text::_('COM_DPE_EVENT_DISTINCT_SPEAKER');?></h2>

	<div class="speaker-cards-container container speakers-grid">

	<?php
    for ($i = 0, $n = count($speakers); $i < $n; $i++) {
        $speaker = $speakers[$i];
        
        echo EventbookingHelperHtml::loadCommonLayout(
            'plugins/speaker_item.php',
            ['speaker' => $speaker, 'speakerContainerClass' => $speakerContainerClass]
        );
    }
    ?>
	
</div>
</div>
</div>