<?php

declare(strict_types=1);

/**
 * @file
 * Scaffold a module and a theme in sandboxes; assert the golden layout.
 *
 * No network, no Docker, no Drupal. Run with: php tests/scaffold_test.php
 */

$failures = [];

$check = static function (string $description, bool $condition) use (&$failures): void {
  if (!$condition) {
    $failures[] = $description;
  }
};

$scaffold = static function (string $name, string $args) use (&$failures): string {
  $dest = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'draft-test-' . $name . '-' . getmypid();
  removeDraftTree($dest);

  $command = sprintf('%s %s --dest=%s --no-interaction %s 2>&1', PHP_BINARY, escapeshellarg(__DIR__ . '/../scaffold.php'), escapeshellarg($dest), $args);
  exec($command, $output, $code);

  if ($code !== 0) {
    $failures[] = sprintf('Scaffold failed for %s: %s', $name, implode("\n", $output));
  }

  return $dest;
};

$has = static function (string $dest, string $path): bool {
  return file_exists($dest . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $path));
};

// Module dataset.
$module = $scaffold('module', '--name="Greet" --machine=greet --type=module --drupal=11');

foreach (['greet.info.yml', 'greet.module', 'greet.routing.yml', 'greet.services.yml', 'src/GreetService.php', 'src/Controller/GreetController.php', 'config/schema/greet.schema.yml', 'tests/src/Unit/GreetServiceTest.php', 'composer.json', 'phpunit.xml', 'bin/assemble.php', 'bin/test.php', 'bin/deploy.php', '.github/workflows/test.yml', '.github/workflows/deploy.yml', 'docs/testing.md', 'README.md', 'LICENSE', '.draft.json'] as $file) {
  $check(sprintf('Module keeps %s.', $file), $has($module, $file));
}

foreach (['greet.theme', 'greet.libraries.yml', 'css/style.css', 'js/script.js', 'tests/src/Functional/LoadTest.php'] as $file) {
  $check(sprintf('Module drops %s.', $file), !$has($module, $file));
}

$info = (string) @file_get_contents($module . '/greet.info.yml');
$check('Module info declares module type.', str_contains($info, 'type: module'));
$service = (string) @file_get_contents($module . '/src/GreetService.php');
$check('Service uses the machine namespace.', str_contains($service, 'namespace Drupal\greet;'));
$check('Service class derives from the machine name.', str_contains($service, 'class GreetService'));
$composer = json_decode((string) @file_get_contents($module . '/composer.json'), TRUE);
$check('Rendered composer.json is valid with drupal-module type.', ($composer['type'] ?? '') === 'drupal-module');

// Theme dataset.
$theme = $scaffold('theme', '--name="Breeze" --machine=breeze --type=theme --drupal=10');

foreach (['breeze.info.yml', 'breeze.theme', 'breeze.libraries.yml', 'css/style.css', 'js/script.js', 'tests/src/Functional/LoadTest.php', 'composer.json', 'bin/assemble.php', 'README.md', 'LICENSE'] as $file) {
  $check(sprintf('Theme keeps %s.', $file), $has($theme, $file));
}

foreach (['breeze.module', 'src/', 'config/', 'tests/src/Unit/'] as $file) {
  $check(sprintf('Theme drops %s.', $file), !$has($theme, $file));
}

$theme_info = (string) @file_get_contents($theme . '/breeze.info.yml');
$check('Theme info declares theme type and Drupal 10.', str_contains($theme_info, 'type: theme') && str_contains($theme_info, '^10'));

// No placeholders survive anywhere.
foreach (['module' => $module, 'theme' => $theme] as $label => $dest) {
  $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dest, FilesystemIterator::SKIP_DOTS));

  foreach ($iterator as $file) {
    $path = $file->getPathname();

    if ($file->isFile() && preg_match('/\{\{[A-Z]+\}\}|your_extension|YourExtension/', (string) file_get_contents($path)) === 1) {
      $check(sprintf('%s leaves no placeholders (%s).', $label, basename($path)), FALSE);
    }

    if (preg_match('/your_extension|YourExtension/', $path) === 1) {
      $check(sprintf('%s leaves no placeholder paths (%s).', $label, basename($path)), FALSE);
    }
  }
}

// Invalid input fails.
$bad_dest = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'draft-test-bad-' . getmypid();
exec(sprintf('%s %s --dest=%s --no-interaction --name="X" --machine="BAD NAME" --type=module 2>&1', PHP_BINARY, escapeshellarg(__DIR__ . '/../scaffold.php'), escapeshellarg($bad_dest)), $bad_out, $bad_code);
$check('Bad machine name fails.', $bad_code !== 0);

if ($failures !== []) {
  echo "FAIL\n";

  foreach ($failures as $failure) {
    echo ' - ' . $failure . PHP_EOL;
  }

  exit(1);
}

echo "OK: module and theme scaffolds match the golden layout.\n";

/**
 * Remove a directory tree.
 */
function removeDraftTree(string $dir): void {
  if (!is_dir($dir)) {
    return;
  }

  $iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS),
    RecursiveIteratorIterator::CHILD_FIRST
  );

  foreach ($iterator as $file) {
    $file->isDir() ? rmdir($file->getPathname()) : unlink($file->getPathname());
  }

  rmdir($dir);
}
