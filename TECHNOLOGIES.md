# Project Technologies & Third-Party Packages

## Overview
This document provides a comprehensive list of all programming languages, frameworks, libraries, and third-party services integrated into the Kabba project.

---

## 📋 Programming Languages

| Language | Version | Purpose |
|----------|---------|---------|
| **PHP** | ^8.2 | Backend server-side development |
| **JavaScript/ES6+** | Latest | Frontend client-side development & build tools |
| **SQL** | - | Database queries (MySQL/SQLite) |
| **HTML5** | - | Markup & templating |
| **CSS3** | - | Styling (via Tailwind CSS) |

---

## 🎯 Core Frameworks & Platforms

### Backend
| Framework/Platform | Version | Purpose |
|-------------------|---------|---------|
| **Laravel** | ^12.0 | Main PHP web framework - MVC architecture, routing, middleware, ORM |
| **Livewire** | ^3.7 | Real-time, reactive component framework for building dynamic interfaces without JavaScript |

### Frontend
| Framework/Library | Version | Purpose |
|------------------|---------|---------|
| **Vite** | ^6.3.5 | Modern build tool & development server - ultra-fast HMR |
| **Tailwind CSS** | ^4.0.0 | Utility-first CSS framework for rapid UI development |
| **Alpine.js** | ^3.14.9 | Lightweight JavaScript framework for interactive components |

---

### Payment Processing
| Package | Version | Purpose |
|---------|---------|---------|
| **authorizenet/authorizenet** | ^2.0 | Authorize.Net payment gateway integration |

### External Services & APIs
| Package | Version | Purpose |
|---------|---------|---------|
| **twilio/sdk** | ^8.8 | SMS, voice calls, messaging via Twilio API |
| **google/auth** | ^1.49 | Google OAuth 2.0 authentication |

### Communication & Messaging
| Service | Purpose | Integration Method |
|---------|---------|-------------------|
| **Twilio** | SMS, voice calls, messaging capabilities | twilio/sdk (^8.8) |
| **Firebase** | Cloud messaging, push notifications | config/services.php |

### Development Tools
| Package | Version | Purpose |
|---------|---------|---------|
| **barryvdh/laravel-debugbar** | ^3.15 | Debug toolbar - inspect queries, requests, cache, views |
| **barryvdh/laravel-ide-helper** | ^3.5 | IDE auto-completion helper - improved IDE support |
| **fakerphp/faker** | ^1.23 | Fake data generation for testing & seeding |
| **knuckleswtf/scribe** | ^5.2 | API documentation generation |
| **laravel/pail** | ^1.2.2 | Monitor application logs in real-time |
| **laravel/pint** | ^1.13 | PHP code style fixer - auto-format code to PSR-12 |
| **laravel/sail** | ^1.41 | Docker development environment |
| **mockery/mockery** | ^1.6 | Mocking library for unit testing |
| **nunomaduro/collision** | ^8.6 | Error page with friendly formatting |
| **phpunit/phpunit** | ^11.5.3 | Unit testing framework |
| **rap2hpoutre/laravel-log-viewer** | ^2.5 | View application logs in browser |

---

### UI Components & Libraries
| Package | Version | Purpose |
|---------|---------|---------|
| **air-datepicker** | ^3.6.0 | Lightweight date & time picker |
| **flatpickr** | ^4.6.13 | Date/time picker UI component |
| **flowbite-datepicker** | ^1.3.2 | Modern datepicker component |
| **choices.js** | ^11.1.0 | Searchable select/dropdown component |
| **sortablejs** | ^1.15.6 | Drag-and-drop sortable lists library |
| **signature_pad** | ^5.0.9 | HTML5 canvas for digital signatures |
| **glightbox** | ^3.3.1 | Lightweight image/content lightbox |

### Notifications & Alerts
| Package | Version | Purpose |
|---------|---------|---------|
| **notyf** | ^3.10.0 | Toast notifications - lightweight & responsive |
| **sweetalert2** | ^11.22.2 | Beautiful modal alerts & confirmations |

### Data Visualization
| Package | Version | Purpose |
|---------|---------|---------|
| **apexcharts** | ^3.54.1 | Interactive JavaScript charts & graphs |

### Forms & Validation
| Package | Version | Purpose |
|---------|---------|---------|
| **parsleyjs** | ^2.9.2 | Client-side form validation |
| **imask** | ^7.6.1 | Input mask - format phone, credit card, etc. |

### Rich Text Editor
| Package | Version | Purpose |
|---------|---------|---------|
| **tinymce** | ^8.0.1 | WYSIWYG rich text editor |

---

## 💾 Database

| Type | Driver | Version | Purpose |
|------|--------|---------|---------|
| **MySQL** | mysql | 5.7+ | Primary relational database |

**Character Set:** UTF-8 (utf8mb4)
**Collation:** utf8mb4_unicode_ci
**Engine:** InnoDB

---

### API Documentation
- **Scribe:** Auto-generate API documentation from code


## 🔐 Security Features Integrated

- **API Token Authentication:** Laravel Sanctum
- **HTML Sanitization:** Stevebauman Purify
- **Role-Based Access Control:** Spatie Permission
- **CSRF Protection:** Built-in Laravel protection
- **SQL Injection Prevention:** Eloquent ORM parameterized queries
- **SSL/TLS Support:** HTTPS encryption ready

---

## 📊 Performance Optimizations

- **Vite:** Ultra-fast build tool with instant HMR
- **Code Minification:** Terser for JavaScript compression
- **CSS Optimization:** Tailwind CSS purging unused styles
- **Database Indexing:** Multi-column indexes for queries
- **Queue System:** Background job processing for long tasks

---

## 📝 Notes

- All versions follow semantic versioning (e.g., ^8.0 = ^major.minor.patch)
- Dependencies are regularly updated following security best practices
- Development tools are separated in require-dev to keep production lean
- Multiple email providers available for flexibility & reliability
- Comprehensive logging & monitoring capabilities built-in

## Project Manager
- Reactjs - frontend
- Laravel - backend

## HRM
- Reactjs - frontend
- Laravel - backend

---

**Last Updated:** March 2026
**PHP Version Required:** 8.2+
**Node Version:** 16+ (recommended 18+)
