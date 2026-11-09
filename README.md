# NSBM ArtCraft — Student Arts & Handcraft Showcasing System
**NSBM Green University | Faculty of Computing**  
*Module: Web and Mobile Application Development (Batch 26.1 IFSL)*  
*Project Topic 2: NSBM ArtCraft*

---

## 🎨 Project Overview

**NSBM ArtCraft** is a web application built with **HTML, CSS (Material Design 3), JavaScript, PHP, and MySQL**. It allows students to showcase paintings, sketches, handcraft, photography, and digital art, and to register for campus exhibitions. Admin users moderate submissions, manage categories and events, and generate reports.

### Key Architectural Highlights

- **Decoupled Frontend & Backend Architecture**
  - **Frontend** — clean `.html` pages that call backend endpoints asynchronously via the Fetch API.
  - **Backend** — PHP JSON API endpoints returning structured responses.
- **Material Design 3 system** — dynamic color tokens, elevation, motion, and full dark/light theming.
- **REST API contract** — every endpoint returns `{ ok: true, data }` or `{ ok: false, error }`.
- **Session-based authentication** — PHP `HttpOnly`, `SameSite=Lax`, `Secure` cookies. No token storage in `localStorage`.
- **Approval workflow** — every artwork starts as `pending` and only appears in the public gallery after admin approval. Rejections carry a curator's note back to the student.
- **Reports module** — live KPI cards and Chart.js visualisations; CSV export supported.
- **Full CRUD** across artworks, categories, events, likes, ratings, comments, and follows.
- **Responsive** — desktop, tablet, and mobile layouts with dedicated bottom navigation.

---

## 📁 System File Structure

```
nsbm-artcraft-web/
├── index.html                   # University Homepage (Hero banner, featured gallery, stats, categories)
├── gallery.html                 # Main Art Gallery (Live filter tabs, search, sorting, likes & ratings)
├── artwork-detail.html          # Artwork View (PNG showcase, artist info, 5-star rating, comments feed)
├── upload-artwork.html          # Student Upload Form (Drag & drop PNG preview, metadata inputs)
├── exhibitions.html             # Showcase Exhibitions Directory (Upcoming, active, and past events)
├── exhibition-detail.html       # Exhibition Detail (Timeline, venue, register to exhibit modal)
├── artists.html                 # Student Artists Directory (Search by faculty, follow buttons)
├── artist-profile.html          # Public Student Portfolio (Artist bio, artworks grid, exhibitions)
├── my-submissions.html          # Student Dashboard (Submission status, review notes, edit & delete)
├── notifications.html           # Real-time Notification Center (Approvals, comments, marks read)
├── login.html                   # Material UI Login (1-click quick demo buttons)
├── register.html                # Student Registration (Student ID, faculty, bio)
├── admin-dashboard.html         # Admin Command Center (KPI metrics, pending moderation queue)
├── admin-artworks.html          # Admin Artworks Manager (Approve, reject, feature toggle, delete)
├── admin-categories.html        # Category CRUD (Add, edit, view counts, delete)
├── admin-exhibitions.html       # Exhibition CRUD (Create events, venues, manage participants)
├── admin-reports.html           # Analytics Reports (Category distribution, CSV exports, printable report)
├── api/                         # Backend PHP 5.4.3 REST API Endpoints
│   ├── config.php               # Database connection (`nsbm-artcraft-web`), PDO, security, session helpers
│   ├── auth.php                 # Login, registration, session check, logout
│   ├── artworks.php             # Full CRUD, status moderation, featured toggle, aggregated metrics
│   ├── categories.php           # Category listing, creation, and deletion
│   ├── exhibitions.php          # Exhibition listing, detail, creation, and student registration
│   ├── interactions.php         # Likes toggle, 5-star rating submissions, comments posting
│   ├── artists.php              # Artist profiles, directory, follow/unfollow toggle
│   ├── notifications.php        # Notification retrieval, unread count polling, mark as read
│   ├── reports.php              # KPI summaries, distribution analytics, CSV file generator
│   └── upload.php               # PNG image upload handler with validation
├── assets/
│   ├── css/
│   │   ├── material.css         # Material UI design system (elevation shadows, buttons, chips, appbar)
│   │   └── style.css            # Custom application styles, responsive layout, exhibition cards
│   ├── js/
│   │   ├── app.js               # Global controller (auth check, navbars, notification poller, toasts)
│   │   ├── gallery.js           # Gallery filters, search, sorting, like buttons
│   │   ├── admin.js             # Admin charts, approval modals, report exporter
│   │   └── upload.js            # Drag-and-drop PNG preview, validation, AJAX submission
│   └── images/                  # 100% Genuine .png format images
│       ├── nsbm-logo.png        # Official NSBM Green University Seal
│       ├── hero-banner.png      # High-res university hero banner
│       ├── cat-*.png            # Category thumbnails (painting, sketching, handcraft, etc.)
│       ├── art-*.png            # Sample showcase artworks (oil paintings, sketches, masks, pottery)
│       ├── exhibit-*.png        # Exhibition event banners
│       └── avatar-*.png         # User and admin profile avatars
├── database/
│   └── nsbm-artcraft-web.sql    # Complete MySQL 5.5.24 schema with seed data
├── .htaccess                    # Apache 2.2 compatible rewrite rules and MIME types
└── README.md                    # System documentation and setup guide
```

