<?php

namespace Drupal\dvb;

use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Client;
use Drupal\Core\Theme\ActiveTheme;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Component\Serialization\Json;

/**
 * Vite asset helper.
 *
 * Usage:
 * vite_asset('assets/js/modules/main-menu.js');
 * ViteAssets::alter($libraries);
 */
final class ViteAssets {

  /**
   * Vite supported port.
   */
  const PORT = 3000;

  /**
   * Construct a vite manifest on a library.
   */
  public function __construct(
    private array $library,
    private ImmutableConfig $performance,
    private ActiveTheme $theme,
    private Client $guzzle
  ) {

  }

  /**
   * Create with DI.
   *
   * @param mixed $library
   *   Library name of current theme OR array of library.
   */
  public static function create($library = NULL): self {
    $performance = \Drupal::config('system.performance');
    $theme = \Drupal::service('theme.manager')->getActiveTheme();
    $guzzle = \Drupal::service('http_client');

    if (is_string($library)) {
      [$extension, $name] = explode('/', $library, 2);
      $library = \Drupal::service('library.discovery')->getLibraryByName($extension, $name);
    }

    return new static($library, $performance, $theme, $guzzle);
  }

  /**
   * Alter Drupal libraries for a theme.
   */
  public static function alter(array &$libraries): void {
    foreach ($libraries as &$library) {
      if (empty($library['vite'])) {
        continue;
      }

      $vite = self::create($library);
      $library = $vite->library();

      if (empty($libraries['hmr']) && $vite->dev()) {
        $libraries['hmr'] = [
          'header' => TRUE,
          'js' => [
            $vite->developmentPath('@vite/client') => [
              'type' => 'external',
              'attributes' => ['type' => 'module'],
            ],
          ],
        ];
      }

      $library['version'] .= '-' . \Drupal::state()->get('system.css_js_query_string');
    }
  }

  /**
   * Replace non-dev asset paths.
   */
  public function library(): array {
    $library = $this->library;

    // Replace defined in library (maintaining order)
    foreach ($library['js'] ?? [] as $file => $options) {
      unset($library['js'][$file]);
      $library['js'][$this->find($file, TRUE)] = $options;
    }

    foreach ($library['css'] ?? [] as $category => $css) {
      foreach ($css as $file => $options) {
        unset($library['css'][$category][$file]);
        $library['css'][$category][$this->find($file, TRUE)] = $options;
      }
    }

    // Extra assets loaded by vite client.
    if ($this->dev()) {
      return $library;
    }

    // Add any extra assets for entries.
    $manifest = $this->manifest();
    foreach ($manifest as $asset) {
      if (!empty($asset['isEntry'])) {
        foreach ($asset['css'] ?? [] as $child) {
          $library['css']['theme'][$this->productionPath($child)] = [
            'minified' => TRUE,
            'weight' => 10,
            'preprocess' => FALSE,
          ];
        }
      }
    }

    return $library;
  }

  /**
   * Get dev or manifested file path.
   */
  public function find(string $file, bool $relative = FALSE): string {
    $file = ltrim($file, '/');

    if ($this->dev()) {
      return $this->developmentPath($file);
    }

    $manifest = $this->manifest();
    if (isset($manifest[$file]['file'])) {
      $file = $this->productionPath($manifest[$file]['file']);
    }

    if (!$relative) {
      $file = base_path() . $this->theme->getPath() . '/' . $file;
    }

    return $file;
  }

  /**
   * Check if vite client is running locally.
   */
  public function dev(): bool {
    if ($this->performance->get('cache.page.max_age') <= 0) {
      return (bool) $this->getInternalUrl();
    }

    return FALSE;
  }

  /**
   * Get the manifest contents.
   */
  private function manifest(): array {
    $manifest = &drupal_static(__FUNCTION__);

    if (!isset($manifest)) {
      $file = $this->theme->getPath() . '/' . $this->productionPath('manifest.json');
      $manifest = file_exists($file) ? Json::decode(file_get_contents($file)) : [];
    }

    return $manifest;
  }

  /**
   * Return the hostname of the first accessible internal domain.
   */
  private function getInternalUrl(): ?string {
    $result = &drupal_static(__FUNCTION__);

    if (!isset($result)) {
      $urls[] = 'http://localhost:' . self::PORT;
      $urls[] = 'http://host.docker.internal:' . self::PORT;

      $services = Json::decode(getenv('LANDO_INFO') ?? '{[]}');

      foreach ($services as $service_info) {
        if ($service_info['type'] !== 'node') {
          continue;
        }

        foreach ($service_info['hostnames'] ?? [] as $hostname) {
          $urls[] = 'http://' . $hostname . ':' . self::PORT;
        }
      }

      $urls = array_reverse($urls);

      foreach ($urls as $url) {
        if ($this->checkUrlConnection($url)) {
          $result = $url;
          break;
        }
      }
    }

    return $result;
  }

  /**
   * Check if an internal domain is accessible.
   */
  private function checkUrlConnection($url): bool {
    $options = [
      'timeout' => 0.05,
      'verify' => FALSE,
      'http_errors' => FALSE,
    ];

    try {
      $result = (bool) $this->guzzle->head($url, $options);
    }
    catch (ConnectException $e) {
      $result = FALSE;
    }

    return $result;
  }

  /**
   * Path for the HMR client.
   */
  private function developmentPath(string $file): string {
    $internal = $this->getInternalUrl();

    if (preg_match('#localhost|host\.docker\.internal#i', $internal)) {
      $base_url = 'http://localhost:' . self::PORT;
    }
    else {
      $services = Json::decode(getenv('LANDO_INFO') ?? '{[]}');

      foreach ($services as $service_info) {
        if ($service_info['type'] !== 'node') {
          continue;
        }
        foreach ($service_info['urls'] ?? [] as $url) {
          $hosts[] = rtrim($url, '/');
        }
      }

      $hosts = array_reverse($hosts);
      $base_url = reset($hosts);
    }

    return $base_url . '/' . $file;
  }

  /**
   * Path relative to theme dist.
   */
  private function productionPath($file): string {
    return 'dist/' . $file;
  }

}
