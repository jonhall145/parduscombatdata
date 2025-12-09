# GitHub Copilot Custom Instructions

## Repository Purpose

This repository hosts the **Pardus Combat Data** project - a combat statistics collection and analysis system for the Pardus browser-based MMORPG. The project consists of:

- **Backend**: PHP-based web application for data collection, storage, and analysis
- **Frontend**: HTML/CSS interface for viewing combat statistics and running queries
- **Userscripts**: GreaseMonkey/TamperMonkey scripts for automated data collection from the game
- **Database**: MySQL database for storing combat logs and player statistics

The system allows players to contribute combat data by installing a userscript that captures combat results and player stats, helping the community understand combat mechanics and critical hit calculations.

## Project Structure

- **`index.php`**: Main landing page with combat analysis results and explanations
- **`combat_data_handler.php`**: Primary backend script for receiving and storing combat data from userscripts
- **`combat_data_handler_beta.php`**: Beta version of the data handler for testing
- **`query_handler.php`**: Processes custom queries from the web interface
- **`skillstat.user.js`**: Production userscript for data collection
- **`skillstat_auto.user.js`**: Auto-submit variant of the userscript
- **`skillstat_cr.user.js`**: Variant for combat record integration
- **`skill_stat_user_testing.user.js`**: Development/testing version of the userscript
- **`debugger.php`**: Debugging utilities for troubleshooting
- **`database_dump.php`**, **`csv_dump.php`**: Data export utilities
- **`styles.css`**: Styling for web interface
- **`*.html`**: Various test pages and interface components

## Coding Conventions

### PHP Code
- Use standard PHP opening tags `<?php`
- Database connections should use MySQLi with error handling
- Sanitize all user inputs to prevent SQL injection
- Use descriptive variable names (e.g., `$tacticsquery`, `$npcresults`)
- Follow existing patterns for database queries and result handling
- Keep database credentials in a consistent location (note: these should be moved to environment variables in production)

### JavaScript/Userscripts
- Use strict mode: `'use strict';`
- Follow GreaseMonkey userscript metadata format
- Use descriptive variable names with camelCase
- Include proper @grant directives for required permissions
- Maintain version numbers in userscript headers
- Use `GM_xmlhttpRequest` for cross-origin requests in userscripts

### General
- Files use UTF-8 encoding
- Line endings should be consistent with the codebase
- Keep existing code style and formatting patterns

## Security Considerations

⚠️ **Critical**: This codebase currently contains hardcoded database credentials in PHP files. When making changes:

1. **Never commit new plaintext credentials** to the repository
2. **Avoid exposing credentials** in error messages or logs
3. **Recommend environment variables** for credential management when refactoring
4. **Sanitize all user inputs** - this is a web application accepting data from userscripts
5. **Validate data types** before database operations (e.g., casting to float/int)
6. **Use prepared statements** or proper escaping for all database queries

## Contribution Guidelines

### Before Making Changes
1. Understand the context of combat data flow: userscript → PHP handler → database → query interface
2. Test locally if possible, or clearly document testing requirements
3. Be mindful that this is a community project used by active players

### Code Changes Should Include
- Clear comments explaining complex game mechanics or calculations
- Preservation of existing functionality unless explicitly fixing a bug
- Input validation for any new data reception endpoints
- Updates to relevant documentation or comments

### Database Changes
- Document any schema changes clearly
- Ensure backward compatibility with existing userscripts where possible
- Consider data migration implications

## Testing

- Test PHP scripts by accessing them through a web server
- Test userscripts in both Firefox (with GreaseMonkey) and Chrome (with TamperMonkey)
- Verify database connectivity and query results
- Test with sample combat data that mirrors real game outputs
- Check error handling with invalid inputs

## Known Issues & Technical Debt

1. **Database credentials are hardcoded** in multiple PHP files - should be moved to environment variables or config file
2. **Error log files** are committed to repository - should be in `.gitignore` (already partially addressed)
3. **Large CSV files** are tracked - `.gitignore` rules exist but some may already be committed
4. **Limited input validation** in some handlers - should be enhanced
5. **No automated testing infrastructure** - tests are manual

## References

- **Pardus Game**: https://www.pardus.at/
- **GreaseMonkey Documentation**: https://www.greasespot.net/
- **TamperMonkey Documentation**: https://www.tampermonkey.net/documentation.php

## Special Instructions for Copilot

### High Priority Tasks
- Security improvements (input validation, credential management)
- Bug fixes in data collection or display
- Query optimization for better performance
- Documentation improvements

### Approach with Caution
- Changes to database schema (coordinate with maintainers)
- Modifications to userscript data format (may break existing installations)
- Changes to combat calculation formulas (requires game mechanics understanding)

### Not Recommended for Copilot
- Large-scale refactoring without maintainer approval
- Changes that would require all users to update their userscripts immediately
- Modifications to core combat data interpretation logic without testing data

## Contact

For questions about this project, contact:
- Asdwolf (Orion server)
- Ranker Five (Artemis server)

---

*Last updated: December 2025*
