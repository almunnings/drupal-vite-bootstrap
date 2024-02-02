<?php

declare(strict_types=1);

namespace Drupal\dvb\Vite;

use Drupal\Component\Serialization\Json;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Extension\ThemeHandlerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Vite manifest utility.
 */
final class ViteManifest implements ContainerInjectionInterface {

  /**
   * Construct the vite manifest utility.
   *
   * @param \Drupal\Core\Extension\ThemeHandlerInterface $themeHandler
   *   The theme handler service.
   */
  public function __construct(
    protected ThemeHandlerInterface $themeHandler,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('theme_handler')
    );
  }

  /**
   * Decode the manifest contents.
   *
   * @return array
   *   The decoded manifest contents.
   */
  public function decode(): array {
    $manifest = &drupal_static(__CLASS__ . '::' . __METHOD__);

    if (is_null($manifest)) {
      $theme = $this->themeHandler->getTheme($this->themeHandler->getDefault());
      $file = $theme->getPath() . '/' . theme_get_setting('vite.manifest_path');

      $manifest = file_exists($file)
        ? Json::decode(file_get_contents($file))
        : [];
    }

    return $manifest;
  }

  /**
   * Get the manifested file path.
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
    $manifest = $this->decode();
    $path = theme_get_setting('vite.dist_path') . '/' . ($manifest[$file]['file'] ?? $file);

    if ($absolute && $path) {
      $default_theme = $this->themeHandler->getDefault();
      $theme_path = $this->themeHandler->getTheme($default_theme)->getPath();
      $path = base_path() . $theme_path . '/' . ltrim($path, '/');
    }

    return $path;
  }

}
