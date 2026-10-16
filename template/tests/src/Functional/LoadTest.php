<?php

declare(strict_types=1);

namespace Drupal\Tests\{{MACHINE}}\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * The front page loads with this theme as the default.
 *
 * @group {{MACHINE}}
 */
class LoadTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = '{{MACHINE}}';

  /**
   * The front page returns 200.
   */
  public function testFrontPage(): void {
    $this->drupalGet('<front>');
    $this->assertSession()->statusCodeEquals(200);
  }

}
