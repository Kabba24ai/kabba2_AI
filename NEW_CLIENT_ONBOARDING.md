# New Client Onboarding Checklist for Kabba Laravel Project

This document lists all credentials and configuration details required from a new client to set up and deploy the Kabba Laravel-based application.

---

## 1. Application & Domain Settings
- **APP_KEY**: Laravel encryption key (generate with `php artisan key:generate` if not provided)
- **APP_URL**: Main application URL
- **FRONT_DOMAIN**: Frontend domain
- **ADMIN_DOMAIN**: Admin panel domain
- **API_DOMAIN**: API domain
- **API_DOMAIN_URL**: Full API URL
- **PROJECT_MANAGER_URL**: (if used)

## 2. Database Credentials
- **DB_HOST**
- **DB_PORT**
- **DB_DATABASE**
- **DB_USERNAME**
- **DB_PASSWORD**

## 3. Mail Service Credentials
- **mail_mailer**
- **mail_host**
- **mail_port**
- **mail_username**
- **mail_password**
- **mail_encryption**
- **mail_from_address**
- **mail_from_name**

## 4. Payment Gateway Credentials
- **payment_api_key**
- **payment_api_secret**
- **payment_api_public_key**
- **payment_test_mode** (true/false)

## 5. Third-Party Service Credentials
- **FIREBASE_PROJECT_ID**
- **FIREBASE_SERVICE_ACCOUNT_PATH**

- Any other custom settings required for integrations or features

---

**Note:**
- All sensitive credentials should be provided securely (never over email in plain text).
- Most of these values are set in the `.env` file or via the admin configuration panel.
- If you are unsure about any item, please consult your technical team or the Kabba support team.

---

*Prepared: February 20, 2026*
