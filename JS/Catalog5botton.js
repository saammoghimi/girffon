document.addEventListener("DOMContentLoaded", () => {
  const cards = document.querySelectorAll(".category-card");

  cards.forEach((card) => {
    const video = card.querySelector(".card-video");
    if (!video) return;

    let locked = false;

    card.addEventListener("mouseenter", () => {
      if (!locked) {
        card.classList.add("active-video");
        video.currentTime = 0;
        video.play().catch(() => {});
      }
    });

    card.addEventListener("mouseleave", () => {
      if (!locked) {
        card.classList.remove("active-video");
        video.pause();
        video.currentTime = 0;
      }
    });

    card.addEventListener("click", () => {
      locked = !locked;

      if (locked) {
        card.classList.add("active-video");
        video.play().catch(() => {});
      } else {
        card.classList.remove("active-video");
        video.pause();
        video.currentTime = 0;
      }
    });
  });
});