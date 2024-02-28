/**
 * DVB jquery-ui.js
 *
 * Fix jquery UI issue with bootstrap.
 */

import jQuery from 'jquery';

export default class Module {
  constructor() {
    const $ = jQuery;

    if (!$.ui?.dialog) {
      return;
    }

    $.widget('ui.dialog', $.ui.dialog, {
      open: function () {
        const close = `
        <span class="ui-button-icon ui-icon ui-icon-closethick"></span>
        <span class="ui-button-icon-space"></span>
        Close
        `;

        const classes = ['ui-button', 'ui-corner-all', 'ui-widget', 'ui-button-icon-only', 'ui-dialog-titlebar-close'];

        [...this.uiDialogTitlebarClose].forEach((button) => {
          button.innerHTML = close;
          button.classList.add(...classes);
        });

        return this._super();
      },
    });
  }
}
