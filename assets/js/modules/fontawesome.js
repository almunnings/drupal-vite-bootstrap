/**
 * DVB fontawesome.js
 *
 * https://fontawesome.com/docs/apis/javascript/import-icons#package-names
 */

import { library, dom } from '@fortawesome/fontawesome-svg-core';

// Font Awesome Icons
import {
  faChevronDown,
  faChevronUp,
  faChevronRight,
  faChevronLeft,
  faCircleNotch,
  faBell,
  faSquareRss,
} from '@fortawesome/free-solid-svg-icons';

export default class Module {
  constructor() {
    // Add icons
    library.add(faChevronDown, faChevronUp, faChevronRight, faChevronLeft, faBell, faCircleNotch, faSquareRss);

    // Attach to a drupal behaviour to update on content changes.
    Drupal.behaviors.fa = {
      attach: (context) => {
        if (context.querySelector('i')) {
          dom.i2svg({ node: context });
        }
      },
    };

    // Initialize this behaviour.
    Drupal.behaviors.fa.attach(document);
  }
}
