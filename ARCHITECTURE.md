# Pardus Combat Data - Architecture Overview

This document provides a technical overview of the application architecture when hosted on Google Cloud Platform.

## Technology Stack

### Frontend
- **HTML5**: Page structure and content
- **CSS3**: Styling (styles.css)
- **JavaScript**: Client-side interactivity and data visualization
- **GreaseMonkey/TamperMonkey Scripts**: Browser automation for data collection

### Backend
- **PHP 8.2**: Server-side application logic
- **MySQLi**: Database access layer
- **Google App Engine Standard**: Hosting platform

### Database
- **MySQL 8.0**: Relational database via Google Cloud SQL
- **InnoDB**: Storage engine with transaction support
- **UTF-8MB4**: Character encoding for full Unicode support

### Infrastructure
- **Google App Engine**: Serverless PHP hosting
- **Google Cloud SQL**: Managed MySQL database
- **Google Cloud DNS**: Domain name resolution
- **Google Cloud CDN**: Content delivery (static files)
- **SSL/TLS**: Automatic certificate management

## System Architecture

```
┌────────────────────────────────────────────────────────────────┐
│                         Internet Users                          │
└─────┬────────────────────────────────────────────────┬─────────┘
      │                                                 │
      │ HTTPS                                          │ HTTPS
      │ (Port 443)                                     │ (Userscript)
      │                                                 │
┌─────▼─────────────────────────────────────────────────▼─────────┐
│                    Google Cloud DNS                              │
│              (asdwolf.com → App Engine IP)                      │
└─────┬──────────────────────────────────────────────────────────┘
      │
      │ HTTPS with Auto SSL
      │
┌─────▼──────────────────────────────────────────────────────────┐
│               Google App Engine (Standard)                      │
│   ┌──────────────────────────────────────────────────────┐    │
│   │              F1 Instance (Free Tier)                  │    │
│   │  ┌─────────────────────────────────────────────┐     │    │
│   │  │         PHP 8.2 Runtime                      │     │    │
│   │  │  ┌────────────────────────────────────┐     │     │    │
│   │  │  │  Application Files                  │     │     │    │
│   │  │  │  • index.php (main interface)       │     │     │    │
│   │  │  │  • combat_data_handler.php          │     │     │    │
│   │  │  │  • query_handler.php                │     │     │    │
│   │  │  │  • login.php                         │     │     │    │
│   │  │  │  • config.php (env-aware)           │     │     │    │
│   │  │  └────────────────────────────────────┘     │     │    │
│   │  │                                              │     │    │
│   │  │  Static Files (served directly)             │     │    │
│   │  │  • styles.css                                │     │    │
│   │  │  • *.user.js (userscripts)                   │     │    │
│   │  │  • favicon.ico                               │     │    │
│   │  └─────────────────────────────────────────────┘     │    │
│   └──────────────────┬───────────────────────────────────┘    │
└───────────────────────┼────────────────────────────────────────┘
                        │
                        │ Unix Socket Connection
                        │ (/cloudsql/project:region:instance)
                        │
┌───────────────────────▼────────────────────────────────────────┐
│              Google Cloud SQL (MySQL 8.0)                       │
│   ┌──────────────────────────────────────────────────────┐    │
│   │       db-f1-micro Instance (Free Tier)               │    │
│   │  ┌────────────────────────────────────────────┐     │    │
│   │  │  Database: pardus_combat_data              │     │    │
│   │  │  ┌──────────────────────────────────────┐ │     │    │
│   │  │  │  Table: combat_data                   │ │     │    │
│   │  │  │  • id (PRIMARY KEY)                   │ │     │    │
│   │  │  │  • attacker, defender                 │ │     │    │
│   │  │  │  • tactics, hit_accuracy, maneuver   │ │     │    │
│   │  │  │  • weaponry, engineering, evasion    │ │     │    │
│   │  │  │  • crits, hits, shots (x2 weapons)   │ │     │    │
│   │  │  │  • ECM, ECCM, ship info              │ │     │    │
│   │  │  │  • submission_time                    │ │     │    │
│   │  │  │                                        │ │     │    │
│   │  │  │  Indexes:                              │ │     │    │
│   │  │  │  • idx_defender (defender)            │ │     │    │
│   │  │  │  • idx_tactics (tactics)              │ │     │    │
│   │  │  │  • idx_attacker (attacker)            │ │     │    │
│   │  │  └──────────────────────────────────────┘ │     │    │
│   │  └────────────────────────────────────────────┘     │    │
│   │                                                       │    │
│   │  Automatic Backups:                                  │    │
│   │  • Daily at 03:00 UTC                                │    │
│   │  • 7-day retention                                   │    │
│   └──────────────────────────────────────────────────────┘    │
└────────────────────────────────────────────────────────────────┘
```

