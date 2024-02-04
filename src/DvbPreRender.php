<?php

declare(strict_types=1);

namespace Drupal\dvb;

use Drupal\Core\Security\TrustedCallbackInterface;

/**
 * Implements trusted prerender callbacks for the DVB theme.
 *
 * @internal
 */
final class DvbPreRender implements TrustedCallbackInterface {

  /**
   * {@inheritdoc}
   */
  public static function trustedCallbacks() {
    return [
      'textFormat',
    ];
  }

  /**
   * Prerender callback for text_format elements.
   *
   * @param array $element
   *   The element to alter in a trusted prerender.
   *
   * @return array
   *   The altered element.
   */
  public static function textFormat(array $element): array {
    $element['format']['format']['#wrapper_attributes']['class'][] = 'mt-0';
    $element['format']['format']['#wrapper_attributes']['class'][] = 'input-group';
    $element['format']['format']['#wrapper_attributes']['class'][] = 'input-group-sm';
    $element['format']['format']['#wrapper_attributes']['class'][] = 'w-auto';
    $element['format']['format']['#label_attributes']['class'][] = 'input-group-text';
    $element['format']['format']['#label_attributes']['class'][] = 'm-0';
    $element['format']['format']['#attributes']['class'][] = 'w-auto';
    $element['format']['format']['#attributes']['class'][] = 'flex-grow-0';

    return $element;
  }

}
