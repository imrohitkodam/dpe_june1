<?php
/**
 * @package        	Joomla
 * @subpackage		Event Booking
 * @author  		Tuan Pham Ngoc
 * @copyright    	Copyright (C) 2010 - 2024 Ossolution Team
 * @license        	GNU/GPL, see LICENSE.php
 */

defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

/**
 * Layout variables
 *
 * @var stdClass $item
 */

$config = EventbookingHelper::getConfig();

HTMLHelper::_('bootstrap.dropdown');
?>
<div class="dropdown">

  <button class="btn btn-secondary btn-savecalendar"
          type="button"
          data-bs-toggle="dropdown"
          data-bs-display="static"
          aria-expanded="false">
    <?php echo Text::_('EB_SAVE_TO'); ?>
  </button>


  <ul class="dropdown-menu calendar-menu">
    <li>
      <a class="dropdown-item d-flex align-items-center gap-2"
         href="<?php echo EventbookingHelperHtml::getAddToGoogleCalendarUrl($item); ?>" target="_blank">
        <i class="fa-brands fa-google"></i>
        <span><?php echo Text::_('EB_GOOGLE_CALENDAR'); ?></span>
      </a>
    </li>
    <li>
      <a class="dropdown-item d-flex align-items-center gap-2"
         href="<?php echo EventbookingHelperHtml::getAddToYahooCalendarUrl($item); ?>" target="_blank">
        <i class="fa-brands fa-yahoo"></i>
        <span><?php echo Text::_('EB_YAHOO_CALENDAR'); ?></span>
      </a>
    </li>
    <li><hr class="dropdown-divider"></li>
    <li>
      <a class="dropdown-item d-flex align-items-center gap-2"
         href="<?php echo Route::_('index.php?option=com_eventbooking&task=event.download_ical&event_id=' . $item->id . '&Itemid=' . $Itemid); ?>">
        <i class="fa-solid fa-download"></i>
        <span><?php echo Text::_('EB_DOWNLOAD_ICAL'); ?></span>
      </a>
    </li>
  </ul>
</div>

<script>
(function(){
  // Close other menus before opening a new one
  document.addEventListener('show.bs.dropdown', function (e) {
    document.querySelectorAll('.dropdown-menu.show').forEach(menu => {
      const inst = bootstrap.Dropdown.getInstance(menu.__originBtn);
      if (inst && menu !== e.target.nextElementSibling) inst.hide();
    });
  });

  // Move menu to body and position it by the button
  function placeMenu(btn, menu){
    const r = btn.getBoundingClientRect();
    const menuRect = menu.getBoundingClientRect();
    const gap = 8;

    let left = r.left;
    let top  = r.bottom + gap;

    // Keep inside viewport horizontally
    const vw = window.innerWidth;
    if (left + menuRect.width > vw - 8) left = Math.max(8, vw - menuRect.width - 8);

    // If near bottom, open upward
    if (top + menuRect.height > window.innerHeight - 8) {
      top = Math.max(8, r.top - gap - menuRect.height);
    }

    menu.style.position = 'fixed';
    menu.style.left = left + 'px';
    menu.style.top  = top  + 'px';
    menu.style.zIndex = 5000; // above cards
  }

  function onShow(e){
    const btn  = e.target;                  // the button
    const menu = btn.nextElementSibling;    // its menu

    // Remember origin
    menu.__originBtn = btn;
    menu.__originParent = menu.parentNode;

    // Move to body so overflow:hidden can't clip it
    document.body.appendChild(menu);
    // Ensure it's visible for size calculation
    menu.classList.add('show'); // Bootstrap adds this too, but ensure for first paint
    placeMenu(btn, menu);

    // Reposition on scroll/resize while open
    menu.__reposition = () => placeMenu(btn, menu);
    window.addEventListener('scroll', menu.__reposition, true);
    window.addEventListener('resize', menu.__reposition);
  }

  function onHide(e){
    const btn  = e.target;
    const menu = document.querySelector('.dropdown-menu.show');
    if (!menu) return;

    // restore to original DOM place
    if (menu.__originParent) {
      menu.__originParent.appendChild(menu);
    }
    // cleanup styles/listeners
    menu.style.position = '';
    menu.style.left = '';
    menu.style.top  = '';
    menu.style.zIndex = '';
    window.removeEventListener('scroll', menu.__reposition, true);
    window.removeEventListener('resize', menu.__reposition);
    delete menu.__reposition;
    delete menu.__originBtn;
    delete menu.__originParent;
  }

  document.addEventListener('shown.bs.dropdown', onShow);
  document.addEventListener('hide.bs.dropdown', onHide);

  // Also toggle via click so only its own menu opens
  document.querySelectorAll('.btn-savecalendar').forEach(btn=>{
    btn.addEventListener('click', function(e){
      e.preventDefault();
      const inst = bootstrap.Dropdown.getOrCreateInstance(this);
      inst.toggle();
    });
  });
})();
</script>
