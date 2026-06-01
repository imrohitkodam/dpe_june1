<?php
/**
 * @package            Joomla
 * @subpackage         Event Booking
 * @author             Tuan Pham Ngoc
 * @copyright          Copyright (C) 2010 - 2024 Ossolution Team
 * @license            GNU/GPL, see LICENSE.php
 */

defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Router\Route;

/**
 * Layout variables
 *
 * @var stdClass $location
 */
?>
<!-- <h2><?php// echo Text::sprintf('EB_VENUE_INFORMATION', $location->name); ?></h2> -->

<?php
// if ($location->image && file_exists(JPATH_ROOT . '/' . $location->image))
// {
?>
	<!-- <img src="<?php //echo Uri::root(true) . '/' . $location->image; ?>" class="eb-venue-image img-polaroid"/> -->
<?php
// }

// if (EventbookingHelper::isValidMessage($location->description))
// {
?>
	<!-- <div class="eb-location-description"><?php //echo HTMLHelper::_('content.prepare', $location->description); ?></div> -->
<?php
// }

?>

<div class="venue-section-container">
	<h2 class="venue-section-title"> <?php  echo Text::sprintf('EB_VENUE_INFORMATIONS');?></h2>
	<p class="venue-desc"><?php echo $location->extraLocation; ?></p>

	<div class="venue-content-block">
<?php
if ($location->image && file_exists(JPATH_ROOT . '/' . $location->image))
{
?>
		<div class="venue-image-wrapper">

			<img src="<?php echo Uri::root(true) . '/' . $location->image; ?>" class="venue-main-image"/>

		</div>
<?php } ?>

		<div class="venue-text-details">
			<h3 class="venue-name"><?php echo $location->name;?></h3>
			<p class="venue-details-text">

				<?php if (EventbookingHelper::isValidMessage($location->description))
					{  echo $location->description; } ?>
			</p>



			<a href="<?php echo Route::_('index.php?option=com_eventbooking&view=map&location_id=' . $location->id . '&Itemid=' . $Itemid); ?>" class="btn-explore-venue">
				Explore the Venue <i class="fas fa-external-link-alt"></i>
			</a>
		</div>
	</div>
</div>