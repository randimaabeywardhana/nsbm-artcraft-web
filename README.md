# NSBM ArtCraft - Student Arts & Handcraft Showcasing System
**NSBM Green University | Faculty of Computing**  
*Module: Web and Mobile Application Development (Batch 26.1 IFSL)*  
*Project Topic 2: NSBM ArtCraft*

---

## 🎨 Project Overview
**NSBM ArtCraft** is an enterprise-grade university web application developed using **HTML, CSS (Material UI), JavaScript, PHP, and MySQL**. It empowers students from the Faculty of Computing, Business, and Science to exhibit their creative paintings, architectural sketches, handcrafted pottery, 3D concepts, and photography to the university community, while participating in campus exhibitions and cultural vernissages.

### Key Architectural Highlights
- **Decoupled Frontend & Backend Architecture**:
  - **Frontend**: Clean `.html` files communicating asynchronously with backend endpoints via AJAX / Fetch API.
  - **Backend**: Clean `.php` RESTful API endpoints delivering structured JSON responses.
- **WAMP Server Compatibility**: Specifically optimized and verified for **Apache 2.2.22**, **PHP 5.4.3**, and **MySQL 5.5.24**.
- **NSBM Brand Identity**: Styled with NSBM's official color palette (Emerald Green `#00843D`, Navy Blue `#002B49`, and Clean White `#FFFFFF`) along with the official NSBM seal logo (`.png`).
- **Strict PNG Image Pipeline**: 100% PNG image assets without SVG dependencies.
- **Comprehensive Notification Center**: Real-time alerts for approvals, rejections with curator feedback, community likes, comments, 5-star ratings, follows, and exhibition registrations.
- **Database Architecture**: Engineered by the **Group Leader & QA Lead** for the 6-member team, featuring 3NF normalization, foreign key cascading integrity, and pre-seeded demonstration data.

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

- **Member 1 (Team Leader & Database Architect)**: Conceptualized system architecture, designed the 10-table normalized relational database schema (`nsbm-artcraft-web.sql`), foreign key cascades, and core PDO connection abstraction.
- **Member 2 (QA Lead & Security Specialist)**: Verified database constraints, optimized query indexes, implemented SHA-256 password salting compatible with PHP 5.4, and validated data sanitation.
- **Member 3 (Frontend Lead - UI/UX Designer)**: Engineered the Material UI design system matching the official NSBM Green University branding palette (`material.css` & `style.css`).
- **Member 4 (Frontend Developer - Client Logic)**: Built asynchronous client-side controllers (`app.js`, `gallery.js`, `upload.js`), interactive modals, drag-and-drop PNG uploader, and notification badges.
- **Member 5 (Backend Developer - CRUD & Auth)**: Developed PHP 5.4 API controllers (`auth.php`, `artworks.php`, `categories.php`, `upload.php`), session handling, and file upload validation.
- **Member 6 (Backend & Reporting Specialist)**: Implemented exhibition workflows (`exhibitions.php`), interaction endpoints (`interactions.php`, `notifications.php`), and automated CSV export engines (`reports.php`).

---

## 🛡️ Quality Assurance & Compatibility Checklist
- [x] Separate clean `.html` frontend files and `.php` backend files.
- [x] Verified compatibility with WAMP stack: Apache 2.2.22, PHP 5.4.3, MySQL 5.5.24.
- [x] Database name configured as `nsbm-artcraft-web`.
- [x] Official NSBM logo included in PNG format.
- [x] All images formatted as `.png` (zero SVG files).
- [x] Complete notification system with unread badge count and push alerts.
- [x] No prices displayed anywhere in the system.
- [x] Professional Material UI styling with elevation shadows and mobile responsiveness.
- [x] Complete CRUD operations across Artworks, Categories, Exhibitions, Ratings, Likes, and Comments.
- [x] CSV data export and print-ready report generators.
