# Laravel Project Structure Overview

This project uses a standard **Laravel** application structure. Below is a simple, readable breakdown of the main folders and files, with key points highlighted.

---

## Root Directory
- `artisan` — Laravel CLI tool
- `composer.json` / `composer.lock` — PHP dependencies
- `package.json` — Node.js dependencies
- `phpunit.xml` — PHPUnit config
- `README.md` — Project documentation
- `vite.config.js`, `tailwind.config.js`, `postcss.config.js` — Frontend build tools

---

## Main Folders

### app/
**Core application logic**
- Enums/ — Application enums (e.g., Orders)
- Events/ — Event classes (Admin, Front)
- Exceptions/ — Custom exception handling
- Helpers/ — Helper classes (Cart, Media, etc.)
- Http/ — Controllers, Middleware, Requests, Resources
- Listeners/ — Event listeners (e.g., Activities)
- Models/ — Eloquent models (Configurations, Customers, etc.)
- Providers/ — Service providers
- Rules/ — Custom validation rules
- Services/ — Service classes
- View/ — View-related logic

### bootstrap/
- app.php — Bootstrap the framework
- cache/ — Framework cache files

### config/
**All application configuration files**
- app.php, auth.php, database.php, etc.

### database/
- database.sqlite — SQLite DB (if used)
- factories/ — Model factories
- migrations/ — DB migrations
- seeders/ — DB seeders

### public/
**Web server root**
- index.php — Entry point
- build/, css/, js/, tinymce/, vendor/ — Assets
- storage/ — Symlink to storage

### resources/
- admin/, front/, shared/ — Views, CSS, JS, language files
- views/ — Blade templates

### routes/
**All route definitions**
- web.php, console.php — Main route files
- admin/, api/, front/ — Route groups

### storage/
- app/, framework/, logs/, debugbar/ — Storage for files, cache, logs

### tests/
- Feature/, Unit/ — Test cases
- TestCase.php — Base test class

### vendor/
**Composer dependencies (auto-generated)**

---


---

## Detailed Folder & File Structure (For Developers)

### 1. Controllers (`app/Http/Controllers/`)
- **Admin Controllers:**  
  `app/Http/Controllers/Admin/{Module}/{Submodule}/[Action]Controller.php`  
  Example:  
  - `Admin/TermsAndConditions/IndexController.php`  
  - `Admin/ProductManagement/Products/EditController.php`
- **Front Controllers:**  
  `app/Http/Controllers/Front/{Module}/{Submodule}/[Action]Controller.php`  
  Example:  
  - `Front/Customer/Dashboard/IndexController.php`  
  - `Front/Checkout/ThankYouController.php`
- **API Controllers:**  
  `app/Http/Controllers/Api/Admin/V1/{Module}/[Action]Controller.php`

### 2. Requests (`app/Http/Requests/`)
- **Admin Requests:**  
  `app/Http/Requests/Admin/{Module}/{Submodule}/[Action]Request.php`
- **Front Requests:**  
  `app/Http/Requests/Front/{Module}/{Submodule}/[Action]Request.php`
- **API Requests:**  
  `app/Http/Requests/Api/Admin/V1/{Module}/[Action]Request.php`

### 3. Enums (`app/Enums/`)
- **Order Enums:**  
  `app/Enums/Orders/Order[Type].php`  
  Example:  
  - `OrderTermsStatus.php`  
  - `OrderPaymentStatus.php`

### 4. Views (`resources/views/`)
- **Admin Views:**  
  `resources/views/admin/{module}/{view}.blade.php`  
  - Partials: `partials/_name.blade.php`  
  - Layouts: `layouts/app.blade.php`  
  - Components: `components/admin/...`
- **Front Views:**  
  `resources/views/front/{module}/{view}.blade.php`  
  - Partials: `partials/_name.blade.php`  
  - Layouts: `layouts/app.blade.php`  
  - Components: `components/front/...`
- **Shared/Global:**  
  - `resources/views/vendor/` (pagination, flash, etc.)  
  - `resources/views/welcome.blade.php`


### 5. Language Files (`resources/lang/`)
- **English:**  
  `resources/lang/en/{file}.php`  
  - General: `validation.php`, `auth.php`, `messages.php`  
  - API: `api/admin/v1/{module}/messages.php`

### 6. API Resource Classes (`app/Http/Resources/Api/Admin/V1/`)
- **Model-based Folder Structure:**  
Each API resource is organized in a folder named after the plural form of the model (e.g., Orders, Users, OrderProducts). Inside each folder, resource classes are created as needed. For example:
- `app/Http/Resources/Api/Admin/V1/Orders/ListResource.php`
- `app/Http/Resources/Api/Admin/V1/Users/ListResource.php`
- `app/Http/Resources/Api/Admin/V1/OrderProducts/ListResource.php`
- **Naming Convention:**  
  - Resource classes are named by purpose, e.g., `ListResource.php`.
  - Additional resources (e.g., `DetailResource.php`, `Collection.php`) can be added per model as needed.

---

### **Naming & Structure Highlights**
- **Controllers/Requests:**  
  Use `IndexController`, `EditController`, `StoreRequest`, etc., for clear intent.
- **Views:**  
  Use folders for modules, partials prefixed with `_`, and separate layouts/components.
- **Enums:**  
  Grouped by domain (e.g., Orders).
- **Language:**  
  Nested for API and modules.

---

> **Note:**  
> When rendering HTML content using the editor on the front end, add the `rich-content` class to ensure proper styling and formatting.

**Tip:** For more details, see the official [Laravel documentation](https://laravel.com/docs).
