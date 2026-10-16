<?php

declare(strict_types=1);

/**
 * @file
 * Draft installer: turns this template into a Drupal module or theme.
 *
 * Usage:
 *   php scaffold.php --dest=/path/to/my_module
 *   php scaffold.php --dest=/path/to/my_module --no-interaction \
 *     --name="My Module" --machine=my_module --type=module --drupal=11
 *
 * Env pre-fills (INSTALL_NAME, INSTALL_MACHINE, INSTALL_TYPE, INSTALL_DRUPAL)
 * beat interactive answers. Files not selected are deleted, never fenced.
 */

const DRAFT_VERSION = '1.0.0';

main($argv);

function main(array $argv): void {
  $options = parseScaffoldOptions($argv);

  if (isset($options['help'])) {
    echo "Draft installer " . DRAFT_VERSION . "\n";
    echo "  --dest=PATH        Destination directory (required).\n";
    echo "  --no-interaction   Use flags/env instead of questions.\n";
    echo "  --name=TEXT        Extension name.\n";
    echo "  --machine=NAME     Machine name [a-z0-9_].\n";
    echo "  --type=TYPE        module|theme.\n";
    echo "  --drupal=VERSION   10|11 (default 11).\n";
    exit(0);
  }

  $dest = $options['dest'] ?? '';

  if ($dest === '') {
    fwrite(STDERR, "Missing --dest. See --help.\n");
    exit(1);
  }

  $answers = isset($options['no-interaction']) ? fromFlags($options) : askDraftQuestions();
  $answers = normalizeDraftAnswers($answers);
  validateDraftAnswers($answers);

  $rendered = renderDraft(__DIR__ . '/template', $dest, $answers);
  writeDraftReceipt($dest, $answers);

  printf("Scaffolded %s (%s) in %d file(s).\n", $answers['machine'], $answers['type'], $rendered);
}

/**
 * Parse CLI flags.
 */
function parseScaffoldOptions(array $argv): array {
  $options = [];

  foreach (array_slice($argv, 1) as $arg) {
    if ($arg === '--help' || $arg === '--no-interaction') {
      $options[ltrim($arg, '-')] = TRUE;
      continue;
    }

    foreach (['--dest=', '--name=', '--machine=', '--type=', '--drupal='] as $prefix) {
      if (str_starts_with($arg, $prefix)) {
        $options[rtrim(substr($prefix, 2, -1), '=')] = substr($arg, strlen($prefix));
        continue 2;
      }
    }

    fwrite(STDERR, sprintf("Unknown option: %s\n", $arg));
    exit(1);
  }

  return $options;
}

/**
 * Answers from flags, falling back to INSTALL_* env, then blanks.
 */
function fromFlags(array $options): array {
  return [
    'name' => $options['name'] ?? getenv('INSTALL_NAME') ?: '',
    'machine' => $options['machine'] ?? getenv('INSTALL_MACHINE') ?: '',
    'type' => $options['type'] ?? getenv('INSTALL_TYPE') ?: 'module',
    'drupal' => $options['drupal'] ?? getenv('INSTALL_DRUPAL') ?: '11',
  ];
}

/**
 * Ask the four questions on STDIN.
 */
function askDraftQuestions(): array {
  $ask = static function (string $label, string $default = ''): string {
    $suffix = $default !== '' ? sprintf(' [%s]', $default) : '';
    fwrite(STDOUT, sprintf('%s%s: ', $label, $suffix));
    $input = trim((string) fgets(STDIN));

    return $input !== '' ? $input : $default;
  };

  return [
    'name' => $ask('Extension name'),
    'machine' => $ask('Machine name (empty to derive)'),
    'type' => $ask('Type [module|theme]', 'module'),
    'drupal' => $ask('Drupal major [10|11]', '11'),
  ];
}

/**
 * Derive blanks and lowercase tokens.
 */
function normalizeDraftAnswers(array $answers): array {
  if (($answers['machine'] ?? '') === '') {
    $slug = strtolower((string) ($answers['name'] ?? 'draft'));
    $slug = (string) preg_replace('/[^a-z0-9]+/', '_', $slug);
    $answers['machine'] = trim($slug, '_') !== '' ? trim($slug, '_') : 'draft';
  }

  $answers['machine'] = strtolower((string) $answers['machine']);
  $answers['type'] = strtolower((string) ($answers['type'] ?? 'module'));
  $answers['drupal'] = (string) ($answers['drupal'] ?? '11');
  $answers['class'] = str_replace(' ', '', ucwords(str_replace('_', ' ', (string) $answers['machine'])));

  return $answers;
}

