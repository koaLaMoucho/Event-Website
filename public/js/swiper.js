swiper = new Swiper("#event-swiper",{
    slidesPerView: 1.5,
    centeredSlides: true,
    lazyLoading: true,
    loop: false,
    keyboard: {
        enabled: true
    },
    navigation: {
        nextEl: "#nav-right",
        prevEl: "#nav-left"
    },
    pagination: {
        el: ("#event-swiper .swiper-custom-pagination"),
        clickable: true,
        renderBullet: function (index, className) {
        return `<div class=${className}>
            <span class="number">${index + 1}</span>
            <span class="line"></span>
            </div>`;
        }
    }
});


function deleteImage(deleteIcon) {
    const eventImageId = deleteIcon.getAttribute('data-event-image-id');
    const data = {
      event_image_id: eventImageId,
    };
    sendAjaxRequest('DELETE', `/delete/event_image/${eventImageId}`, null, function () {
      if (this.status === 200) {
        const responseData = JSON.parse(this.responseText);
  
        const swiperContainer = document.getElementById('event-swiper');
        const swiperSlides = swiperContainer.querySelectorAll('.swiper-slide');
        let deletedSlideIndex = -1;
  
        swiperSlides.forEach((slide, index) => {
          const imageId = slide.querySelector('img').getAttribute('data-event-image-id');
          if (imageId === eventImageId) {
            slide.parentNode.removeChild(slide);
            swiper.removeSlide(index);
          }
        });
  
        const imageToRemove = deleteIcon.closest('.image-container');
        imageToRemove.parentNode.removeChild(imageToRemove);
      } else {
        console.error('Error deleting image:', this.statusText);
      }
    });
  }
