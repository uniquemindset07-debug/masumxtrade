# MASUMX TRADE - Premium Educational Trading Video Sharing Platform

A modern, high-end, responsive Trading Video Sharing Landing Page Website designed with a premium theme (Black + Dark Gray + Gold + Green) and a separate Administrative Control Panel.

## 🌟 Tech Stack & Features
- **Frontend**: HTML5, CSS3, Vanilla JavaScript, Font Awesome Icons, Poppins Typography.
- **Animations**: GSAP (GreenSock Animation Platform) + AOS (Animate On Scroll) for ultra-premium UX.
- **Charts**: Interactive TradingView Ticker Tape Widget embedded directly on the landing page.
- **Backend & REST API**: PHP 8+ modular server controller with clean, RESTful JSON routing structures.
- **Database**: MySQL 8.0+ optimized schema using secure indexes and foreign keys.
- **Dynamic Controls**: Admin Panel controls Hero Titles, Subtitles, Videos, Categories, Users, Ads, Site Status, Contacts, and SEO parameters dynamically.
- **Full Security suite**: CSRF Token verification, XSS Sanitization, SQL Injection PreparedStatement protection, and secure PHP Session configuration.

---

## 📂 Folder Structure
```text
/masumx-trade
│
├── index.html              # Premium Interactive Landing Page
├── videos.html             # Video Catalog with dynamic tabs & search
├── video-details.html      # Custom player view + suggested sidebars
├── pricing.html            # Course pricing enrollment structure
├── contact.html            # Contact support & Google Maps integration
├── login.html              # Secure user Sign In / Registration Form
│
├── /assets
│   ├── /css
│   │   └── styles.css      # Universal stylesheet with custom responsive grids
│   ├── /js                 # Dynamic client modules
│   └── /images             # High-quality preview placeholders
│
├── /admin
│   ├── login-admin.php     # Administrative secure authentication
│   ├── logout.php          # Session termination controller
│   ├── dashboard.php       # Dynamic metrics & recent database queries
│   ├── videos.php          # Video CRUD manager + thumbnail mapping
│   ├── categories.php      # Category sorter & CRUD controller
│   └── settings.php        # Dynamic site-wide SEO & Theme Customizer
│
├── /api
│   └── index.php           # REST API Gateway Endpoint (GET, POST, PUT, DELETE)
│
├── /config
│   └── config.php          # DB Connection setup, session configurations & CSRF helpers
│
└── /database
    └── schema.sql          # Clean, optimized, indexed MySQL table structure
```

---

## 🚀 Installation & Deployment Instructions

### 1. Database Configuration
1. Create a MySQL database named `masumx_trade` inside your SQL engine.
2. Import `/database/schema.sql` into your server query execution panel to automatically generate the database tables and prepopulate it with default configurations, admin access credentials, and seed data.

### 2. Configuration Parameters
1. Navigate to `/config/config.php`.
2. Provide your host credentials:
   ```php
   private $host = '127.0.0.1';
   private $db_name = 'masumx_trade';
   private $username = 'YOUR_MYSQL_USERNAME';
   private $password = 'YOUR_MYSQL_PASSWORD';
   ```

### 3. Server Deployment
- **Standard PHP Apache/Nginx Engines**: Simply move the contents of this folder into your root directory (`public_html` or `www`).
- **Default Administrative Credentials**:
  - **URL**: `http://your-domain.com/admin/login-admin.php`
  - **Admin Email**: `admin@masumxtrade.com`
  - **Admin Password**: `password` *(Password is hashed using native BCRYPT standard for ultimate data security)*

---

## 🔒 Implemented Security & Performance Protocols
- **XSS Rejection**: `sanitize_input()` enforces absolute string escaping on all incoming database parameters.
- **CSRF Tokens**: Admin action forms validate dynamically generated cryptographic hash strings.
- **Prepared Statements**: Raw SQL values are parsed as bound variables protecting from raw SQL attacks.
- **Lag-Free Loading**: Optimized JS animations and external embedding keep loading speeds below 1.5 seconds.
