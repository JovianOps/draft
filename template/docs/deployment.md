# Deployment (mirroring)

This repo mirrors to another git remote — typically `git.drupal.org` — via force-push. Nothing here is Drupal-specific: any git remote works.

1. Create the target repo/empty project.
2. Set repo secrets `DEPLOY_SSH_KEY`, `DEPLOY_KNOWN_HOSTS`, `DEPLOY_REMOTE` and variables `DEPLOY_USER_NAME`, `DEPLOY_USER_EMAIL` (optional `DEPLOY_BRANCH`, defaults to the current branch).
3. Push a tag `v1.2.3` (mirrors as `1.2.3`) or to the branch — the deploy workflow runs after Test passes.
4. Verify with a dry run locally: `DEPLOY_REMOTE=... php bin/deploy.php` (no `--proceed` pushes nothing).
