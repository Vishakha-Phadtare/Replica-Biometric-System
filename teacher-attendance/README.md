# AI Face Biometric Attendance Management System (GCOER)

An enterprise-grade, responsive AI-powered Face Recognition Attendance Management System designed for educational institutions and organizations. Built with PHP, MySQL, Vanilla CSS3, Bootstrap 5.3, Face-API.js, and Chart.js.

![PHP](https://img.shields.io/badge/PHP-8.x-777BB4?style=for-the-badge&logo=php&logoColor=white)
![MySQL](https://img.shields.io/badge/MySQL-8.0-4479A1?style=for-the-badge&logo=mysql&logoColor=white)
![JavaScript](https://img.shields.io/badge/JavaScript-ES6+-F7DF1E?style=for-the-badge&logo=javascript&logoColor=black)
![Bootstrap](https://img.shields.io/badge/Bootstrap-5.3-7952B3?style=for-the-badge&logo=bootstrap&logoColor=white)
![Face--API](https://img.shields.io/badge/AI-Face--API.js-00ADD8?style=for-the-badge&logo=tensorflow&logoColor=white)

---

## 🌟 Key Features

- **🤖 AI Facial Recognition**:
  - Real-time facial landmark detection (68 facial landmarks) and 128-dimensional biometric descriptor matching via `face-api.js` (TensorFlow.js backend).
  - High accuracy matching using Euclidean distance calculation with tuned verification thresholds.
- **📱 Fully Responsive Design (Mobile, Tablet, Laptop, Desktop)**:
  - **Mobile Phones (< 576px)**: Touch-first UI, slide-out Offcanvas navigation drawer, sticky app bar, optimized touch targets (≥ 44px).
  - **Tablets (768px – 1024px)**: 2-column balanced stats cards, fluid filter toolbar, and auto-dismissing navigation drawer.
  - **Laptops & Desktops (≥ 992px)**: Fixed sticky sidebar, 4-column metrics dashboard, and side-by-side analytical charts.
- **📊 Real-Time Analytics & Dashboard**:
  - Total faculty count, present count, absent count, and real-time attendance rate percentage.
  - Weekly attendance trend bar chart powered by Chart.js.
  - Department-wise breakdown with automated progress bars.
  - Recent live check-ins feed.
- **📸 Biometric Teacher Registration**:
  - Web camera stream integration with real-time face tracking box overlay.
  - Instant descriptor generation and serialization into MySQL database.
- **🖥️ Standalone Kiosk Mode**:
  - Dedicated full-screen kiosk interface (`attendance-kiosk.php`) for entrance checkpoints.
  - Optional GPS/Geofencing verification for physical campus presence.
  - Continuous face recognition loop with automatic punch-in registration and audio/visual feedback.
- **📋 Daily Attendance Logs & Export**:
  - Multi-parameter filtering by Teacher Name/ID, Date Range (From/To), and Academic Department.
  - Instant CSV export for reporting and administrative records.
- **⚡ Smooth SPA Experience**:
  - Dynamic page transitions with history pushState and auto offcanvas drawer dismiss.

---

## 📁 Project Structure

```text
├── api/
│   ├── db.php                  # Database connection via PDO
│   ├── login.php               # Admin authentication handler
│   ├── register_teacher.php    # Teacher enrollment endpoint
│   ├── delete_teacher.php      # Teacher deletion & cascade cleanup
│   ├── get_teachers.php        # API returning teacher profiles & face descriptors
│   ├── mark_attendance.php     # Attendance check-in processing
│   ├── get_attendance.php      # Filtered attendance query handler
│   ├── get_dashboard_stats.php # Live dashboard metrics API
│   └── test_db.php             # Database diagnostic test
├── css/
│   └── style.css               # Responsive design system & custom animations
├── js/
│   ├── face-api.min.js         # Core face-api.js bundle
│   ├── face-register.js        # Webcam registration & descriptor extraction
│   ├── kiosk.js                # Kiosk face recognition loop & attendance punching
│   ├── dashboard.js            # Live dashboard clock and dynamic updates
│   └── attendance-records.js   # Filter and CSV export scripts
├── models/
│   └── face-api-models/        # Pre-trained deep learning weights (SSD MobileNet / Landmark 68)
├── includes/
│   ├── header.php              # Responsive top bar, metadata & CSS includes
│   ├── sidebar.php             # Offcanvas drawer (mobile) / sticky sidebar (desktop)
│   └── footer.php              # SPA script router & Bootstrap bundle
├── database/
│   └── teacher_attendance.sql  # MySQL schema & default demo data
├── index.php                   # Portal login page
├── dashboard.php               # Main administration dashboard
├── attendance-kiosk.php        # Standalone camera kiosk for attendance
├── attendance_records.php      # Filterable logs & CSV export
├── register_teacher.php        # Teacher registration with camera capture
├── teachers.php                # Faculty directory & biometric management
├── reports.php                 # Period-based summary reports
├── fingerprint_simulation.php  # Hardware integration demonstration sandbox
└── download_models.ps1         # PowerShell model weights downloader
```

---

## 🚀 Getting Started

### 1. Prerequisites
- **PHP 8.0+**
- **MySQL 5.7+ / MariaDB 10.4+** (e.g. via XAMPP, WampServer, or standalone)
- **Web Browser** with Webcam permissions (Google Chrome, Edge, Safari, Firefox)

### 2. Database Setup
1. Open **phpMyAdmin** or your MySQL client:
   ```sql
   CREATE DATABASE teacher_attendance;
   ```
2. Import the database schema from `database/teacher_attendance.sql`:
   ```bash
   mysql -u root -p teacher_attendance < database/teacher_attendance.sql
   ```
3. Verify connection configuration in `api/db.php`:
   ```php
   $host = 'localhost';
   $dbname = 'teacher_attendance';
   $username = 'root';
   $password = ''; // Set your MySQL password if any
   ```

### 3. Running the Server

#### Option A: Using PHP Built-in Server (Recommended for Quick Start)
Navigate to the directory and run:
```bash
php -S localhost:8080
```
Open your browser at: **[http://localhost:8080/](http://localhost:8080/)**

#### Option B: Using XAMPP / Apache
1. Copy the `teacher-attendance` folder into your XAMPP `htdocs` directory:
   ```text
   C:\xampp\htdocs\teacher-attendance\
   ```
2. Start **Apache** and **MySQL** in the XAMPP Control Panel.
3. Open your browser at: **[http://localhost/teacher-attendance/](http://localhost/teacher-attendance/)**

---

## 🔐 Default Credentials

| Role | Username | Password |
| :--- | :--- | :--- |
| **Administrator** | `admin` | `admin123` |

---

## 📱 Responsive Breakpoints

| Device Category | Viewport Width | Layout Behavior |
| :--- | :--- | :--- |
| **Mobile Phones** | `< 576px` | 1-column cards, offcanvas drawer, compact filters, swipeable tables |
| **Phablets / Large Phones** | `576px – 767px` | 2-column stats, offcanvas drawer, stacked forms |
| **Tablets (iPads / Android)** | `768px – 991px` | 2-column cards, balanced filter toolbar, offcanvas drawer |
| **Laptops** | `992px – 1199px` | Fixed sticky sidebar, 4-column metric cards, side-by-side charts |
| **Desktops & Monitors** | `≥ 1200px` | Full width layout, maximum screen real estate utilization |

---

## 📄 License
This project is developed for educational and institutional attendance automation.
