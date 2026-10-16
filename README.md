# Draft

A lean Drupal module and theme scaffold. Four questions, delete-lists instead of template surgery, three small binaries — then it gets out of your way.

```bash
php scaffold.php --dest=my_module
# or: php scaffold.php --dest=my_module --no-interaction --name="Mine" --machine=mine --type=module --drupal=11
cd my_module && php bin/assemble.php && php bin/test.php
```

## Why another scaffold

Eddy (`drevops/eddy`) is the reference: ~2600-line variant-pruning installer with marker blocks, Ahoy + Makefile duality, 9 lifecycle commands, 10-leg CI matrices, Docusaurus docs. It is powerful and heavy. Draft makes the opposite trades, all original code:

| Eddy | Draft |
| :--- | :--- |
| Marker-block pruning (`#;< TOKEN`) across every file | Copy + delete-lists per type; 5 tokens max |
| Ahoy + Makefile + raw binaries | `composer`-free: `php bin/*.php` directly |
| 9 commands (start/stop/info/qrcode/tunnel/…) | 3 commands: `assemble`, `test`, `deploy` (dry-run by default) |
| 10-leg CI matrix, pcov/Codecov, Selenium | 1 lint + 1 test job, SQLite, no coverage gate until earned |
| Docusaurus site + snapshot fixtures | 3 markdown docs + 2 golden scaffold tests |

Deliberately absent until needed: kernel/functional suites beyond the smoke tests, JS tooling, browser testing, update-in-place (re-scaffold is cheap — extensions are small).

## Layout

- `scaffold.php` — the installer (interactive, flags, `INSTALL_*` env).
- `template/` — the extension; type-specific files are deleted by drop-lists, never fenced.
- `tests/scaffold_test.php` — golden module + theme scaffolds, fully offline.

## Verification status (honest)

- ✅ Scaffolding: proven here — `php tests/scaffold_test.php` prints `OK: module and theme scaffolds match the golden layout.` Zero leftover placeholders under both datasets.
- ✅ All PHP lint-clean (`php -l`).
- ⏳ `bin/assemble.php` (real Drupal download + SQLite install) and the template CI run in CI on every push; treat them as beta until green there.
