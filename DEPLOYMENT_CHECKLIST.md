# Google Cloud Platform Deployment Checklist

Use this checklist to track your deployment progress. Check off items as you complete them.

## Pre-Deployment Preparation

- [ ] Google Account created
- [ ] Access to asdwolf.com DNS settings confirmed
- [ ] Credit card ready for GCP account verification (won't be charged)
- [ ] Google Cloud SDK installed locally
- [ ] Repository cloned to local machine

## Google Cloud Project Setup

- [ ] GCP project created: `pardus-combat-data`
- [ ] Project selected: `gcloud config set project pardus-combat-data`
- [ ] Billing enabled (required for free tier)
- [ ] APIs enabled:
  - [ ] App Engine API
  - [ ] Cloud SQL Admin API
  - [ ] Cloud SQL API
  - [ ] DNS API
- [ ] App Engine initialized in europe-west2 region (or europe-west1/europe-west3)

## Database Setup

- [ ] Cloud SQL instance created: `pardus-combat-db`
- [ ] Root password set and saved securely
- [ ] Database `pardus_combat_data` created
- [ ] Application user `pardus_app_user` created
- [ ] Application user password set and saved securely
- [ ] Cloud SQL connection name obtained and saved
- [ ] Database schema imported from `database_schema.sql`
- [ ] `combat_data` table created successfully
- [ ] Permissions granted to application user

## Application Configuration

- [ ] `app.yaml` updated with Cloud SQL connection name
- [ ] `config.php` created from `config.example.php`
- [ ] `config.php` updated with:
  - [ ] Cloud SQL connection name
  - [ ] Application database password
- [ ] `.gcloudignore` file reviewed
- [ ] Configuration files not committed to git

## Application Deployment

- [ ] First deployment executed: `gcloud app deploy`
- [ ] Deployment successful
- [ ] Application accessible at App Engine URL
- [ ] Basic functionality tested:
  - [ ] Index page loads
  - [ ] Database connection working
  - [ ] Query handler responding
  - [ ] Userscript files downloadable

## Custom Domain Configuration

- [ ] Domain mapping created: `gcloud app domain-mappings create asdwolf.com`
- [ ] DNS records obtained from Google
- [ ] DNS A records added to registrar:
  - [ ] 216.239.32.21
  - [ ] 216.239.34.21
  - [ ] 216.239.36.21
  - [ ] 216.239.38.21
- [ ] DNS AAAA records added to registrar:
  - [ ] 2001:4860:4802:32::15
  - [ ] 2001:4860:4802:34::15
  - [ ] 2001:4860:4802:36::15
  - [ ] 2001:4860:4802:38::15
- [ ] Old DNS records removed/updated
- [ ] DNS propagation verified: `dig asdwolf.com`
- [ ] Website accessible at https://asdwolf.com

## SSL/TLS Certificate

- [ ] Certificate provisioning initiated (automatic)
- [ ] Certificate status checked: `gcloud app domain-mappings describe asdwolf.com`
- [ ] Certificate active (may take up to 24 hours)
- [ ] HTTPS working at asdwolf.com

## Monitoring & Alerts Setup

- [ ] Billing account ID obtained
- [ ] Budget alert created at $5 threshold
- [ ] Alert thresholds set (50%, 90%, 100%)
- [ ] Cloud Console monitoring page bookmarked
- [ ] Log viewing command tested: `gcloud app logs tail`

## Testing & Verification

- [ ] Website loads at https://asdwolf.com
- [ ] Index page displays correctly
- [ ] Combat data analysis visible
- [ ] Query form functional
- [ ] Userscript downloadable
- [ ] Userscript tested with data submission
- [ ] Database receives submitted data
- [ ] No errors in application logs
- [ ] SSL certificate valid (green padlock)
- [ ] All static assets (CSS, JS) loading

## Security Verification

- [ ] `config.php` excluded from version control
- [ ] No credentials in committed files
- [ ] HTTPS enforced (HTTP redirects to HTTPS)
- [ ] Database credentials secured
- [ ] Cloud SQL not publicly accessible
- [ ] Application logs reviewed for security issues
- [ ] Input validation tested
- [ ] XSS protection verified

## Documentation & Handoff

- [ ] Credentials saved in secure location:
  - [ ] GCP project ID
  - [ ] Cloud SQL root password
  - [ ] Cloud SQL app user password
  - [ ] Cloud SQL connection name
- [ ] Deployment commands documented
- [ ] Common maintenance tasks noted
- [ ] Troubleshooting steps reviewed
- [ ] Backup strategy planned
- [ ] Team members trained (if applicable)

## Post-Deployment Tasks

- [ ] Monitor logs for first 24 hours
- [ ] Verify daily active users can submit data
- [ ] Check database growth rate
- [ ] Review billing/usage after 1 week
- [ ] Confirm staying within free tier limits
- [ ] Schedule first manual backup test
- [ ] Add monitoring alerts (optional):
  - [ ] Error rate alert
  - [ ] Latency alert
  - [ ] Database CPU alert

## Ongoing Maintenance (Weekly/Monthly)

- [ ] Review application logs
- [ ] Check billing/usage dashboard
- [ ] Verify backups are running
- [ ] Review database size growth
- [ ] Test backup restoration (monthly)
- [ ] Update dependencies if needed
- [ ] Review security best practices

## Emergency Contacts & Resources

**Key Documentation**
- Full Guide: `HOSTING_GOOGLE_CLOUD.md`
- Quick Start: `QUICKSTART_GCP.md`
- Database Schema: `database_schema.sql`

**Important URLs**
- GCP Console: https://console.cloud.google.com/
- App Engine Dashboard: https://console.cloud.google.com/appengine
- Cloud SQL Dashboard: https://console.cloud.google.com/sql
- Billing Dashboard: https://console.cloud.google.com/billing
- Application URL: https://asdwolf.com

**Support Resources**
- GCP Community: https://www.googlecloudcommunity.com/
- Stack Overflow: https://stackoverflow.com/questions/tagged/google-cloud-platform
- GitHub Issues: https://github.com/jonhall145/parduscombatdata/issues

**Useful Commands**
```bash
# View logs
gcloud app logs tail

# Check app status
gcloud app describe

# Deploy updates
gcloud app deploy

# Connect to database
gcloud sql connect pardus-combat-db --user=pardus_app_user

# Check domain mapping
gcloud app domain-mappings describe asdwolf.com

# List backups
gcloud sql backups list --instance=pardus-combat-db
```

---

## Notes Section

Use this space to record any project-specific notes, issues encountered, or custom configurations:

```
Date: __________
Notes:




```

---

**Deployment Completed By:** ___________________

**Date:** __________

**Total Time:** ________ hours

**Issues Encountered:** 

```



```

**Next Review Date:** __________
