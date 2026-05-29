````md
# RentnKing – Live Migration Documentation

## Overview
This document outlines the complete process for migrating RentnKing applications from the temporary environment to the live production domain.

---

# Live Migration Setup Guide

## Step 1: Update Domain Nameservers

Update the client domain nameservers to point to the new hosting environment.

### Nameservers
```txt
ns1.kabba.ai
ns2.kabba.ai
````

### Action Required

* Log in to the domain registrar panel.
* Replace existing nameservers with the above nameservers.
* Save changes and wait for DNS propagation.

---

# Step 2: Create Hosting Account

Create a new hosting account in the Namecheap server environment.

### Required Details

* Username
* Password
* Hosting Package
* Domain Assignment

### Notes

* Store credentials securely.
* Enable access for deployment and server management.

---

# Step 3: Configure SSL & Coming Soon Page

### Tasks

* Install and configure SSL certificates.
* Force HTTPS redirection.
* Upload and configure the “Coming Soon” page during migration.

### Verification

* SSL should display as secure (`https://`)
* Temporary maintenance page should load correctly.

---

# Step 4: Create Required Subdomains

Create the following subdomains and assign applications accordingly.

| Subdomain                                                                      | Application Type     | Assigned To   |
| ------------------------------------------------------------------------------ | -------------------- | ------------- |
| [https://opportunities.rentnking.com/](https://opportunities.rentnking.com/)   | Individual Other App | Nipa Soni     |
| [https://admin.rentnking.com/](https://admin.rentnking.com/)                   | Main Application     | Raj Chotaliya |
| [https://api.rentnking.com/](https://api.rentnking.com/)                       | Main Application     | Raj Chotaliya |
| [https://projectmanager.rentnking.com/](https://projectmanager.rentnking.com/) | Individual Other App | Spyro Dennis  |
| [https://timetrackerpro.rentnking.com/](https://timetrackerpro.rentnking.com/) | Individual Other App | Nipa Soni     |

### Configuration Notes

* Configure DNS records properly.
* Ensure SSL is enabled for each subdomain.
* Verify subdomains resolve successfully.

---

# Step 5: Migrate Applications via Git

Migrate each application individually using Git deployment.

### Migration Process

1. Clone repository on server.
2. Configure environment variables.
3. Install dependencies.
4. Build and deploy application.
5. Verify application functionality.

### Important

* Migrate applications one by one.
* Validate stability before proceeding to the next application.

---

# Step 6: Database Setup & Migration

### Tasks

* Create new production databases.
* Configure database users and permissions.
* Import/migrate existing database data.
* Update environment configuration files.

### Verification Checklist

* Database connection successful
* All tables migrated
* No missing records
* Application connected properly

---

# Step 7: Remove Unwanted Users

### Cleanup Tasks

* Remove temporary/test users
* Remove inactive accounts
* Verify production admin accounts only

### Security Recommendation

* Reset admin passwords if necessary
* Enable strong password policies

---

# Step 8: Add Domains into RentnKing Client Table

### Action

Insert newly configured domains into the `clients` table using phpMyAdmin.

### Required Information

* Domain Name
* Client ID
* Application Mapping
* Status

### Verification

* Ensure domain mapping works correctly
* Verify tenant/client routing functionality

---

# Step 9: Final Application Testing

Perform complete testing for all applications.

## Testing Checklist

### General Testing

* Domain accessibility
* SSL verification
* Login functionality
* API connectivity
* Database operations

### Application Testing

* Admin Panel
* API Endpoints
* Opportunities App
* Project Manager
* Time Tracker Pro

### Performance Testing

* Page loading speed
* Error logs
* Server resource usage

### Final Verification

* All applications operational
* No broken links
* DNS fully propagated
* HTTPS working correctly

---

# Post-Migration Recommendations

## Monitoring

* Monitor server logs for 24–48 hours
* Track downtime/errors
* Verify backup schedules

## Backup

* Take full server backup after successful migration
* Store backup securely

## Security

* Enable firewall/security rules
* Restrict server access
* Configure automated backups

---

# Migration Status Checklist

| Task                    | Status |
| ----------------------- | ------ |
| Nameservers Updated     | ☐      |
| Hosting Account Created | ☐      |
| SSL Installed           | ☐      |
| Coming Soon Page Added  | ☐      |
| Subdomains Created      | ☐      |
| Applications Migrated   | ☐      |
| Database Migrated       | ☐      |
| Unwanted Users Removed  | ☐      |
| Client Domains Added    | ☐      |
| Final Testing Completed | ☐      |

---

# Notes

* Migration should be completed during low-traffic hours.
* DNS propagation may take up to 24 hours.
* Keep rollback backup available before final deployment.

```
```
