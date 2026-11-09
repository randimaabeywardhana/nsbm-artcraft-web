CREATE DATABASE IF NOT EXISTS `nsbm-artcraft-web` DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci;
USE `nsbm-artcraft-web`;


-- --------------------------------------------------------
-- Table: users
-- Stores Student creators, visitors, and University Admin accounts
-- --------------------------------------------------------
CREATE TABLE `users` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `student_id` VARCHAR(30) DEFAULT NULL,
  `name` VARCHAR(100) NOT NULL,
  `email` VARCHAR(100) NOT NULL,
  `password` VARCHAR(255) NOT NULL,
  `role` ENUM('admin', 'student') NOT NULL DEFAULT 'student',
  `faculty` VARCHAR(100) DEFAULT 'Faculty of Computing',
  `bio` TEXT DEFAULT NULL,
  `avatar` VARCHAR(255) DEFAULT 'assets/images/avatar-default.png',
  `created_at` DATETIME NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_email` (`email`),
  UNIQUE KEY `idx_student_id` (`student_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------
-- Table: categories
-- Categories of arts and handcrafted items
-- --------------------------------------------------------
CREATE TABLE `categories` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(100) NOT NULL,
  `slug` VARCHAR(100) NOT NULL,
  `description` TEXT DEFAULT NULL,
  `icon_image` VARCHAR(255) NOT NULL,
  `created_at` DATETIME NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_category_slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------
-- Table: artworks
-- Core showcase artwork items submitted by students
-- --------------------------------------------------------
CREATE TABLE `artworks` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `user_id` INT(11) NOT NULL,
  `category_id` INT(11) NOT NULL,
  `title` VARCHAR(200) NOT NULL,
  `description` TEXT NOT NULL,
  `medium` VARCHAR(100) NOT NULL,
  `dimensions` VARCHAR(100) DEFAULT 'N/A',
  `image_path` VARCHAR(255) NOT NULL,
  `status` ENUM('pending', 'approved', 'rejected') NOT NULL DEFAULT 'pending',
  `admin_feedback` TEXT DEFAULT NULL,
  `is_featured` TINYINT(1) NOT NULL DEFAULT 0,
  `views_count` INT(11) NOT NULL DEFAULT 0,
  `created_at` DATETIME NOT NULL,
  `updated_at` DATETIME NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_art_user` (`user_id`),
  KEY `idx_art_category` (`category_id`),
  KEY `idx_art_status` (`status`),
  CONSTRAINT `fk_art_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_art_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------
-- Table: exhibitions
-- University showcase events and art exhibitions
-- --------------------------------------------------------
CREATE TABLE `exhibitions` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `title` VARCHAR(200) NOT NULL,
  `theme` VARCHAR(200) NOT NULL,
  `description` TEXT NOT NULL,
  `venue` VARCHAR(200) NOT NULL,
  `start_date` DATE NOT NULL,
  `end_date` DATE NOT NULL,
  `banner_image` VARCHAR(255) NOT NULL,
  `status` ENUM('upcoming', 'active', 'completed') NOT NULL DEFAULT 'upcoming',
  `created_at` DATETIME NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------
-- Table: exhibition_participants
-- Students registered to exhibit in showcase events
-- --------------------------------------------------------
CREATE TABLE `exhibition_participants` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `exhibition_id` INT(11) NOT NULL,
  `user_id` INT(11) NOT NULL,
  `artwork_id` INT(11) DEFAULT NULL,
  `notes` TEXT DEFAULT NULL,
  `registered_at` DATETIME NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_ep_exhibit` (`exhibition_id`),
  KEY `idx_ep_user` (`user_id`),
  CONSTRAINT `fk_ep_exhibit` FOREIGN KEY (`exhibition_id`) REFERENCES `exhibitions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_ep_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_ep_art` FOREIGN KEY (`artwork_id`) REFERENCES `artworks` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------
-- Table: artwork_likes
-- Likes given by students/users to artworks
-- --------------------------------------------------------
CREATE TABLE `artwork_likes` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `artwork_id` INT(11) NOT NULL,
  `user_id` INT(11) NOT NULL,
  `created_at` DATETIME NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_unique_like` (`artwork_id`, `user_id`),
  CONSTRAINT `fk_like_art` FOREIGN KEY (`artwork_id`) REFERENCES `artworks` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_like_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------
