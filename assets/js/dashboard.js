document.addEventListener('DOMContentLoaded', () => {
  const thumbs = Array.from(document.querySelectorAll('.photo-pile a.polaroid'));
  const photos = window.dashboardHeroPhotos?.length
    ? window.dashboardHeroPhotos
    : thumbs.map(a => a.getAttribute('href'));

  const captions = Array.from(document.querySelectorAll('.photo-pile .caption'))
    .map(c => c.textContent.trim() || 'Photo');

  const lb = document.getElementById('ch-lightbox');
  if (!lb || !photos.length) return;

  const lbImg = document.getElementById('ch-lightbox-img') || lb.querySelector('.ch-img');
  const lbClose = lb.querySelector('.ch-close');
  const lbPrev = lb.querySelector('.ch-prev');
  const lbNext = lb.querySelector('.ch-next');
  const lbCap = document.getElementById('ch-lightbox-caption') || lb.querySelector('.ch-caption');
  const lbCount = lb.querySelector('.ch-count');

  if (!lbImg || !lbClose || !lbPrev || !lbNext) return;

  let idx = 0;
  let open = false;
  let touchStartX = 0;

  function positionCloseButton() {
    const navbar = document.querySelector('.navbar');
    if (navbar && lbClose) {
      lbClose.style.top = `${navbar.offsetHeight + 10}px`;
    }
  }

  function setImg(i) {
    idx = (i + photos.length) % photos.length;

    lbImg.src = photos[idx] || '';
    lbImg.alt = captions[idx] || 'Photo';

    if (lbCap) {
      lbCap.textContent = captions[idx] || 'Photo';
    }

    if (lbCount) {
      lbCount.textContent = `${idx + 1} / ${photos.length}`;
    }
  }

  function show(i) {
    setImg(i);
    positionCloseButton();
    lb.classList.remove('ch-hidden');
    document.body.style.overflow = 'hidden';
    open = true;
  }

  function hide() {
    lb.classList.add('ch-hidden');
    document.body.style.overflow = '';
    lbImg.removeAttribute('src');
    open = false;
  }

  thumbs.forEach((a, i) => {
    a.addEventListener('click', (e) => {
      e.preventDefault();
      show(i);
    });
  });

  lbClose.addEventListener('click', hide);
  lbPrev.addEventListener('click', () => setImg(idx - 1));
  lbNext.addEventListener('click', () => setImg(idx + 1));

  lb.addEventListener('click', (e) => {
    if (e.target === lb) hide();
  });

  document.addEventListener('keydown', (e) => {
    if (!open) return;

    if (e.key === 'Escape') hide();
    if (e.key === 'ArrowLeft') setImg(idx - 1);
    if (e.key === 'ArrowRight') setImg(idx + 1);
  });

  lb.addEventListener('touchstart', (e) => {
    touchStartX = e.changedTouches[0].clientX;
  }, { passive: true });

  lb.addEventListener('touchend', (e) => {
    const dx = e.changedTouches[0].clientX - touchStartX;
    if (Math.abs(dx) > 40) {
      dx > 0 ? setImg(idx - 1) : setImg(idx + 1);
    }
  }, { passive: true });

  function preload(src) {
    if (!src) return;
    const image = new Image();
    image.src = src;
  }

  lbNext.addEventListener('click', () => preload(photos[(idx + 1) % photos.length]));
  lbPrev.addEventListener('click', () => preload(photos[(idx - 1 + photos.length) % photos.length]));

  window.addEventListener('resize', () => {
    if (open) positionCloseButton();
  });
});
