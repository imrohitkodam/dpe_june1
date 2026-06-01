<?php
/**
 * @package     Shika
 * @subpackage  com_tjlms
 *
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (C) 2009 - 2020 Techjoomla. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Plugin\PluginHelper;

$lesson_formats_array = array (
	'scorm',
	'htmlzips',
	'tincanlrs',
	'video',
	'document',
	'textmedia',
	'externaltool',
	'event',
	'survey',
	'form',
	/*'quiz',
	'exercise',
	'feedback'*/
);
?>
<div class="<?php echo COM_TJLMS_WRAPPER_DIV; ?>">
	<div class="modal tjBs3 format_types" role="dialog" data-backdrop="static" data-keyboard="false" id="lessonTypeModal">
		<div class="modal-header">
			<button type="button" class="close" onclick="window.history.back();"; data-dismiss="modal" aria-hidden="true">×</button>
		  <h4 class="modal-title center"><?php echo Text::_('COM_TJLMS_MODUELS_PICK_LESSONTYPE'); ?></h4>
		</div>
		<div class="modal-body lesson-type-containers">
			<?php

			$quizFormatsArray = array('quiz', 'exercise', 'feedback');

			$ind = 0;

			foreach ($lesson_formats_array as $formatName)
			{
				/* Check if any of the plugin of provided lesson format is enabled*/
				$plugformat = 'tj' . $formatName;
				PluginHelper::importPlugin($plugformat);

				// Call the plugin and get the result
				$results = Factory::getApplication()->triggerEvent('onGetSubFormat_tj' . $formatName . 'ContentInfo');

				if (!empty($results))
				{
					if (!in_array($formatName, $quizFormatsArray))
					{
						$createLink = "index.php?option=com_tjlms&view=lessonform&layout=edit&ptype=" . $formatName;
					}
					else
					{
						$createLink = "index.php?option=com_tmt&task=test.edit&gradingtype=" . $formatName;
					}

					if ($ind == 0)
					{
					?>
						<div class="row-fluid">
					<?php
					}
					?>
							<div class="span4 text-center my-15">
								<a href="<?php echo $createLink;?>" class="bg-rep d-inline-block lecture-icons <?php echo $formatName ?>"
									data-type="<?php echo ucfirst($formatName)?>">
									<span><?php echo Text::_("COM_TJLMS_" . strtoupper($formatName) . "_LESSON"); ?></span>
								</a>
							</div>

					<?php
					if ($ind == 2)
					{
						$ind = 0;
					?>
						</div>
					<?php
					}
					else
					{
						$ind++;
					}
				}
			}
			?>
		</div>
	</div>
</div>
