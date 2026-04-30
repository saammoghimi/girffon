document.addEventListener("DOMContentLoaded", () => {
  const aboutVideo = document.getElementById("aboutHeroVideo");
  if (!aboutVideo) {
    return;
  }

  aboutVideo.addEventListener("mouseenter", () => {
    aboutVideo.pause();
  });

  aboutVideo.addEventListener("mouseleave", () => {
    const playPromise = aboutVideo.play();
    if (playPromise && typeof playPromise.catch === "function") {
      playPromise.catch(() => {
        // Ignore autoplay restrictions after hover leave.
      });
    }
  });
});