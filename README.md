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

### PSR Standards

Laravel projects commonly follow PHP-FIG's [PSR standards](https://www.php-fig.org/psr/):

- **Line Length:**  
    Limit lines to **120 characters** for readability.  
    Avoid horizontal scrolling; break long lines as needed.

- **Page Length:**  
    No strict limit, but keep files concise and focused.  
    Split large files into smaller, logical units (ideally under **300–400 lines**).

- **Variable Naming:**  
    Use **camelCase** for variables (`$orderStatus`, `$userEmail`).  
    Names should be descriptive and meaningful.

- **Function/Method Naming:**  
    Use **camelCase** (`getOrderStatus()`, `updateProductPrice()`).  
    Methods should clearly describe their action.

- **File Naming:**  
    Use **StudlyCase** (PascalCase) for class files (`OrderStatus.php`, `ProductCustomStaticLabel.php`).  
    Use lowercase with hyphens for config and view files (`app.php`, `edit.blade.php`).


# 📖 Coding Standards (PSR-12 with Project Conventions)

This project follows **[PSR-1](https://www.php-fig.org/psr/psr-1/)** and **[PSR-12](https://www.php-fig.org/psr/psr-12/)** coding style guidelines with some additional conventions.

---

## 📄 File & Folder Structure

* **One class per file**.
* File names **match class names** (in `StudlyCase`).
* Directory names follow **StudlyCase** for PSR-4 autoloading.

**Example:**

```
app/
 ├── Http/
 │    ├── Controllers/
 │    │    └── UserController.php
 │    ├── Middleware/
 │    │    └── Authenticate.php
 │    └── Requests/
 │         └── StoreUserRequest.php
 ├── Models/
 │    └── User.php
 └── Services/
      └── BillingService.php
```

---

## 📌 Formatting

* **Line Length**: Max **120 characters**, 80 preferred.
* **Indentation**: 4 spaces (no tabs).
* **Encoding**: UTF-8 without BOM.
* **Blank Lines**:

  * After namespace
  * After `use` statements
  * Before return statements

---

## 📝 Naming Conventions

* **Classes / Interfaces / Traits** → `StudlyCase`

  ```php
  class UserProfile {}
  interface CacheDriver {}
  trait LogsActivity {}
  ```
* **Methods / Functions** → `camelCase`

  ```php
  public function getUserById() {}
  ```
* **Variables / Properties** → `camelCase`

  ```php
  $userName = 'John';
  protected $createdAt;
  ```
* **Constants** → `UPPER_CASE_WITH_UNDERSCORES`

  ```php
  const MAX_RETRIES = 3;
  ```

---

## 🔧 Functions & Methods

* Always declare **visibility** (`public`, `protected`, `private`).
* Use **type hints** and **return types**.
* Keep methods **≤ 50 lines**.

**Example:**

```php
/**
 * Calculate the total price with tax.
 *
 * @param int   $quantity Number of items
 * @param float $price    Price per item
 * @param float $taxRate  Tax rate (e.g., 0.0825 for 8.25%)
 *
 * @return float Total price including tax
 */
public function calculateTotal(int $quantity, float $price, float $taxRate = 0.0): float
{
    return ($quantity * $price) * (1 + $taxRate);
}
```

---

## ⚡ Control Structures

* Always use braces `{}` even for one-line statements.
* Space after control keywords.

**Example:**

```php
if ($user->isActive()) {
    $this->sendNotification();
}
```

---

## 📚 DocBlocks

Use **DocBlocks** for:

* Classes
* Public methods
* Complex private methods

**Class Example:**

```php
/**
 * Handles billing operations for user subscriptions.
 *
 * @category  Services
 * @package   App\Services
 */
class BillingService
{
    /**
     * Charge a user for their subscription.
     *
     * @param \App\Models\User $user
     * @param float            $amount
     *
     * @return bool True if charge succeeded
     */
    public function charge(User $user, float $amount): bool
    {
        // Billing logic here...
        return true;
    }
}
```

---

## ✅ Summary

* **80–120 chars per line**
* **StudlyCase** → Classes & Files
* **camelCase** → Methods & Variables
* **UPPER\_CASE** → Constants
* **One class per file, file name = class name**
* **Use DocBlocks** for clarity

---

> Following PSR standards ensures code consistency, maintainability, and easier collaboration.

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
### Storage Folder Structure

Inside `storage/app/public/`, static files and icons are organized for both admin and front-end use:

- **admin/**: Contains static assets and icons used in the admin panel (e.g., admin-specific images, icons, and uploads).
- **front/**: Contains static assets and icons for the front-end of the application (e.g., user-facing images, icons, and uploads).
- **media/**: Stores uploaded media files through the admin panel, such as images, videos, and other uploads that may be used across both admin and front sections.

This structure keeps assets organized by context and usage, making it easier to manage and reference files in your application.

> This approach keeps static assets organized and leverages Laravel's built-in file storage system for serving files securely.

## Product Enum: ProductCustomStaticLabel

Located at: `app/Enums/Products/ProductCustomStaticLabel.php`

This enum defines custom static labels for product rental options:
- `rental_damage_waiver` — "Damage Waiver Protection"
- `rental_track_insurance` — "Thrown Track Coverage"

**Usage Notes:**
- Do not change enum keys if orders exist, as this will break order data integrity.

### Usages Across the Codebase

- **Blade Views:**
  - `resources/views/front/products/details.blade.php`: Used to display and reference rental options for products.
  - `resources/views/components/front/cart-sidebar-preview.blade.php`: Used to show rental item names in the cart.
  - `resources/views/components/front/checkout/cart-summary.blade.php`: Used to summarize rental options in checkout.
  - `resources/views/admin/order_management/orders/edit.blade.php`: Used to display rental options in order editing.

- **Helpers:**
  - `app/Helpers/CartHelper.php`: Used to resolve and price rental options when building cart items.

- **Controllers:**
  - `app/Http/Controllers/Admin/ProductManagement/Products/UpdateController.php`: Handles updating product rental options and their prices.
  
> **Note:**  
> When rendering HTML content using the editor on the front end, add the `rich-content` class to ensure proper styling and formatting.

**Tip:** For more details, see the official [Laravel documentation](https://laravel.com/docs).

**Rules:**
Equipment through web only be soft assignment
**Equipment Assignment Rules:**

- **Web Orders:** Equipment is soft-assigned only.
- **Mobile Orders:** Equipment is hard-assigned only.
- **Hard Assignment Criteria:**
    - If `current_order_product_id` is not null, the equipment is considered hard-assigned.
    - If checklist questions are not answered, the equipment is also considered hard-assigned.

## Database Tables: Fresh Start Truncate List

The following tables should be truncated when performing a "fresh start" reset of the application data:

- `customer_accounts`
- `customer_cards`
- `customer_notes`
- `customer_addresses`
- `customers`
- `invoices`
- `invoice_items`

- `receipts`
- `receipt_items`

- `order_addresses`
- `order_extra_charges`
- `order_histories`
- `order_media`
- `order_notes`
- `order_payments`
- `order_product_checklist_question_answers`
- `order_product_checklist_questions`
- `order_product_damage_charge_logs`
- `order_product_funnel_logs`
- `order_products`
- `orders`

> **Note:** Truncating these tables will remove all customer and order-related data, ensuring a clean state for testing or development.

