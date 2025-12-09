# Google Cloud Platform Hosting - Frequently Asked Questions

Common questions and answers about hosting Pardus Combat Data on Google Cloud Platform.

## General Questions

### Q: Will I really be charged $0 per month?

**A:** Yes, as long as you stay within the free tier limits:
- **App Engine F1 instance**: 28 instance hours/day (enough for 1 instance 24/7) - FREE
- **Cloud SQL f1-micro**: First instance - FREE
- **Cloud SQL Storage**: Up to 10 GB - FREE
- **Data egress**: 1 GB/day (Americas only) - FREE
- **SSL certificates**: Unlimited - FREE

However, you will be charged if you:
- Exceed 28 instance hours per day (run multiple instances)
- Use more than 10 GB of database storage
- Transfer more than 1 GB of data out per day
- Upgrade to higher-tier instances

Set up billing alerts to monitor usage.

### Q: Do I really need a credit card?

**A:** Yes, Google requires a credit card for account verification, but you won't be charged unless you:
1. Manually upgrade from free tier
2. Exceed free tier limits
3. Enable paid services

The free tier is truly free within its limits.

### Q: How much traffic can the free tier handle?

**A:** The F1 instance can handle:
- **Concurrent users**: 10-50 depending on query complexity
- **Requests per day**: Thousands of simple requests
- **Database queries**: Moderate load on f1-micro

For the combat data application with typical usage, the free tier should be more than sufficient.

### Q: What happens if I exceed the free tier limits?

**A:** 
1. You'll receive email alerts (if configured)
2. Billing will start automatically
3. Costs are minimal for small overages:
   - Additional F1 instance: ~$0.05/hour
   - Extra storage: $0.17/GB/month
   - Extra egress: $0.12/GB

Set up billing alerts at $5 to catch any issues early.

## Technical Questions

### Q: Why App Engine instead of Compute Engine?

**A:** App Engine is better for this use case because:
- **Simpler deployment**: Just `gcloud app deploy`
- **Auto-scaling**: Scales to zero when not in use (saves money)
- **Free tier**: More generous than Compute Engine
- **Managed platform**: No server maintenance
- **Built-in SSL**: Free automatic HTTPS

Compute Engine would require you to manage the OS, web server, and PHP yourself.

### Q: Why Cloud SQL instead of Cloud Spanner or Firestore?

**A:** 
- **Compatibility**: The app uses MySQL (Cloud SQL)
- **Free tier**: Cloud SQL f1-micro is free (first instance)
- **Simple migration**: Easy to move existing MySQL data
- **Familiar**: Standard MySQL queries and tools

Cloud Spanner is more expensive and overkill for this use case. Firestore would require rewriting the application.

### Q: Can I use Cloud Run instead of App Engine?

**A:** Yes, but App Engine is recommended because:
- App Engine Standard has a more generous free tier
- Simpler configuration for PHP applications
- Better integration with Cloud SQL
- No containerization needed

Cloud Run would require creating a Dockerfile and container image.

### Q: What PHP version should I use?

**A:** Use `php82` (PHP 8.2) as specified in `app.yaml`. This is:
- Currently supported by Google
- Compatible with the codebase
- Receives security updates
- Performs better than older versions

### Q: How do I connect to Cloud SQL from my local machine?

**A:**

Option 1: Cloud SQL Proxy (Recommended)
```bash
# Install Cloud SQL Proxy
curl -o cloud-sql-proxy https://storage.googleapis.com/cloud-sql-connectors/cloud-sql-proxy/v2.8.0/cloud-sql-proxy.linux.amd64
chmod +x cloud-sql-proxy

# Start proxy
./cloud-sql-proxy --port 3306 pardus-combat-data:us-central1:pardus-combat-db

# Connect via MySQL client
mysql -h 127.0.0.1 -u pardus_app_user -p pardus_combat_data
```

Option 2: Direct connection (less secure)
```bash
# Add your IP to authorized networks
gcloud sql instances patch pardus-combat-db \
  --authorized-networks=YOUR_IP/32

# Connect directly
mysql -h CLOUD_SQL_IP -u pardus_app_user -p pardus_combat_data
```

