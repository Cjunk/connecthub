document.addEventListener('DOMContentLoaded', () => {
  const feedEl = document.getElementById('dashboard-live-feed');

  if (!feedEl) return;

  let latestId = 0;

  function escapeHtml(value) {
    return String(value ?? '')
      .replaceAll('&', '&amp;')
      .replaceAll('<', '&lt;')
      .replaceAll('>', '&gt;')
      .replaceAll('"', '&quot;')
      .replaceAll("'", '&#039;');
  }

  function iconFor(item) {
    const map = {
      event_created: 'fa-calendar-plus',
      photo_uploaded: 'fa-camera',
      comment_created: 'fa-comment',
      group_created: 'fa-users',
      member_joined: 'fa-user-plus'
    };

    return map[item.activity_type] || 'fa-bolt';
  }

  function colorFor(item) {
    const map = {
      event_created: 'bg-primary',
      photo_uploaded: 'bg-warning',
      comment_created: 'bg-success',
      group_created: 'bg-info',
      member_joined: 'bg-secondary'
    };

    return map[item.activity_type] || 'bg-primary';
  }

  function renderItem(item) {
    const id = Number(item.id || 0);
    if (id > latestId) latestId = id;

    const imageHtml = item.image_url
      ? `<div class="feed-image mt-2"><img src="${escapeHtml(item.image_url)}" alt=""></div>`
      : '';

    return `
      <div class="feed-item" data-feed-id="${id}">
        <div class="feed-icon ${colorFor(item)}">
          <i class="fas ${iconFor(item)}"></i>
        </div>
        <div class="feed-content">
          <strong>${escapeHtml(item.title)}</strong>
          <div class="text-muted small">${escapeHtml(item.message)}</div>
          ${imageHtml}
          <div class="small text-muted mt-1">${escapeHtml(item.created_at)}</div>
        </div>
      </div>
    `;
  }

  async function loadFeed() {
    try {
      const response = await fetch('/api/activity-feed.php?limit=20', {
        headers: { 'Accept': 'application/json' }
      });

      const data = await response.json();

      if (!data.success || !Array.isArray(data.items)) {
        feedEl.innerHTML = '<p class="text-muted mb-0">Could not load activity feed.</p>';
        return;
      }

      if (data.items.length === 0) {
        feedEl.innerHTML = '<p class="text-muted mb-0">No activity yet.</p>';
        return;
      }

      feedEl.innerHTML = data.items.map(renderItem).join('');
    } catch (error) {
      feedEl.innerHTML = '<p class="text-muted mb-0">Activity feed temporarily unavailable.</p>';
    }
  }

  loadFeed();
  setInterval(loadFeed, 30000);
});
