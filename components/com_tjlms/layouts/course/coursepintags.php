<?php
/**
 * @package     Shika
 * @subpackage  com_tjlms
 *
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (C) 2009 - 2020 Techjoomla. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('JPATH_BASE') or die;
$courseData = $displayData;

?>
<div class="course_tags mt-5">
	<?php
		$tagsCount = 0;
		$overTags = array();

		foreach ($courseData['course_tags'] as $tag)
		{
			$tagsCount++;

			if ($tagsCount <= 2)
			{
				?>
					<div title="<?php echo $tag->title; ?>">
						<?php echo $tag->title; ?>
					</div>
				<?php
			}
			else
			{
				$overTags[] = $tag->title;
			}
		}

		$moreTagsCount = count($overTags);

		if ($moreTagsCount > 0)
		{
			$overTags = implode(', ', $overTags);
			?>
				<div title="<?php echo $overTags; ?>" class="cursor-pointer">
					<?php echo " + " . $moreTagsCount; ?>
				</div>
			<?php
		}
	?>
</div>