## Data Flow

### 1. Combat Data Collection

```
┌──────────────────┐
│  Pardus Game     │  User plays game and enters combat
│  (Browser)       │
└────────┬─────────┘
         │
         │ 1. User presses 'y' or clicks button
         │
┌────────▼─────────┐
│  Userscript      │  • Scrapes combat results from page
│  (skillstat.js)  │  • Extracts player skills
│                  │  • Formats data as POST request
└────────┬─────────┘
         │
         │ 2. HTTPS POST to combat_data_handler.php
         │    Data: attacker, defender, skills, combat results
         │
┌────────▼─────────────────┐
│  App Engine              │
│  combat_data_handler.php │  • Validates input data
│                          │  • Sanitizes values
│                          │  • Checks for duplicates
└────────┬─────────────────┘
         │
         │ 3. INSERT query via MySQLi
         │
┌────────▼─────────┐
│  Cloud SQL       │  • Stores combat record
│  combat_data     │  • Updates indexes
│  table           │  • Returns success/failure
└──────────────────┘
```

### 2. Data Analysis & Viewing

```
┌──────────────────┐
│  User Browser    │  User visits asdwolf.com
└────────┬─────────┘
         │
         │ 1. HTTPS GET request
         │
┌────────▼─────────────────┐
│  App Engine              │
│  index.php               │  • Loads page template
│                          │  • Executes pre-defined queries
│                          │  • Fetches statistics
└────────┬─────────────────┘
         │
         │ 2. SELECT queries via MySQLi
         │
┌────────▼─────────┐
│  Cloud SQL       │  • Executes aggregation queries
│  combat_data     │  • Uses indexes for performance
│  table           │  • Returns result sets
└────────┬─────────┘
         │
         │ 3. Result data
         │
┌────────▼─────────────────┐
│  App Engine              │  • Formats results as HTML
│  index.php               │  • Escapes output (XSS protection)
│                          │  • Generates tables/charts
└────────┬─────────────────┘
         │
         │ 4. HTML response with embedded data
         │
┌────────▼─────────┐
│  User Browser    │  • Renders page
│                  │  • Displays statistics
└──────────────────┘
```

### 3. Custom Query Execution

```
┌──────────────────┐
│  User Browser    │  User submits query form
└────────┬─────────┘
         │
         │ 1. HTTPS POST with parameters
         │    (skill ranges, opponent filter)
         │
┌────────▼─────────────────┐
│  App Engine              │
│  query_handler.php       │  • Validates parameters against whitelist
│                          │  • Sanitizes numeric ranges
│                          │  • Constructs safe SQL query
└────────┬─────────────────┘
         │
         │ 2. Dynamic SELECT with WHERE clauses
         │
┌────────▼─────────┐
│  Cloud SQL       │  • Filters by skill ranges
│  combat_data     │  • Filters by opponent
│  table           │  • Aggregates results
└────────┬─────────┘
         │
         │ 3. Filtered result set
         │
┌────────▼─────────────────┐
│  App Engine              │  • Calculates hit rates, crit rates
│  query_handler.php       │  • Formats as HTML table
│                          │  • Escapes all output
└────────┬─────────────────┘
         │
         │ 4. HTML response
         │
┌────────▼─────────┐
│  User Browser    │  • Updates page via AJAX
│                  │  • Displays filtered results
└──────────────────┘
```

## Database Schema

### combat_data Table

```sql
CREATE TABLE combat_data (
    -- Primary Key
    id                 INT AUTO_INCREMENT PRIMARY KEY,
    
    -- Identification
    attacker           VARCHAR(255) NOT NULL,    -- Player name
    defender           VARCHAR(255) NOT NULL,    -- NPC/player name
    logid              VARCHAR(255),             -- Unique combat log ID
    ship               VARCHAR(255),             -- Primary ship
    ship2              VARCHAR(255),             -- Secondary ship
    
    -- Player Skills (post-combat)
    tactics            DECIMAL(10, 2),           -- Combat skill
    hit_accuracy       DECIMAL(10, 2),           -- Accuracy skill
    maneuver           DECIMAL(10, 2),           -- Evasion skill
    weaponry           DECIMAL(10, 2),           -- Weapon skill
    engineering        DECIMAL(10, 2),           -- Tech skill
    evasion            DECIMAL(10, 2),           -- Defense skill
    
    -- Electronic Warfare
    ECM                VARCHAR(50),              -- Jamming system
    ECCM               VARCHAR(50),              -- Anti-jamming system
    
    -- Primary Weapon Stats
    crits              INT,                      -- Critical hits
    critsm             INT,                      -- Mining critical hits
    hits               INT,                      -- Total hits
    hitsm              INT,                      -- Mining hits
    shots              INT,                      -- Total shots
    shotsm             INT,                      -- Mining shots
    jams               INT,                      -- Weapon jams
    
    -- Secondary Weapon Stats
    crits2             INT,                      -- Critical hits
    critsm2            INT,                      -- Mining critical hits
    hits2              INT,                      -- Total hits
    hitsm2             INT,                      -- Mining hits
    shots2             INT,                      -- Total shots
    shotsm2            INT,                      -- Mining shots
    jams2              INT,                      -- Weapon jams
    
    -- Metadata
    submission_time    INT NOT NULL,             -- Unix timestamp
    
    -- Indexes for Performance
    INDEX idx_defender (defender),               -- Filter by opponent
    INDEX idx_tactics (tactics),                 -- Filter by skill
    INDEX idx_attacker (attacker),               -- Filter by player
    INDEX idx_submission_time (submission_time), -- Time-based queries
    INDEX idx_skills (tactics, hit_accuracy,     -- Multi-skill filters
                     maneuver, weaponry, 
                     engineering)
) ENGINE=InnoDB 
  DEFAULT CHARSET=utf8mb4 
  COLLATE=utf8mb4_unicode_ci;
```

