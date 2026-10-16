<?php

declare(strict_types=1);

/**
 * @file
 * Example service. Rename it, grow it, or delete it.
 */

namespace Drupal\{{MACHINE}};

/**
 * Greets the caller.
 */
class {{CLASS}}Service {

  /**
   * Greet a name.
   */
  public function greet(string $name): string {
    $name = trim($name);

    return $name === '' ? 'Hello, world.' : sprintf('Hello, %s.', $name);
  }

}
