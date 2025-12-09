# Quick Start Guide - Google Cloud Platform Deployment

This is a condensed version of the full deployment guide. For detailed explanations, see [HOSTING_GOOGLE_CLOUD.md](HOSTING_GOOGLE_CLOUD.md).

## Prerequisites

- Google Account
- Domain access to asdwolf.com
- Google Cloud SDK installed locally

## 1. Install Google Cloud SDK

```bash
# macOS
brew install google-cloud-sdk

# Or download from: https://cloud.google.com/sdk/docs/install

# Authenticate
gcloud auth login
```

## 2. Create and Configure GCP Project

```bash
# Create project
gcloud projects create pardus-combat-data --name="Pardus Combat Data"
gcloud config set project pardus-combat-data

# Enable billing (required but won't be charged on free tier)
# Visit: https://console.cloud.google.com/billing

# Enable required APIs
gcloud services enable appengine.googleapis.com sqladmin.googleapis.com sql-component.googleapis.com dns.googleapis.com

# Initialize App Engine
gcloud app create --region=europe-west2
```

## 3. Set Up Cloud SQL

```bash
# Create MySQL instance (free tier)
gcloud sql instances create pardus-combat-db \
  --database-version=MYSQL_8_0 \
  --tier=db-f1-micro \
  --region=europe-west2 \
  --root-password=REPLACE_WITH_SECURE_PASSWORD \
  --backup

# Create database
gcloud sql databases create pardus_combat_data --instance=pardus-combat-db

# Create app user
gcloud sql users create pardus_app_user \
  --instance=pardus-combat-db \
  --password=REPLACE_WITH_SECURE_PASSWORD

# Get connection name (save this!)
gcloud sql instances describe pardus-combat-db --format="value(connectionName)"
```

## 4. Create Database Schema

```bash
# Connect to Cloud SQL
gcloud sql connect pardus-combat-db --user=root

# Run SQL commands from database_schema.sql
mysql> source database_schema.sql
mysql> EXIT;
```

Or manually:

```sql
USE pardus_combat_data;

CREATE TABLE combat_data (
    id INT AUTO_INCREMENT PRIMARY KEY,
    attacker VARCHAR(255) NOT NULL,
    ship VARCHAR(255),
    ship2 VARCHAR(255),
    defender VARCHAR(255) NOT NULL,
    logid VARCHAR(255),
    tactics DECIMAL(10, 2) DEFAULT 0,
    hit_accuracy DECIMAL(10, 2) DEFAULT 0,
    maneuver DECIMAL(10, 2) DEFAULT 0,
    weaponry DECIMAL(10, 2) DEFAULT 0,
    engineering DECIMAL(10, 2) DEFAULT 0,
    evasion DECIMAL(10, 2) DEFAULT 0,
    ECM VARCHAR(50),
    ECCM VARCHAR(50),
    crits INT DEFAULT 0,
    critsm INT DEFAULT 0,
    hits INT DEFAULT 0,
    hitsm INT DEFAULT 0,
    shots INT DEFAULT 0,
    shotsm INT DEFAULT 0,
    jams INT DEFAULT 0,
    crits2 INT DEFAULT 0,
    critsm2 INT DEFAULT 0,
    hits2 INT DEFAULT 0,
    hitsm2 INT DEFAULT 0,
    shots2 INT DEFAULT 0,
    shotsm2 INT DEFAULT 0,
    jams2 INT DEFAULT 0,
    submission_time INT NOT NULL,
    INDEX idx_defender (defender),
    INDEX idx_tactics (tactics),
    INDEX idx_attacker (attacker)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

GRANT SELECT, INSERT, UPDATE ON pardus_combat_data.* TO 'pardus_app_user'@'%';
FLUSH PRIVILEGES;
EXIT;
```

## 5. Configure Application Files

### Update app.yaml

Edit `app.yaml` and replace `YOUR_CONNECTION_NAME` with the connection name from step 3.

```yaml
beta_settings:
  cloud_sql_instances: "pardus-combat-data:europe-west2:pardus-combat-db"
```

### Create config.php

```bash
cp config.example.php config.php
```

Edit `config.php` and update:

