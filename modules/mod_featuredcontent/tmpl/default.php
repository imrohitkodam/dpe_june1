<?php
/**
 * @package     Joomla.Site
 * @subpackage  mod_custom
 *
 * @copyright   (C) 2009 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */
defined('_JEXEC') or die;
use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Language\Text;
$modId = 'mod-custom' . $module->id;
$doc = Factory::getDocument();
$doc->addStyleSheet(Uri::base() . 'modules/mod_featuredcontent/css/featuredmodule.css');
?>
<?php if ($contentDatas) { ?>
    <div class='position-relative dash-sliders mb-4' id="<?php echo $modId; ?>">
      <div class="slider">
        <div class="slides-container">
          <?php
          foreach ($contentDatas as $contentData) {
            $title = $contentData->title;
            $image = json_decode($contentData->images);
            $introtext = $contentData->introtext;
          ?>
            <div class="slide">
              <div class='row sliderrow'>
                <!-- Column 1: Image -->
                <div class='col-md-auto img-slide-div'>
                  <div class="side-img-container">
                    <?php if ($image && isset($image->image_intro) && $image->image_intro) { ?>
                      <img src="<?php echo $image->image_intro; ?>" class="articleimg" alt="<?php echo htmlspecialchars($title); ?>">
                    <?php } else { ?>
                      <div class="articleimg-placeholder">
                         <i class="fa fa-warning"></i>
                      </div>
                    <?php } ?>
                  </div>
                </div>
                <!-- Column 2 & 3 wrapper -->
                <div class='col-md text-slide-div'>
                  <!-- Column 2: Content (Title & Text) -->
                  <div class="main-content-col">
                    <div class="contenttitle"><?php echo $title; ?></div>
                    <div class="introtext"><?php echo $introtext; ?></div>
                  </div>
                  <!-- Column 3: Read More -->
                  <div class="readmore-col">
                    <a class="readmore-link-sep" href="<?php echo $contentData->link; ?>" itemprop="url">
                      <?php echo Text::_('Read more') ?> &rarr;
                    </a>
                  </div>
                </div>
              </div>
            </div>
          <?php } ?>
        </div>
      </div>
      <div class="slider-controls">
        <div class="ctrl-btn prev-slide" id="prev"><i class="fa fa-angle-left"></i></div>
        <div class="ctrl-btn next-slide" id="next"><i class="fa fa-angle-right"></i></div>
      </div>
    </div>
<?php } ?>
<script>
    jQuery(document).ready(function($) {
      var $slider = $('#<?php echo $modId; ?>');
      var $container = $slider.find('.slides-container');
      var currentIndex = 0;
      var slideCount = $slider.find('.slide').length;
      function updateSlider() {
        var slideWidth = $slider.find('.slide').outerWidth();
        var translateValue = -currentIndex * slideWidth;
        $container.css('transform', 'translateX(' + translateValue + 'px)');
      }
      function nextSlide() {
        if (currentIndex < slideCount - 1) {
          currentIndex++;
        } else {
          currentIndex = 0;
        }
        updateSlider();
      }
      function prevSlide() {
        if (currentIndex > 0) {
          currentIndex--;
        } else {
          currentIndex = slideCount - 1;
        }
        updateSlider();
      }
      $slider.find('.next-slide').click(function(e) {
        e.preventDefault();
        e.stopPropagation();
        nextSlide();
      });
      $slider.find('.prev-slide').click(function(e) {
        e.preventDefault();
        e.stopPropagation();
        prevSlide();
      });
      $(window).resize(function() {
        updateSlider();
      });
      <?php if (isset($sliderTimer) && $sliderTimer > 0) { ?>
      function startAutoSlide() {
        return setInterval(function() {
          nextSlide();
        }, <?php echo (int)$sliderTimer; ?>);
      }
      var autoSlideInterval = startAutoSlide();
      $slider.hover(
        function() { clearInterval(autoSlideInterval); },
        function() { 
          clearInterval(autoSlideInterval);
          autoSlideInterval = startAutoSlide();
        }
      );
      <?php } ?>
    });
</script>