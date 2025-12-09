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
  dns.googleapis.com \
  secretmanager.googleapis.com
```

### Step 3: Initialize App Engine

```bash
# Initialize App Engine in your preferred region
# Choose a region close to your users
# europe-west2 (London, UK) is part of the free tier
gcloud app create --region=europe-west2

# Available free tier regions in EU:
# - europe-west2 (London, UK)
# - europe-west3 (Frankfurt, Germany)
# - europe-west1 (Belgium)
```

### Step 4: Create Cloud SQL Instance

```bash
# Create MySQL instance (db-f1-micro is free tier eligible)
gcloud sql instances create pardus-combat-db \
  --database-version=MYSQL_8_0 \
  --tier=db-f1-micro \
  --region=europe-west2 \
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

**Important**: Save these credentials - we'll store them in Secret Manager in the next step:
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

### Step 6: Store Credentials in Secret Manager

For security best practices, store database credentials in Google Secret Manager:

```bash
# Create secrets for database credentials
echo -n "pardus_app_user" | gcloud secrets create db-username --data-file=-
echo -n "YOUR_SECURE_APP_PASSWORD" | gcloud secrets create db-password --data-file=-
echo -n "pardus-combat-data:europe-west2:pardus-combat-db" | gcloud secrets create db-connection-name --data-file=-

# Get your App Engine service account email
PROJECT_ID=$(gcloud config get-value project)
SERVICE_ACCOUNT="${PROJECT_ID}@appspot.gserviceaccount.com"

# Grant App Engine access to the secrets
gcloud secrets add-iam-policy-binding db-username \
  --member="serviceAccount:${SERVICE_ACCOUNT}" \
  --role="roles/secretmanager.secretAccessor"

gcloud secrets add-iam-policy-binding db-password \
  --member="serviceAccount:${SERVICE_ACCOUNT}" \
  --role="roles/secretmanager.secretAccessor"

gcloud secrets add-iam-policy-binding db-connection-name \
  --member="serviceAccount:${SERVICE_ACCOUNT}" \
  --role="roles/secretmanager.secretAccessor"

# Verify secrets were created
gcloud secrets list
```

**Security Benefits:**
- ✅ Credentials never stored in code or config files
- ✅ Automatic encryption at rest
- ✅ Access control via IAM
- ✅ Audit logging of secret access
- ✅ Version management for secrets

### Step 7: Configure Application for Cloud SQL

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

### Step 8: Update config.php to Use Secret Manager

Edit your `config.php` file to retrieve credentials from Secret Manager:

