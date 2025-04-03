<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Banner Slider</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <style>
    .banner-transition {
      transition: opacity 0.5s ease-in-out;
      position: absolute;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
    }

    .banner-active {
      opacity: 1;
      z-index: 10;
      pointer-events: auto;
    }

    .banner-inactive {
      opacity: 0;
      z-index: 0;
      pointer-events: none;
    }

    .dots {
        background-color: #4ECDC4;
    }
    .dots:hover {
        background-color: #44A9A2;
    }
  </style>
</head>

<body class="bg-gray-100">
  <div class="px-[20%] pt-24">
    <div id="banner-container" class="relative w-full h-screen md:h-96 overflow-hidden rounded-2xl my-5">
       <a id="banner-0" class="banner-transition banner-active cursor-pointer" data-link="/category/collections/anime-mood/one-piece">
            <img src="../images/banners/banner0.webp" alt="Banner 0" class="w-full h-full object-cover rounded-2xl">
        </a>
        <a id="banner-1" class="banner-transition banner-inactive cursor-pointer" data-link="/category/limited">
            <img src="../images/banners/banner1.webp" alt="Banner 1" class="w-full h-full object-cover rounded-2xl">
        </a>
        <a id="banner-2" class="banner-transition banner-inactive cursor-pointer" data-link="https://www.example.com/banner2">
            <img src="../images/banners/banner2.webp" alt="Banner 2" class="w-full h-full object-cover rounded-2xl">
        </a>

      <div class="absolute top-1/2 left-0 w-full -translate-y-1/2 flex justify-between px-4 z-20">
        <button id="prev-btn" class="text-2xl rounded-full p-0 w-10 h-10 flex items-center justify-center bg-white bg-opacity-50 hover:bg-opacity-75 transition-all">
        ❮
        </button>
        <button id="next-btn" class="text-2xl rounded-full p-0 w-10 h-10 flex items-center justify-center bg-white bg-opacity-50 hover:bg-opacity-75 transition-all">
        ❯
        </button>
      </div>

      <div class="absolute bottom-4 left-0 w-full flex justify-center space-x-2 z-20">
        <div id="dot-0" class="dots w-3 h-3 rounded-full dots cursor-pointer"></div>
        <div id="dot-1" class="dots w-3 h-3 rounded-full bg-gray-500 cursor-pointer"></div>
        <div id="dot-2" class="dots w-3 h-3 rounded-full bg-gray-500 cursor-pointer"></div>
      </div>
    </div>
  </div>

<script>
document.addEventListener('DOMContentLoaded', function () {
  const banners = document.querySelectorAll('[id^="banner-"]');
  const dots = document.querySelectorAll('[id^="dot-"]');
  const prevBtn = document.getElementById('prev-btn');
  const nextBtn = document.getElementById('next-btn');

  let currentIndex = 0;
  const bannerCount = 3; // Явно задаем количество баннеров
  let autoplayInterval;

  function updateBanners(newIndex) {
    // Проверяем, что новый индекс в допустимом диапазоне
    if (newIndex >= bannerCount) {
      newIndex = 0;
    }
    if (newIndex < 0) {
      newIndex = bannerCount - 1;
    }

    // Обновляем классы баннеров
    for (let i = 0; i < bannerCount; i++) {
      const banner = document.getElementById(`banner-${i}`);
      if (i === newIndex) {
        banner.classList.remove('banner-inactive');
        banner.classList.add('banner-active');
      } else {
        banner.classList.remove('banner-active');
        banner.classList.add('banner-inactive');
      }
    }

    // Обновляем точки навигации
    for (let i = 0; i < bannerCount; i++) {
      const dot = document.getElementById(`dot-${i}`);
      if (i === newIndex) {
        dot.classList.remove('bg-gray-500');
        dot.classList.add('dots');
      } else {
        dot.classList.remove('dots');
        dot.classList.add('bg-gray-500');
      }
    }

    currentIndex = newIndex;
  }

  function nextSlide() {
    const nextIndex = (currentIndex + 1) % bannerCount;
    updateBanners(nextIndex);
  }

  function prevSlide() {
    const prevIndex = (currentIndex - 1 + bannerCount) % bannerCount;
    updateBanners(prevIndex);
  }

  function startAutoplay() {
    stopAutoplay();
    autoplayInterval = setInterval(nextSlide, 12000);
  }

  function stopAutoplay() {
    if (autoplayInterval) {
      clearInterval(autoplayInterval);
    }
  }

  // Обработчики событий
  nextBtn.addEventListener('click', () => {
    nextSlide();
    startAutoplay();
  });

  prevBtn.addEventListener('click', () => {
    prevSlide();
    startAutoplay();
  });

  dots.forEach((dot, index) => {
    // Проверяем, что индекс в допустимом диапазоне
    if (index < bannerCount) {
      dot.addEventListener('click', () => {
        updateBanners(index);
        startAutoplay();
      });
    }
  });

banners.forEach((banner) => {
    banner.addEventListener('click', () => {
      const link = banner.getAttribute('data-link');
      if (link) {
        window.location.href = link;
      }
    });
  });

  const bannerContainer = document.getElementById('banner-container');
  bannerContainer.addEventListener('mouseenter', stopAutoplay);
  bannerContainer.addEventListener('mouseleave', startAutoplay);

  // Начинаем с первого баннера
  updateBanners(0);
  startAutoplay();
});
</script>


</body>

</html>
