<?php

declare(strict_types=1);

/**
 * @file
 * Run the unit suite inside the assembled site.
 *
 * Usage: php bin/test.php (run php bin/assemble.php first)
 */

$root = dirname(__DIR__);
$build = $root . DIRECTORY_SEPARATOR . 'build';

if (!is_file($build . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'bin' . DIRECTORY_SEPARATOR . 'phpunit')) {
  fwrite(STDERR, "Assembled site missing; run php bin/assemble.php first.\n");
  exit(1);
}

$suites = [];

// A .skip_functional sentinel drops the slow suite even when it exists.
$skip_functional = is_file($root . DIRECTORY_SEPARATOR . '.skip_functional');

foreach (['unit', 'functional'] as $suite) {
  if ($suite === 'functional' && $skip_functional) {
    echo "Skipping functional suite (.skip_functional present).\n";
    continue;
  }

  if (is_dir($root . DIRECTORY_SEPARATOR . 'tests' . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . ucfirst($suite))) {
    $suites[] = $suite;
  }
}

if ($suites === []) {
  fwrite(STDERR, "No test suites found under tests/src.\n");
  exit(1);
}

passthru(sprintf(
  '%s %s -c %s --testsuite=%s',
  PHP_BINARY,
  escapeshellarg($build . '/vendor/bin/phpunit'),
  escapeshellarg($root . '/phpunit.xml'),
  escapeshellarg(implode(',', $suites))
), $code);

exit($code);
