const slider = document.querySelector(".drag-scroll");

let isDown = false;
let startX;
let scrollLeft;

if (slider) {
  slider.addEventListener("mousedown", (e) => {
    isDown = true;
    slider.classList.add("active");
    startX = e.pageX - slider.offsetLeft;
    scrollLeft = slider.scrollLeft;
  });

  slider.addEventListener("mouseleave", () => {
    isDown = false;
  });

  slider.addEventListener("mouseup", () => {
    isDown = false;
  });

  slider.addEventListener("mousemove", (e) => {
    if (!isDown) return;
    e.preventDefault();
    const x = e.pageX - slider.offsetLeft;
    const walk = (x - startX) * 2;
    slider.scrollLeft = scrollLeft - walk;
  });

  slider.addEventListener("touchstart", (e) => {
    isDown = true;
    startX = e.touches[0].pageX;
    scrollLeft = slider.scrollLeft;
  });

  slider.addEventListener("touchend", () => {
    isDown = false;
  });

  slider.addEventListener("touchmove", (e) => {
    if (!isDown) return;
    const x = e.touches[0].pageX;
    const walk = (x - startX) * 2;
    slider.scrollLeft = scrollLeft - walk;
  });
}