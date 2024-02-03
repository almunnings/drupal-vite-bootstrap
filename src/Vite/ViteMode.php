<?php

declare(strict_types=1);

namespace Drupal\dvb\Vite;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\State\StateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Vite mode utility.
 */
final class ViteMode implements ContainerInjectionInterface {

  /**
   * Construct the vite mode utility.
   *
   * @param \Drupal\Core\State\StateInterface $state
   *   The state client.
   */
  public function __construct(
    protected StateInterface $state
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('state')
    );
  }

  /**
   * Get the state of production mode.
   *
   * @return bool
   *   The state of production mode.
   */
  public function production(): bool {
    return !$this->developer();
  }

  /**
   * Get the state of developer mode.
   *
   * @return bool
   *   The state of developer mode.
   */
  public function developer(): bool {
    return (bool) $this->state->get('vite.developer_mode', FALSE);
  }

}
