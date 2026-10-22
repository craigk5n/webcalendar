# Security Policy

## Supported Versions

| Version | Supported |
|---------|-----------|
| 1.9.x   | Yes       |
| < 1.9   | No        |

Only the latest release in a supported branch receives security patches.

## Reporting a Vulnerability

**Do not file security issues as public GitHub issues.**

To report a vulnerability, email **craig@k5n.us** with:

- A description of the vulnerability
- Steps to reproduce or a proof of concept
- The affected version(s)
- Any suggested fix (optional)

### What to Expect

- **Acknowledgment** within 72 hours of your report.
- **Assessment** and severity determination within 1 week.
- **Fix or mitigation** for confirmed vulnerabilities, typically within
  30 days depending on complexity.
- A coordinated disclosure timeline agreed upon with the reporter.

## Disclosure Policy

We follow coordinated disclosure:

1. Reporter notifies us privately.
2. We confirm and develop a fix.
3. We release the fix and publish a
   [GitHub Security Advisory](https://github.com/craigk5n/webcalendar/security/advisories).
4. Reporter may publish details after the advisory is public.

We aim to resolve confirmed vulnerabilities within 90 days of the
initial report. If more time is needed, we will communicate the revised
timeline to the reporter.

## Credit

We credit reporters in the security advisory and release notes unless
they prefer to remain anonymous. Let us know your preference when
reporting.

## Scope

The following are in scope for security reports:

- Authentication or authorization bypass
- SQL injection
- Cross-site scripting (XSS)
- Cross-site request forgery (CSRF)
- Remote code execution
- Path traversal or local file inclusion
- Information disclosure (credentials, PII, internal paths)
- MCP server token or access control issues

The following are out of scope:

- Denial of service (unless trivially exploitable)
- Issues in third-party dependencies (report to the upstream project)
- Issues requiring physical access to the server
- Social engineering

## Security Best Practices

See [docs/security.md](docs/security.md) for deployment hardening
guidance including file permissions, web server configuration, database
security, and session settings.