### Query Patterns

**Common queries optimized by indexes:**

```sql
-- Analysis by tactics level (uses idx_tactics)
SELECT FLOOR(tactics), SUM(hits), SUM(crits), defender
FROM combat_data
GROUP BY FLOOR(tactics), defender;

-- Filter by opponent (uses idx_defender)
SELECT * FROM combat_data
WHERE defender = 'Space Dragon Queen';

-- Filter by skill ranges (uses idx_skills)
SELECT * FROM combat_data
WHERE tactics BETWEEN 50 AND 100
  AND hit_accuracy BETWEEN 40 AND 90;

-- Player history (uses idx_attacker)
SELECT * FROM combat_data
WHERE attacker = 'Asdwolf'
ORDER BY submission_time DESC;
```

## Security Architecture

### Input Validation

```
User Input
    │
    ├─→ Type Checking (is_numeric, isset)
    │       │
    │       ├─→ PASS → Continue
    │       └─→ FAIL → Reject request
    │
    ├─→ Whitelist Validation (for SQL column names)
    │       │
    │       ├─→ PASS → Use validated value
    │       └─→ FAIL → Reject request
    │
    ├─→ Range Validation (0-200 for skills)
    │       │
    │       ├─→ PASS → Continue
    │       └─→ FAIL → Reject request
    │
    └─→ Sanitization (addslashes for special contexts)
            │
            └─→ Safe to use in application
```

### Output Escaping

```
Database Data
    │
    ├─→ HTML Context
    │       │
    │       └─→ htmlspecialchars($value, ENT_QUOTES, 'UTF-8')
    │               │
    │               └─→ Safe HTML output
    │
    ├─→ JavaScript Context
    │       │
    │       └─→ addslashes($value) + JSON encoding
    │               │
    │               └─→ Safe JS output
    │
    └─→ SQL Context
            │
            └─→ Parameterized queries or whitelist
                    │
                    └─→ SQL injection safe
```

### Connection Security

```
┌─────────────────────┐
│  External Request   │
└─────────┬───────────┘
          │
          │ HTTPS Only (enforced by app.yaml)
          │ TLS 1.2+ with modern ciphers
          │
┌─────────▼───────────┐
│  App Engine         │
│  (secure: always)   │
└─────────┬───────────┘
          │
          │ Unix Socket (not TCP)
          │ Private connection
          │ No network exposure
          │
┌─────────▼───────────┐
│  Cloud SQL          │
│  (no public IP)     │
└─────────────────────┘
```

## Performance Considerations

### Caching Strategy

```
Static Files (app.yaml handlers):
├─→ Userscripts: 1 day cache
├─→ CSS files: 1 day cache
├─→ Favicon: 7 days cache
└─→ HTML files: 1 hour cache

Dynamic Content:
├─→ No caching (always fresh data)
└─→ Database queries optimized with indexes
```

### Auto-Scaling

```
Traffic Level          Instance Count    Cost
─────────────────────────────────────────────
No traffic            0 instances       $0.00
Low (< 100 req/min)   1 instance (F1)   $0.00 (free tier)
Medium (100-500)      1-2 instances     $0.00-0.05/hr
High (> 500)          2+ instances      $0.05+/hr per instance

Free tier provides 28 instance hours/day = 1 instance 24/7
```

### Database Performance

```
Query Type                    Optimization
──────────────────────────────────────────────
Filter by defender            idx_defender index
Filter by tactics range       idx_tactics index
Filter by multiple skills     idx_skills composite index
Player history lookup         idx_attacker index
Time-based queries           idx_submission_time index

Expected Performance:
├─→ Simple queries: < 50ms
├─→ Aggregations: 50-200ms
└─→ Complex multi-filter: 200-500ms
```