-- Table: artwork_ratings
-- 1 to 5 star ratings submitted by users
-- --------------------------------------------------------
CREATE TABLE `artwork_ratings` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `artwork_id` INT(11) NOT NULL,
  `user_id` INT(11) NOT NULL,
  `rating` TINYINT(1) NOT NULL,
  `created_at` DATETIME NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_unique_rating` (`artwork_id`, `user_id`),
  CONSTRAINT `fk_rating_art` FOREIGN KEY (`artwork_id`) REFERENCES `artworks` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_rating_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------
-- Table: artwork_comments
-- Community comments and creative feedback
-- --------------------------------------------------------
CREATE TABLE `artwork_comments` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `artwork_id` INT(11) NOT NULL,
  `user_id` INT(11) NOT NULL,
  `comment` TEXT NOT NULL,
  `created_at` DATETIME NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_comment_art` (`artwork_id`),
  CONSTRAINT `fk_comment_art` FOREIGN KEY (`artwork_id`) REFERENCES `artworks` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_comment_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------
-- Table: user_follows
-- Follow relationships between community members and creators
-- --------------------------------------------------------
CREATE TABLE `user_follows` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `follower_id` INT(11) NOT NULL,
  `following_id` INT(11) NOT NULL,
  `created_at` DATETIME NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_unique_follow` (`follower_id`, `following_id`),
  CONSTRAINT `fk_follow_follower` FOREIGN KEY (`follower_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_follow_following` FOREIGN KEY (`following_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------
