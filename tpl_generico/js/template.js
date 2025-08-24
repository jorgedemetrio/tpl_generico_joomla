$(document).ready(function(){document.addEventListener('DOMContentLoaded', function () {
  const header = document.getElementById('header');
  const mainContent = document.getElementById('main-content');

  if (header && mainContent && header.classList.contains('sticky-top')) {
    const setMainContentPadding = () => {
      const headerHeight = header.offsetHeight;
      mainContent.style.paddingTop = `${headerHeight}px`;
    };

    // Set padding on initial load
    setMainContentPadding();

    // Adjust padding on window resize
    window.addEventListener('resize', setMainContentPadding);
  }
});
});