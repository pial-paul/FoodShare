# Deployment and operations

## Fresh installation

Choose a supported PHP build with mysqli/mysqlnd, mbstring and fileinfo, an InnoDB database, and a production web server with PHP-FPM or the supported Apache PHP integration for the host. Use local loopback or a private protected database connection. Deploy PHP and the database on the same trusted host for the first pilot. The current connection code does not configure TLS for a remote database. Review the [PHP support calendar](https://www.php.net/supported-versions.php).

1. Create the database with utf8mb4. Run the two schema files in README order under a dedicated migration identity. Set the PHP runtime account to SELECT/INSERT/UPDATE on the application tables it uses and DELETE on `login_limits` for bucket cleanup. Keep account creation, schema alteration, role provisioning and backup privileges with separate operators. On a host shared with other applications, narrow grants further by table and column.
2. Point the virtual host's document root to the release's **public/** directory. Keep the checkout, database dumps, session files and private photo storage outside it. Disable directory indexes. Deny execution in writable directories. Serve PHP through the host's configured PHP handler. The development server is for local use.
3. Inject the following through the host's secret manager, service settings or FPM pool environment. Confirm that the worker inherits them. Keep secrets out of the repository, command history, screenshots and logs.

| Setting | Meaning |
| --- | --- |
| `FOODSHARE_DB_USER`, `FOODSHARE_DB_PASSWORD` | Required password-protected runtime identity |
| `FOODSHARE_DB_HOST`, `FOODSHARE_DB_PORT`, `FOODSHARE_DB_NAME` | Defaults are 127.0.0.1, 3306 and foodshare_db |
| `FOODSHARE_UPLOAD_DIR` | Persistent private directory owned by the PHP service account. Local default is storage/photos in the checkout. Keep the same volume across releases. |
| `FOODSHARE_TIMEZONE` | IANA zone shared by web workers and expiry scheduler, e.g. Asia/Dhaka for a pilot there. Dates are inclusive through local midnight. Receipt timestamps use UTC. |
| `FOODSHARE_HTTPS=1` | Require secure session cookies behind a TLS-terminating proxy. Redirect HTTP at the edge. |

4. Configure persistent private session storage with a host-appropriate expiry. Set PHP `display_errors=Off`, `log_errors=On`, `upload_max_filesize=5M`, `post_max_size=6M` or greater. Set a matching request-body limit at the proxy. Apply edge throttles to registration, sign-in and photo traffic based on observed use. The application sign-in limit uses the socket peer and email. Behind a proxy this may group users at the same address.
5. Have a coordinator register normally. Verify that person's identity out of band, then promote only the verified user ID through the DBA connection, for example `UPDATE users SET role='admin' WHERE id=? AND email=?` with bound values in the operator's client. Record who approved the promotion. Public registration accepts donor and receiver roles.
6. Schedule `php bin/expire.php` every 15 minutes under the same environment. Browse and write paths independently check the service date. Monitor the job's exit status and last successful completion. Re-running it is safe.
7. Run the full suite against a **separate empty test database**. On staging, verify HTTPS, a two-browser pickup, suspension, sign-out, the error reference in restricted logs, a private photo after redeploy, and a 404 for /config/config.php and /sql/foodshare_db.sql. Complete a restore drill before admitting partners.

## Upgrade an original installation

Stop writes and old workers. Snapshot database, uploads and configuration together and prove a restore into an isolated database. Keep a rollback release. Changing DNS or the document root alone cannot undo schema changes.

1. Against the original populated schema, run `php bin/check-upgrade.php` using the migration identity. Resolve any inconsistent donation IDs with the coordinator. For example, the original race allowed two pending requests for one listing. Determine the actual receiver from partner records and retain a correction log. The check performs reads only.
2. Inspect old free-text locations with donors. Move precise addresses into the new private field and shorten public locations **before** reopening browse. Legacy text may already contain stored HTML entities and backslashes from the original input helper. Correct only reviewed values. Review administrator identities, deactivate the original seeded account and provision an individually verified coordinator. Old MD5 values are unusable with password verification.
3. Run only `sql/migrations/001_handoffs.sql` against the restored copy, then repeat preflight and the migration on the backed-up production database during the write freeze. A MySQL-family DDL sequence can commit statement by statement. If it fails, keep writes stopped and restore the snapshot into a separate database before retrying. Compare row counts by table, request state and donation state. Existing completed rows retain unknown completion time and weight.
4. Copy inspected JPG/PNG files from the old img/uploads directory to the new private photo volume, preserving direct filenames. The protected photo route recognizes the original stored prefix. Quarantine unsupported files. Verify known photos, sample old logins, special characters and listings using the new site. The old upload directory must cease to be web-accessible.
5. Update the web root, take the old installation offline, start the new workers and validate the staging checklist. If rollback becomes necessary **after** accepting writes, freeze again and reconcile new handoffs before switching datasets.

## Reliability and release

Log request route, response status, latency and the application error reference. Restrict access and omit request bodies, cookies, email, addresses and pickup notes. Probe the public landing page **and** run a separate private DB-backed check because the landing page can render during a database outage. Alert on sustained 5xx responses, failed expiry jobs, storage capacity and backup failures. Establish thresholds from staging traffic and the pilot's collection hours.

Take encrypted database and photo backups on the same recovery schedule. Agree acceptable data loss and recovery time with the partner. Restore into an isolated environment, compare row counts and monthly totals, and verify a protected photo and a full new pickup. Record the measured recovery times and the operator. Test session invalidation and rotate database credentials after a service compromise.

Use reviewed feature branches and require green CI before merging. Keep the change log's Unreleased entry until the maintainer approves a version. For the first pilot, create an annotated prerelease tag from the verified merge commit, record migration identifiers, runtime versions and rollback point, then attach release notes. Promote to a stable release after the pilot exit criteria in the roadmap. Continuous deployment requires a selected host and secret configuration.