## Domain and SSL Questions

### Q: How long does DNS propagation take?

**A:** 
- **Minimum**: 5 minutes (if DNS TTL is low)
- **Typical**: 1-4 hours
- **Maximum**: 24-48 hours (rare)

Use https://www.whatsmydns.net/ to check propagation status worldwide.

### Q: How long does SSL certificate provisioning take?

**A:**
- **After DNS propagates**: Usually 15 minutes to 4 hours
- **Maximum**: 24 hours

Check status with:
```bash
gcloud app domain-mappings describe asdwolf.com
```

Look for `certificateStatus: ACTIVE`

### Q: Can I use www.asdwolf.com as well?

**A:** Yes! Add another domain mapping:

```bash
# Add www subdomain
gcloud app domain-mappings create www.asdwolf.com --certificate-management=AUTOMATIC

# Add DNS records provided by Google to your registrar
```

Then optionally redirect www to non-www or vice versa using a redirect service or Cloud Load Balancer.

### Q: Can I use a subdomain like combat.asdwolf.com?

**A:** Absolutely! Same process:

```bash
gcloud app domain-mappings create combat.asdwolf.com --certificate-management=AUTOMATIC
```

Add the DNS records, and you're done.

### Q: What if my domain registrar doesn't support AAAA records?

**A:** Most modern registrars support AAAA records, but if yours doesn't:
1. Just add the A records (IPv4) - this will work fine
2. Consider transferring to a better registrar (Cloudflare, Namecheap, etc.)
3. Use Cloudflare as a DNS proxy (free plan available)

## Deployment Questions

### Q: How do I deploy updates?

**A:**

```bash
# From the project directory
git pull origin main

# Deploy to App Engine
gcloud app deploy

# That's it! Takes 2-5 minutes
```

### Q: Can I roll back to a previous version?

**A:** Yes!

```bash
# List versions
gcloud app versions list

# Rollback to previous version
gcloud app services set-traffic default --splits=PREVIOUS_VERSION_ID=1

# Or split traffic 50/50 for testing
gcloud app services set-traffic default --splits=NEW_VERSION=0.5,OLD_VERSION=0.5
```

### Q: How do I view logs?

**A:**

```bash
# Real-time logs
gcloud app logs tail

# Last 100 lines
gcloud app logs read --limit=100

# Filter by severity
gcloud app logs read --level=error

# Filter by time
gcloud app logs read --since=2h
```

### Q: Where is the database schema?

**A:** In the repository: `database_schema.sql`

Import it:
```bash
gcloud sql connect pardus-combat-db --user=root
mysql> source /path/to/database_schema.sql
```

Or manually run the CREATE TABLE statements from the file.

## Database Questions

### Q: How do I back up the database?

**A:**

**Automated backups** (configured during setup):
- Runs daily at 3:00 AM
- Kept for 7 days
- First 7 days free

**Manual backup**:
```bash
gcloud sql backups create --instance=pardus-combat-db
```

**Export to Cloud Storage**:
```bash
# Create bucket first
gsutil mb gs://pardus-combat-backup/

# Export
gcloud sql export sql pardus-combat-db \
  gs://pardus-combat-backup/backup-$(date +%Y%m%d).sql \
  --database=pardus_combat_data
```

### Q: How do I restore from backup?

**A:**

```bash
# List backups
gcloud sql backups list --instance=pardus-combat-db

# Restore from specific backup
gcloud sql backups restore BACKUP_ID \
  --backup-instance=pardus-combat-db
```

**Warning**: This overwrites the current database!

### Q: How do I migrate existing data to Cloud SQL?

**A:**

**Option 1: Export/Import via SQL dump**
```bash
# On old server
mysqldump -u user -p pardus_combat_data > backup.sql

# Import to Cloud SQL
gcloud sql connect pardus-combat-db --user=root
mysql> source backup.sql
```

