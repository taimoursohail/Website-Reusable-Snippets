/**
 * Slippery Smooth Scroll
 * Vanilla JS - No dependencies
 * Works on mouse wheel (desktop) and touch (mobile)
 */

(function () {
  let currentScroll = window.pageYOffset;
  let targetScroll = currentScroll;
  let ease = 0.08; // smaller = more slippery
  let ticking = false;

  // Update scroll target on wheel
  window.addEventListener("wheel", (e) => {
    targetScroll += e.deltaY;
    clampTarget();
    requestTick();
  }, { passive: false });

  // Update scroll target on touch
  let touchStartY = 0;
  window.addEventListener("touchstart", (e) => {
    touchStartY = e.touches[0].clientY;
  }, { passive: true });

  window.addEventListener("touchmove", (e) => {
    let touchY = e.touches[0].clientY;
    let deltaY = touchStartY - touchY;
    touchStartY = touchY;
    targetScroll += deltaY * 2; // amplify touch scroll
    clampTarget();
    requestTick();
  }, { passive: false });

  function clampTarget() {
    const maxScroll = document.body.scrollHeight - window.innerHeight;
    if (targetScroll < 0) targetScroll = 0;
    if (targetScroll > maxScroll) targetScroll = maxScroll;
  }

  function requestTick() {
    if (!ticking) {
      requestAnimationFrame(updateScroll);
      ticking = true;
    }
  }

  function updateScroll() {
    currentScroll += (targetScroll - currentScroll) * ease;
    window.scrollTo(0, currentScroll);

    if (Math.abs(targetScroll - currentScroll) > 0.5) {
      requestAnimationFrame(updateScroll);
    } else {
      ticking = false;
    }
  }

  console.log("Slippery scroll enabled");
})();
