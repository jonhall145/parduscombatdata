# Pardus Combat Data Analysis

A web application for analyzing combat data from the Pardus online game. This tool collects and analyzes combat statistics to help understand game mechanics like critical hit rates and other combat-related formulas.

## Features

- Combat data collection via userscript
- Statistical analysis of combat results
- Interactive charts and data visualization
- Filtering by various skill parameters
- Export functionality for collected data

## Setup

### Deployment Options

**🚀 Google Cloud Platform (Recommended - FREE)**
- **Free Tier**: Host for free on Google Cloud's free tier with your custom domain
- **📚 Documentation Index**: See [HOSTING_INDEX.md](HOSTING_INDEX.md) for complete guide navigation
- **⚡ Quick Start**: See [QUICKSTART_GCP.md](QUICKSTART_GCP.md) for rapid deployment (15-30 min)
- **📖 Full Guide**: See [HOSTING_GOOGLE_CLOUD.md](HOSTING_GOOGLE_CLOUD.md) for comprehensive instructions (1-2 hours)
- **❓ FAQ**: See [FAQ_GCP_HOSTING.md](FAQ_GCP_HOSTING.md) for common questions and troubleshooting
- Includes automatic scaling, SSL certificates, managed database, and monitoring

**Self-Hosted / Traditional Setup**

### Prerequisites

- PHP 7.0 or higher with mysqli extension
- MySQL/MariaDB database
- Web server (Apache, Nginx, etc.)

### Installation

1. Clone this repository to your web server directory

2. Copy the example configuration file and update with your database credentials:
   ```bash
   cp config.example.php config.php
   ```

3. Edit `config.php` with your database credentials:
   ```php
   define('DB_SERVER', 'localhost');
   define('DB_USERNAME', 'your_username');
   define('DB_PASSWORD', 'your_password');
   define('DB_NAME', 'your_database_name');
   ```

4. Create the necessary database tables using the schema in `database_schema.sql`:
   ```bash
   mysql -u your_username -p your_database_name < database_schema.sql
   ```

5. For HTTPS deployments, ensure your server is configured with SSL/TLS certificates

### Userscript Installation

To contribute combat data, install the userscript:

1. Install a userscript manager (Tampermonkey, Greasemonkey, etc.)
2. Install `skillstat.user.js` from the web interface
3. After each combat in Pardus, press 'y' twice or click the button to submit data

## Security Notes

- **Google Cloud Platform**: Credentials stored in Google Secret Manager (encrypted, audited, easily rotated)
- **Self-Hosted**: Database credentials are stored in `config.php` which is excluded from version control
- All database output is properly escaped to prevent XSS attacks
- Input validation is performed on all user-submitted data
- HTTPS should be used in production to protect data in transit
- Password-protected areas use secure cookie settings

## Files

- `index.php` - Main analysis interface
- `combat_data_handler.php` - Processes combat data submissions
- `query_handler.php` - Handles custom analysis queries
- `skillstat.user.js` - Userscript for data collection
- `config.php` - Database configuration (not in git)
- `login.php` - Authentication for protected areas

## Contributing

Combat data contributions are welcome! Install the userscript and submit your combat results.

For code contributions, please ensure:
- Database credentials are never committed
- All user input is validated and sanitized
- Output is properly escaped to prevent XSS
- Follow existing code style

## Contact

- Asdwolf (Orion)
- Ranker Five (Artemis)
