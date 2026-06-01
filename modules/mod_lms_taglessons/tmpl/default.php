<?php
/**
 * @package     LMS_Shika
 * @subpackage  mod_lms_taglessons
 * @copyright   Copyright (C) 2014 - 2023 Techjoomla. All rights reserved.
 * @license     GNU GPLv2 <http://www.gnu.org/licenses/old-licenses/gpl-2.0.html>
 * @link        http://www.techjoomla.com
 */

// No direct access.
defined('_JEXEC') or die;

use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Factory;
use Joomla\CMS\Date\Date;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Language\Text;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Component\ComponentHelper;

HTMLHelper::_('behavior.modal');
jimport('joomla.application.component.model');

if (empty($lessonsData))
{
	?>
	<div class="alert alert-warning text-center mt-20">
		<h4><?php echo Text::_('MOD_LMS_TAG_LESSONS_NO_LESSON');?></h4>
	</div>
	<?php
}
else
{
?>
<div>
	<?php
	foreach ($lessonsData as $lesson)
	{
		?>
		<div class="item card-cover col-xs-6 col-md-3  pb-20">
			<div class="bs-2 br-tl10 pin-inner">
				<div class="pin-top-c p-relative">
					<a class="height-185 d-block br-tl10" title="<?php echo $lesson->title;?>" style="background:url('<?php echo $lesson->lessonImage;?>');background-repeat: no-repeat; background-size: cover; background-position:center top">
				</a>
				</div>
				<div class="br-bl10 br-br10 px-10 pb-10 text-left">
					<div>
						<?php
						$dateFormat = Text::_('MOD_LMS_TAG_LESSONS_DATE_FORMAT');

						if ($lesson->start_date != $nullDate && $lesson->end_date != $nullDate )
						{
							echo $startDate = HTMLHelper::date($lesson->start_date, $dateFormat, true);
							echo ' - ';
							echo $endDate = HTMLHelper::date($lesson->end_date, $dateFormat, true);
						}
						?>
					</div>
					<div class="font-600 height-100">
					<?php
						if (strlen($lesson->title) > 50)
						{
							$lessonTitle = substr($lesson->title, 0, 50) . '...';
						}
						else
						{
							$lessonTitle = $lesson->title;
						}
					?>
					<p>
						<?php
						if ($lesson->start_date != $nullDate && $lesson->end_date != $nullDate )
						{
							if ($lesson->start_date > $currentDate || $lesson->end_date < $currentDate)
							{
								echo $lessonTitle;
							}
							else
							{
							?>
							<a class="text-darkgray" href="<?php echo $lesson->lessonUrl; ?>" target="_blank">
							<?php echo $lessonTitle; ?>
							</a>
							<?php
							}
						}
						elseif ($lesson->start_date == $nullDate || $lesson->end_date == $nullDate )
						{
						?>
							<a class="text-darkgray" href="<?php echo $lesson->lessonUrl; ?>" target="_blank">
								<?php echo $lessonTitle; ?>
							</a> <?php
						}
						?>
					</p>
					</div>
					<span class="event-sourse-type">
						<?php
						if (strlen($lesson->lessoncategory) > 50)
						{
							echo substr($lesson->lessoncategory, 0, 50) . '...';
						}
						else
						{
							echo  $lesson->lessoncategory;
						}
						?>
					</span>
				</div>
			</div>
		</div>
	<?php
	}
	?>
</div>
<?php
}
?>
