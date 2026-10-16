# {{NAME}}

A contributed Drupal {{TYPE}}.

## Develop

```bash
php bin/assemble.php   # throwaway Drupal site in build/ with this {{TYPE}} enabled
php bin/test.php       # unit suite (modules) / front-page smoke (themes)
```

`build/` is gitignored. Delete and re-assemble any time.

## Test

Suites live under `tests/src`. Unit tests run without a database; the theme smoke test boots the assembled site on SQLite. CI runs both on every push (`.github/workflows/test.yml`).

## Release

Tag `1.0.0`, `1.1.0`, … — the deploy workflow mirrors the tag to `DEPLOY_REMOTE` (e.g. `git@git.drupal.org:project/{{MACHINE}}.git`) after tests pass. Set `DEPLOY_SSH_KEY`, `DEPLOY_KNOWN_HOSTS`, `DEPLOY_REMOTE`, `DEPLOY_USER_NAME`, `DEPLOY_USER_EMAIL`, and optional `DEPLOY_BRANCH` as repo secrets/variables. Dry-run any time: `php bin/deploy.php` without `--proceed`.

## License

Ships under the Jovian Source-Available License (`LICENSE`). Publishing to Drupal.org requires GPL-2.0-or-later — relicense deliberately if you go that route.