-- Table: notifications
-- Real-time notification alerts for students & admins
-- --------------------------------------------------------
CREATE TABLE `notifications` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `user_id` INT(11) NOT NULL,
  `title` VARCHAR(200) NOT NULL,
  `message` TEXT NOT NULL,
  `type` VARCHAR(50) NOT NULL DEFAULT 'system',
  `link` VARCHAR(255) DEFAULT NULL,
  `is_read` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` DATETIME NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_notif_user` (`user_id`),
  CONSTRAINT `fk_notif_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


-- ============================================================================
-- SEED DATA INSERTION
-- Password hash for 'admin123': 9b1192ee7eae28955abaf108eb5964eda1c9724b83d8ef2a739f5c1eb078cf46
-- Password hash for 'student123': a59e76be510a8fa5565ba860d63714a59ae6d4e988804b33491150ea66fa2f2b
-- ============================================================================

-- 1. Users
INSERT INTO `users` (`id`, `student_id`, `name`, `email`, `password`, `role`, `faculty`, `bio`, `avatar`, `created_at`) VALUES
(1, 'NSBM-ADM-001', 'Admin Curator', 'admin@nsbm.ac.lk', '9b1192ee7eae28955abaf108eb5964eda1c9724b83d8ef2a739f5c1eb078cf46', 'admin', 'Faculty of Computing', 'Chief Art Curator & Exhibition Coordinator, NSBM Green University', 'assets/images/avatar-admin.png', '2026-01-10 09:00:00'),
(2, 'NSBM-SOC-2024-101', 'Kavindu Perera', 'kavindu@students.nsbm.ac.lk', 'a59e76be510a8fa5565ba860d63714a59ae6d4e988804b33491150ea66fa2f2b', 'student', 'Faculty of Computing', 'Software Engineering undergrad passionate about traditional oil painting and algorithmic digital patterns.', 'assets/images/avatar-1.png', '2026-01-12 10:15:00'),
(3, 'NSBM-SOD-2024-205', 'Dinithi Senanayake', 'dinithi@students.nsbm.ac.lk', 'a59e76be510a8fa5565ba860d63714a59ae6d4e988804b33491150ea66fa2f2b', 'student', 'Faculty of Business', 'Creative visual storyteller specializing in Sri Lankan architectural graphite sketches and heritage crafts.', 'assets/images/avatar-2.png', '2026-01-14 11:30:00'),
(4, 'NSBM-SOB-2024-312', 'Nuwan Bandara', 'nuwan@students.nsbm.ac.lk', 'a59e76be510a8fa5565ba860d63714a59ae6d4e988804b33491150ea66fa2f2b', 'student', 'Faculty of Business', 'Woodcraft artisan and metal repoussé artist bringing traditional folklore into modern home crafts.', 'assets/images/avatar-3.png', '2026-01-15 14:00:00'),
(5, 'NSBM-SOS-2024-419', 'Sachini Fernando', 'sachini@students.nsbm.ac.lk', 'a59e76be510a8fa5565ba860d63714a59ae6d4e988804b33491150ea66fa2f2b', 'student', 'Faculty of Science', 'Nature and campus documentary photographer capturing raw moods, light, and architectural lines.', 'assets/images/avatar-4.png', '2026-01-16 16:45:00');

-- 2. Categories
INSERT INTO `categories` (`id`, `name`, `slug`, `description`, `icon_image`, `created_at`) VALUES
(1, 'Painting & Fine Arts', 'painting', 'Masterpieces in oil, acrylic, gouache, and watercolor capturing emotion and nature.', 'assets/images/cat-painting.png', '2026-01-10 10:00:00'),
(2, 'Sketching & Drawing', 'sketching', 'Graphite pencil, charcoal, ink hatching, and life drawing studies.', 'assets/images/cat-sketching.png', '2026-01-10 10:05:00'),
(3, 'Handcrafts & Pottery', 'handcraft', 'Hand-thrown ceramics, terracotta sculptures, woodwork, and eco-friendly handicrafts.', 'assets/images/cat-handcraft.png', '2026-01-10 10:10:00'),
(4, 'Digital Art & 3D', 'digital-art', 'Concept art, matte paintings, vector illustrations, and 3D creative designs.', 'assets/images/cat-digital.png', '2026-01-10 10:15:00'),
(5, 'Sculptures & Mixed Media', 'sculpture', 'Metal crafts, recycled materials, resin, and multi-dimensional sculptural installations.', 'assets/images/cat-sculpture.png', '2026-01-10 10:20:00'),
(6, 'Creative Photography', 'photography', 'Fine-art campus photography, portraiture, wildlife, and architecture perspectives.', 'assets/images/cat-photography.png', '2026-01-10 10:25:00');

-- 3. Artworks
INSERT INTO `artworks` (`id`, `user_id`, `category_id`, `title`, `description`, `medium`, `dimensions`, `image_path`, `status`, `admin_feedback`, `is_featured`, `views_count`, `created_at`, `updated_at`) VALUES
(1, 2, 1, 'Whispers of Sinharaja Rainforest', 'A multi-layered oil and acrylic composition depicting the endemic biodiversity of Sri Lanka Sinharaja rainforest canopy under morning sunlight.', 'Oil & Acrylic on Canvas', '24 x 36 inches', 'assets/images/art-1.png', 'approved', 'Exemplary brushwork and atmospheric perspective. Approved for front showcase.', 1, 342, '2026-01-20 10:30:00', '2026-01-20 10:30:00'),
(2, 3, 2, 'Architectural Heritage of Colombo', 'An ultra-fine graphite study honoring the classical colonial facade of Colombo National Museum and Independence Memorial Hall.', 'Graphite Pencil on Heavy Paper', 'A2 (420 x 594 mm)', 'assets/images/art-2.png', 'approved', 'Incredible attention to structural details and shading gradients.', 1, 289, '2026-01-22 14:15:00', '2026-01-22 14:15:00'),
(3, 4, 3, 'Traditional Mask of Ambalangoda', 'Hand-carved Kaduru wood mask portraying the Gurulu Raksha with all-natural vegetable-based pigments and beeswax coating.', 'Carved Kaduru Wood & Pigment', '30 x 45 x 18 cm', 'assets/images/art-3.png', 'approved', 'Marvelous cultural craftsmanship. Perfect addition to the craft collection.', 1, 410, '2026-01-25 09:20:00', '2026-01-25 09:20:00'),
(4, 2, 4, 'Futuristic Cyber Sri Lanka 2050', 'Digital environmental design illustrating Colombo port city merged with hyperloop stations and renewable solar flora.', 'Digital Concept Painting', '3840 x 2160 px', 'assets/images/art-4.png', 'approved', 'Futuristic vision aligns beautifully with NSBM Green Innovation ethos.', 1, 520, '2026-01-27 16:40:00', '2026-01-27 16:40:00'),
(5, 4, 3, 'Eco-Clay Terracotta Pottery Series', 'Series of wheel-thrown functional vases glazed with natural ash and textured using coconut husk patterns.', 'Terracotta Clay & Ash Glaze', 'Set of 3 (Heights: 15, 22, 30 cm)', 'assets/images/art-5.png', 'approved', 'Superb organic textures and eco-friendly firing process.', 0, 195, '2026-01-29 11:10:00', '2026-01-29 11:10:00'),
(6, 5, 6, 'Morning Mist over Ella Rock', 'Long-exposure dawn shot across the misty mountain ridges of Ella, capturing golden light penetrating pine clouds.', 'Digital Photography (Canon 5D Mk IV)', '4000 x 3000 px Fine Art Print', 'assets/images/art-6.png', 'approved', 'Captivating contrast and morning mist composition.', 1, 310, '2026-02-01 08:30:00', '2026-02-01 08:30:00'),
(7, 3, 1, 'Golden Waves of Mirissa Coast', 'Impressionistic watercolour study expressing the warmth of southern beaches at twilight with rhythmic ocean foam.', 'Watercolour on Arches 300gsm', '18 x 24 inches', 'assets/images/art-7.png', 'approved', 'Lively watercolor bleeding and serene color palette.', 0, 165, '2026-02-03 13:00:00', '2026-02-03 13:00:00'),
(8, 4, 5, 'Traditional Brass Peacock Lamp', 'Hand-etched brass desktop oil lamp featuring intricate floral motifs and the majestic Lankan peacock crest.', 'Repoussé Etched Brass', '35 x 15 x 15 cm', 'assets/images/art-8.png', 'approved', 'Exquisite metal chiseling and authentic cultural finish.', 0, 178, '2026-02-04 15:45:00', '2026-02-04 15:45:00'),
(9, 3, 2, 'Kandyan Ves Dancer in Motion', 'Dynamic charcoal sketch exploring the energetic pirouettes and dramatic shadows of a traditional Kandyan ceremonial dancer.', 'Charcoal & White Pastel on Toned Paper', 'A3 (297 x 420 mm)', 'assets/images/art-9.png', 'pending', NULL, 0, 84, '2026-02-05 10:00:00', '2026-02-05 10:00:00'),
(10, 5, 6, 'Geometric Modernity - NSBM Library', 'Architectural perspective highlighting the symmetrical glass spirals and sustainable green roof of NSBM main library.', 'Monochrome Architectural Photography', '30 x 40 cm Gallery Print', 'assets/images/art-10.png', 'pending', NULL, 0, 42, '2026-02-05 14:20:00', '2026-02-05 14:20:00'),
(11, 4, 3, 'Batik Tapestry of Tropical Flora', 'Handmade cotton batik tapestry using ancient wax-resist dye techniques depicting tropical heliconias and ferns.', 'Hand-dyed Cotton Fabric', '90 x 150 cm', 'assets/images/art-11.png', 'pending', NULL, 0, 29, '2026-02-06 09:15:00', '2026-02-06 09:15:00'),
(12, 2, 1, 'Echoes of the Green Cosmos', 'Fluid acrylic pouring study experimenting with NSBM green, navy blue, and pearlescent gold pigments.', 'Fluid Acrylic on Deep-Edge Wood Panel', '30 x 30 inches', 'assets/images/art-12.png', 'rejected', 'Please re-upload a clearer image without glare along the lower edges.', 0, 56, '2026-02-06 11:30:00', '2026-02-06 11:30:00');

-- 4. Exhibitions
INSERT INTO `exhibitions` (`id`, `title`, `theme`, `description`, `venue`, `start_date`, `end_date`, `banner_image`, `status`, `created_at`) VALUES
(1, 'NSBM Green Vernissage 2026: Youth Expressions', 'Nature, Culture & Green Technology', 'The premier annual visual arts showcase bringing together top student artists from Computing, Business, and Science faculties to exhibit high-caliber works.', 'Grand Auditorium Art Lounge, NSBM Green University', '2026-03-15', '2026-03-20', 'assets/images/exhibit-1.png', 'upcoming', '2026-01-15 10:00:00'),
(2, 'Computing Meets Aesthetics: Digital Art Expo', 'Algorithmic Art, 3D & Digital Futurism', 'Faculty of Computing flagship exhibition celebrating creative coders, digital illustrators, and game assets conceptualizers.', 'Faculty of Computing Atrium, NSBM', '2026-02-10', '2026-02-28', 'assets/images/exhibit-2.png', 'active', '2026-01-18 11:30:00'),
(3, 'Sustainability & Heritage Craft Fair 2025', 'Zero Waste & Traditional Lankan Handcrafts', 'An eco-conscious showcase exhibiting handmade ceramics, wood carvings, and upcycled sustainable lifestyle crafts.', 'Student Center Open Pavilion', '2025-11-05', '2025-11-10', 'assets/images/exhibit-3.png', 'completed', '2025-10-01 09:00:00');

-- 5. Exhibition Participants
INSERT INTO `exhibition_participants` (`id`, `exhibition_id`, `user_id`, `artwork_id`, `notes`, `registered_at`) VALUES
(1, 1, 2, 1, 'Presenting large-format Sinharaja rainforest canopy canvas.', '2026-01-21 11:00:00'),
(2, 1, 3, 2, 'Featuring framed heritage architectural pencil works.', '2026-01-23 15:30:00'),
(3, 1, 4, 3, 'Showcasing authentic hand-carved Kaduru mask with presentation stand.', '2026-01-26 10:45:00'),
(4, 2, 2, 4, 'Interactive 4K concept art projection piece.', '2026-01-28 14:10:00'),
(5, 2, 5, 6, 'Digital high-contrast nature photo exhibition.', '2026-02-02 09:20:00');

-- 6. Artwork Likes
INSERT INTO `artwork_likes` (`id`, `artwork_id`, `user_id`, `created_at`) VALUES
(1, 1, 3, '2026-01-21 12:00:00'),
(2, 1, 4, '2026-01-22 09:15:00'),
(3, 1, 5, '2026-01-22 17:30:00'),
(4, 2, 2, '2026-01-23 11:20:00'),
(5, 2, 5, '2026-01-24 14:05:00'),
(6, 3, 2, '2026-01-26 10:10:00'),
(7, 3, 3, '2026-01-26 12:40:00'),
(8, 4, 3, '2026-01-28 15:00:00'),
(9, 4, 4, '2026-01-29 11:15:00'),
(10, 4, 5, '2026-01-29 16:50:00'),
(11, 6, 2, '2026-02-02 10:05:00'),
(12, 6, 3, '2026-02-02 14:40:00');

-- 7. Artwork Ratings
INSERT INTO `artwork_ratings` (`id`, `artwork_id`, `user_id`, `rating`, `created_at`) VALUES
(1, 1, 3, 5, '2026-01-21 12:05:00'),
(2, 1, 4, 5, '2026-01-22 09:20:00'),
(3, 1, 5, 5, '2026-01-22 17:35:00'),
(4, 2, 2, 5, '2026-01-23 11:25:00'),
(5, 2, 4, 4, '2026-01-23 18:00:00'),
(6, 3, 2, 5, '2026-01-26 10:15:00'),
(7, 3, 5, 5, '2026-01-26 13:00:00'),
(8, 4, 3, 5, '2026-01-28 15:05:00'),
(9, 4, 4, 5, '2026-01-29 11:20:00'),
(10, 4, 5, 4, '2026-01-29 16:55:00'),
(11, 6, 2, 5, '2026-02-02 10:10:00'),
(12, 6, 4, 5, '2026-02-02 15:00:00');

-- 8. Artwork Comments
INSERT INTO `artwork_comments` (`id`, `artwork_id`, `user_id`, `comment`, `created_at`) VALUES
(1, 1, 3, 'The lighting filtering through the leaves looks almost hyper-realistic. Truly remarkable work Kavindu!', '2026-01-21 12:10:00'),
(2, 1, 4, 'Proud to see our university talent producing such fine cultural art. Will definitely see this at the Vernissage.', '2026-01-22 09:30:00'),
(3, 2, 2, 'Dinithi, the architectural perspective and patience required for those stone pillars is mindblowing!', '2026-01-23 11:30:00'),
(4, 3, 5, 'The organic wood finish smells authentic even through the photograph. Splendid craftsmanship Nuwan!', '2026-01-26 13:05:00'),
(5, 4, 3, 'Such a visionary concept! The green architecture blending with Colombo skyline is top tier.', '2026-01-28 15:10:00'),
(6, 6, 2, 'The mist layers provide so much depth. What lens and shutter speed did you shoot this with, Sachini?', '2026-02-02 10:15:00');

-- 9. User Follows
INSERT INTO `user_follows` (`id`, `follower_id`, `following_id`, `created_at`) VALUES
(1, 2, 3, '2026-01-20 15:00:00'),
(2, 2, 4, '2026-01-20 15:05:00'),
(3, 3, 2, '2026-01-21 14:00:00'),
(4, 3, 4, '2026-01-21 14:05:00'),
(5, 4, 2, '2026-01-25 11:00:00'),
(6, 5, 2, '2026-01-27 10:00:00'),
(7, 5, 3, '2026-01-27 10:05:00');

-- 10. Notifications
INSERT INTO `notifications` (`id`, `user_id`, `title`, `message`, `type`, `link`, `is_read`, `created_at`) VALUES
(1, 2, 'Artwork Approved!', 'Your submission "Whispers of Sinharaja Rainforest" has been approved and featured on the homepage.', 'approval', 'artwork-detail.html?id=1', 0, '2026-01-20 10:35:00'),
(2, 3, 'Artwork Approved!', 'Your architectural sketch "Architectural Heritage of Colombo" was approved by Curator.', 'approval', 'artwork-detail.html?id=2', 0, '2026-01-22 14:20:00'),
(3, 4, 'Artwork Approved!', 'Your woodcraft "Traditional Mask of Ambalangoda" is now live on the NSBM ArtCraft gallery.', 'approval', 'artwork-detail.html?id=3', 1, '2026-01-25 09:25:00'),
(4, 2, 'Exhibition Registration Confirmed', 'You have been registered for "Computing Meets Aesthetics: Digital Art Expo" with artwork #4.', 'exhibition', 'exhibition-detail.html?id=2', 0, '2026-01-28 14:15:00'),
(5, 2, 'New Like on your Artwork', 'Dinithi Senanayake liked your artwork "Whispers of Sinharaja Rainforest".', 'like', 'artwork-detail.html?id=1', 1, '2026-01-21 12:00:00'),
(6, 2, 'New Comment on your Artwork', 'Nuwan Bandara commented: "Proud to see our university talent producing such fine cultural art..."', 'comment', 'artwork-detail.html?id=1', 0, '2026-01-22 09:30:00'),
(7, 2, 'Submission Status Update', 'Your artwork "Echoes of the Green Cosmos" was reviewed. Reason: Please re-upload a clearer image without glare.', 'approval', 'my-submissions.html', 0, '2026-02-06 11:35:00');
