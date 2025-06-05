const textSlides = document.querySelectorAll('.text-slide');
    let currentTextSlide = 0;
    setInterval(() => {
      textSlides[currentTextSlide].classList.remove('active');
      currentTextSlide = (currentTextSlide + 1) % textSlides.length;
      textSlides[currentTextSlide].classList.add('active');
    }, 4000);

    const carouselItems = document.querySelectorAll('.carousel-item');
    let currentCarouselItem = 0, carouselInterval;
    function showCarouselItem(i) {
      carouselItems.forEach(item => item.classList.remove('active'));
      carouselItems[i].classList.add('active');
      currentCarouselItem = i;
    }
    function nextCarouselItem() {
      showCarouselItem((currentCarouselItem + 1) % carouselItems.length);
      resetCarouselInterval();
    }
    function prevCarouselItem() {
      showCarouselItem((currentCarouselItem - 1 + carouselItems.length) % carouselItems.length);
      resetCarouselInterval();
    }
    function resetCarouselInterval() {
      clearInterval(carouselInterval);
      carouselInterval = setInterval(nextCarouselItem, 3000);
    }
    document.querySelector('.next-btn').addEventListener('click', nextCarouselItem);
    document.querySelector('.prev-btn').addEventListener('click', prevCarouselItem);
    resetCarouselInterval();