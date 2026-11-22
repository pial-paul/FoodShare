# Security

Please report suspected vulnerabilities privately. Use **Security → Report a vulnerability** on GitHub when enabled. If unavailable, ask the maintainer for a private contact method without including sensitive details in the public request. Include the affected commit, feature, expected access boundary and observed behavior. Remove personal data and credentials. The maintainer should acknowledge privately, assess affected releases and coordinate a fix and disclosure. [GitHub reporting guide](https://docs.github.com/en/code-security/how-tos/report-and-fix-vulnerabilities/report-privately)

## Security assessment

| Asset or boundary | Implemented control | Residual risk and release check |
| --- | --- | --- |
| Accounts and credentials | Password hashing and verification, public role allowlist, strict sessions, ID rotation, current role fetched per protected request, pair-based login throttle | Self-registration does not establish a partner's identity. Verify coordinators, restrict pilot access at the host and test edge throttling. |
| State changes | POST, session-bound CSRF token, prepared queries, scoped roles and ownership, row locks, unique assignment | Review every new transition against the handoff state machine. Re-run concurrency tests. |
| User text and photos | Escaped HTML, local CSS, CSP, random private photo filenames, size/dimension and MIME checks, authorized retrieval, nosniff | Original image bytes can contain identifying metadata. Review pilot media policy, storage quotas and retention. Review legacy images and locations before import. |
| Service access and records | Environment secrets, isolated web root, generic server errors with correlation references, reversible moderation | Configure TLS, backups, permissions and log access on the actual host. Account suspension can leave an active reservation requiring coordinator release. |
| Food handling | Handling notes, expiry checks on reads and writes, receiver confirmation | Software cannot inspect food. Collaborating operators must agree acceptance, escalation and incident procedures before distribution. |

The repository includes defensive regression tests. No external penetration test, certification or production audit is claimed.
