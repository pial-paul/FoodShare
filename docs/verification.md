# Verification

Run from the repository root with PHP 8.2+, mysqli/mysqlnd, mbstring, fileinfo and Node.js 22+. Node drives HTTP clients for tests. The web application uses PHP alone.

Create a new empty InnoDB database whose name ends in `_test`. Supply its connection settings through the FOODSHARE_DB_* environment variables. Give this disposable test principal schema and fixture privileges on that database only.

```sh
git ls-files -z '*.php' | xargs -0 -n1 php -l
node --test tests/handoffs.test.mjs
```

The suite refuses a nonempty database. It builds baseline tables, inserts synthetic legacy rows, runs preflight and migration, starts two loopback PHP servers, and uses isolated session and photo storage. It leaves the database available for inspection. Use a fresh database for a second run.

| Regression | Evidence in the suite |
| --- | --- |
| Upgrade allocates a pickup incorrectly | An inconsistent fixture stops preflight. A consistent original row and password survive migration. |
| Two receivers reserve the same donation | Separate sessions send concurrent HTTP requests to separate server processes. SQL must contain exactly one assignment. |
| A stale or unrelated action alters the new reservation | Releasing an old request after a re-reservation leaves the current assignment intact. Other receivers cannot approve or confirm it. |
| A listing exposes private pickup notes too early | Browse shows the food, public area and handling notes. The approved receiver's dashboard shows the private instructions. |
| Test or incomplete weights inflate reports | A repeated receipt leaves measured weight intact. An unweighed pickup increments confirmations alone. The monthly CLI report is checked. |
| Invalid input, photo handling or session behavior regresses | The suite checks date validation, CSRF, invalid photo contents, actual image upload and authenticated retrieval, session rotation, logout, account suspension and login throttling. |
| Outdated food stays actionable | Approval is checked after expiry and the expiry job runs twice. An expired item disappears from browse. |

## Evidence boundary

The local suite ran on Windows using PHP 8.2.12 CLI, MariaDB 10.4.32 and Node 22.11.0. These describe the verification environment. Select patched supported service versions for deployment. CI defines separate MySQL 8.4 and MariaDB 10.11 jobs. Check the actual run on the release commit. Screenshots show the running landing and registration routes using synthetic local sessions.

Before a pilot, run keyboard-only and screen-reader walkthroughs across both roles, 200% zoom, a slow-network upload and an interrupted pickup confirmation. Save viewport, browser, assistive technology, observed problem and retest result in the release issue. Load-test a staging fixture representing the expected partner inventory and concurrency. Record p50/p95, database plan and error rate before setting capacity claims. An independent security review, staging restore and participant usability study each need their own evidence.