/**
 * Validate the normalized answers.
 */
function validateDraftAnswers(array $answers): void {
  if (($answers['name'] ?? '') === '') {
    fwrite(STDERR, "Extension name is required.\n");
    exit(1);
  }

  if (preg_match('/^[a-z0-9_]+$/', (string) $answers['machine']) !== 1) {
    fwrite(STDERR, "Machine name must match [a-z0-9_].\n");
    exit(1);
  }

  if (!in_array($answers['type'], ['module', 'theme'], TRUE)) {
    fwrite(STDERR, "Type must be module|theme.\n");
    exit(1);
  }

  if (!in_array($answers['drupal'], ['10', '11'], TRUE)) {
    fwrite(STDERR, "Drupal major must be 10|11.\n");
    exit(1);
  }
}

/**
 * Copy the template, drop unselected files, render tokens. Returns count.
 */
function renderDraft(string $template, string $dest, array $answers): int {
  if (!is_dir($template)) {
    fwrite(STDERR, "Template directory missing.\n");
    exit(1);
  }

  $tokens = [
    '{{NAME}}' => (string) $answers['name'],
    '{{MACHINE}}' => (string) $answers['machine'],
    '{{CLASS}}' => (string) $answers['class'],
    '{{TYPE}}' => (string) $answers['type'],
    '{{DRUPAL}}' => (string) $answers['drupal'],
  ];

  $drop = $answers['type'] === 'module' ? themeOnlyPaths() : moduleOnlyPaths();
  $count = 0;

  $iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($template, FilesystemIterator::SKIP_DOTS),
    RecursiveIteratorIterator::SELF_FIRST
  );

  foreach ($iterator as $file) {
    $relative = substr($file->getPathname(), strlen($template) + 1);
    $slash = str_replace(DIRECTORY_SEPARATOR, '/', $relative);

    if (dropDraftPath($slash, $drop)) {
      continue;
    }

    $target_relative = str_replace('your_extension', (string) $answers['machine'], $slash);
    $target_relative = str_replace('YourExtension', (string) $answers['class'], $target_relative);
    $target_relative = strtr($target_relative, $tokens);
    $target = rtrim($dest, '/\\') . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $target_relative);

    if ($file->isDir()) {
      if (!is_dir($target)) {
        mkdir($target, 0777, TRUE);
      }
      continue;
    }

    if (is_file($target)) {
      fwrite(STDERR, sprintf("Refusing to overwrite %s.\n", $target_relative));
      exit(1);
    }

    $content = strtr((string) file_get_contents($file->getPathname()), $tokens);

    if ($answers['type'] === 'module' && str_ends_with($target_relative, '.info.yml')) {
      // Themes require a base theme; modules must not declare one.
      $content = (string) preg_replace('/^base theme:.*(\r?\n)?/m', '', $content);
    }

    if (preg_match('/\{\{[A-Z]+\}\}/', $content) === 1 || preg_match('/\{\{[A-Z]+\}\}/', $target_relative) === 1) {
      fwrite(STDERR, sprintf("Unreplaced token in %s.\n", $target_relative));
      exit(1);
    }

    $dir = dirname($target);
    if (!is_dir($dir)) {
      mkdir($dir, 0777, TRUE);
    }

    file_put_contents($target, $content);
    $count++;
  }

  return $count;
}

/**
 * Paths kept only for themes (dropped when scaffolding a module).
 *
 * @return list<string>
 */
function themeOnlyPaths(): array {
  return ['your_extension.theme', 'your_extension.libraries.yml', 'css/', 'js/', 'tests/src/Functional/'];
}

/**
 * Paths kept only for modules (dropped when scaffolding a theme).
 *
 * @return list<string>
 */
function moduleOnlyPaths(): array {
  return ['src/', 'config/', 'tests/src/Unit/', 'your_extension.module', 'your_extension.routing.yml', 'your_extension.services.yml'];
}

/**
 * Whether a template path is dropped for this install.
 */
function dropDraftPath(string $slash, array $drop): bool {
  foreach ($drop as $prefix) {
    $base = rtrim($prefix, '/');

    if ($slash === $base || str_starts_with($slash, $base . '/')) {
      return TRUE;
    }
  }

  return FALSE;
}

/**
 * Store answers for future reference.
 */
function writeDraftReceipt(string $dest, array $answers): void {
  $receipt = ['draft' => DRAFT_VERSION, 'date' => date('c'), 'answers' => $answers];
  file_put_contents(
    rtrim($dest, '/\\') . DIRECTORY_SEPARATOR . '.draft.json',
    json_encode($receipt, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n"
  );
}