```php
<?php
// Database configuration for Google Cloud SQL
// Uses Secret Manager for secure credential storage

// Detect if running on App Engine
$onGCP = (getenv('GAE_ENV') !== false);

if ($onGCP) {
    // Retrieve credentials from Secret Manager
    // Using Google Cloud Secret Manager PHP client would be ideal,
    // but for App Engine Standard PHP, we use the REST API
    function getSecret($secretName) {
        $projectId = getenv('GOOGLE_CLOUD_PROJECT');
        $url = "http://metadata.google.internal/computeMetadata/v1/instance/service-accounts/default/token";
        
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Metadata-Flavor: Google'));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);
        $curlError = curl_error($ch);
        curl_close($ch);
        
        if ($response === false || !empty($curlError)) {
            error_log("Failed to get access token from metadata server: " . $curlError);
            return null;
        }
        
        // Parse JSON and validate structure
        $tokenData = json_decode($response, true);
        if (json_last_error() !== JSON_ERROR_NONE || !isset($tokenData['access_token'])) {
            error_log("Failed to parse access token response: " . json_last_error_msg());
            return null;
        }
        
        $token = $tokenData['access_token'];
        
        $secretUrl = "https://secretmanager.googleapis.com/v1/projects/{$projectId}/secrets/{$secretName}/versions/latest:access";
        
        $ch = curl_init($secretUrl);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Authorization: Bearer ' . $token,
            'Content-Type: application/json'
        ));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);
        
        if ($response === false || !empty($curlError)) {
            error_log("Failed to retrieve secret {$secretName}: cURL error: " . $curlError);
            return null;
        }
        
        if ($httpCode === 200) {
            $data = json_decode($response, true);
            if (json_last_error() !== JSON_ERROR_NONE || !isset($data['payload']['data'])) {
                error_log("Failed to parse secret {$secretName} response: " . json_last_error_msg());
                return null;
            }
            return base64_decode($data['payload']['data']);
        } else {
            error_log("Failed to retrieve secret {$secretName}: HTTP {$httpCode} - {$response}");
            return null;
        }
    }
    
    // Retrieve credentials from Secret Manager
    $dbUsername = getSecret('db-username');
    $dbPassword = getSecret('db-password');
    $connectionName = getSecret('db-connection-name');
    
    // Fallback to environment variables if secrets fail
    if (!$dbUsername || !$dbPassword || !$connectionName) {
        error_log("Secret Manager retrieval failed, using environment variables");
        $dbUsername = getenv('DB_USERNAME') ?: 'pardus_app_user';
        $dbPassword = getenv('DB_PASSWORD');
        $connectionName = getenv('CLOUD_SQL_CONNECTION_NAME');
    }
    
    $socketDir = getenv('DB_SOCKET_DIR') ?: '/cloudsql';
    
    define('DB_SOCKET', $socketDir . '/' . $connectionName);
    define('DB_USERNAME', $dbUsername);
    define('DB_PASSWORD', $dbPassword);
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
        $conn = new mysqli(null, DB_USERNAME, DB_PASSWORD, DB_NAME, null, DB_SOCKET);
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

**How it Works:**
1. Code detects if running on App Engine
2. If on App Engine, retrieves credentials from Secret Manager using the metadata server for authentication
3. Falls back to environment variables if Secret Manager is unavailable
4. Locally, uses traditional config values for development

### Step 9: Create .gcloudignore File

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

### Step 10: Deploy to App Engine

```bash
# Make sure you're in the project directory
cd /path/to/parduscombatdata

# Update app.yaml with your actual Cloud SQL connection name
# Edit app.yaml and replace YOUR_CONNECTION_NAME

# No need to add credentials to config.php - they're in Secret Manager!
# The application will automatically retrieve them at runtime

# Deploy to App Engine
gcloud app deploy app.yaml

# Deploy will show you the URL (e.g., https://pardus-combat-data.uc.r.appspot.com)
# Type 'y' to confirm deployment

# View your application
gcloud app browse
```

### Step 11: Configure Custom Domain (asdwolf.com)

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

### Step 12: Verify Deployment

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

### Step 13: Set Up SSL/TLS Certificate

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
- 1 GB outbound data per day (worldwide, including Europe)
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

### 1. Secure Credential Management

✅ **Credentials are stored in Google Secret Manager** (configured in Step 6)
- Never hardcode credentials in source code
- Never commit credentials to Git
- Use Secret Manager for all sensitive data
- Rotate secrets regularly (every 90 days recommended)

```bash
# Never commit config.php with real credentials
echo "config.php" >> .gitignore

# Verify secrets are properly configured
gcloud secrets list
gcloud secrets versions access latest --secret=db-password
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

### Rotating Secrets in Secret Manager

To rotate database passwords or other credentials:

```bash
# Update the database password
gcloud sql users set-password pardus_app_user \
  --instance=pardus-combat-db \
  --password=NEW_SECURE_PASSWORD

# Update the secret with the new password
echo -n "NEW_SECURE_PASSWORD" | gcloud secrets versions add db-password --data-file=-

# No need to redeploy - App Engine will automatically use the latest version
# The application retrieves secrets on each request

# Verify the new version was created
gcloud secrets versions list db-password
```

**Secret Manager Best Practices:**
- ✅ Rotate passwords every 90 days
- ✅ Use strong, randomly-generated passwords
- ✅ Monitor secret access via Cloud Audit Logs
- ✅ Never log or print secret values
- ✅ Use latest version (default) for automatic updates

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
