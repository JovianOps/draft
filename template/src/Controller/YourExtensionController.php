<?php

declare(strict_types=1);

namespace Drupal\{{MACHINE}}\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\{{MACHINE}}\{{CLASS}}Service;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Greeting page.
 */
class {{CLASS}}Controller extends ControllerBase {

  /**
   * Construct the controller.
   */
  public function __construct(
    private readonly {{CLASS}}Service $greeter,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static($container->get('{{MACHINE}}.greeter'));
  }

  /**
   * Greet by name.
   */
  public function hello(string $name): array {
    return ['#markup' => $this->greeter->greet($name)];
  }

}
