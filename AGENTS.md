# AGENTS.md — vibecoding on `jovian/draft`

A lean Drupal module/theme scaffold. Four questions, delete-lists, three binaries.

## Run everything

```powershell
php tests/scaffold_test.php   # golden module + theme, offline, must print OK
php -l scaffold.php           # every PHP file, every time
```

`bin/assemble.php` downloads real Drupal — network + time. It runs in CI, not here by default.

## Rules

1. `declare(strict_types=1);` in every PHP file.
2. **Original work only.** Never copy code, text, or fixtures from other projects. Their licenses do not belong here.
3. No marker blocks, no fences, no commented-out variants. Type differences = drop-lists in `scaffold.php` (`themeOnlyPaths` / `moduleOnlyPaths`). Max 5 tokens (`NAME/MACHINE/CLASS/TYPE/DRUPAL`).
4. Every template addition lands in `tests/scaffold_test.php` golden assertions (kept + dropped + no placeholders).
5. Symlink live source, copy tool configs (tools break on symlinked configs). Sentinel files (`.skip_*`) beat flags for skipping slow steps.
6. Keep it lean: no new binary, suite, doc, or dependency without a stated reason.
