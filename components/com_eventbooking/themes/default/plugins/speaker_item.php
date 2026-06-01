<?php
/**
 * @package        	Joomla
 * @subpackage		Event Booking
 * @author  		Tuan Pham Ngoc
 * @copyright    	Copyright (C) 2010 - 2024 Ossolution Team
 * @license        	GNU/GPL, see LICENSE.php
 */

defined('_JEXEC') or die ;

use Joomla\CMS\Language\Text;
use Joomla\CMS\Uri\Uri;

/**
 * Layout variables
 *
 * @var stdClass $speaker
 * @var string   $speakerContainerClass
 */

$rootUri               = Uri::root(true);
$bootstrapHelper       = EventbookingHelperBootstrap::getInstance();
$imageCircleClass      = $bootstrapHelper->getClassMapping('img-circle');
$speakerContainerClass = $speakerContainerClass ?? 'eb-speaker-container';
?>

		<div class="speaker-profile-card">
			<div class="speaker-image-frame">
				<?php

					if ($speaker->avatar)
				{
					if ($speaker->url)
					{
						?>
						<a href="<?php echo $speaker->url; ?>" class="eb-speaker-url">
							<img src="<?php echo $rootUri . '/' . $speaker->avatar; ?>" class="speaker-photo" />
						</a>
						<?php
					}
					else
					{
						?>
						<img src="<?php echo $rootUri . '/' . $speaker->avatar; ?>" class="speaker-photo"/>
						<?php
					}
					?>
					<?php
				}
				?>

			</div>

			<h3 class="speaker-name">

				<?php if ($speaker->url)
				{
				?>
					<a href="<?php echo $speaker->url; ?>" >
						<?php echo Text::_($speaker->name); ?>
					</a>
				<?php
				}
				else
				{
					echo Text::_($speaker->name);
				}
				?>


			</h3>
			<p class="speaker-title">
				<?php if ($speaker->title)
				{
					?>
					<?php echo Text::_($speaker->title); ?>
					<?php
				}
				?>
			</p>
			<p class="speaker-bio"><?php echo $speaker->description; ?></p>
            <a href="javascript:void(0);" 
   class="read-more-link" 
   data-name="<?php echo htmlspecialchars($speaker->name, ENT_QUOTES, 'UTF-8'); ?>" 
   data-description="<?php echo htmlspecialchars(strip_tags($speaker->description), ENT_QUOTES, 'UTF-8'); ?>">
   Read More
</a>

		</div>
		<!-- Modal Popup -->
<div class="modal-overlay" id="speakerModal">
  <div class="modal-content">
   <div class="modal-close-name mb-4 border-bottom">
		<button class="modal-close" id="closeModal">&times;</button>
		<h3 id="modalName" class="modalName1"></h3>
	</div>
    <p id="modalDescription"></p>
  </div>
</div>



<!-- 
<div class="<?php echo $speakerContainerClass; ?>">
	<?php
	if ($speaker->avatar)
	{
	?>
		<div class="eb-speaker-avatar">
			<?php
			if ($speaker->url)
			{
				?>
				<a href="<?php echo $speaker->url; ?>" class="eb-speaker-url">
					<img src="<?php echo $rootUri . '/' . $speaker->avatar; ?>" class="<?php echo $imageCircleClass; ?>" />
				</a>
				<?php
			}
			else
			{
			?>
				<img src="<?php echo $rootUri . '/' . $speaker->avatar; ?>" class="<?php echo $imageCircleClass; ?>" />
			<?php
			}
			?>
		</div>
	<?php
	}

	if ($speaker->url)
	{
	?>
		<h4 class="eb-speaker-name">
			<a href="<?php echo $speaker->url; ?>" class="eb-speaker-url">
				<?php echo Text::_($speaker->name); ?>
			</a>
		</h4>
	<?php
	}
	else
	{
	?>
		<h4 class="eb-speaker-name"><?php echo Text::_($speaker->name); ?></h4>
	<?php
	}

	if ($speaker->title)
	{
	?>
		<h5 class="eb-speaker-title"><?php echo Text::_($speaker->title); ?></h5>
	<?php
	}
	?>
	<p class="eb-speaker-description">
		<?php echo $speaker->description; ?>
	</p>
</div>
 -->


 <script>
document.addEventListener('DOMContentLoaded', function() {
  const modal = document.getElementById('speakerModal');
  const modalName = document.getElementById('modalName');
  const modalDescription = document.getElementById('modalDescription');
  const closeModal = document.getElementById('closeModal');
  const readMoreLinks = document.querySelectorAll('.read-more-link');

  readMoreLinks.forEach(link => {
    link.addEventListener('click', function() {
      const name = this.getAttribute('data-name');
      const description = this.getAttribute('data-description');
      modalName.textContent = name;
      modalDescription.textContent = description;
      modal.style.display = 'flex';
    });
  });

  closeModal.addEventListener('click', () => {
    modal.style.display = 'none';
  });

  modal.addEventListener('click', (e) => {
    if (e.target === modal) modal.style.display = 'none';
  });
});
</script>