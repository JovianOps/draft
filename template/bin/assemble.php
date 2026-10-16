<?php

declare(strict_types=1);

/**
 * @file
 * Assemble a throwaway Drupal site with this extension installed.
 *
 * Usage: php bin/assemble.php [--drupal=11] [--profile=minimal]
 *
 * Builds into build/ (gitignored): composer create-project, symlink the
 * extension, install to SQLite, enable the extension. Nothing here is clever;
 * read it before trusting it.
 */

main($argv);

function main(array $argv): void {
  $drupal = '11';
  $profile = 'minimal';

  foreach (array_slice($argv, 1) as $arg) {
    if (str_starts_with($arg, '--drupal=')) {
      $drupal = substr($arg, strlen('--drupal='));
    }
    elseif (str_starts_with($arg, '--profile=')) {
      $profile = substr($arg, strlen('--profile='));
    }
    else {
      fwrite(STDERR, sprintf("Unknown option: %s\n", $arg));
      exit(1);
    }
  }

  $root = dirname(__DIR__);
  $build = $root . DIRECTORY_SEPARATOR . 'build';
  $machine = machineName($root);

  // Fail fast: Drupal 11 needs PHP 8.3+, Drupal 10 needs PHP 8.1+. Checking
  // before the download beats failing five minutes into composer install.
  step('Checking PHP against Drupal requirements');
  $minimum_php = ['10' => '8.1', '11' => '8.3'];

  if (!isset($minimum_php[$drupal])) {
    fwrite(STDERR, sprintf("Unsupported Drupal major: %s (want 10|11).\n", $drupal));
    exit(1);
  }

  $running = sprintf('%d.%d', PHP_MAJOR_VERSION, PHP_MINOR_VERSION);

  if (version_compare($running, $minimum_php[$drupal], '<')) {
    fwrite(STDERR, sprintf("Drupal %s needs PHP %s+, running on PHP %s.\n", $drupal, $minimum_php[$drupal], $running));
    exit(1);
  }

  echo "  PHP {$running} meets Drupal {$drupal} (>= {$minimum_php[$drupal]}).\n";

  step(sprintf('Creating Drupal %s site in build/', $drupal));
  run(sprintf('composer create-project --no-install --no-interaction --no-progress drupal/recommended-project:^%s %s', escapeshellarg($drupal), escapeshellarg($build)));

  // Distribution zips of drupal/core leave out core/tests, which holds the
  // test base classes and the PHPUnit bootstrap. Install core from source.
  step('Keeping core tests available');
  $created_json = readJsonFile($build . DIRECTORY_SEPARATOR . 'composer.json');
  $created_json['config']['preferred-install']['drupal/core'] = 'source';
  file_put_contents($build . DIRECTORY_SEPARATOR . 'composer.json', json_encode($created_json, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n");

  step('Requiring dev tooling');
  run(sprintf('composer --working-dir=%s require --dev --no-progress --with-all-dependencies drupal/core-dev:^%s drush/drush', escapeshellarg($build), escapeshellarg($drupal)));

  step('Installing dependencies');
  run(sprintf('composer --working-dir=%s install --no-progress --no-interaction', escapeshellarg($build)));

  // Suggested dependencies are the extension's optionals: install them so
  // the build tests what authors offer, without forcing them on consumers.
  step('Installing suggested dependencies');
  $extension_json = readJsonFile($root . DIRECTORY_SEPARATOR . 'composer.json');
  $build_json = readJsonFile($build . DIRECTORY_SEPARATOR . 'composer.json');

  foreach (array_keys($extension_json['suggest'] ?? []) as $suggest) {
    if (isset($build_json['require'][$suggest]) || isset($build_json['require-dev'][$suggest])) {
      echo "  Skipping {$suggest} (already required).\n";
      continue;
    }

    run(sprintf('composer --working-dir=%s require --no-progress %s', escapeshellarg($build), escapeshellarg((string) $suggest)));
  }

  step('Linking the extension');
  // The extension itself is symlinked (live source, no copy drift), but tool
  // configs must always be copied: several tools cannot resolve symlinked
  // configs. There are none yet; keep the rule when adding any.
  // Link item by item, never the build directory itself: linking the whole
  // root would nest build/ inside build/ forever, and Drupal's extension
  // discovery follows the loop until it chokes.
  $kind = is_dir($root . DIRECTORY_SEPARATOR . 'src') ? 'modules' : 'themes';
  $link = $build . DIRECTORY_SEPARATOR . 'web' . DIRECTORY_SEPARATOR . $kind . DIRECTORY_SEPARATOR . 'custom' . DIRECTORY_SEPARATOR . $machine;
  @mkdir($link, 0777, TRUE);

  foreach (glob($root . DIRECTORY_SEPARATOR . '*') ?: [] as $item) {
    if (in_array(basename($item), ['build', 'vendor', '.git'], TRUE)) {
      continue;
    }

    symlink($item, $link . DIRECTORY_SEPARATOR . basename($item));
  }

  step('Installing the site');
  run(sprintf('php %s install -y --db-url=sqlite://localhost/sites/default/files/.ht.sqlite --account-pass=admin %s %s', escapeshellarg($build . '/vendor/bin/drush'), escapeshellarg($profile), '>&2'));
  run(sprintf('php %s --root=%s/web en -y %s', escapeshellarg($build . '/vendor/bin/drush'), escapeshellarg($build), escapeshellarg($machine)));

  step('Site ready');
  echo "Serve it: php -S localhost:8080 -t {$build}/web\n";
  echo 'Login: php build/vendor/bin/drush --root=build/web uli' . "\n";
}

/**
 * Read the machine name from the extension info file.
 */
function machineName(string $root): string {
  foreach (glob($root . DIRECTORY_SEPARATOR . '*.info.yml') ?: [] as $file) {
    return basename($file, '.info.yml');
  }

  fwrite(STDERR, "No *.info.yml found; is this an extension root?\n");
  exit(1);
}

/**
 * Read a JSON file or exit loudly.
 */
function readJsonFile(string $path): array {
  $data = json_decode((string) @file_get_contents($path), TRUE);

  if (!is_array($data)) {
    fwrite(STDERR, sprintf("Cannot read %s.\n", $path));
    exit(1);
  }

  return $data;
}

/**
 * Print a step header.
 */
function step(string $text): void {
  echo "==> {$text}\n";
}

/**
 * Run a shell command or exit loudly.
 */
function run(string $command): void {
  echo "  $ {$command}\n";
  passthru($command, $code);

  if ($code !== 0) {
    fwrite(STDERR, "Command failed with exit {$code}.\n");
    exit(1);
  }
}
