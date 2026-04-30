document.addEventListener("DOMContentLoaded", () => {
  const banners = document.querySelectorAll(".wide-banner");

  banners.forEach((banner) => {
    const video = banner.querySelector(".banner-video");
    if (!video) return;

    let pauseTimeout;

    // فقط 5 ثانیه اول
    video.addEventListener("timeupdate", () => {
      if (video.currentTime >= 5) {
        video.currentTime = 0;
        video.play();
      }
    });

    // وقتی موس روی همان بنر می‌رود
    banner.addEventListener("mouseenter", () => {
      video.pause();

      clearTimeout(pauseTimeout);

      pauseTimeout = setTimeout(() => {
        video.play();
      }, 1000);
    });

    // وقتی روی همان بنر کلیک می‌شود
    banner.addEventListener("click", () => {
      video.pause();

      clearTimeout(pauseTimeout);

      pauseTimeout = setTimeout(() => {
        video.play();
      }, 100);
    });
  });
});