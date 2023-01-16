/**
 * IconAgency fontawesome.js
 */

import { library, dom } from '@fortawesome/fontawesome-svg-core';

// Font Awesome Icons
import {
  faRocketLaunch,
  faChevronDown,
  faChevronUp,
  faChevronRight,
  faChevronLeft,
  faSquareRss,
} from '@fortawesome/pro-light-svg-icons';

import { faCircleNotch, faBell } from '@fortawesome/pro-solid-svg-icons';

export default class Module {
  constructor() {
    // Add icons
    library.add(
      faRocketLaunch,
      faChevronDown,
      faChevronUp,
      faChevronRight,
      faChevronLeft,
      faBell,
      faCircleNotch,
      faSquareRss,
    );

    // Attach to a drupal behaviour to update on content changes.
    Drupal.behaviors.fa = {
      attach: (context) => {
        if (context.querySelector('i')) {
          dom.i2svg({ node: context });
        }
      },
    };

    // Initalize this behaviour.
    Drupal.behaviors.fa.attach(document);
  }
}
