<?php
/**
 * @package     JMailAlerts
 * @subpackage  jma_tjlms_displayusers
 *
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (C) 2009 - 2020 Techjoomla. All rights reserved.
 * @license     GNU General Public License version 2 or later
 */

// Do not allow direct access
defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\Factory;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Language\Text;
use Joomla\CMS\HTML\HTMLHelper;

$comtjlmsHelper = new comtjlmsHelper;
?>
<h2 class="subTitle">
	<?php echo JText::_($pluginParams->get('plugintitle')); ?>
</h2>
<?php
if ($this->params->get('show_recent_completions_users'))
{
	?>
	<h2 class="subTitle">
		<?php echo Text::_('PLG_JMA_CD_RECENT_COMPLETION_TITLE'); ?>
	</h2>

	<table class="jma_latestuser product-table">
		<tr>
		<?php
		$i = 1;

		foreach ($list as $user)
		{
			$profileAvatar = $comtjlmsHelper->sociallibraryobj->getAvatar(Factory::getUser($user->id));

			$courseUrl = 'index.php?option=com_tjlms&view=course&id=' . (int) $user->courseId;
			$courseUrl = Uri::root() . substr(Route::_($courseUrl, false), strlen(Uri::base(true)) + 1);
			?>
			<td class="jma_user_img">
					<div>
						<img class="jma_latestuser_img"
							src="<?php echo $profileAvatar ?>" alt="<?php echo $user->name ?>"/>

						<div class="d-block jma-userinfo">
							<?php echo $user->name ?>
						</div>
						<div class="d-block jma-course">
							<?php echo "Completed Course" ?>
							<b>
								<a href="<?php echo $courseUrl ?>" target="_blank" title="<?php echo $user->title ?>">
									 <?php echo $user->title ?>
							 	</a>
							 </b>
						</div>
						<div class="d-block jma-time">
							<?php
							echo 'On ';
							echo HTMLHelper::date($user->timeend); ?>

						</div>
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
	</table>
	<?php
}
