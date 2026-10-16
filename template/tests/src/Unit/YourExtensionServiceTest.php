<?php

declare(strict_types=1);

namespace Drupal\Tests\{{MACHINE}}\Unit;

use Drupal\{{MACHINE}}\{{CLASS}}Service;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\{{MACHINE}}\{{CLASS}}Service
 *
 * @group {{MACHINE}}
 */
class {{CLASS}}ServiceTest extends UnitTestCase {

  /**
   * @covers ::greet
   *
   * @dataProvider greetCases
   */
  public function testGreet(string $input, string $expected): void {
    $this->assertSame($expected, (new {{CLASS}}Service())->greet($input));
  }

  /**
   * Greeting cases.
   */
  public static function greetCases(): array {
    return [
      'named' => ['Ada', 'Hello, Ada.'],
      'blank' => ['   ', 'Hello, world.'],
    ];
  }

}
