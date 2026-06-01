<?php
/**
 * @package     JTicketing
 * @subpackage  com_jticketing
 *
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (C) 2009 - 2019 Techjoomla. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

/** @var $this JLikeViewExtendedTodos */
use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Table\Table;
use Joomla\Registry\Registry;

Table::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_jlike/tables');
$jlikeContentTable = Table::getInstance('Content', 'JlikeTable');

Table::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_subusers/tables');
$roleTable = Table::getInstance('role', 'SubusersTable');

JLoader::import('helpers.lesson', JPATH_SITE . '/components/com_tjlms');
$TjlmsLessonHelper = new TjlmsLessonHelper;

JLoader::import('helpers.main', JPATH_SITE . '/components/com_dpe');
$dpeHelper = new DpeMainHelper;

JLoader::import("/components/com_subusers/includes/rbacl", JPATH_ADMINISTRATOR);

// DPE - Hack  - Start
JLoader::import("/components/com_dpe/includes/dpe", JPATH_SITE);
$params = DPE::config();
$dateTimeFormat = (String) $params->get('dateTimeFormat');

// Lesson xref table to get cluster Id
$xrefTable = DPE::table('TjlmsClusterXref');

// DPE - Hack  - End

?>
<?php
	foreach($this->items as $item)
	{
		$jlikeContentTable->load(array('id' => $item->content_id, 'element'=> 'com_tjlms.lesson'));
		$lessonId = $jlikeContentTable->element_id;
		$attempt = $TjlmsLessonHelper->getlesson_total_attempts_done($lessonId, $item->assigned_to);
		$lessonData = $TjlmsLessonHelper->getLessonStatusDetails($lessonId, $item->assigned_to, $attempt);

		// Get cluster if by passing lesson id
		$xrefTable->load(array('lesson_id' => $lessonId));

		// This code will show role against cluster if multiple role assigned

		if (property_exists($xrefTable, 'cluster_id'))
		{
			$role = RBACL::getRoleByUser($item->assigned_to, 'com_cluster', $xrefTable->cluster_id);

			sort($role);

			$roleTable->load(array('id' => $role[0], 'state' => 1));
		}

		$intractions = new Registry($jlikeContentTable->params);
		$dueDate = Factory::getDate($item->due_date, 'UTC');
      	$timeSpentTxt = $lessonData->total_time_spent ? Text::_('COM_DPE_DOCUMENT_TIME_SPENT') . $lessonData->total_time_spent : Text::_('COM_DPE_DOCUMENT_TIME_SPENT_NOT_OPENED');
	?>
	<tr>
		<td>
			<strong title="<?php echo $timeSpentTxt; ?>"><?php echo $this->escape($item->userName);?></strong>
			<span class="d-block"><?php echo $this->escape($roleTable->name);?></span>
			<small><?php echo $dpeHelper->showHoursAndMins($lessonData->original_time);?></small>
        </td>
		<td>
			<?php echo HTMLHelper::date($dueDate, Text::_('COM_JLIKE_PAST_DATE_FORMAT'));?>
		</td>
		<td>
			<?php
				$timeleft = $dueDate->toUnix() - Factory::getDate(HTMLHelper::date('now', Text::_('DATE_FORMAT_JS1'), false))->toUnix();
				$daysleft = round((($timeleft/24)/60)/60);

				if ($daysleft < 0)
				{?>
					<?php echo '-';?>
				<?php
				}
				else
				{
					switch($daysleft)
					{
						case 0:
							$dueDays = Text::_('COM_JLIKE_DATE_TODAY');
							break;
						case +1:
							$dueDays = Text::_('COM_JLIKE_DATE_TOMORROW');
							break;
						default:
							$dueDays = Text::sprintf('COM_JLIKE_DATE_IN_DAYS', $daysleft);
					}
					?>
					<span class="<?php echo strtolower($dueDays);?>">
						<?php echo $dueDays;?>
					</span>
				<?php
				}
				?>
		</td>

		<?php if ($intractions['read_interaction']) { ?>
			<td>
				<?php
					if ($item->read)
					{?>
						<i class="fa fa-check-circle-o fa-lg txt-dark-green d-inline-block align-middle" aria-hidden="true" title="<?php echo HTMLHelper::date($item->read_date, $dateTimeFormat);?>"></i>
					<?php
					}
					else
					{?>
						<i class="fa fa-circle-o fa-lg d-inline-block align-middle" aria-hidden="true"></i>
					<?php
					}
					?>
			</td>
		<?php }
		if ($intractions['practice_interaction']) { ?>
			<td>
				<?php
				if ($item->used)
				{?>
					<i class="fa fa-check-circle-o fa-lg txt-dark-green d-inline-block align-middle me-2" aria-hidden="true"
					title="<?php echo HTMLHelper::date($item->used_date, $dateTimeFormat);?>"></i>
				<a class="d-inline-block align-middle" href="javascript:void();"><i class="fa fa-info-circle fa-lg text-info hasPopover" tabindex="0" data-trigger="focus" data-toggle="popover" data-placement="bottom" data-content="<?php echo $this->escape($item->description); ?>" aria-hidden="true"></i></a>
					<?php
				}
				else
				{?>
					<i class="fa fa-circle-o fa-lg" aria-hidden="true"></i>
				<?php
				}
				?>
			</td>
		<?php } ?>
	</tr>
	<?php
	}
	?>
	<?php 
	
	if($intractions['practice_interaction'])
	{
		$usedInPractice = 'used';
	}
	else
	{
	 	$usedInPractice = 'notused' ;
	};
	?>		
	<input type="hidden" name="used_in_practice" id="used_in_practice" value="<?php echo $usedInPractice ;?>" />	

<!--DPE Hack-->
<script type="text/javascript">
jQuery(document).ready(function(){
    const popoverIcons = document.querySelectorAll(".hasPopover");

    popoverIcons.forEach(function(icon) {
        // Create popover container
        let popoverContainer = document.createElement("div");
        popoverContainer.classList.add("popover-container");

        // Wrap the icon inside the container
        icon.parentNode.insertBefore(popoverContainer, icon);
        popoverContainer.appendChild(icon);

        // Create popover content element
        let popoverContent = document.createElement("div");
        popoverContent.classList.add("popover-content");
        popoverContent.setAttribute("inert", ""); // Initially disable interaction
        popoverContent.innerText = icon.getAttribute("data-content"); 
        popoverContainer.appendChild(popoverContent);

        // Show popover on hover
        icon.addEventListener("mouseenter", function() {
            popoverContent.style.visibility = "visible";
            popoverContent.style.opacity = "1";
            popoverContent.removeAttribute("inert"); // Allow interaction
        });

        // Hide popover on mouse leave
        icon.addEventListener("mouseleave", function() {
            popoverContent.style.visibility = "hidden";
            popoverContent.style.opacity = "0";
            popoverContent.setAttribute("inert", ""); // Prevent interaction
        });
    });
});
</script>