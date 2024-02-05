<?php

declare(strict_types=1);

namespace Drupal\dvb\Vite;

use Drupal\Component\Serialization\Json;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Messenger\MessengerTrait;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ConnectException;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Vite server utility.
 */
final class ViteServer implements ContainerInjectionInterface {

  use MessengerTrait, StringTranslationTrait;

  /**
   * Construct the vite server utility.
   *
   * @param \GuzzleHttp\Client $httpClient
   *   The http client.
   */
  public function __construct(
    protected Client $httpClient
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('http_client')
    );
  }

  /**
   * Get the URL for the HMR Vite client.
   *
   * @return string|null
   *   The URL to the Vite client.
   */
  public function find(?string $file = NULL): ?string {
    $base_url = $this->getViteBaseUrl();

    return $file
      ? $base_url . '/' . $file
      : $base_url;
  }

  /**
   * Get the Vite development host.
   *
   * @return string|null
   *   The hostname of the first accessible internal domain.
   */
  protected function getViteBaseUrl(): ?string {
    $base_url = &drupal_static(__CLASS__ . '::' . __METHOD__, FALSE);

    if ($base_url === FALSE) {
      $base_url = NULL;

      $hosts = array_merge(
        $this->lando(),
        $this->ddev(),
        $this->docker(),
        ['localhost' => 'http://localhost:' . theme_get_setting('vite.port')],
      );

      foreach ($hosts as $internal => $external) {
        if ($this->connect($internal)) {
          $base_url = $external;
          break;
        }
      }
    }

    if (!$base_url) {
      throw new \Exception('Vite server not running. Run `npm run dev` and clear caches, or disable development mode in the theme settings.');
    }

    return $base_url;
  }

  /**
   * Get any node services host names from Lando.
   *
   * @return array
   *   The host names for node services in Lando.
   */
  private function lando(): array {
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
  private function ddev(): array {
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
      fn($binding) => $binding[1] === (string) theme_get_setting('vite.port')
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
  private function docker(): array {
    return file_exists('/.dockerenv')
      ? ['host.docker.internal' => 'http://localhost:' . theme_get_setting('vite.port')]
      : [];
  }

  /**
   * Try connect to a running Vite service through the internal domain.
   *
   * @param string $hostname
   *   The internal domain to check.
   *
   * @return bool
   *   TRUE if the domain is responding.
   */
  private function connect(string $hostname): bool {
    try {
      $options = [
        'timeout' => 0.1,
        'verify' => FALSE,
        'http_errors' => FALSE,
      ];

      return (bool) $this->httpClient->head(
        'http://' . $hostname . ':' . theme_get_setting('vite.port'),
        $options
      );
    }
    catch (ConnectException) {
      return FALSE;
    }
  }

}