## Deployment Process

### Build & Deploy Flow

```
Local Development
    │
    ├─→ 1. Code changes in repository
    │
    ├─→ 2. Update app.yaml (if needed)
    │
    ├─→ 3. Test locally with PHP dev server
    │
    ├─→ 4. Commit to Git
    │
    └─→ 5. Deploy to GCP
            │
            ├─→ gcloud app deploy
            │       │
            │       ├─→ Package application files
            │       ├─→ Upload to Google Cloud Storage
            │       ├─→ Build container image
            │       ├─→ Deploy to App Engine
            │       └─→ Route traffic to new version
            │
            └─→ Deployment complete (2-5 minutes)
```

### Zero-Downtime Deployment

```
Old Version (v1)           New Version (v2)
    100% traffic               0% traffic
        │                          │
        ├─→ Deploy v2              │
        │                          │
        ├─→ v2 starts    ← ────────┘
        │      │
        │      └─→ Health check passes
        │
        ├─→ Traffic gradually shifts
        │      50% v1, 50% v2
        │
        ├─→ All traffic to v2
        │      0% v1, 100% v2
        │
        └─→ v1 stopped after 1 hour
```

## Monitoring & Observability

### Available Metrics

```
App Engine Metrics:
├─→ Request count (requests/second)
├─→ Request latency (p50, p95, p99)
├─→ Error rate (4xx, 5xx responses)
├─→ Instance count (current active)
├─→ CPU utilization (%)
└─→ Memory usage (MB)

Cloud SQL Metrics:
├─→ Database connections (active)
├─→ Query execution time (ms)
├─→ CPU utilization (%)
├─→ Memory usage (MB)
├─→ Storage used (GB)
└─→ Network bytes sent/received
```

### Logging Architecture

```
Application Logs
    │
    ├─→ PHP error_log() → Cloud Logging
    ├─→ HTTP access logs → Cloud Logging
    ├─→ MySQL slow query log → Cloud Logging
    └─→ Security events → Cloud Logging
            │
            ├─→ Searchable in Cloud Console
            ├─→ Exportable to BigQuery
            ├─→ Alerting via Cloud Monitoring
            └─→ Retention: 30 days (default)
```

## Cost Breakdown

### Free Tier Usage (Monthly)

```
Service              Limit              Usage        Cost
─────────────────────────────────────────────────────────
App Engine F1       28 hrs/day         24 hrs/day   $0.00
Cloud SQL f1-micro  1st instance       1 instance   $0.00
Cloud SQL Storage   10 GB              < 5 GB       $0.00
Data Egress         1 GB/day           < 500 MB/day $0.00
SSL Certificates    Unlimited          1-2 certs    $0.00
DNS Queries         1 billion/month    < 1k/day     $0.00
─────────────────────────────────────────────────────────
TOTAL                                                $0.00
```

### If You Exceed Free Tier

```
Overage Scenario                      Additional Cost
────────────────────────────────────────────────────────
+1 F1 instance (1 hour)              ~$0.05
+10 GB database storage               $1.70/month
+10 GB data egress                    $1.20
─────────────────────────────────────────────────────────

Recommended: Set billing alert at $5 to catch overages
```

## Maintenance Operations

### Common Tasks

```
Update Application:
    git pull → gcloud app deploy (2-5 min)

Database Backup:
    Automatic daily → Manual: gcloud sql backups create

View Logs:
    gcloud app logs tail (real-time)

Rollback:
    gcloud app services set-traffic default --splits=v1=1

Database Maintenance:
    gcloud sql connect pardus-combat-db --user=root
    → OPTIMIZE TABLE combat_data;
```

## Disaster Recovery

### Backup Strategy

```
Component          Backup Method           Frequency    Retention
────────────────────────────────────────────────────────────────────
Database Data      Automatic backup        Daily        7 days
Database Data      Manual export          Weekly       30 days
Application Code   Git repository         On change    Permanent
Configuration      Secret Manager         On change    Versioned
```

### Recovery Procedures

```
Scenario                  Recovery Method              RTO
─────────────────────────────────────────────────────────────
App Engine failure        Automatic (multi-zone)       < 1 min
Database failure          Automatic failover           < 2 min
Data corruption           Restore from backup          30-60 min
Complete project loss     Redeploy from Git + backup   2-4 hours
Accidental deletion       Restore specific backup      30-60 min
```

---

**For deployment instructions, see:**
- [HOSTING_GOOGLE_CLOUD.md](HOSTING_GOOGLE_CLOUD.md) - Full deployment guide
- [QUICKSTART_GCP.md](QUICKSTART_GCP.md) - Quick start guide
- [FAQ_GCP_HOSTING.md](FAQ_GCP_HOSTING.md) - Common questions

**Last Updated**: December 2024
