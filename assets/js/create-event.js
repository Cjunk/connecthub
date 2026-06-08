(function () {
  const $ = id => document.getElementById(id);

  // Set min date to today (Sydney users — HTML date is local; server-side validates anyway)
  const dateEl = $('event_date');
  if (dateEl) dateEl.min = new Date().toISOString().split('T')[0];

  // Location dynamic fields
  const locType = $('locType'), vNameG = $('venue_name_group'), vAddrG = $('venue_address_group'), oLinkG = $('online_link_group');
  const vName = $('venue_name'), oLink = $('online_link');

  function updateVisibility() {
    const v = locType.value;
    if (vName) vName.removeAttribute('required');
    if (oLink) oLink.removeAttribute('required');

    if (v === 'in_person') {
      vNameG.style.display = vAddrG.style.display = 'block';
      oLinkG.style.display = 'none';
      if (vName) vName.setAttribute('required','required');
    } else if (v === 'online') {
      vNameG.style.display = vAddrG.style.display = 'none';
      oLinkG.style.display = 'block';
      if (oLink) oLink.setAttribute('required','required');
    } else {
      vNameG.style.display = vAddrG.style.display = oLinkG.style.display = 'block';
      if (vName) vName.setAttribute('required','required');
      if (oLink) oLink.setAttribute('required','required');
    }
  }
  if (locType) {
    locType.addEventListener('change', updateVisibility);
    updateVisibility();
  }

  // Top-of-card banner preview (hidden until file chosen)
  const fileInput = $('cover_image');
  const bannerWrap = $('bannerWrap');
  const bannerImg = $('bannerImg');
  let objectUrl = null;

  function showPreview(file) {
    if (!file) { bannerWrap.style.display = 'none'; return; }
    if (objectUrl) URL.revokeObjectURL(objectUrl);
    objectUrl = URL.createObjectURL(file);
    bannerImg.src = objectUrl;
    bannerWrap.style.display = 'block';
  }
  if (fileInput && bannerWrap && bannerImg) {
    fileInput.addEventListener('change', e => showPreview(e.target.files[0]));
  }

  // Submit feedback
  const form = $('eventForm');
  const submitBtn = $('submitBtn');
  const submitIcon = $('submitIcon');
  if (form && submitBtn && submitIcon) {
    form.addEventListener('submit', function () {
      submitBtn.disabled = true;
      submitIcon.classList.add('spin');
      submitBtn.innerHTML = '<i class="fas fa-sync-alt me-1 spin"></i> Creating...';
    });
  }
})();
