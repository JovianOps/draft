# Security Policy

## Supported versions

Security fixes are released for the latest `1.x` template only.

| Version | Supported |
| --- | --- |
| 1.x | Yes |

## Reporting a vulnerability

Report vulnerabilities privately — never via public issue. Use [GitHub Security Advisories](https://github.com/JovianOps/draft/security/advisories/new).

Include the affected version, scaffold answers or project state to reproduce, and expected impact. A minimal reproduction is the most useful report. Accepted reports are fixed and published together with reporter credit unless anonymity is requested.

## Scope

In scope: `scaffold.php` path handling (must never write outside `--dest`) and validation, `bin/deploy.php` ref handling (must never push without `--proceed`), template defaults that weaken generated projects.

Not vulnerabilities here: findings in scaffolded projects' own code, or in Drupal core/contrib downloaded by `bin/assemble.php` — update them and report upstream.
