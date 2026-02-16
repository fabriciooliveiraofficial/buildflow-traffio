# Orchestration Plan: Login (Production DB) and BrowserSync Fix

## Goal
Connect the local development environment to the production MySQL database (Hostinger) and fix the BrowserSync "Connection Refused" error.

## 1. Login Issue (Production DB Connection)
- **Constraint**: Hostinger shared hosting requires enabling "Remote MySQL" for specific IPs.
- **Action**: Update `.env` with production database credentials.
- **Verification**: Use `db_test.php` to confirm remote connectivity from the container.

## 2. BrowserSync Issue (WebSocket Port)
- **Problem**: The browser-sync client is hardcoded (or detecting internal port) to 3000, but exposed as 3005.
- **Action**: Force the socket port and domain in `docker-compose.yml` or a `bs-config.js`.

## Orchestration Roles (Phase 2)
- **backend-specialist**: Configure `.env` and handle auth logic verification.
- **database-architect**: Test remote MySQL connectivity and troubleshoot firewall issues.
- **devops-engineer**: Fix BrowserSync port mapping and ensure container health.
- **test-engineer**: Run `checklist.py` and verify end-to-end functionality.

## Success Criteria
- [ ] Local app connects to Hostinger MySQL.
- [ ] Login successful with production credentials.
- [ ] BrowserSync console is clean and "Live Reload" is active.
