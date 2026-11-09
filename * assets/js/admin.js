/**
 * NSBM ArtCraft - Admin Operations Controller
 * Metrics, Moderation Queue, Category & Exhibition Managers, and CSV Exports
 */

const Admin = (function() {
  let pendingList = [];
  let currentReviewId = null;

  async function init() {
    await App.checkAuth();
    const user = App.getUser();
    if (!user || user.role !== 'admin') {
      App.showToast('Administrator privileges required.', 'error');
      setTimeout(() => { window.location.href = 'login.html'; }, 800);
      return;
    }

    // Page-specific initializers
    if (document.getElementById('admin-summary-grid')) {
      loadDashboardMetrics();
    }
    if (document.getElementById('artworks-management-table')) {
      loadArtworksManagement();
    }
    if (document.getElementById('categories-management-table')) {
      loadCategoriesManagement();
    }
    if (document.getElementById('exhibitions-management-table')) {
      loadExhibitionsManagement();
    }
    if (document.getElementById('reports-overview')) {
      loadReportsData();
    }
  }

  // Load KPI Dashboard metrics
  async function loadDashboardMetrics() {
    try {
      const res = await fetch('api/reports.php?action=summary');
      const data = await res.json();
      if (data.status === 'success') {
        const stats = data.data.stats;
        
        document.getElementById('metric-total-art').textContent = stats.total_artworks;
        document.getElementById('metric-approved-art').textContent = stats.approved_artworks;
        document.getElementById('metric-pending-art').textContent = stats.pending_artworks;
        document.getElementById('metric-students').textContent = stats.total_students;
        document.getElementById('metric-views').textContent = stats.total_views;
        document.getElementById('metric-likes').textContent = stats.total_likes;

        renderPendingQueue(stats.pending_queue || []);
      }
    } catch (e) {
      console.error('Error loading dashboard metrics:', e);
    }
  }

  function renderPendingQueue(queue) {
    const tableBody = document.getElementById('pending-queue-body');
    if (!tableBody) return;

    if (queue.length === 0) {
      tableBody.innerHTML = `<tr><td colspan="6" style="text-align: center; color: var(--text-muted); padding: 30px;">All submissions reviewed! No pending artworks.</td></tr>`;
      return;
    }

    let html = '';
    queue.forEach(item => {
      html += `
        <tr>
          <td><img src="${item.image_path}" class="table-thumb" alt="${item.title}"></td>
          <td>
            <strong>${item.title}</strong><br>
            <span style="font-size: 0.78rem; color: var(--text-muted);">${item.category_name} &bull; ${item.medium}</span>
          </td>
          <td>
            ${item.artist_name}<br>
            <span style="font-size: 0.78rem; color: var(--text-muted);">${item.student_id || 'Student'}</span>
          </td>
          <td><span class="chip chip-warning">Pending Review</span></td>
          <td>${item.created_at.split(' ')[0]}</td>
          <td>
            <div style="display: flex; gap: 8px;">
              <button class="btn btn-primary btn-sm" onclick="Admin.reviewArtwork(${item.id}, 'approved')">Approve</button>
              <button class="btn btn-danger btn-sm" onclick="Admin.openRejectModal(${item.id})">Reject</button>
              <a href="artwork-detail.html?id=${item.id}" target="_blank" class="btn btn-outline btn-sm">Inspect</a>
            </div>
          </td>
        </tr>
      `;
    });
    tableBody.innerHTML = html;
  }

  // Load Artworks for the dedicated Admin Artworks manager
  async function loadArtworksManagement() {
    const tableBody = document.getElementById('artworks-management-body');
    if (!tableBody) return;

    try {
      const res = await fetch('api/artworks.php?action=list&status=all');
      const data = await res.json();
      if (data.status === 'success') {
        const artworks = data.data.artworks || [];
        let html = '';
        artworks.forEach(art => {
          let statusBadge = '<span class="chip chip-warning">Pending</span>';
          if (art.status === 'approved') statusBadge = '<span class="chip chip-green">Approved</span>';
          if (art.status === 'rejected') statusBadge = '<span class="chip chip-danger">Rejected</span>';

          html += `
            <tr>
              <td><img src="${art.image_path}" class="table-thumb" alt="${art.title}"></td>
              <td>
                <a href="artwork-detail.html?id=${art.id}" style="font-weight: 700; color: var(--nsbm-blue);">${art.title}</a><br>
                <span style="font-size: 0.78rem; color: var(--text-muted);">${art.category_name}</span>
              </td>
              <td>${art.artist_name}</td>
              <td>${statusBadge}</td>
              <td>
                <button class="btn btn-sm ${art.is_featured == 1 ? 'btn-primary' : 'btn-outline'}" onclick="Admin.toggleFeatured(${art.id}, this)">
                  ${art.is_featured == 1 ? '★ Featured' : 'Feature'}
                </button>
              </td>
              <td>
                <span title="Likes: ${art.likes_count}">❤️ ${art.likes_count}</span> &bull; 
                <span title="Rating: ${art.avg_rating}">⭐ ${art.avg_rating}</span>
              </td>
              <td>
                <div style="display: flex; gap: 6px;">
                  ${art.status !== 'approved' ? `<button class="btn btn-primary btn-sm" onclick="Admin.reviewArtwork(${art.id}, 'approved')">Approve</button>` : ''}
                  ${art.status !== 'rejected' ? `<button class="btn btn-danger btn-sm" onclick="Admin.openRejectModal(${art.id})">Reject</button>` : ''}
                  <button class="btn btn-outline btn-sm" style="color: var(--color-danger); border-color: var(--color-danger);" onclick="Admin.deleteArtwork(${art.id})">Delete</button>
                </div>
              </td>
            </tr>
          `;
        });
        tableBody.innerHTML = html;
      }
    } catch (e) {
      tableBody.innerHTML = `<tr><td colspan="7" style="color: var(--color-danger); text-align: center; padding: 20px;">Error loading artworks.</td></tr>`;
    }
  }

  // Review approval/rejection
  async function reviewArtwork(id, status, feedback = '') {
    try {
      const res = await fetch('api/artworks.php?action=review', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id, status, feedback })
      });
      const data = await res.json();
      if (data.status === 'success') {
        App.showToast(`Artwork successfully ${status}!`, 'success');
        closeRejectModal();
        if (document.getElementById('pending-queue-body')) loadDashboardMetrics();
        if (document.getElementById('artworks-management-body')) loadArtworksManagement();
      } else {
        App.showToast(data.message, 'error');
      }
    } catch (e) {
      App.showToast('Operation failed.', 'error');
    }
  }

  function openRejectModal(id) {
    currentReviewId = id;
    const modal = document.getElementById('reject-modal');
    if (modal) {
      modal.classList.add('active');
      document.getElementById('reject-reason-input').value = '';
    }
  }

  function closeRejectModal() {
    currentReviewId = null;
    const modal = document.getElementById('reject-modal');
    if (modal) modal.classList.remove('active');
  }

  function confirmReject() {
    if (!currentReviewId) return;
    const reason = document.getElementById('reject-reason-input').value.trim();
    reviewArtwork(currentReviewId, 'rejected', reason);
  }

  // Toggle Featured
  async function toggleFeatured(id, btn) {
    try {
      const res = await fetch('api/artworks.php?action=toggle_featured', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id })
      });
      const data = await res.json();
      if (data.status === 'success') {
        const isFeatured = data.data.is_featured;
        btn.className = `btn btn-sm ${isFeatured == 1 ? 'btn-primary' : 'btn-outline'}`;
        btn.textContent = isFeatured == 1 ? '★ Featured' : 'Feature';
        App.showToast(isFeatured == 1 ? 'Marked as Featured!' : 'Unfeatured.', 'success');
      }
    } catch (e) {
      App.showToast('Could not update featured status.', 'error');
    }
  }

  // Delete Artwork
  async function deleteArtwork(id) {
    if (!confirm('Are you sure you want to permanently delete this artwork? This cannot be undone.')) {
      return;
    }

    try {
      const res = await fetch('api/artworks.php?action=delete', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id })
      });
      const data = await res.json();
      if (data.status === 'success') {
        App.showToast('Artwork deleted successfully.', 'success');
        if (document.getElementById('artworks-management-body')) loadArtworksManagement();
        if (document.getElementById('pending-queue-body')) loadDashboardMetrics();
      } else {
        App.showToast(data.message, 'error');
      }
    } catch (e) {
      App.showToast('Failed to delete artwork.', 'error');
    }
  }

  // Categories CRUD
  async function loadCategoriesManagement() {
    const tableBody = document.getElementById('categories-management-body');
    if (!tableBody) return;

    try {
      const res = await fetch('api/categories.php?action=list');
      const data = await res.json();
      if (data.status === 'success') {
        const categories = data.data.categories || [];
        let html = '';
        categories.forEach(c => {
          html += `
            <tr>
              <td><img src="${c.icon_image}" style="width: 44px; height: 32px; border-radius: 4px; object-fit: cover;"></td>
              <td><strong>${c.name}</strong></td>
              <td><code>${c.slug}</code></td>
              <td style="font-size: 0.82rem; color: var(--text-muted);">${c.description || '-'}</td>
              <td><span class="chip chip-green">${c.artworks_count} Artworks</span></td>
              <td>
                <button class="btn btn-danger btn-sm" onclick="Admin.deleteCategory(${c.id})">Delete</button>
              </td>
            </tr>
          `;
        });
        tableBody.innerHTML = html;
      }
    } catch (e) {
      console.error(e);
    }
  }

  async function createCategory() {
    const name = document.getElementById('cat-name-input').value.trim();
    const description = document.getElementById('cat-desc-input').value.trim();
    const icon_image = document.getElementById('cat-icon-select').value;

    if (!name) {
      App.showToast('Please enter category name.', 'error');
      return;
    }

    try {
      const res = await fetch('api/categories.php?action=create', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ name, description, icon_image })
      });
      const data = await res.json();
      if (data.status === 'success') {
        App.showToast('Category created successfully!', 'success');
        document.getElementById('cat-name-input').value = '';
        document.getElementById('cat-desc-input').value = '';
        const modal = document.getElementById('create-category-modal');
        if (modal) modal.classList.remove('active');
        loadCategoriesManagement();
      } else {
        App.showToast(data.message, 'error');
      }
    } catch (e) {
      App.showToast('Failed to create category.', 'error');
    }
  }

  async function deleteCategory(id) {
    if (!confirm('Are you sure you want to delete this category?')) return;
    try {
      const res = await fetch(`api/categories.php?action=delete&id=${id}`, { method: 'POST' });
      const data = await res.json();
      if (data.status === 'success') {
        App.showToast('Category deleted.', 'success');
        loadCategoriesManagement();
      } else {
        App.showToast(data.message, 'error');
      }
    } catch (e) {
      App.showToast('Delete failed.', 'error');
    }
  }

  // Exhibitions CRUD
  async function loadExhibitionsManagement() {
    const tableBody = document.getElementById('exhibitions-management-body');
    if (!tableBody) return;

    try {
      const res = await fetch('api/exhibitions.php?action=list');
      const data = await res.json();
      if (data.status === 'success') {
        const exhibitions = data.data.exhibitions || [];
        let html = '';
        exhibitions.forEach(ex => {
          let statusBadge = `<span class="chip chip-blue">${ex.status}</span>`;
          if (ex.status === 'active') statusBadge = `<span class="chip chip-green">Active</span>`;
          if (ex.status === 'completed') statusBadge = `<span class="chip">Completed</span>`;

          html += `
            <tr>
              <td><img src="${ex.banner_image}" style="width: 60px; height: 35px; object-fit: cover; border-radius: 4px;"></td>
              <td>
                <a href="exhibition-detail.html?id=${ex.id}" style="font-weight: 700; color: var(--nsbm-blue);">${ex.title}</a><br>
                <span style="font-size: 0.78rem; color: var(--text-muted);">${ex.theme}</span>
              </td>
              <td>${ex.venue}</td>
              <td>${ex.start_date} to ${ex.end_date}</td>
              <td>${statusBadge}</td>
              <td><span class="chip chip-green">${ex.participants_count} Registered</span></td>
              <td>
                <button class="btn btn-danger btn-sm" onclick="Admin.deleteExhibition(${ex.id})">Delete</button>
              </td>
            </tr>
          `;
        });
        tableBody.innerHTML = html;
      }
    } catch (e) {
      console.error(e);
    }
  }

  async function createExhibition() {
    const payload = {
      title: document.getElementById('ex-title').value.trim(),
      theme: document.getElementById('ex-theme').value.trim(),
      venue: document.getElementById('ex-venue').value.trim(),
      start_date: document.getElementById('ex-start-date').value,
      end_date: document.getElementById('ex-end-date').value,
      description: document.getElementById('ex-desc').value.trim(),
      banner_image: document.getElementById('ex-banner-select').value,
      status: document.getElementById('ex-status').value
    };

    if (!payload.title || !payload.theme || !payload.venue || !payload.start_date || !payload.end_date) {
      App.showToast('Please fill all required exhibition fields.', 'error');
      return;
    }

    try {
      const res = await fetch('api/exhibitions.php?action=create', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload)
      });
      const data = await res.json();
      if (data.status === 'success') {
        App.showToast('Exhibition created and announced to all students!', 'success');
        const modal = document.getElementById('create-exhibition-modal');
        if (modal) modal.classList.remove('active');
        loadExhibitionsManagement();
      } else {
        App.showToast(data.message, 'error');
      }
    } catch (e) {
      App.showToast('Failed to create exhibition.', 'error');
    }
  }

  async function deleteExhibition(id) {
    if (!confirm('Are you sure you want to delete this exhibition?')) return;
    try {
      const res = await fetch(`api/exhibitions.php?action=delete&id=${id}`, { method: 'POST' });
      const data = await res.json();
      if (data.status === 'success') {
        App.showToast('Exhibition deleted.', 'success');
        loadExhibitionsManagement();
      } else {
        App.showToast(data.message, 'error');
      }
    } catch (e) {
      App.showToast('Failed to delete exhibition.', 'error');
    }
  }

  // Reports data
  async function loadReportsData() {
    try {
      const res = await fetch('api/reports.php?action=summary');
      const data = await res.json();
      if (data.status === 'success') {
        const stats = data.data.stats;

        // Render Category breakdown table
        const catBody = document.getElementById('report-category-body');
        if (catBody) {
          let html = '';
          (stats.category_stats || []).forEach(cs => {
            html += `
              <tr>
                <td><strong>${cs.name}</strong></td>
                <td>${cs.total_submissions}</td>
                <td><span class="chip chip-green">${cs.approved_count}</span></td>
                <td><span class="chip chip-warning">${cs.pending_count}</span></td>
              </tr>
            `;
          });
          catBody.innerHTML = html;
        }

        // Render Faculty breakdown table
        const facBody = document.getElementById('report-faculty-body');
        if (facBody) {
          let html = '';
          (stats.faculty_stats || []).forEach(fs => {
            html += `
              <tr>
                <td><strong>${fs.faculty}</strong></td>
                <td>${fs.student_count} Students</td>
                <td>${fs.artwork_count} Submissions</td>
              </tr>
            `;
          });
          facBody.innerHTML = html;
        }

        // Render Top Liked
        const topLikedContainer = document.getElementById('report-top-liked');
        if (topLikedContainer) {
          let html = '';
          (stats.top_liked || []).forEach((tl, idx) => {
            html += `
              <div style="display: flex; align-items: center; justify-content: space-between; padding: 10px 0; border-bottom: 1px solid var(--border-color);">
                <div style="display: flex; align-items: center; gap: 12px;">
                  <span style="font-weight: 800; color: var(--nsbm-green); width: 20px;">#${idx+1}</span>
                  <img src="${tl.image_path}" style="width: 40px; height: 40px; border-radius: 6px; object-fit: cover;">
                  <div>
                    <a href="artwork-detail.html?id=${tl.id}" style="font-weight: 700; color: var(--nsbm-blue); font-size: 0.9rem;">${tl.title}</a>
                    <div style="font-size: 0.78rem; color: var(--text-muted);">${tl.artist_name}</div>
                  </div>
                </div>
                <span class="chip chip-danger">❤️ ${tl.likes_count} likes</span>
              </div>
            `;
          });
          topLikedContainer.innerHTML = html;
        }
      }
    } catch (e) {
      console.error(e);
    }
  }

  return {
    init,
    reviewArtwork,
    openRejectModal,
    closeRejectModal,
    confirmReject,
    toggleFeatured,
    deleteArtwork,
    createCategory,
    deleteCategory,
    createExhibition,
    deleteExhibition
  };
})();

document.addEventListener('DOMContentLoaded', Admin.init);
