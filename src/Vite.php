<?php

namespace Drupal\dvb;

use GuzzleHttp\Client;
use Drupal\Core\Theme\ActiveTheme;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Component\Serialization\Json;

/**
 * Vite asset helper.
 *
 * Usage:
 * vite_asset('assets/js/modules/main-menu.js');
 * Vite::alter($libraries);
 */
final class Vite {

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
            $vite->devPath('@vite/client') => [
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
          $library['css']['theme'][$this->outPath($child)] = [
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
      return $this->devPath($file);
    }

    $manifest = $this->manifest();
    if (isset($manifest[$file]['file'])) {
      $file = $this->outPath($manifest[$file]['file']);
    }

    if (!$relative) {
      $file = base_path() . $this->theme->getPath() . '/' . $file;
    }

    return $file;
  }

  /**
   * Get the manifest contents.
   */
  protected function manifest(): array {
    $manifest = &drupal_static(__FUNCTION__);

    if (!isset($manifest)) {
      $file = $this->theme->getPath() . '/' . $this->outPath('manifest.json');
      $manifest = file_exists($file) ? Json::decode(file_get_contents($file)) : [];
    }

    return $manifest;
  }

  /**
   * Check if vite client is running locally.
   */
  protected function dev(): bool {
    $dev = &drupal_static(__FUNCTION__);

    if (!isset($dev)) {
      $dev = FALSE;

      if (!$this->performance->get('cache.page.max_age') > 0) {

        try {
          $dev = (bool) $this->guzzle->head(
            $this->devHost(TRUE), [
              'timeout' => 0.25,
              'verify' => FALSE,
              'http_errors' => FALSE,
            ]);
        }
        catch (\Exception $e) {
          // ... Swallow error
        }
      }
    }

    return $dev;
  }

  /**
   * Path for the HMR client.
   */
  protected function devHost($internal = FALSE): string {
    $vite = $this->library['vite'];

    if (file_exists('/.dockerenv')) {
      $docker = getenv('LANDO_HOST_IP') ?? 'host.docker.internal';
    }

    $host = ($internal && isset($docker)) ? $docker : $vite['host'];

    return $vite['scheme'] . '://' . $host . ':' . $vite['port'];
  }

  /**
   * Path for the HMR client.
   */
  protected function devPath(string $file): string {
    return $this->devHost() . '/' . $file;
  }

  /**
   * Path relative to theme dist.
   */
  protected function outPath($file): string {
    return rtrim($this->library['vite']['outDir'], '/') . '/' . $file;
  }

}