**Option 2: CSV export/import**
```bash
# Export from old database
mysql -u user -p -e "SELECT * FROM combat_data" pardus_combat_data > data.csv

# Import via Cloud SQL
gcloud sql import csv pardus-combat-db \
  gs://your-bucket/data.csv \
  --database=pardus_combat_data \
  --table=combat_data
```

### Q: Can I access the database from other applications?

**A:** Yes, but carefully:

**From App Engine**: Automatic via Unix socket (configured)

**From other GCP services**: 
```bash
# Enable private IP
gcloud sql instances patch pardus-combat-db \
  --network=default \
  --no-assign-ip
```

**From external applications**: 
- Use Cloud SQL Proxy (recommended)
- Or add authorized networks (less secure)

## Cost and Billing Questions

### Q: How do I set up billing alerts?

**A:**

```bash
# Get billing account
gcloud billing accounts list

# Create budget alert
gcloud billing budgets create \
  --billing-account=BILLING_ACCOUNT_ID \
  --display-name="Pardus Combat Alert" \
  --budget-amount=5 \
  --threshold-rule=percent=50 \
  --threshold-rule=percent=90 \
  --threshold-rule=percent=100
```

### Q: How do I check my current usage?

**A:**

**Via Console**: https://console.cloud.google.com/billing

**Via gcloud**:
```bash
# App Engine instances
gcloud app instances list

# Cloud SQL details
gcloud sql instances describe pardus-combat-db

# Storage usage
gcloud sql instances describe pardus-combat-db \
  --format="value(settings.dataDiskSizeGb)"
```

### Q: What if I want to delete everything?

**A:**

```bash
# Delete App Engine version (can't delete last version without disabling)
gcloud app versions delete VERSION_ID

# Delete Cloud SQL instance
gcloud sql instances delete pardus-combat-db

# Delete the entire project (nuclear option)
gcloud projects delete pardus-combat-data
```

**Warning**: Deleting Cloud SQL instance deletes all data permanently!

## Troubleshooting Questions

### Q: I get "502 Bad Gateway" - what's wrong?

**A:** Check logs:
```bash
gcloud app logs tail --level=error
```

Common causes:
1. **PHP syntax error**: Fix the error shown in logs
2. **Database connection failed**: Check `config.php` and `app.yaml`
3. **Memory limit**: Upgrade instance class (costs money)
4. **Timeout**: Query taking too long

### Q: My domain doesn't work but App Engine URL does

**A:** 

Check DNS:
```bash
dig asdwolf.com
nslookup asdwolf.com
```

Should show Google's IP addresses (216.239.x.x)

If not:
1. Verify DNS records at registrar
2. Wait for propagation (up to 48 hours)
3. Check domain mapping: `gcloud app domain-mappings list`

### Q: SSL certificate says "PENDING" or "FAILED_PERMANENT"

**A:**

**PENDING**: Wait up to 24 hours after DNS propagates

**FAILED_PERMANENT**: Usually DNS issues
1. Verify DNS records are correct
2. Remove and re-add domain mapping:
```bash
gcloud app domain-mappings delete asdwolf.com
gcloud app domain-mappings create asdwolf.com --certificate-management=AUTOMATIC
```

### Q: Database queries are slow

**A:**

1. **Check indexes**: Verify indexes exist in database
```sql
SHOW INDEX FROM combat_data;
```

2. **Enable Query Insights**:
```bash
gcloud sql instances patch pardus-combat-db \
  --insights-config-query-insights-enabled
```

3. **Analyze slow queries** in Cloud Console

4. **Upgrade instance** (costs money):
```bash
gcloud sql instances patch pardus-combat-db --tier=db-g1-small
```

### Q: I can't connect to Cloud SQL from App Engine

**A:**

Check these:
1. `app.yaml` has correct connection name in `beta_settings`
2. `config.php` uses Unix socket connection for GCP
3. Cloud SQL API is enabled
4. Cloud SQL instance is running

Verify:
```bash
gcloud sql instances describe pardus-combat-db --format="value(state)"
# Should show: RUNNABLE

gcloud services list | grep sqladmin
# Should show: sqladmin.googleapis.com
```

