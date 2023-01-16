/**
 * IconAgency bootstrap.js
 *
 * Adds bootstrap js and functionality.
 */

import * as bootstrap from 'bootstrap';

export default class Module {
  constructor() {
    Drupal.behaviors.iconBootstrap = {
      attach: (context) => {
        const popovers = context.querySelectorAll('[data-bs-toggle="popover"]');
        const alerts = context.querySelectorAll('.alert');
        const offcanvas = context.querySelectorAll('.offcanvas');

        [...popovers].forEach((element) => new bootstrap.Popover(element));
        [...alerts].forEach((element) => new bootstrap.Alert(element));
        [...offcanvas].forEach((element) => element.removeAttribute('hidden'));
      },
    };

    Drupal.behaviors.iconBootstrap.attach(document);
  }
}
