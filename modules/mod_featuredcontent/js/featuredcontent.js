// jQuery(document).ready(function () {
//   var currentIndex = 0;
//   var slideCount = jQuery('.slide').length;
//   var slideWidth = jQuery('.slide').width();

//   jQuery('.next-slide').click(function () {
//     showNextSlide();
//   });

//   jQuery('.prev-slide').click(function () {
//     showPrevSlide();
//   });

//   function showNextSlide() {
//     if (currentIndex < slideCount - 1) {
//       currentIndex++;
//     } else {
//       currentIndex = 0;
//     }
//     updateSlider();
//   }

//   function showPrevSlide() {
//     if (currentIndex > 0) {
//       currentIndex--;
//     } else {
//       currentIndex = slideCount - 1;
//     }
//     updateSlider();
//   }

//   function updateSlider() {
//     var translateValue = -currentIndex * slideWidth;
//     jQuery('.slides-container').css('transform', 'translateX(' + translateValue + 'px)');
//   }

//   function autoSlide() {
//     showNextSlide();
//   }

  
// });
