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

  step(sprintf('Creating Drupal %s site in build/', $drupal));
  run(sprintf('composer create-project --no-interaction --no-progress drupal/recommended-project:^%s %s', escapeshellarg($drupal), escapeshellarg($build)));

  step('Requiring dev tooling');
  run(sprintf('composer --working-dir=%s require --dev --no-progress --with-all-dependencies drupal/core-dev:^%s drush/drush', escapeshellarg($build), escapeshellarg($drupal)));

  step('Linking the extension');
  $kind = is_dir($root . DIRECTORY_SEPARATOR . 'src') ? 'modules' : 'themes';
  $link = $build . DIRECTORY_SEPARATOR . 'web' . DIRECTORY_SEPARATOR . $kind . DIRECTORY_SEPARATOR . 'custom' . DIRECTORY_SEPARATOR . $machine;
  @mkdir(dirname($link), 0777, TRUE);
  symlink($root, $link);

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
