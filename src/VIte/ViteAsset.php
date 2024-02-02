<?php

declare(strict_types=1);

namespace Drupal\dvb\Vite;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Extension\ThemeHandlerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Vite asset utility.
 */
final class ViteAsset implements ContainerInjectionInterface {

  /**
   * Construct the vite asset utility.
   *
   * @param \Drupal\Core\Extension\ThemeHandlerInterface $themeHandler
   *   The theme handler service.
   * @param \Drupal\dvb\Vite\ViteManifest $manifest
   *   The vite manifest.
   * @param \Drupal\dvb\Vite\ViteMode $mode
   *   The vite mode.
   * @param \Drupal\dvb\Vite\ViteServer $server
   *   The vite server.
   */
  public function __construct(
    protected ThemeHandlerInterface $themeHandler,
    protected ViteManifest $manifest,
    protected ViteMode $mode,
    protected ViteServer $server,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $resolver = $container->get('class_resolver');

    return new static(
      $container->get('theme_handler'),
      $resolver->getInstanceFromDefinition(ViteManifest::class),
      $resolver->getInstanceFromDefinition(ViteMode::class),
      $resolver->getInstanceFromDefinition(ViteServer::class),
    );
  }

  /**
   * Get dev or manifested file path.
   *
   * @param string $file
   *   The file to find.
   * @param bool $absolute
   *   Whether to return the absolute path.
   *
   * @return string|null
   *   The path to the file.
   */
  public function find(string $file, bool $absolute = FALSE): ?string {
    $file = ltrim($file, '/');

    return $this->mode->developer()
      ? $this->server->find($file)
      : $this->manifest->find($file, $absolute);
  }

}
