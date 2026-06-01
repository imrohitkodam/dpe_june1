<?php
/**
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (C) 2009 - 2021 Techjoomla. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access to this file
defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Uri\Uri;

HTMLHelper::_('script', 'media/vendor/jquery/js/jquery.min.js');

HTMLHelper::_('script', 'media/com_dpe/js/dpe.min.js');

$app       = Factory::getApplication()->input;
$licenceId = $app->getInt('licence_id', 0);
$language  = Factory::getLanguage()->getTag();

$document	= Factory::getDocument();
$document->addStyleSheet(Uri::root() . '/templates/shaper_helix3/css/custom.css');

?>
<div class="timelog-add-form activity-edit front-end-edit ml-20 mr-20">
	<button type="button" class="close" onclick="licence.closePopup();">&times;</button>
	<h3 class="activity-header mb-30"><?php echo Text::_('COM_MULTIAGENCY_SLA_ACTIVITY_STREAM')?></h3>
	<div id="tj-activitystream" tj-activitystream-widget tj-activitystream-theme="slafeed" tj-activitystream-bs="bs3"
	tj-activitystream-client="com_multiagency"
	tj-activitystream-type="'multiagency.addsla','multiagency.updatestartdatesla','multiagency.updateenddatesla','multiagency.archivesla','multiagency.updateslatools'" tj-activitystream-target-id="<?php echo $licenceId;?>" tj-activitystream-limit="10" tj-activitystream-language="<?php echo $language;?>">
	</div>
</div>
