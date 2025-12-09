# Quick Reference Card - GCP Hosting

Essential commands and information for managing your Pardus Combat Data deployment on Google Cloud Platform.

## 🚀 One-Time Setup

```bash
# Install Google Cloud SDK
brew install google-cloud-sdk  # macOS
# or download from https://cloud.google.com/sdk/docs/install

# Authenticate
gcloud auth login

# Create and configure project
gcloud projects create pardus-combat-data
gcloud config set project pardus-combat-data
gcloud services enable appengine.googleapis.com sqladmin.googleapis.com
gcloud app create --region=europe-west2

# Create Cloud SQL instance
gcloud sql instances create pardus-combat-db \
  --database-version=MYSQL_8_0 \
  --tier=db-f1-micro \
  --region=europe-west2 \
  --root-password=YOUR_PASSWORD \
  --backup

# Create database and user
gcloud sql databases create pardus_combat_data --instance=pardus-combat-db
gcloud sql users create pardus_app_user --instance=pardus-combat-db --password=YOUR_PASSWORD

# Get connection name (SAVE THIS!)
gcloud sql instances describe pardus-combat-db --format="value(connectionName)"
```

## 📁 Configuration Files

### Update app.yaml
```yaml
beta_settings:
  cloud_sql_instances: "YOUR_CONNECTION_NAME_HERE"
```

### Create config.php from config.example.php
```php
// Update these values:
$connectionName = 'YOUR_CONNECTION_NAME';
define('DB_PASSWORD', 'YOUR_APP_PASSWORD');
```

## 🔧 Daily Operations

### Deploy Application
```bash
gcloud app deploy              # Deploy with confirmation
gcloud app deploy -q           # Deploy without confirmation
gcloud app browse              # Open in browser
```

### View Logs
```bash
gcloud app logs tail                    # Real-time logs
gcloud app logs tail --level=error      # Errors only
gcloud app logs read --limit=100        # Last 100 lines
gcloud app logs read --since=2h         # Last 2 hours
```

### Check Status
```bash
gcloud app describe                     # App Engine status
gcloud app instances list               # Running instances
gcloud app versions list                # All versions
gcloud sql instances describe pardus-combat-db  # Database status
```

## 🗄️ Database Operations

### Connect to Database
```bash
# Via Cloud SQL Proxy (recommended)
gcloud sql connect pardus-combat-db --user=pardus_app_user

# Direct connection (requires authorized network)
mysql -h INSTANCE_IP -u pardus_app_user -p pardus_combat_data
```

### Common Database Commands
```sql
-- Check database size
USE pardus_combat_data;
SELECT COUNT(*) FROM combat_data;

-- Check table status
SHOW TABLE STATUS LIKE 'combat_data';

-- Optimize table
OPTIMIZE TABLE combat_data;

-- View recent submissions
SELECT * FROM combat_data ORDER BY submission_time DESC LIMIT 10;
```

### Backup & Restore
```bash
# List backups
gcloud sql backups list --instance=pardus-combat-db

# Create manual backup
gcloud sql backups create --instance=pardus-combat-db

# Restore from backup
gcloud sql backups restore BACKUP_ID --backup-instance=pardus-combat-db

# Export to Cloud Storage
gcloud sql export sql pardus-combat-db \
  gs://bucket-name/backup.sql \
  --database=pardus_combat_data
```

## 🌐 Domain Management

### Add Custom Domain
```bash
# Add domain mapping
gcloud app domain-mappings create asdwolf.com --certificate-management=AUTOMATIC

# Check domain status
gcloud app domain-mappings describe asdwolf.com

# List all domain mappings
gcloud app domain-mappings list

# Delete domain mapping
gcloud app domain-mappings delete asdwolf.com
```

### Check DNS
```bash
# Check DNS records
dig asdwolf.com
nslookup asdwolf.com

# Check SSL certificate status
curl -vI https://asdwolf.com 2>&1 | grep -i "subject\|issuer"
```

## 🔄 Version Management

### Deploy & Rollback
```bash
# Deploy new version (doesn't switch traffic)
gcloud app deploy --no-promote

# List all versions
gcloud app versions list

# Split traffic between versions
gcloud app services set-traffic default --splits=v1=0.5,v2=0.5

# Route all traffic to specific version
gcloud app services set-traffic default --splits=v1=1

# Delete old version
gcloud app versions delete v1

# Rollback to previous version
gcloud app services set-traffic default --splits=PREVIOUS_VERSION=1
```

## 📊 Monitoring & Alerts

### Check Usage
```bash
# View current quotas
gcloud app quotas list

# Check billing
gcloud billing accounts list
gcloud billing projects describe pardus-combat-data
```

### Create Budget Alert
```bash
gcloud billing budgets create \
  --billing-account=ACCOUNT_ID \
  --display-name="Pardus Alert" \
  --budget-amount=5 \
  --threshold-rule=percent=50 \
  --threshold-rule=percent=90
```

### Monitor Resources
```bash
# CPU and memory usage
gcloud app instances list --service=default

# Database connections
gcloud sql operations list --instance=pardus-combat-db --limit=10

# Storage usage
gcloud sql instances describe pardus-combat-db \
  --format="value(settings.dataDiskSizeGb)"
```

