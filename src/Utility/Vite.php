<?php

namespace Drupal\dvb\Utility;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Theme\ActiveTheme;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ConnectException;

/**
 * Vite asset utility.
 */
final class Vite {

  /**
   * The active theme.
   *
   * @var \Drupal\Core\Theme\ActiveTheme
   */
  protected ActiveTheme $activeTheme;

  /**
   * The http client.
   *
   * @var \GuzzleHttp\Client
   */
  protected Client $httpClient;

  /**
   * The performance config.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected ImmutableConfig $performance;

  /**
   * Construct a vite manifest on a library.
   *
   * @param array $libraries
   *   The Drupal libraries to alter.
   * @param int $port
   *   The Vite port.
   * @param float $timeout
   *   The timeout for the Vite server.
   */
  public function __construct(
    private array $libraries,
    private int $port = 3000,
    private float $timeout = 0.05,
  ) {
    $this->activeTheme = \Drupal::service('theme.manager')->getActiveTheme();
    $this->httpClient = \Drupal::service('http_client');
    $this->performance = \Drupal::config('system.performance');
  }

  /**
   * Get the Vite altered libraries.
   *
   * @return array
   *   The altered libraries.
   */
  public function getLibraries(): array {
    if ($this->isDevelopmentMode()) {
      $this->libraries['hmr'] = [
        'header' => TRUE,
        'js' => [
          $this->getDevelopmentUrl('@vite/client') => [
            'type' => 'external',
            'attributes' => ['type' => 'module'],
          ],
        ],
      ];
    }

    return array_map([$this, 'remap'], $this->libraries);
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
  protected function remap(array $library): array {
    if (empty($library['vite'])) {
      return $library;
    }

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

    // Extra assets would be loaded by Vite client.
    if ($this->isDevelopmentMode()) {
      return $library;
    }

    // Add any extra assets for entries.
    $manifest = $this->getManifest();
    foreach ($manifest as $asset) {
      if (!empty($asset['isEntry'])) {
        foreach ($asset['css'] ?? [] as $child) {
          $library['css']['theme'][$this->getProductionUrl($child)] = [
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
   *
   * @param string $file
   *   The file to find.
   * @param bool $relative
   *   Return a relative path.
   *
   * @return string
   *   The path to the file.
   */
  public function find(string $file, bool $relative = FALSE): string {
    $file = ltrim($file, '/');

    if ($this->isDevelopmentMode()) {
      return $this->getDevelopmentUrl($file);
    }

    $manifest = $this->getManifest();
    if (isset($manifest[$file]['file'])) {
      $file = $this->getProductionUrl($manifest[$file]['file']);
    }

    if (!$relative) {
      $file = base_path() . $this->activeTheme->getPath() . '/' . $file;
    }

    return $file;
  }

  /**
   * Get the manifest contents.
   *
   * @return array
   *   The manifest contents.
   */
  protected function getManifest(): array {
    $manifest = &drupal_static(__FUNCTION__);

    if (!isset($manifest)) {
      $file = $this->activeTheme->getPath() . '/' . $this->getProductionUrl('manifest.json');
      $manifest = file_exists($file) ? Json::decode(file_get_contents($file)) : [];
    }

    return $manifest ?: [];
  }

  /**
   * Check if vite client is running locally.
   *
   * @return bool
   *   TRUE if vite client is running locally.
   */
  protected function isDevelopmentMode(): bool {
    $development = &drupal_static(__FUNCTION__);

    if (!isset($development)) {
      $cached = (int) $this->performance->get('cache.page.max_age') > 0;
      $development = $cached ? FALSE : (bool) $this->getDevelopmentHost();
    }

    return $development;
  }

  /**
   * Get any node services host names from Lando.
   *
   * @return array
   *   The host names for node services in Lando.
   */
  protected function getLandoHosts(): array {
    $lando_info = Json::decode(getenv('LANDO_INFO') ?: '{}');

    // Get the URLs for the node services.
    $lando_services = array_filter(
      $lando_info,
      fn($service) => $service['type'] === 'node'
    );

    $lando_urls = [];
    foreach ($lando_services as $hostname) {
      // Prefer HTTPS host, else first.
      $urls = array_filter($hostname['urls'], fn($url) => strpos($url, 'https') === 0);
      $url = reset($urls) ?: reset($hostname['urls']);

      $internal = reset($hostname['hostnames']);
      $lando_urls[$internal] = rtrim($url, '/');
    }

    return $lando_urls;
  }

  /**
   * Get the Vite development host.
   *
   * @return string|null
   *   The hostname of the first accessible internal domain.
   */
  protected function getDevelopmentHost(): ?string {
    $result = &drupal_static(__FUNCTION__);
    if (isset($result)) {
      return $result;
    }

    $hosts = array_merge(
      $this->getLandoHosts(),
      ['host.docker.internal' => 'http://localhost:' . $this->port],
      ['localhost' => 'http://localhost:' . $this->port],
    );

    $result = NULL;
    foreach ($hosts as $internal => $external) {
      if ($this->isConnectionOk($internal)) {
        $result = $external;
        break;
      }
    }

    return $result;
  }

  /**
   * Path for the Vite HMR file.
   *
   * @param string $file
   *   The file to get the path for.
   *
   * @return string
   *   The URL to the file.
   */
  protected function getDevelopmentUrl(string $file): string {
    return $this->getDevelopmentHost() . '/' . $file;
  }

  /**
   * Path relative to theme dist.
   *
   * @param string $file
   *   The file to get the path for.
   *
   * @return string
   *   The URL to the file.
   */
  protected function getProductionUrl(string $file): string {
    return 'dist/' . $file;
  }

  /**
   * Check if an internal host is responding.
   *
   * @param string $hostname
   *   The internal domain to check.
   *
   * @return bool
   *   TRUE if the domain is responding.
   */
  protected function isConnectionOk(string $hostname): bool {
    try {
      $options = [
        'timeout' => $this->timeout,
        'verify' => FALSE,
        'http_errors' => FALSE,
      ];

      // HEAD request to avoid downloading the whole page.
      // Only need to know if the server is responding.
      $url = 'http://' . $hostname . ':' . $this->port;
      return (bool) $this->httpClient->head($url, $options);
    }
    catch (ConnectException) {
      return FALSE;
    }
  }

}