## Security Questions

### Q: Is my data secure?

**A:** Yes, when configured correctly:
- ✅ HTTPS enforced (all traffic encrypted)
- ✅ Database not publicly accessible (Unix socket)
- ✅ Credentials not in code (via config.php)
- ✅ SQL injection protection (whitelist validation)
- ✅ XSS protection (htmlspecialchars)
- ✅ Automatic security updates (managed platform)

### Q: Should I enable Cloud Armor?

**A:** Only if you experience DDoS attacks. Cloud Armor:
- Costs money (not free tier)
- Requires Load Balancer (additional cost)
- Overkill for small sites

For most users, App Engine's built-in DDoS protection is sufficient.

### Q: How do I rotate database passwords?

**A:**

```bash
# Change password
gcloud sql users set-password pardus_app_user \
  --instance=pardus-combat-db \
  --password=NEW_SECURE_PASSWORD

# Update config.php with new password

# Redeploy
gcloud app deploy
```

### Q: Should I use Secret Manager for credentials?

**A:** For production, yes (more secure). For this project, `config.php` with proper `.gitignore` is acceptable.

To use Secret Manager:
```bash
# Store password
echo -n "password" | gcloud secrets create db-password --data-file=-

# Grant access to App Engine
gcloud secrets add-iam-policy-binding db-password \
  --member="serviceAccount:PROJECT_ID@appspot.gserviceaccount.com" \
  --role="roles/secretmanager.secretAccessor"
```

Then modify `config.php` to read from Secret Manager.

## Migration Questions

### Q: Can I move from GCP to another host later?

**A:** Yes! The application is portable:

1. **Export database**:
```bash
gcloud sql export sql pardus-combat-db \
  gs://bucket/backup.sql \
  --database=pardus_combat_data
```

2. **Download code**: Already in Git repository

3. **Set up new host** with PHP + MySQL

4. **Import database** on new host

5. **Update config.php** with new credentials

### Q: Can I run this locally for development?

**A:** Yes!

1. Install PHP + MySQL locally
2. Create `config.php` with local credentials
3. Import database schema
4. Run with PHP built-in server:
```bash
php -S localhost:8000
```

The `config.php` detects if running on GCP or locally.

## Getting Help

### Q: Where can I get help with deployment?

**A:**

1. **Documentation**:
   - [HOSTING_GOOGLE_CLOUD.md](HOSTING_GOOGLE_CLOUD.md) - Full guide
   - [QUICKSTART_GCP.md](QUICKSTART_GCP.md) - Quick start
   - [DEPLOYMENT_CHECKLIST.md](DEPLOYMENT_CHECKLIST.md) - Checklist

2. **GCP Resources**:
   - [GCP Documentation](https://cloud.google.com/docs)
   - [GCP Community](https://www.googlecloudcommunity.com/)
   - [Stack Overflow](https://stackoverflow.com/questions/tagged/google-cloud-platform)

3. **Project Support**:
   - [GitHub Issues](https://github.com/jonhall145/parduscombatdata/issues)
   - Contact: Asdwolf (Orion) or Ranker Five (Artemis)

### Q: What if something goes wrong?

**A:** 

1. **Check logs**: `gcloud app logs tail --level=error`
2. **Review checklist**: [DEPLOYMENT_CHECKLIST.md](DEPLOYMENT_CHECKLIST.md)
3. **Try troubleshooting section**: [HOSTING_GOOGLE_CLOUD.md#troubleshooting](HOSTING_GOOGLE_CLOUD.md#troubleshooting)
4. **Search Stack Overflow** with error message
5. **Open GitHub issue** with detailed description

Always include:
- Error messages from logs
- Steps to reproduce
- Output of relevant `gcloud` commands

---

**Last Updated**: December 2024

**Didn't find your question?** 
- Check the full guide: [HOSTING_GOOGLE_CLOUD.md](HOSTING_GOOGLE_CLOUD.md)
- Open an issue: https://github.com/jonhall145/parduscombatdata/issues