## 🔒 Security Operations

### Update Database Password
```bash
# Change password
gcloud sql users set-password pardus_app_user \
  --instance=pardus-combat-db \
  --password=NEW_PASSWORD

# Update config.php with new password
# Redeploy: gcloud app deploy
```

### Manage Access
```bash
# List authorized networks
gcloud sql instances describe pardus-combat-db \
  --format="value(settings.ipConfiguration.authorizedNetworks)"

# Add authorized network (for external access)
gcloud sql instances patch pardus-combat-db \
  --authorized-networks=YOUR_IP/32

# Remove public IP (more secure)
gcloud sql instances patch pardus-combat-db --no-assign-ip
```

## 🚨 Troubleshooting

### Common Issues

**502 Bad Gateway**
```bash
gcloud app logs tail --level=error
# Check for PHP errors or database connection issues
```

**Database Connection Failed**
```bash
# Verify connection name
gcloud sql instances describe pardus-combat-db --format="value(connectionName)"

# Check app.yaml has correct connection name
cat app.yaml | grep cloud_sql_instances

# Verify instance is running
gcloud sql instances describe pardus-combat-db --format="value(state)"
```

**Domain Not Working**
```bash
# Check DNS propagation
dig asdwolf.com
nslookup asdwolf.com

# Check domain mapping
gcloud app domain-mappings describe asdwolf.com

# Check certificate status
gcloud app domain-mappings describe asdwolf.com --format="value(sslSettings.certificateStatus)"
```

**Slow Database Queries**
```bash
# Enable query insights
gcloud sql instances patch pardus-combat-db \
  --insights-config-query-insights-enabled

# View in console
# https://console.cloud.google.com/sql/instances/pardus-combat-db/insights
```

## 🧹 Cleanup & Maintenance

### Regular Maintenance
```bash
# Check for updates
gcloud components update

# Clean old versions (keep last 2)
gcloud app versions list
gcloud app versions delete OLD_VERSION_1 OLD_VERSION_2

# Optimize database monthly
gcloud sql connect pardus-combat-db --user=root
mysql> OPTIMIZE TABLE combat_data;
```

### Complete Cleanup (DELETE EVERYTHING)
```bash
# ⚠️ WARNING: This deletes all data permanently!

# Delete domain mapping
gcloud app domain-mappings delete asdwolf.com

# Delete Cloud SQL instance (destroys all data!)
gcloud sql instances delete pardus-combat-db

# Can't delete App Engine, but can disable
gcloud app versions delete VERSION_ID

# Delete entire project (nuclear option)
gcloud projects delete pardus-combat-data
```

## 📱 Mobile Quick Commands

### Check Everything is OK
```bash
gcloud app describe && \
gcloud sql instances describe pardus-combat-db --format="value(state)" && \
gcloud app logs read --limit=5 --level=error
```

### Quick Deploy
```bash
cd /path/to/parduscombatdata && \
git pull && \
gcloud app deploy -q && \
gcloud app browse
```

### Health Check
```bash
# Check app is responding
curl -I https://asdwolf.com

# Check database is accessible
gcloud sql instances describe pardus-combat-db --format="value(state)"

# Check for recent errors
gcloud app logs read --limit=10 --level=error --since=1h
```

## 🔗 Important URLs

| Resource | URL |
|----------|-----|
| GCP Console | https://console.cloud.google.com/ |
| App Engine Dashboard | https://console.cloud.google.com/appengine |
| Cloud SQL Dashboard | https://console.cloud.google.com/sql |
| Billing Dashboard | https://console.cloud.google.com/billing |
| Logs Viewer | https://console.cloud.google.com/logs |
| Your Application | https://asdwolf.com |
| App Engine Default | https://pardus-combat-data.uc.r.appspot.com |

## 💾 Backup This Information

**Save these securely:**
- ✅ GCP Project ID: `pardus-combat-data`
- ✅ Cloud SQL Connection Name: `project:region:instance`
- ✅ Database Root Password: `________`
- ✅ Database App User Password: `________`
- ✅ Billing Account ID: `________`

## 📚 Full Documentation

For detailed explanations, see:
- [HOSTING_INDEX.md](HOSTING_INDEX.md) - Documentation navigation
- [QUICKSTART_GCP.md](QUICKSTART_GCP.md) - Quick deployment guide
- [HOSTING_GOOGLE_CLOUD.md](HOSTING_GOOGLE_CLOUD.md) - Full deployment guide
- [FAQ_GCP_HOSTING.md](FAQ_GCP_HOSTING.md) - Common questions
- [ARCHITECTURE.md](ARCHITECTURE.md) - Technical details

## 🆘 Emergency Contacts

- **Documentation Issues**: https://github.com/jonhall145/parduscombatdata/issues
- **GCP Support**: https://www.googlecloudcommunity.com/
- **Stack Overflow**: https://stackoverflow.com/questions/tagged/google-cloud-platform
- **Project Contact**: Asdwolf (Orion) or Ranker Five (Artemis)

---

**Print this reference card for quick access during operations!**

*Last Updated: December 2024*
