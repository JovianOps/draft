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

$server = NULL;

if (in_array('functional', $suites, TRUE)) {
  // Functional tests drive a real web server and install a testing profile
  // per test on SQLite. The server serves the assembled build; each test
  // owns its tables inside the given database file.
  $server = startTestServer($build . DIRECTORY_SEPARATOR . 'web');
  putenv('SIMPLETEST_BASE_URL=http://127.0.0.1:8080');
  putenv('SIMPLETEST_DB=sqlite://localhost/' . $build . '/sites/default/files/.ht-test.sqlite');
}

passthru(sprintf(
  '%s %s -c %s --testsuite=%s',
  PHP_BINARY,
  escapeshellarg($build . '/vendor/bin/phpunit'),
  escapeshellarg($root . '/phpunit.xml'),
  escapeshellarg(implode(',', $suites))
), $code);

if ($server !== NULL) {
  proc_terminate($server);
  proc_close($server);
}

exit($code);

/**
 * Start the PHP built-in server over the build. Returns the process.
 *
 * @return resource
 */
function startTestServer(string $docroot) {
  $null = PHP_OS_FAMILY === 'Windows' ? 'NUL' : '/dev/null';
  $log = $docroot . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'test-server.log';
  $process = proc_open(
    [PHP_BINARY, '-S', '127.0.0.1:8080', '-t', $docroot],
    [0 => ['file', $null, 'r'], 1 => ['file', $log, 'w'], 2 => ['file', $log, 'w']],
    $pipes
  );

  if (!is_resource($process)) {
    fwrite(STDERR, "Could not start the test web server.\n");
    exit(1);
  }

  $context = stream_context_create(['http' => ['timeout' => 2, 'ignore_errors' => TRUE]]);

  for ($i = 0; $i < 100; $i++) {
    $probe = @file_get_contents('http://127.0.0.1:8080/', FALSE, $context);

    if ($probe !== FALSE) {
      return $process;
    }

    usleep(100000);
  }

  fwrite(STDERR, "The test web server never answered. Server log:\n");
  fwrite(STDERR, (string) @file_get_contents($log));
  proc_terminate($process);
  proc_close($process);
  exit(1);
}
