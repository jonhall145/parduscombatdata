# Hosting Pardus Combat Data on Google Cloud Platform

This guide provides comprehensive instructions for hosting the Pardus Combat Data application on Google Cloud Platform's free tier using your custom domain (asdwolf.com).

## Table of Contents

1. [Overview](#overview)
2. [Prerequisites](#prerequisites)
3. [Architecture](#architecture)
4. [Step-by-Step Deployment](#step-by-step-deployment)
5. [Cost Estimation](#cost-estimation)
6. [Maintenance & Monitoring](#maintenance--monitoring)
7. [Troubleshooting](#troubleshooting)

## Overview

This application will be deployed using:
- **Google App Engine (Standard Environment)** - PHP 8.2 runtime
- **Google Cloud SQL** - MySQL 8.0 database
- **Google Cloud DNS** - For custom domain management
- **Google Cloud CDN** (optional) - For static asset caching

### Why This Architecture?

- **App Engine Standard** fits within the free tier and auto-scales
- **Cloud SQL** provides managed MySQL with automatic backups
- Simple deployment with `gcloud` CLI
- Free SSL/TLS certificates included
- Custom domain support with Cloud DNS

## Prerequisites

### Required Accounts & Access

1. **Google Account** - For GCP access
2. **Domain Access** - Access to asdwolf.com DNS settings
3. **Credit Card** - Required for GCP account verification (won't be charged on free tier)

### Required Software

Install the following on your local machine:

```bash
# Google Cloud SDK (includes gcloud CLI)
# macOS
brew install google-cloud-sdk

# Windows - Download from:
# https://cloud.google.com/sdk/docs/install

# Linux
curl https://sdk.cloud.google.com | bash
exec -l $SHELL

# Verify installation
gcloud --version
```

### Repository Setup

```bash
# Clone the repository
git clone https://github.com/jonhall145/parduscombatdata.git
cd parduscombatdata

# Copy configuration template
cp config.example.php config.php
```

## Architecture

```
┌─────────────────────────────────────────────────────────┐
│                     Internet                             │
└─────────────────────┬───────────────────────────────────┘
                      │
                      │ HTTPS (asdwolf.com)
                      │
┌─────────────────────▼───────────────────────────────────┐
│              Google Cloud DNS                            │
│         (Routes asdwolf.com to App Engine)              │
└─────────────────────┬───────────────────────────────────┘
                      │
┌─────────────────────▼───────────────────────────────────┐
│          Google App Engine (PHP 8.2)                    │
│  ┌──────────────────────────────────────────────┐      │
│  │  Pardus Combat Data Application              │      │
│  │  - index.php                                  │      │
│  │  - combat_data_handler.php                    │      │
│  │  - query_handler.php                          │      │
│  │  - Userscripts (static files)                 │      │
│  └──────────────┬───────────────────────────────┘      │
└─────────────────┼───────────────────────────────────────┘
                  │
                  │ Private IP Connection
                  │
┌─────────────────▼───────────────────────────────────────┐
│         Google Cloud SQL (MySQL 8.0)                    │
│  - combat_data table                                    │
│  - Automatic backups                                    │
│  - High availability                                     │
└─────────────────────────────────────────────────────────┘
```

## Step-by-Step Deployment

### Step 1: Create Google Cloud Project

1. Go to [Google Cloud Console](https://console.cloud.google.com/)
2. Click "Select a project" → "New Project"
3. Enter project details:
   - **Project name**: `pardus-combat-data`
   - **Project ID**: `pardus-combat-data` (or available variant)
4. Click "Create"

### Step 2: Enable Required APIs

```bash
# Authenticate with Google Cloud
gcloud auth login

# Set your project
gcloud config set project pardus-combat-data

# Enable required APIs
gcloud services enable \
  appengine.googleapis.com \
  sqladmin.googleapis.com \
  sql-component.googleapis.com \
  cloudresourcemanager.googleapis.com \
  compute.googleapis.com \
  dns.googleapis.com
```

### Step 3: Initialize App Engine

```bash
# Initialize App Engine in your preferred region
# Choose a region close to your users
# us-central (Iowa) is part of the free tier
gcloud app create --region=us-central

# Available free tier regions:
# - us-west2 (Los Angeles)
# - us-central1 (Iowa)
# - us-east1 (South Carolina)
```

### Step 4: Create Cloud SQL Instance

```bash
# Create MySQL instance (db-f1-micro is free tier eligible)
gcloud sql instances create pardus-combat-db \
  --database-version=MYSQL_8_0 \
  --tier=db-f1-micro \
  --region=us-central1 \
  --root-password=YOUR_SECURE_ROOT_PASSWORD \
  --backup-start-time=03:00 \
  --backup

# Set a secure root password - replace YOUR_SECURE_ROOT_PASSWORD above

# Create database
gcloud sql databases create pardus_combat_data \
  --instance=pardus-combat-db

# Create database user for the application
gcloud sql users create pardus_app_user \
  --instance=pardus-combat-db \
  --password=YOUR_SECURE_APP_PASSWORD

# Get the instance connection name (you'll need this)
gcloud sql instances describe pardus-combat-db \
  --format="value(connectionName)"
```

**Important**: Save these credentials securely:
- Root password
- Application user password
- Instance connection name (format: `project:region:instance`)

### Step 5: Create Database Schema

```bash
# Connect to Cloud SQL instance
gcloud sql connect pardus-combat-db --user=root

# Enter your root password when prompted
# Then run these SQL commands:
```

```sql
USE pardus_combat_data;

-- Create combat_data table based on the application's requirements
CREATE TABLE combat_data (
    id INT AUTO_INCREMENT PRIMARY KEY,
    attacker VARCHAR(255),
    ship VARCHAR(255),
    ship2 VARCHAR(255),
    defender VARCHAR(255),
    tactics DECIMAL(10, 2),
    hit_accuracy DECIMAL(10, 2),
    maneuver DECIMAL(10, 2),
    weaponry DECIMAL(10, 2),
    engineering DECIMAL(10, 2),
    evasion DECIMAL(10, 2),
    ECM VARCHAR(50),
    ECCM VARCHAR(50),
    crits INT,
    critsm INT,
    crits2 INT,
    critsm2 INT,
    hits INT,
    hitsm INT,
    hits2 INT,
    hitsm2 INT,
    shots INT,
    shotsm INT,
    shots2 INT,
    shotsm2 INT,
    jams INT,
    jams2 INT,
    logid VARCHAR(255),
    submission_time INT,
    INDEX idx_defender (defender),
    INDEX idx_tactics (tactics),
    INDEX idx_attacker (attacker)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Grant privileges to application user
GRANT SELECT, INSERT, UPDATE ON pardus_combat_data.* TO 'pardus_app_user'@'%';
FLUSH PRIVILEGES;

-- Exit MySQL
EXIT;
```

### Step 6: Configure Application for Cloud SQL

Create `app.yaml` in the project root:

```yaml
runtime: php82

# App Engine standard environment
env: standard

# Instance class (F1 is free tier)
instance_class: F1

# Auto-scaling configuration
automatic_scaling:
  min_instances: 0
  max_instances: 1
  target_cpu_utilization: 0.65

# Environment variables
env_variables:
  # Set to production mode
  APP_ENV: 'production'

# Database connection via Unix socket
# Replace YOUR_CONNECTION_NAME with actual value from Step 4
beta_settings:
  cloud_sql_instances: "YOUR_CONNECTION_NAME"

# Handlers for static files (cached for performance)
handlers:
  # Userscripts
  - url: /(skillstat.*\.user\.js)
    static_files: \1
    upload: skillstat.*\.user\.js
    mime_type: application/javascript
    expiration: "1d"
  
  # Styles
  - url: /styles\.css
    static_files: styles.css
    upload: styles\.css
    mime_type: text/css
    expiration: "1d"
  
  # Favicon
  - url: /favicon\.ico
    static_files: favicon.ico
    upload: favicon\.ico
    mime_type: image/x-icon
    expiration: "7d"
  
  # HTML files
  - url: /(.+\.html)
    static_files: \1
    upload: .+\.html
    expiration: "1h"
  
  # All other requests go to PHP
  - url: /.*
    script: auto
    secure: always

# Skip files during deployment
skip_files:
  - ^(.*/)?#.*#$
  - ^(.*/)?.*~$
  - ^(.*/)?.*\.py[co]$
  - ^(.*/)?.*/RCS/.*$
  - ^(.*/)?\..*$
  - ^(.*/)?.*\.csv$
  - ^(.*/)?error_log.*$
  - ^(.*/)?config\.php$
```

### Step 7: Update config.php for Cloud SQL

Edit your `config.php` file:

```php
<?php
// Database configuration for Google Cloud SQL
// Connection via Unix socket in App Engine

// Detect if running on App Engine
$onGCP = (getenv('GAE_ENV') !== false);

if ($onGCP) {
    // Cloud SQL connection via Unix socket
    $connectionName = getenv('CLOUD_SQL_CONNECTION_NAME') ?: 'YOUR_CONNECTION_NAME';
    $socketDir = getenv('DB_SOCKET_DIR') ?: '/cloudsql';
    
    define('DB_SERVER', 'localhost:' . $socketDir . '/' . $connectionName);
    define('DB_USERNAME', 'pardus_app_user');
    define('DB_PASSWORD', 'YOUR_SECURE_APP_PASSWORD');
    define('DB_NAME', 'pardus_combat_data');
} else {
    // Local development settings
    define('DB_SERVER', 'localhost');
    define('DB_USERNAME', 'your_local_username');
    define('DB_PASSWORD', 'your_local_password');
    define('DB_NAME', 'pardus_combat_data');
}

// Function to create database connection
function getDatabaseConnection() {
    global $onGCP;
    
    if ($onGCP) {
        // App Engine connection - use Unix socket
        $conn = new mysqli(null, DB_USERNAME, DB_PASSWORD, DB_NAME, null, DB_SERVER);
    } else {
        // Standard connection
        $conn = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);
    }
    
    if ($conn->connect_error) {
        error_log("Database connection failed: " . $conn->connect_error);
        die("Connection failed. Please try again later.");
    }
    
    return $conn;
}
?>
```

### Step 8: Create .gcloudignore File

Create `.gcloudignore` in the project root:

```
# Git files
.git
.gitignore
.github/

# Documentation
README.md
SECURITY_IMPROVEMENTS.md
HOSTING_GOOGLE_CLOUD.md

# Development files
*.csv
error_log*
db.csv
file.csv

# Test files
databasetest.php
testchart.html
testchart2.html

# IDE files
.vscode/
.idea/
*.swp
*.swo

# Local config (will be set via app.yaml)
config.php

# Temporary files
tmp/
temp/
```

### Step 9: Deploy to App Engine

```bash
# Make sure you're in the project directory
cd /path/to/parduscombatdata

# Update app.yaml with your actual Cloud SQL connection name
# Edit app.yaml and replace YOUR_CONNECTION_NAME

# Also update config.php with your credentials
# Edit config.php and replace YOUR_SECURE_APP_PASSWORD and YOUR_CONNECTION_NAME

# Deploy to App Engine
gcloud app deploy app.yaml

# Deploy will show you the URL (e.g., https://pardus-combat-data.uc.r.appspot.com)
# Type 'y' to confirm deployment

# View your application
gcloud app browse
```

### Step 10: Configure Custom Domain (asdwolf.com)

#### Add Custom Domain in GCP

```bash
# Add custom domain to App Engine
gcloud app domain-mappings create asdwolf.com --certificate-management=AUTOMATIC

# This will provide DNS records that you need to add
# Record the values shown (usually A and AAAA records)
```

You'll see output like:
```
Please add the following entries to your domain registrar:

NAME                  TYPE  DATA
asdwolf.com           A     216.239.32.21
asdwolf.com           A     216.239.34.21
asdwolf.com           A     216.239.36.21
asdwolf.com           A     216.239.38.21
asdwolf.com           AAAA  2001:4860:4802:32::15
asdwolf.com           AAAA  2001:4860:4802:34::15
asdwolf.com           AAAA  2001:4860:4802:36::15
asdwolf.com           AAAA  2001:4860:4802:38::15
```

#### Update DNS Records

1. Log into your domain registrar where asdwolf.com is registered
2. Go to DNS management
3. Add all the A and AAAA records provided by Google
4. Remove any conflicting A or CNAME records
5. Save changes

DNS propagation can take 24-48 hours but usually happens within minutes to hours.

#### Add Subdomain (Optional)

If you want to use a subdomain like `combat.asdwolf.com`:

```bash
gcloud app domain-mappings create combat.asdwolf.com --certificate-management=AUTOMATIC
```

Then add the DNS records for the subdomain as well.

### Step 11: Verify Deployment

```bash
# Check application status
gcloud app describe

# View logs
gcloud app logs tail -s default

# Check domain mapping
gcloud app domain-mappings list

# Test the application
curl -I https://asdwolf.com
curl -I https://pardus-combat-data.uc.r.appspot.com
```

### Step 12: Set Up SSL/TLS Certificate

App Engine automatically provisions and renews SSL certificates for custom domains. Verify:

```bash
# Check certificate status
gcloud app domain-mappings describe asdwolf.com

# Look for:
# sslSettings:
#   certificateId: <id>
#   certificateStatus: ACTIVE
```

Certificate provisioning can take up to 24 hours after DNS propagates.

## Cost Estimation

### Free Tier Limits (per day)

Google Cloud Platform offers a "Always Free" tier:

**App Engine Standard (F1 instance)**
- 28 instance hours per day (enough for 1 instance running 24/7)
- 1 GB outbound data (Americas only)
- 5 GB Cloud Storage

**Cloud SQL (db-f1-micro)**
- First instance only
- 10 GB storage
- No backup storage

**Expected Monthly Costs**

For a low-to-medium traffic site:

| Service | Free Tier | Expected Usage | Cost |
|---------|-----------|----------------|------|
| App Engine F1 | 28 hrs/day | ~24 hrs/day | $0.00 |
| Cloud SQL f1-micro | First instance | 1 instance | $0.00 |
| Cloud SQL Storage | 10 GB | < 5 GB | $0.00 |
| Egress (Data Out) | 1 GB/day | < 500 MB/day | $0.00 |
| SSL Certificates | Unlimited | 1-2 certs | $0.00 |
| **Total** | | | **$0.00** |

**⚠️ Important Notes:**

1. **Exceeding Free Tier**: If traffic exceeds free tier limits, you'll be charged:
   - App Engine: ~$0.05/hour for additional instances
   - Cloud SQL Storage: $0.17/GB/month over 10GB
   - Egress: $0.12/GB over 1GB/day

2. **Monitoring Usage**: Set up billing alerts to avoid surprises

```bash
# Create budget alert
gcloud billing budgets create \
  --billing-account=YOUR_BILLING_ACCOUNT_ID \
  --display-name="Pardus Combat Data Alert" \
  --budget-amount=5 \
  --threshold-rule=percent=50 \
  --threshold-rule=percent=90 \
  --threshold-rule=percent=100
```

3. **Database Backups**: First 7 days of backups are free, then $0.08/GB/month

## Maintenance & Monitoring

### Regular Tasks

#### View Application Logs

```bash
# Tail logs in real-time
gcloud app logs tail

# View last 100 lines
gcloud app logs read --limit=100

# Filter by severity
gcloud app logs read --level=error

# Filter by time
gcloud app logs read --since=2h
```

#### Monitor Resources

```bash
# Check instance status
gcloud app instances list

# Check Cloud SQL status
gcloud sql instances describe pardus-combat-db

# Monitor database connections
gcloud sql operations list --instance=pardus-combat-db
```

#### Database Backups

```bash
# List backups
gcloud sql backups list --instance=pardus-combat-db

# Create on-demand backup
gcloud sql backups create --instance=pardus-combat-db

# Restore from backup
gcloud sql backups restore BACKUP_ID \
  --backup-instance=pardus-combat-db \
  --backup-id=BACKUP_ID
```

#### Update Application

```bash
# Pull latest changes
git pull origin main

# Deploy new version
gcloud app deploy

# Rollback if needed
gcloud app versions list
gcloud app versions rollback VERSION_ID
```

### Setting Up Monitoring Alerts

1. Go to [Cloud Console Monitoring](https://console.cloud.google.com/monitoring)
2. Create alerts for:
   - App Engine latency > 2 seconds
   - Cloud SQL CPU > 80%
   - Error rate > 5%

### Database Maintenance

```bash
# Connect to database for maintenance
gcloud sql connect pardus-combat-db --user=root

# Inside MySQL:
# Check table status
USE pardus_combat_data;
SHOW TABLE STATUS;

# Optimize tables
OPTIMIZE TABLE combat_data;

# Check database size
SELECT 
    table_name AS 'Table',
    ROUND(((data_length + index_length) / 1024 / 1024), 2) AS 'Size (MB)'
FROM information_schema.TABLES
WHERE table_schema = 'pardus_combat_data';
```

## Security Best Practices

### 1. Secure Configuration Files

```bash
# Never commit config.php
echo "config.php" >> .gitignore

# Use environment variables in app.yaml for sensitive data
# Or use Google Secret Manager (recommended)
```

### 2. Enable Cloud Audit Logs

```bash
# Enable audit logs for Cloud SQL
gcloud sql instances patch pardus-combat-db \
  --database-flags=general_log=on
```

### 3. Configure Cloud SQL IP Access (if needed)

```bash
# For additional security, restrict IP access
# Note: App Engine connects via Unix socket, so this is for external tools

# List authorized networks
gcloud sql instances describe pardus-combat-db \
  --format="value(settings.ipConfiguration.authorizedNetworks)"

# Add your IP for administrative access only
gcloud sql instances patch pardus-combat-db \
  --authorized-networks=YOUR_IP_ADDRESS/32
```

### 4. Enable Cloud Armor (Optional - for DDoS protection)

For high-value sites, consider enabling Cloud Armor:

```bash
# Cloud Armor requires Load Balancer (additional cost)
# See: https://cloud.google.com/armor/docs/configure-security-policies
```

### 5. Regular Security Updates

```bash
# Check for PHP runtime updates
gcloud app runtimes list

# Update app.yaml if newer runtime available
# Change: runtime: php82
# To:     runtime: php83 (when available)
```

## Troubleshooting

### Common Issues

#### Issue: "Error: Cloud SQL connection failed"

**Solution**:
```bash
# Check Cloud SQL instance is running
gcloud sql instances describe pardus-combat-db --format="value(state)"

# Verify connection name in app.yaml matches
gcloud sql instances describe pardus-combat-db --format="value(connectionName)"

# Check Cloud SQL API is enabled
gcloud services list | grep sqladmin
```

#### Issue: "502 Bad Gateway"

**Solution**:
```bash
# Check application logs
gcloud app logs tail --level=error

# Common causes:
# 1. PHP syntax error - check logs
# 2. Database connection timeout - verify config.php
# 3. Memory limit exceeded - check instance class
```

#### Issue: "Domain mapping not working"

**Solution**:
```bash
# Check domain mapping status
gcloud app domain-mappings describe asdwolf.com

# Verify DNS records
nslookup asdwolf.com
dig asdwolf.com

# DNS changes can take 24-48 hours to propagate
# Use https://www.whatsmydns.net/ to check propagation
```

#### Issue: "SSL certificate not active"

**Solution**:
```bash
# Check certificate status
gcloud app domain-mappings describe asdwolf.com

# Certificate provisioning requires:
# 1. Correct DNS records
# 2. DNS propagation complete (24-48 hours)
# 3. Domain ownership verification

# Force certificate renewal (if needed)
gcloud app domain-mappings update asdwolf.com
```

#### Issue: "Database queries slow"

**Solution**:
```bash
# Check Cloud SQL insights
gcloud sql instances describe pardus-combat-db \
  --format="value(settings.insightsConfig)"

# Enable Query Insights
gcloud sql instances patch pardus-combat-db \
  --insights-config-query-insights-enabled

# Analyze slow queries in Cloud Console:
# https://console.cloud.google.com/sql/instances/pardus-combat-db/insights
```

### Debug Mode

To enable detailed error reporting during troubleshooting:

1. Edit relevant PHP files temporarily:
```php
// At the top of index.php, combat_data_handler.php, etc.
ini_set('display_errors', 1);
error_reporting(E_ALL);
```

2. Deploy and test
3. **Important**: Remove debug settings before production use!

### Getting Help

```bash
# GCP Support (free tier has community support only)
# Stack Overflow: Tag with [google-app-engine] [google-cloud-sql]
# GCP Community: https://www.googlecloudcommunity.com/

# Check service status
# https://status.cloud.google.com/
```

## Advanced Configuration (Optional)

### Using Google Secret Manager

For better security, store credentials in Secret Manager:

```bash
# Enable Secret Manager API
gcloud services enable secretmanager.googleapis.com

# Create secrets
echo -n "pardus_app_user" | gcloud secrets create db-username --data-file=-
echo -n "YOUR_SECURE_APP_PASSWORD" | gcloud secrets create db-password --data-file=-

# Grant App Engine access
gcloud secrets add-iam-policy-binding db-username \
  --member="serviceAccount:pardus-combat-data@appspot.gserviceaccount.com" \
  --role="roles/secretmanager.secretAccessor"

gcloud secrets add-iam-policy-binding db-password \
  --member="serviceAccount:pardus-combat-data@appspot.gserviceaccount.com" \
  --role="roles/secretmanager.secretAccessor"
```

Update `config.php`:
```php
<?php
if ($onGCP) {
    // Access secrets
    $username = file_get_contents('php://stdin');
    $password = file_get_contents('php://stdin');
    // Configure connection with secrets
}
?>
```

### Enable Cloud CDN

For better performance with static assets:

```bash
# Cloud CDN requires Load Balancer
# See: https://cloud.google.com/cdn/docs/setting-up-cdn-with-bucket
```

### Set Up Continuous Deployment

Using Cloud Build for automatic deployments:

1. Create `cloudbuild.yaml`:
```yaml
steps:
  - name: 'gcr.io/cloud-builders/gcloud'
    args: ['app', 'deploy']
timeout: '1600s'
```

2. Connect to GitHub:
```bash
gcloud builds triggers create github \
  --repo-name=parduscombatdata \
  --repo-owner=jonhall145 \
  --branch-pattern="^main$" \
  --build-config=cloudbuild.yaml
```

## Additional Resources

- [Google Cloud Free Tier](https://cloud.google.com/free)
- [App Engine PHP Documentation](https://cloud.google.com/appengine/docs/standard/php)
- [Cloud SQL for MySQL](https://cloud.google.com/sql/docs/mysql)
- [Custom Domains with App Engine](https://cloud.google.com/appengine/docs/standard/mapping-custom-domains)
- [Cloud Console](https://console.cloud.google.com/)

## Support

For issues specific to this deployment guide:
- Create an issue on GitHub: https://github.com/jonhall145/parduscombatdata/issues
- Contact: Asdwolf (Orion) or Ranker Five (Artemis)

For Google Cloud Platform issues:
- [GCP Community Support](https://www.googlecloudcommunity.com/)
- [Stack Overflow](https://stackoverflow.com/questions/tagged/google-cloud-platform)

---

**Last Updated**: December 2024
**Version**: 1.0
