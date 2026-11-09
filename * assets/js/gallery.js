/**
 * NSBM ArtCraft - Gallery Controller
 * Dynamic filtering, live search, sorting, liking, and star ratings
 */

const Gallery = (function() {
  let currentArtworks = [];
  let currentCategory = '';
  let currentSearch = '';
  let currentSort = 'newest';

  async function loadCategories(containerId = 'category-tabs-container') {
    const container = document.getElementById(containerId);
    if (!container) return;

    try {
      const res = await fetch('api/categories.php?action=list');
      const data = await res.json();
      if (data.status === 'success') {
        const categories = data.data.categories || [];
        
        // Read URL param if any
        const urlParams = new URLSearchParams(window.location.search);
        currentCategory = urlParams.get('category') || '';

        let html = `
          <button class="cat-tab ${currentCategory === '' ? 'active' : ''}" onclick="Gallery.filterCategory('')">
            All Creations
          </button>
        `;

        categories.forEach(c => {
          const isActive = currentCategory === c.slug;
          html += `
            <button class="cat-tab ${isActive ? 'active' : ''}" onclick="Gallery.filterCategory('${c.slug}')">
              ${c.name} (${c.artworks_count})
            </button>
          `;
        });

        container.innerHTML = html;
      }
    } catch (e) {
      console.error('Error loading categories:', e);
    }
  }

  async function loadArtworks(gridId = 'artworks-grid') {
    const grid = document.getElementById(gridId);
    if (!grid) return;

    grid.innerHTML = `
      <div style="grid-column: 1 / -1; text-align: center; padding: 60px 20px;">
        <div style="display: inline-block; width: 40px; height: 40px; border: 3px solid var(--nsbm-green-subtle); border-top-color: var(--nsbm-green); border-radius: 50%; animation: spin 0.8s linear infinite;"></div>
        <p style="margin-top: 14px; color: var(--text-muted); font-weight: 500;">Loading artworks & handcrafted items...</p>
      </div>
      <style>@keyframes spin { to { transform: rotate(360deg); } }</style>
    `;

    try {
      let url = `api/artworks.php?action=list&status=approved&sort=${currentSort}`;
      if (currentCategory) {
        url += `&category=${encodeURIComponent(currentCategory)}`;
      }
      if (currentSearch) {
        url += `&search=${encodeURIComponent(currentSearch)}`;
      }

      const res = await fetch(url);
      const data = await res.json();

      if (data.status === 'success') {
        currentArtworks = data.data.artworks || [];
        renderArtworkGrid(currentArtworks, grid);
      } else {
        grid.innerHTML = `<div style="grid-column: 1 / -1; text-align: center; color: var(--color-danger); padding: 40px;">${data.message || 'Error loading artworks.'}</div>`;
      }
    } catch (e) {
      grid.innerHTML = `<div style="grid-column: 1 / -1; text-align: center; color: var(--color-danger); padding: 40px;">Failed to connect to backend server.</div>`;
    }
  }

  function renderArtworkGrid(artworks, container) {
    if (!artworks || artworks.length === 0) {
      container.innerHTML = `
        <div style="grid-column: 1 / -1; text-align: center; padding: 60px 20px; background: #fff; border-radius: var(--radius-md); border: 1px dashed var(--border-color);">
          <div style="font-size: 2.5rem; margin-bottom: 12px;">🎨</div>
          <h3 style="color: var(--nsbm-blue); font-size: 1.25rem; font-weight: 700;">No artworks found</h3>
          <p style="color: var(--text-muted); max-width: 400px; margin: 8px auto 20px;">
            No items matched your current filter or search criteria. Try selecting another category or check back soon!
          </p>
          <a href="upload-artwork.html" class="btn btn-primary btn-sm">Be the first to submit in this category</a>
        </div>
      `;
      return;
    }

    let html = '';
    artworks.forEach(art => {
      const isLiked = art.is_liked;
      const avgRate = Number(art.avg_rating).toFixed(1);
      const starsDisplay = '★'.repeat(Math.round(avgRate)) + '☆'.repeat(5 - Math.round(avgRate));

      html += `
        <div class="card artwork-card">
          <div class="card-img-wrap">
            <a href="artwork-detail.html?id=${art.id}">
              <img src="${art.image_path}" class="card-img-top" alt="${art.title}" loading="lazy">
            </a>
            <span class="card-badge ${art.is_featured == 1 ? 'featured' : ''}">
              ${art.is_featured == 1 ? '★ Featured' : art.category_name}
            </span>
          </div>

          <div class="card-body">
            <a href="artwork-detail.html?id=${art.id}">
              <h3 class="card-title">${art.title}</h3>
            </a>
            <p class="card-text">${art.description ? (art.description.length > 85 ? art.description.substring(0, 85) + '...' : art.description) : ''}</p>
            
            <div style="margin-bottom: 12px; display: flex; gap: 6px; flex-wrap: wrap;">
              <span class="chip chip-green">${art.medium}</span>
              ${art.dimensions !== 'N/A' ? `<span class="chip chip-blue">${art.dimensions}</span>` : ''}
            </div>

            <div class="card-footer">
              <a href="artist-profile.html?id=${art.user_id}" class="artist-pill">
                <img src="${art.artist_avatar || 'assets/images/avatar-default.png'}" alt="${art.artist_name}">
                <span>${art.artist_name}</span>
              </a>

              <div class="card-metrics">
                <button class="metric-btn ${isLiked ? 'liked' : ''}" onclick="Gallery.toggleLike(${art.id}, this)">
                  <svg width="16" height="16" viewBox="0 0 24 24" fill="${isLiked ? 'currentColor' : 'none'}" stroke="currentColor" stroke-width="2"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path></svg>
                  <span class="like-cnt">${art.likes_count}</span>
                </button>

                <div class="rating-stars" title="Rating: ${avgRate} / 5 (${art.ratings_count} votes)">
                  <span style="color: var(--color-gold); font-size: 0.95rem;">★</span>
                  <span style="font-weight: 700; color: var(--text-main); font-size: 0.82rem;">${avgRate}</span>
                </div>
              </div>
            </div>
          </div>
        </div>
      `;
    });

    container.innerHTML = html;
  }

  function filterCategory(slug) {
    currentCategory = slug;
    
    // Update active tab styles
    const tabs = document.querySelectorAll('.cat-tab');
    tabs.forEach(t => t.classList.remove('active'));
    event.currentTarget.classList.add('active');

    loadArtworks();
  }

  function handleSearch(term) {
    currentSearch = term.trim();
    loadArtworks();
  }

  function handleSort(sortVal) {
    currentSort = sortVal;
    loadArtworks();
  }

  async function toggleLike(artId, btnElement) {
    const user = App.getUser();
    if (!user) {
      App.showToast('Please login to like artworks.', 'warning');
      setTimeout(() => { window.location.href = 'login.html'; }, 1000);
      return;
    }

    try {
      const res = await fetch('api/interactions.php?action=like_toggle', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ artwork_id: artId })
      });
      const data = await res.json();
      if (data.status === 'success') {
        const isLiked = data.data.is_liked;
        const cnt = data.data.likes_count;

        btnElement.classList.toggle('liked', isLiked);
        const svg = btnElement.querySelector('svg');
        if (svg) svg.setAttribute('fill', isLiked ? 'currentColor' : 'none');
        const countSpan = btnElement.querySelector('.like-cnt');
        if (countSpan) countSpan.textContent = cnt;

        App.showToast(data.message, 'success');
      } else {
        App.showToast(data.message, 'error');
      }
    } catch (e) {
      App.showToast('Failed to record like.', 'error');
    }
  }

  return {
    loadCategories,
    loadArtworks,
    filterCategory,
    handleSearch,
    handleSort,
    toggleLike
  };
})();
