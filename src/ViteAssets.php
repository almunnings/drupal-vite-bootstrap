<?php

namespace Drupal\dvb;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Theme\ActiveTheme;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ConnectException;

/**
 * Vite asset helper.
 *
 * This is in the theme to avoid needing a custom module.
 * Which could be beneficial for SaaS based hosting.
 *
 * Usage:
 * vite_asset('assets/js/modules/main-menu.js');
 * ViteAssets::alter($libraries);
 */
final class ViteAssets {

  /**
   * Construct a vite manifest on a library.
   */
  public function __construct(
    private FileSystemInterface $fileSystem,
    private ActiveTheme $activeTheme,
    private Client $httpClient,
    private ImmutableConfig $performance,
    private array $libraries,
    private int $port,
  ) {}

  /**
   * Create with DI.
   *
   * @param array $libraries
   *   Libraries for the theme.
   * @param int $port
   *   Port for the vite server.
   *
   * @return static
   *   The ViteAssets instance.
   */
  public static function create(array $libraries, int $port = 3000): self {
    $performance = \Drupal::config('system.performance');
    $fileSystem = \Drupal::service('file_system');
    $activeTheme = \Drupal::service('theme.manager')->getActiveTheme();
    $httpClient = \Drupal::service('http_client');

    return new static(
      $fileSystem,
      $activeTheme,
      $httpClient,
      $performance,
      $libraries,
      $port
    );
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

    // Extra assets loaded by vite client.
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
      $cached = $this->performance->get('cache.page.max_age');
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
    $lando_info = getenv('LANDO_INFO');
    $lando_services = $lando_info ? Json::decode($lando_info) : [];

    $lando_services = array_filter(
      $lando_services,
      fn($service) => $service['type'] === 'node'
    );

    $results = [];
    foreach ($lando_services as $hostname) {
      $internal = reset($hostname['hostnames']);
      $external = reset($hostname['urls']);
      $results[$internal] = parse_url($external, PHP_URL_HOST);
    }

    return $results;
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
      ['host.docker.internal' => 'localhost:' . $this->port],
      ['localhost' => 'localhost:' . $this->port],
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
   * Path for the HMR client.
   *
   * @param string $file
   *   The file to get the path for.
   *
   * @return string
   *   The URL to the file.
   */
  protected function getDevelopmentUrl(string $file): string {
    $host = $this->getDevelopmentHost();
    $protocol = stristr($host, 'localhost') ? 'http' : 'https';

    return $protocol . '://' . $host . '/' . $file;
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
   * Check if an internal domain is responding.
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
        'timeout' => 0.05,
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
