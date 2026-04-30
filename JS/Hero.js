const slides = document.querySelectorAll(".slide");
const dots = document.querySelectorAll(".dot");
const videos = document.querySelectorAll(".slide video");

let currentSlide = 0;
let sliderInterval;
const slideDuration = 5900; // 5.95 ثانیه

function resetVideos() {
  videos.forEach((video, i) => {
    video.pause();
    video.currentTime = 0;

    if (i === currentSlide) {
      video.play().catch(() => {});
    }
  });
}

function showSlide(index) {
  slides.forEach((slide, i) => {
    slide.classList.toggle("active", i === index);
    dots[i].classList.toggle("active", i === index);
  });

  currentSlide = index;
  resetVideos();
}

function nextSlide() {
  let next = currentSlide + 1;
  if (next >= slides.length) next = 0;
  showSlide(next);
}

function startSlider() {
  clearInterval(sliderInterval);
  sliderInterval = setInterval(nextSlide, slideDuration);
}

function resetSlider() {
  startSlider();
}

dots.forEach((dot, index) => {
  dot.addEventListener("click", () => {
    showSlide(index);
    resetSlider();
  });
});

showSlide(0);
startSlider();


document.querySelectorAll(".slide video").forEach((video) => {
  video.parentElement.addEventListener("mouseenter", () => {
    video.pause();
  });

  video.parentElement.addEventListener("mouseleave", () => {
    if (video.parentElement.classList.contains("active")) {
      video.play().catch(() => {});
    }
  });
});