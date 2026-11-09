/**
 * NSBM ArtCraft - Global Application Controller
 * Handles Navigation, Authentication State, Notifications, and Toasts
 */

const App = (function() {
  let currentUser = null;
  let unreadNotificationsCount = 0;

  // Base API Path
  const API_BASE = 'api';

  // Initialize Application
  async function init() {
    await checkAuth();
    renderHeader();
    renderFooter();
    renderFAB();
    initNotificationPoller();
  }

  // Check Current Authentication Session
  async function checkAuth() {
    try {
      const res = await fetch(`${API_BASE}/auth.php?action=check`);
      const data = await res.json();
      if (data.status === 'success' && data.data && data.data.user) {
        currentUser = data.data.user;
      } else {
        currentUser = null;
      }
    } catch (e) {
      console.warn('Auth check error:', e);
      currentUser = null;
    }
  }

  // Get Current Logged-in User
  function getUser() {
    return currentUser;
  }

  // Render Consistent Header & Navbar
  function renderHeader() {
    const headerContainer = document.getElementById('app-header');
    if (!headerContainer) return;

    const currentPath = window.location.pathname.split('/').pop() || 'index.html';

    const isAdmin = currentUser && currentUser.role === 'admin';
    const isStudent = currentUser && currentUser.role === 'student';

    let userMenuHtml = '';
    if (currentUser) {
      userMenuHtml = `
        <div class="notif-wrapper" style="position: relative;">
          <button class="notif-btn" id="notif-bell-btn" title="Notifications" onclick="App.toggleNotifDropdown()">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path><path d="M13.73 21a2 2 0 0 1-3.46 0"></path></svg>
            <span class="notif-badge" id="app-notif-badge" style="display: ${unreadNotificationsCount > 0 ? 'inline-block' : 'none'};">${unreadNotificationsCount}</span>
          </button>
          
          <!-- Notifications Quick Dropdown -->
          <div class="dropdown-card" id="notif-dropdown" style="width: 320px; right: 0;">
            <div class="dropdown-header" style="display: flex; justify-content: space-between; align-items: center;">
              <div>
                <span class="u-name">Notifications</span>
                <span class="u-role" id="dropdown-unread-label" style="display: block;">0 unread</span>
              </div>
              <a href="notifications.html" style="font-size: 0.78rem; font-weight: 700; color: var(--nsbm-green);">View All</a>
            </div>
            <div id="notif-dropdown-list" style="max-height: 280px; overflow-y: auto;">
              <div style="padding: 16px; text-align: center; color: var(--text-muted); font-size: 0.85rem;">Loading...</div>
            </div>
          </div>
        </div>

        <div class="user-menu-wrapper">
          <button class="user-btn" onclick="App.toggleUserDropdown()">
            <img src="${currentUser.avatar || 'assets/images/avatar-default.png'}" class="avatar" alt="Avatar">
            <span class="name">${currentUser.name}</span>
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"></polyline></svg>
          </button>

          <div class="dropdown-card" id="user-dropdown">
            <div class="dropdown-header">
              <div class="u-name">${currentUser.name}</div>
              <div class="u-role">${currentUser.role === 'admin' ? 'University Curator (Admin)' : (currentUser.student_id || 'Student')}</div>
            </div>
            
            ${isAdmin ? `
              <a href="admin-dashboard.html" class="dropdown-item">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"></rect><rect x="14" y="3" width="7" height="7"></rect><rect x="14" y="14" width="7" height="7"></rect><rect x="3" y="14" width="7" height="7"></rect></svg>
                Admin Dashboard
              </a>
              <a href="admin-artworks.html" class="dropdown-item">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="12 2 2 7 12 12 22 7 12 2"></polygon><polyline points="2 17 12 22 22 17"></polyline><polyline points="2 12 12 17 22 12"></polyline></svg>
                Moderate Artworks
              </a>
              <a href="admin-reports.html" class="dropdown-item">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="20" x2="18" y2="10"></line><line x1="12" y1="20" x2="12" y2="4"></line><line x1="6" y1="20" x2="6" y2="14"></line></svg>
                Reports & Analytics
              </a>
            ` : `
              <a href="my-submissions.html" class="dropdown-item">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>
                My Submissions
              </a>
              <a href="artist-profile.html?id=${currentUser.id}" class="dropdown-item">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
                My Public Portfolio
              </a>
              <a href="upload-artwork.html" class="dropdown-item">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="17 8 12 3 7 8"></polyline><line x1="12" y1="3" x2="12" y2="15"></line></svg>
                Upload Artwork
              </a>
            `}
            <a href="notifications.html" class="dropdown-item">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path><path d="M13.73 21a2 2 0 0 1-3.46 0"></path></svg>
              Notification Center
            </a>
            <div class="dropdown-divider"></div>
            <a href="javascript:void(0)" onclick="App.logout()" class="dropdown-item" style="color: var(--color-danger);">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><polyline points="16 17 21 12 16 7"></polyline><line x1="21" y1="12" x2="9" y2="12"></line></svg>
              Logout
            </a>
          </div>
        </div>
      `;
    } else {
      userMenuHtml = `
        <a href="login.html" class="btn btn-outline btn-sm">Login</a>
        <a href="register.html" class="btn btn-primary btn-sm">Register</a>
      `;
    }

    headerContainer.innerHTML = `
      <!-- Topbar with Official University Meta -->
      <div class="nsbm-topbar">
        <div class="container">
          <div>
            <span>NSBM Green University | Faculty of Computing Showcase</span>
          </div>
          <div class="quick-links">
            <a href="https://www.nsbm.ac.lk" target="_blank" rel="noopener">NSBM Portal</a>
            <a href="exhibitions.html">Showcase Calendar</a>
            <a href="gallery.html">Explore Gallery</a>
          </div>
        </div>
      </div>

      <!-- Main Sticky App Navigation -->
      <nav class="nsbm-navbar">
        <div class="container nav-wrapper">
          <a href="index.html" class="nav-brand">
            <img src="assets/images/nsbm-logo.png" alt="NSBM Official Logo" class="logo">
            <div class="brand-text">
              <span class="title">NSBM <span>ArtCraft</span></span>
              <span class="sub">Student Arts & Handcraft System</span>
            </div>
          </a>

          <ul class="nav-menu">
            <li class="nav-item">
              <a href="index.html" class="nav-link ${currentPath === 'index.html' || currentPath === '' ? 'active' : ''}">Home</a>
            </li>
            <li class="nav-item">
              <a href="gallery.html" class="nav-link ${currentPath === 'gallery.html' || currentPath === 'artwork-detail.html' ? 'active' : ''}">Gallery</a>
            </li>
            <li class="nav-item">
              <a href="exhibitions.html" class="nav-link ${currentPath === 'exhibitions.html' || currentPath === 'exhibition-detail.html' ? 'active' : ''}">Exhibitions</a>
            </li>
            <li class="nav-item">
              <a href="artists.html" class="nav-link ${currentPath === 'artists.html' || currentPath === 'artist-profile.html' ? 'active' : ''}">Artists</a>
            </li>
            ${currentUser ? `
              <li class="nav-item">
                <a href="upload-artwork.html" class="nav-link ${currentPath === 'upload-artwork.html' ? 'active' : ''}">Submit Art</a>
              </li>
            ` : ''}
            ${isAdmin ? `
              <li class="nav-item">
                <a href="admin-dashboard.html" class="nav-link admin-link ${currentPath.startsWith('admin-') ? 'active' : ''}">Admin Panel</a>
              </li>
            ` : ''}
          </ul>

          <div class="nav-actions">
            ${userMenuHtml}
          </div>
        </div>
      </nav>
    `;

    // Close dropdowns on outside click
    document.addEventListener('click', function(e) {
      if (!e.target.closest('.user-menu-wrapper')) {
        const uDrop = document.getElementById('user-dropdown');
        if (uDrop) uDrop.classList.remove('show');
      }
      if (!e.target.closest('.notif-wrapper')) {
        const nDrop = document.getElementById('notif-dropdown');
        if (nDrop) nDrop.classList.remove('show');
      }
    });

    // Update unread count if logged in
    if (currentUser) {
      updateUnreadBadge();
    }
  }

  // Render Footer
  function renderFooter() {
    const footerContainer = document.getElementById('app-footer');
    if (!footerContainer) return;

    footerContainer.innerHTML = `
      <footer class="nsbm-footer">
        <div class="footer-top">
          <div class="container footer-grid">
            <div class="footer-col">
              <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 16px;">
                <img src="assets/images/nsbm-logo.png" alt="NSBM Seal" style="height: 48px; background: #fff; padding: 4px; border-radius: 8px;">
                <div>
                  <h4 style="margin: 0; padding: 0; color: #fff;">NSBM Green University</h4>
                  <span style="font-size: 0.78rem; color: #CBD5E1;">Faculty of Computing | Batch 26.1 IFSL</span>
                </div>
              </div>
              <p style="font-size: 0.88rem; color: #CBD5E1; line-height: 1.6;">
                NSBM ArtCraft is the official campus platform empowering university students to showcase artistic creations, handcrafted artifacts, digital media, and photography to the wider academic community.
              </p>
            </div>

            <div class="footer-col">
              <h4>Quick Navigation</h4>
              <ul class="footer-links">
                <li><a href="index.html">Campus Home</a></li>
                <li><a href="gallery.html">Artworks & Crafts Gallery</a></li>
                <li><a href="exhibitions.html">Upcoming Exhibitions</a></li>
                <li><a href="artists.html">Student Artist Directory</a></li>
                <li><a href="upload-artwork.html">Submit Creative Project</a></li>
              </ul>
            </div>

            <div class="footer-col">
              <h4>Exhibition Categories</h4>
              <ul class="footer-links">
                <li><a href="gallery.html?category=painting">Painting & Fine Arts</a></li>
                <li><a href="gallery.html?category=sketching">Sketching & Charcoal</a></li>
                <li><a href="gallery.html?category=handcraft">Handcrafts & Ceramics</a></li>
                <li><a href="gallery.html?category=digital-art">Digital Art & 3D</a></li>
                <li><a href="gallery.html?category=photography">Creative Photography</a></li>
              </ul>
            </div>

            <div class="footer-col">
              <h4>Platform Support</h4>
              <ul class="footer-links">
                <li><span style="color: #CBD5E1; font-size: 0.88rem;">NSBM Green University Town, Pitipana, Homagama, Sri Lanka</span></li>
                <li><a href="mailto:inquiries@nsbm.ac.lk">inquiries@nsbm.ac.lk</a></li>
                <li><span style="color: #10B981; font-size: 0.85rem; font-weight: 600;">WAMP Stack: Apache 2.2 / PHP 5.4 / MySQL 5.5</span></li>
              </ul>
            </div>
          </div>
        </div>

        <div class="footer-bottom">
          <div class="container">
            <div>
              &copy; 2026 NSBM Green University. All Rights Reserved. Student Arts and Handcraft Showcasing System.
            </div>
            <div style="font-size: 0.82rem; color: #94A3B8;">
              Designed with Material UI &bull; Official NSBM Palette (Green, Navy & White)
            </div>
          </div>
        </div>
      </footer>
    `;
  }

  // Render Floating Action Button for easy submission
  function renderFAB() {
    const fabExisting = document.getElementById('global-fab');
    if (fabExisting) fabExisting.remove();

    const fab = document.createElement('a');
    fab.id = 'global-fab';
    fab.className = 'fab-btn';
    fab.href = currentUser ? 'upload-artwork.html' : 'login.html';
    fab.title = 'Upload Artwork';
    fab.innerHTML = '+';
    document.body.appendChild(fab);
  }

  // Dropdown Toggles
  function toggleUserDropdown() {
    const drop = document.getElementById('user-dropdown');
    if (drop) drop.classList.toggle('show');
  }

  function toggleNotifDropdown() {
    const drop = document.getElementById('notif-dropdown');
    if (!drop) return;
    const isShowing = drop.classList.contains('show');
    if (!isShowing) {
      drop.classList.add('show');
      loadQuickNotifications();
    } else {
      drop.classList.remove('show');
    }
  }

  // Load Notifications for Quick Dropdown
  async function loadQuickNotifications() {
    const list = document.getElementById('notif-dropdown-list');
    if (!list) return;

    try {
      const res = await fetch(`${API_BASE}/notifications.php?action=list&limit=5`);
      const data = await res.json();
      if (data.status === 'success') {
        const notifs = data.data.notifications || [];
        const unread = data.data.unread_count || 0;
        
        const label = document.getElementById('dropdown-unread-label');
        if (label) label.textContent = `${unread} unread`;

        if (notifs.length === 0) {
          list.innerHTML = `<div style="padding: 20px; text-align: center; color: var(--text-muted); font-size: 0.85rem;">No notifications yet.</div>`;
          return;
        }

        let html = '';
        notifs.forEach(n => {
          html += `
            <a href="${n.link || 'notifications.html'}" style="display: block; padding: 10px 16px; border-bottom: 1px solid var(--border-color); background: ${n.is_read == 0 ? 'var(--nsbm-green-subtle)' : '#fff'}; transition: background 0.15s;">
              <div style="font-size: 0.85rem; font-weight: 700; color: var(--nsbm-blue);">${n.title}</div>
              <div style="font-size: 0.78rem; color: var(--text-muted); line-height: 1.3; margin-top: 2px;">${n.message}</div>
              <div style="font-size: 0.7rem; color: var(--text-light); margin-top: 4px;">${n.created_at}</div>
            </a>
          `;
        });
        list.innerHTML = html;
      }
    } catch (e) {
      list.innerHTML = `<div style="padding: 16px; text-align: center; color: var(--color-danger); font-size: 0.82rem;">Failed to load.</div>`;
    }
  }

  // Update Notification Badge Counter
  async function updateUnreadBadge() {
    try {
      const res = await fetch(`${API_BASE}/notifications.php?action=unread_count`);
      const data = await res.json();
      if (data.status === 'success') {
        unreadNotificationsCount = data.data.unread_count || 0;
        const badge = document.getElementById('app-notif-badge');
        if (badge) {
          badge.textContent = unreadNotificationsCount;
          badge.style.display = unreadNotificationsCount > 0 ? 'inline-block' : 'none';
        }
      }
    } catch (e) {
      // quiet fail
    }
  }

  // Poller for real-time notification updates
  function initNotificationPoller() {
    if (currentUser) {
      setInterval(updateUnreadBadge, 15000); // Check every 15s
    }
  }

  // Logout Function
  async function logout() {
    try {
      await fetch(`${API_BASE}/auth.php?action=logout`);
      currentUser = null;
      showToast('You have successfully logged out.', 'info');
      setTimeout(() => {
        window.location.href = 'index.html';
      }, 600);
    } catch (e) {
      window.location.href = 'index.html';
    }
  }

  // Toast Notification System
  function showToast(message, type = 'success') {
    let container = document.getElementById('toast-container');
    if (!container) {
      container = document.createElement('div');
      container.id = 'toast-container';
      document.body.appendChild(container);
    }

    const toast = document.createElement('div');
    toast.className = `toast toast-${type}`;
    toast.innerHTML = `
      <div style="flex: 1;">${message}</div>
      <button style="background: none; border: none; color: #fff; cursor: pointer; font-size: 1.1rem; line-height: 1;" onclick="this.parentElement.remove()">&times;</button>
    `;
    container.appendChild(toast);

    setTimeout(() => {
      toast.style.opacity = '0';
      toast.style.transition = 'opacity 0.3s ease';
      setTimeout(() => toast.remove(), 300);
    }, 4000);
  }

  return {
    init,
    getUser,
    checkAuth,
    logout,
    toggleUserDropdown,
    toggleNotifDropdown,
    showToast,
    updateUnreadBadge,
    API_BASE
  };
})();

document.addEventListener('DOMContentLoaded', App.init);
