const video = document.querySelector(".banner-video");

let pauseTimeout;

if (video) {
  // محدود کردن ویدیو به 5 ثانیه
  video.addEventListener("timeupdate", () => {
    if (video.currentTime >= 5) {
      video.currentTime = 0;
      video.play();
    }
  });

  // وقتی موس میره روی بنر → توقف
  video.addEventListener("mouseenter", () => {
    video.pause();

    clearTimeout(pauseTimeout);

    pauseTimeout = setTimeout(() => {
      video.play();
    }, 1000);
  });

  // وقتی کلیک می‌کنه → توقف
  video.addEventListener("click", () => {
    video.pause();

    clearTimeout(pauseTimeout);

    pauseTimeout = setTimeout(() => {
      video.play();
    }, 100);
  });
}