```php
// In the GCP section:
$connectionName = 'pardus-combat-data:europe-west2:pardus-combat-db';
define('DB_PASSWORD', 'YOUR_SECURE_APP_PASSWORD');
```

## 6. Deploy Application

```bash
# From the project directory
cd /path/to/parduscombatdata

# Deploy to App Engine
gcloud app deploy

# View the app
gcloud app browse
```

Your app will be available at: `https://pardus-combat-data.uc.r.appspot.com`

## 7. Configure Custom Domain

```bash
# Add custom domain
gcloud app domain-mappings create asdwolf.com --certificate-management=AUTOMATIC

# This will output DNS records like:
# NAME           TYPE  DATA
# asdwolf.com    A     216.239.32.21
# asdwolf.com    A     216.239.34.21
# asdwolf.com    A     216.239.36.21
# asdwolf.com    A     216.239.38.21
# asdwolf.com    AAAA  2001:4860:4802:32::15
# (and more AAAA records)
```

### Update DNS at Your Registrar

1. Log into your domain registrar (where asdwolf.com is registered)
2. Go to DNS management
3. Delete any existing A or CNAME records for asdwolf.com
4. Add all the A and AAAA records provided by Google
5. Save changes

DNS propagation takes 0-48 hours (usually < 1 hour).

## 8. Verify Deployment

```bash
# Check app status
gcloud app describe

# Check domain mapping
gcloud app domain-mappings describe asdwolf.com

# View logs
gcloud app logs tail

# Test the site
curl -I https://asdwolf.com
```

## 9. Set Up Billing Alerts

```bash
# Get billing account ID
gcloud billing accounts list

# Create budget alert at $5
gcloud billing budgets create \
  --billing-account=YOUR_BILLING_ACCOUNT_ID \
  --display-name="Pardus Combat Alert" \
  --budget-amount=5 \
  --threshold-rule=percent=50 \
  --threshold-rule=percent=90 \
  --threshold-rule=percent=100
```

## Common Commands

```bash
# View logs
gcloud app logs tail

# Deploy updates
gcloud app deploy

# Check versions
gcloud app versions list

# Rollback if needed
gcloud app services set-traffic default --splits=PREVIOUS_VERSION=1

# Connect to database
gcloud sql connect pardus-combat-db --user=pardus_app_user

# List backups
gcloud sql backups list --instance=pardus-combat-db

# Create backup
gcloud sql backups create --instance=pardus-combat-db
```

## Cost Monitoring

Free tier limits (per day):
- App Engine F1: 28 instance hours (1 instance 24/7)
- Cloud SQL f1-micro: First instance free
- Data egress: 1 GB/day (worldwide)

Check usage:
- Console: https://console.cloud.google.com/billing
- `gcloud app instances list`
- `gcloud sql instances describe pardus-combat-db`

## Troubleshooting

**502 Bad Gateway**: Check logs with `gcloud app logs tail --level=error`

**Database connection failed**: Verify connection name in app.yaml matches Cloud SQL instance

**Domain not working**: Check DNS propagation with `dig asdwolf.com` or https://www.whatsmydns.net/

**SSL certificate pending**: Wait 24 hours after DNS propagates, then check:
```bash
gcloud app domain-mappings describe asdwolf.com
```

## Getting Help

- Full guide: [HOSTING_GOOGLE_CLOUD.md](HOSTING_GOOGLE_CLOUD.md)
- GCP Documentation: https://cloud.google.com/docs
- Stack Overflow: Tag [google-app-engine]
- Project Issues: https://github.com/jonhall145/parduscombatdata/issues

## Security Notes

- ✅ config.php is in .gitignore (never commit credentials)
- ✅ HTTPS enforced via App Engine
- ✅ SQL injection protection via whitelisting
- ✅ XSS protection via htmlspecialchars
- ⚠️ Keep passwords secure
- ⚠️ Enable billing alerts

---

**Next Steps After Deployment:**

1. Test the application at https://asdwolf.com
2. Install the userscript and test data submission
3. Monitor logs for the first few days
4. Set up regular database backups
5. Review security settings

For detailed information, see [HOSTING_GOOGLE_CLOUD.md](HOSTING_GOOGLE_CLOUD.md).
