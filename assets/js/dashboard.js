document.addEventListener('DOMContentLoaded', () => {
  const photos = window.dashboardHeroPhotos || [];
  const thumbs   = Array.from(document.querySelectorAll('.photo-pile a.polaroid'));
  const captions = Array.from(document.querySelectorAll('.photo-pile .caption')).map(c => c.textContent.trim() || 'Photo');

  // Lightbox elements
  const lb        = document.getElementById('ch-lightbox');
  const lbImg     = lb.querySelector('.ch-img');
  const lbClose   = lb.querySelector('.ch-close');
  const lbPrev    = lb.querySelector('.ch-prev');
  const lbNext    = lb.querySelector('.ch-next');
  const lbCap     = lb.querySelector('.ch-caption');
  const lbCount   = lb.querySelector('.ch-count');

  let idx = 0, open = false, touchStartX = 0;

  // Dynamic close button positioning to account for navbar height
  function positionCloseButton() {
    const navbar = document.querySelector('.navbar');
    if (navbar) {
      const navbarHeight = navbar.offsetHeight;
      lbClose.style.top = (navbarHeight + 10) + 'px'; // 10px padding below navbar
    }
  }

  function setImg(i){
    idx = (i + photos.length) % photos.length;
    lbImg.src = photos[idx];
    lbImg.alt = captions[idx] || 'Photo';
    lbCap.textContent = captions[idx] || 'Photo';
    lbCount.textContent = (idx+1) + ' / ' + photos.length;
  }

  function show(i){
    setImg(i);
    positionCloseButton(); // Position button before showing
    lb.classList.remove('ch-hidden');
    document.body.style.overflow = 'hidden';
    open = true;
  }
  function hide(){
    lb.classList.add('ch-hidden');
    document.body.style.overflow = '';
    lbImg.removeAttribute('src');
    open = false;
  }

  // Position button on window resize in case navbar height changes
  window.addEventListener('resize', () => {
    if (open) positionCloseButton();
  });

  // Open on thumbnail click
  thumbs.forEach((a, i) => {
    a.addEventListener('click', (e) => {
      e.preventDefault();
      show(i);
    });
  });

  // Controls
  lbClose.addEventListener('click', hide);
  lbPrev.addEventListener('click', () => setImg(idx-1));
  lbNext.addEventListener('click', () => setImg(idx+1));

  // Click outside image to close
  lb.addEventListener('click', (e) => {
    if (e.target === lb) hide();
  });

  // Keyboard
  document.addEventListener('keydown', (e) => {
    if (!open) return;
    if (e.key === 'Escape') hide();
    if (e.key === 'ArrowLeft') setImg(idx-1);
    if (e.key === 'ArrowRight') setImg(idx+1);
  });

  // Basic swipe (mobile)
  lb.addEventListener('touchstart', (e) => { touchStartX = e.changedTouches[0].clientX; }, {passive:true});
  lb.addEventListener('touchend', (e) => {
    const dx = e.changedTouches[0].clientX - touchStartX;
    if (Math.abs(dx) > 40) (dx > 0 ? setImg(idx-1) : setImg(idx+1));
  }, {passive:true});

  // Preload next/prev for snappier nav
  function preload(src){ const i = new Image(); i.src = src; }
  lbNext.addEventListener('click', () => preload(photos[(idx+1)%photos.length]));
  lbPrev.addEventListener('click', () => preload(photos[(idx-1+photos.length)%photos.length]));
});
