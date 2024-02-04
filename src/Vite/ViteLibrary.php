<?php

declare(strict_types=1);

namespace Drupal\dvb\Vite;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Vite library utility.
 */
final class ViteLibrary implements ContainerInjectionInterface {

  /**
   * Construct the vite library utility.
   *
   * @param \Drupal\dvb\Vite\ViteAsset $asset
   *   The vite asset.
   * @param \Drupal\dvb\Vite\ViteManifest $manifest
   *   The vite manifest.
   * @param \Drupal\dvb\Vite\ViteMode $mode
   *   The vite mode.
   */
  public function __construct(
    protected ViteAsset $asset,
    protected ViteManifest $manifest,
    protected ViteMode $mode,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $resolver = $container->get('class_resolver');

    return new static(
      $resolver->getInstanceFromDefinition(ViteAsset::class),
      $resolver->getInstanceFromDefinition(ViteManifest::class),
      $resolver->getInstanceFromDefinition(ViteMode::class),
    );
  }

  /**
   * Get the Vite altered libraries.
   *
   * (Optionally) Create the HMR library for development mode.
   *
   * @return array
   *   The altered libraries.
   */
  public function alter(array $libraries): array {
    if ($this->mode->developer()) {
      $libraries['hmr'] = [
        'header' => TRUE,
        'js' => [
          $this->asset->find('@vite/client') => [
            'type' => 'external',
            'attributes' => [
              'type' => 'module',
            ],
          ],
        ],
      ];
    }

    return array_map(
      fn ($library) => $this->map($library),
      $libraries
    );
  }

  /**
   * Replace non-dev asset paths.
   *
   * @param array $library
   *   The library to alter.
   *
   * @return array
   *   The altered library.
   */
  private function map(array $library): array {
    if (empty($library['vite'])) {
      return $library;
    }

    foreach ($library['js'] ?? [] as $file => $options) {
      unset($library['js'][$file]);
      $library['js'][$this->asset->find($file)] = $options;

      // Find any child css assets that need to load.
      // In development mode, Vite client does this.
      if ($this->mode->production()) {
        $manifest = $this->manifest->decode();

        foreach ($manifest[$file]['css'] ?? [] as $child) {
          $library['css']['theme'][$child] = [
            'minified' => TRUE,
            'weight' => 10,
            'preprocess' => FALSE,
          ];
        }
      }
    }

    foreach ($library['css'] ?? [] as $category => $css) {
      foreach ($css as $file => $options) {
        unset($library['css'][$category][$file]);
        $library['css'][$category][$this->asset->find($file)] = $options;
      }
    }

    return $library;
  }

}
