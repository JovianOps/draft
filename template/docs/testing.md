# Testing

- `php bin/assemble.php` builds a real Drupal site in `build/` and installs this extension on SQLite. Slow (one `composer create-project`), so run it once per session.
- `php bin/test.php` runs whichever suites exist: `unit` for modules, `functional` smoke for themes.
- Add kernel/functional suites under `tests/src` when the extension earns them; CI picks them up via `phpunit.xml`.

Conventions: one test class per class, data providers for cases, `@group {{MACHINE}}` on everything.
