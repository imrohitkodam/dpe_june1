<?php
/**
 * @package     JMailAlerts
 * @subpackage  jma_coursedisplay
 *
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (C) 2009 - 2020 Techjoomla. All rights reserved.
 * @license     GNU General Public License version 2 or later
 */

// Do not allow direct access
defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Router\Route;

$coursesData = $displayData;

?>
<table class="jma_latestuser product-table">
	<tbody class="course-body">
		<tr>
			<?php
			$i = 1;

			foreach ($coursesData as $course)
			{
				$courseUrl = 'index.php?option=com_tjlms&view=course&id=' . (int) $course->id;
				$courseUrl = Uri::root() . substr(Route::_($courseUrl, false), strlen(Uri::base(true)) + 1);
				?>

				<td class="jma_user_img">
					<div>
						<a href="<?php echo $courseUrl ?>" target="_blank" title="<?php echo $this->escape($course->title) ?>">
							<img class="jma_latestuser_img" src="<?php echo $course->image; ?>" alt="<?php echo $this->escape($course->title) ?>"/>
						</a>
						<div class="d-block jma-course"> <?php echo $this->escape($course->title)?></div>
					</div>
				</td>

				<?php
				$i++;

				if ($i > 3)
				{
					$i = 1;
					?>

					</tr>
					<?php
				}
			}
			?>
	</tbody>
</table>
