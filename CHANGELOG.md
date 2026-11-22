# Change log

## Unreleased — pilot preparation

- Added a complete donor approval and receiver confirmation workflow, expiry cleanup, recorded weight coverage and a monthly aggregate report.
- Replaced empty shared views, placeholder dashboard actions and the duplicate logout handler. Added a responsive local-stylesheet interface and private pickup instructions.
- Moved web entry points under public/, added environment-based configuration and isolated photo serving, hardened session and form handling, and replaced irreversible admin deletion with suspension, withdrawal and release.
- Introduced an additive migration, upgrade preflight, cross-process integration suite, GitHub Actions configuration and deployment runbook.

**Upgrade requirement.** Switch the document root to public/, migrate the existing schema, and move legacy photos into private storage. [Operations](docs/operations.md)