---

## 👥 Project Team Roles & Attributions (6-Member Team)

Member 1 (Team Leader, Database Architect & Deployment Lead): Designed the 10-table normalized relational database schema (nsbm-artcraft-web.sql) with foreign key cascades, programmed the PDO connection layer (api/config.php), managed the GitHub repository, handled live deployment on InfinityFree, and compiled the final project report.

Member 2 (Backend API Developer & QA Lead): Built core REST endpoints — artworks.php, categories.php, exhibitions.php, artists.php, interactions.php, notifications.php, reports.php, upload.php — collaborated on login.html & register.html, and executed end-to-end QA testing across student and curator flows.

Member 3 (Frontend Lead & UI/UX Designer): Engineered the Material UI design system (material.css & style.css) using NSBM's brand palette, built the global appbar with the official NSBM seal PNG, designed the homepage (index.html), and developed shared navigation/toast logic in app.js.

Member 4 (Student Panel Frontend Developer): Built the dynamic gallery (gallery.html & gallery.js), artwork detail with 5-star ratings and comments (artwork-detail.html), drag-and-drop upload form (upload-artwork.html & upload.js), submission tracking (my-submissions.html), artist directory/profiles, and notification center.

Member 5 (Admin Panel & Exhibitions Frontend Developer): Developed the curator dashboard (admin-dashboard.html & admin.js), moderation table with feedback modals (admin-artworks.html), category manager (admin-categories.html), exhibitions manager (admin-exhibitions.html), print-friendly reports (admin-reports.html), and the public exhibitions interface (exhibitions.html & exhibition-detail.html).

Member 6 (Authentication, Security & Media Assets Developer): Implemented the secure authentication API (api/auth.php) with salted password hashing, session persistence, and role-based access validation; prepared and integrated all PNG media assets — 12 sample artworks, category thumbnails, exhibition banners, and user avatars.

---

## 🛡️ Quality Assurance & Compatibility Checklist
☑ Architecture: Decoupled frontend (.html) and backend REST API (.php) with JSON responses.
☑ Security: Salted SHA-256 password hashing, secure sessions, and server-side role guards (Admin/Student).
☑ Data Protection: 100% PDO prepared statements (SQL injection prevention) and XSS output escaping.
☑ CRUD Operations: Complete Create, Read, Update, Delete across artworks, categories, and exhibitions.
☑ Approval Workflow: Moderation queue with pending → approved / rejected (with curator feedback).
☑ File Uploads: Strict PNG validation, 5 MB file size limit, and conflict-free unique filenames.
☑ Interactions: Live 5-star ratings, like toggle, comments feed, and artist follow system.
☑ Notifications: Real-time in-app alerts for status updates, reviews, and community interactions.
☑ Exhibitions: Event scheduling, student registration, and participant roster management.
☑ Reports & Analytics: Dashboard KPI cards, category distribution metrics, and printable summaries.
☑ UI & Branding: Official NSBM colors (Green/Navy/White) and responsive Material UI design.
☑ Environment: Tested and functional on local WAMP (PHP 5.4 / MySQL 5.5) and live InfinityFree hosting.
☑ Cross-Browser: Verified on Chrome, Firefox, Edge, and mobile viewports.

