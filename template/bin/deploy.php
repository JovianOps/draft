<?php

declare(strict_types=1);

/**
 * @file
 * Mirror this extension to another git remote (e.g. Drupal.org).
 *
 * Usage:
 *   DEPLOY_REMOTE=git@git.drupal.org:project/my_module.git \
 *   DEPLOY_BRANCH=1.x \
 *   php bin/deploy.php --proceed
 *
 * Without --proceed this is a dry run: it shows exactly what would be pushed.
 * Tags in the form vX.Y.Z push as refs/tags/X.Y.Z.
 */

$proceed = in_array('--proceed', array_slice($argv, 1), TRUE);
$remote = (string) getenv('DEPLOY_REMOTE');
$branch = (string) getenv('DEPLOY_BRANCH') ?: currentBranch();

if ($remote === '') {
  fwrite(STDERR, "DEPLOY_REMOTE is required.\n");
  exit(1);
}

$tag = tagForRef();

if ($tag !== NULL) {
  $ref = sprintf('refs/tags/%s', $tag);
  $what = sprintf('tag %s', $tag);
}
else {
  $ref = sprintf('HEAD:refs/heads/%s', $branch);
  $what = sprintf('branch %s', $branch);
}

printf("Pushing %s to %s (dry run without --proceed).\n", $what, $remote);

if (!$proceed) {
  exit(0);
}

passthru(sprintf('git push --force %s %s', escapeshellarg($remote), escapeshellarg($ref)), $code);
exit($code);

/**
 * Current git branch, or main when undetectable.
 */
function currentBranch(): string {
  $branch = trim((string) shell_exec('git rev-parse --abbrev-ref HEAD 2>/dev/null'));

  return $branch !== '' ? $branch : 'main';
}

/**
 * Tag for HEAD when it looks like vX.Y.Z, else NULL.
 */
function tagForRef(): ?string {
  $tag = trim((string) shell_exec('git describe --exact-match --tags HEAD 2>/dev/null'));

  return preg_match('/^v\d+\.\d+\.\d+$/', $tag) === 1 ? ltrim($tag, 'v') : NULL;
}
