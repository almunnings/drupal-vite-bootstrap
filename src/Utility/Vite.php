<?php

declare(strict_types=1);

namespace Drupal\dvb\Utility;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Extension\ThemeHandlerInterface;
use Drupal\Core\State\StateInterface;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ConnectException;

/**
 * Vite asset utility.
 */
final class Vite {

  /**
   * Drupal theme handler.
   *
   * @var \Drupal\Core\Extension\ThemeHandlerInterface
   */
  protected ThemeHandlerInterface $themeHandler;

  /**
   * The http client.
   *
   * @var \GuzzleHttp\Client
   */
  protected Client $httpClient;

  /**
   * The environment state.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected StateInterface $state;

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
    private float $timeout = 0.1,
  ) {
    $this->themeHandler = \Drupal::service('theme_handler');
    $this->httpClient = \Drupal::service('http_client');
    $this->state = \Drupal::service('state');
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
          $this->find('@vite/client') => [
            'type' => 'external',
            'attributes' => [
              'type' => 'module',
            ],
          ],
        ],
      ];
    }

    return array_map(
      fn ($definition) => $this->remap($definition),
      $this->libraries
    );
  }

  /**
   * Get dev or manifested file path.
   *
   * @param string $file
   *   The file to find.
   *
   * @return string
   *   The path to the file.
   */
  public function find(string $file): string {
    $file = ltrim($file, '/');

    if ($this->isDevelopmentMode()) {
      return $this->getDevelopmentHost() . '/' . $file;
    }

    $manifest = $this->getManifest();

    return 'dist/' . ($manifest[$file]['file'] ?? $file);
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
  private function remap(array $library): array {
    if (empty($library['vite'])) {
      return $library;
    }

    foreach ($library['js'] ?? [] as $file => $options) {
      unset($library['js'][$file]);
      $library['js'][$this->find($file)] = $options;

      // Find any child css assets that need to load.
      // In development mode, Vite client does this.
      if (!$this->isDevelopmentMode()) {
        $manifest = $this->getManifest();

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
        $library['css'][$category][$this->find($file)] = $options;
      }
    }

    return $library;
  }

  /**
   * Get the manifest contents.
   *
   * @return array
   *   The manifest contents.
   */
  private function getManifest(): array {
    static $manifest;
    if (isset($manifest)) {
      return $manifest;
    }

    $theme = $this->themeHandler->getTheme($this->themeHandler->getDefault());
    $file = $theme->getPath() . '/dist/.vite/manifest.json';
    $content = file_exists($file) ? file_get_contents($file) : '{}';
    $manifest = Json::decode($content);

    return $manifest;
  }

  /**
   * Check if we should attempt loading vite dev.
   *
   * @return bool
   *   TRUE if vite client is running locally.
   */
  private function isDevelopmentMode(): bool {
    return (bool) $this->state->get('theme_dvb_developer_mode', FALSE);
  }

  /**
   * Get any node services host names from Lando.
   *
   * @return array
   *   The host names for node services in Lando.
   */
  private function getHostsForLando(): array {
    if (!getenv('LANDO_INFO')) {
      return [];
    }

    $lando_info = Json::decode(getenv('LANDO_INFO'));

    $lando_services = array_filter(
      $lando_info,
      fn($service) => $service['type'] === 'node' && !empty($service['urls'])
    );

    $lando_urls = [];
    foreach ($lando_services as $service) {
      $urls = array_filter($service['urls'], fn($url) => str_starts_with($url, 'https'));
      $url = reset($urls) ?: reset($service['urls']);

      $internal = reset($service['hostnames']);
      $lando_urls[$internal] = rtrim($url, '/');
    }

    return $lando_urls;
  }

  /**
   * Get any node services host names from ddev.
   *
   * @return array
   *   The host names for node services in ddev.
   */
  private function getHostsForDdev(): array {
    if (!getenv('DDEV_HOSTNAME') || !getenv('HTTPS_EXPOSE') || !getenv('HOSTNAME')) {
      return [];
    }

    // Get the bindings from the DDEV string.
    $bindings = array_map(
      fn($mapping) => explode(':', $mapping),
      explode(',', getenv('HTTPS_EXPOSE'))
    );

    // Find the binding that maps port 3000.
    $binding = array_filter(
      $bindings,
      fn($binding) => $binding[1] === (string) $this->port
    );

    $binding = reset($binding);

    return $binding
      ? [getenv('HOSTNAME') => 'https://' . getenv('DDEV_HOSTNAME') . ':' . $binding[0]]
      : [];
  }

  /**
   * Get any docker host names to try.
   *
   * @return array
   *   The host names for node services in ddev.
   */
  private function getHostsForDocker(): array {
    return file_exists('/.dockerenv')
      ? ['host.docker.internal' => 'http://localhost:' . $this->port]
      : [];
  }

  /**
   * Get the Vite development host.
   *
   * @return string|null
   *   The hostname of the first accessible internal domain.
   */
  private function getDevelopmentHost(): ?string {
    static $development_host;
    if (isset($development_host)) {
      return $development_host;
    }

    $hosts = array_merge(
      $this->getHostsForLando(),
      $this->getHostsForDdev(),
      $this->getHostsForDocker(),
      ['localhost' => 'http://localhost:' . $this->port],
    );

    $development_host = NULL;
    foreach ($hosts as $internal => $external) {
      if ($this->isConnectionOk($internal)) {
        $development_host = $external;
        break;
      }
    }

    return $development_host;
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
  private function isConnectionOk(string $hostname): bool {
    try {
      $options = [
        'timeout' => $this->timeout,
        'verify' => FALSE,
        'http_errors' => FALSE,
      ];

      // HEAD request to avoid downloading the whole page.
      // Only need to know if the server is responding.
      return (bool) $this->httpClient->head(
        'http://' . $hostname . ':' . $this->port,
        $options
      );
    }
    catch (ConnectException) {
      return FALSE;
    }
  }

}
