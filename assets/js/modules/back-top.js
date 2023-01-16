/**
 * IconAgency back-top.js
 *
 * Adds a floating "back to top" element.
 */

import '../../scss/navigation/back-top.scss';

export default class Module {
  constructor() {
    this.footer = document.querySelector('.site-footer');

    if (this.footer) {
      this.inject();
      this.toggle();
      window.addEventListener('scroll', this.toggle, { passive: true });
    }
  }

  inject() {
    const html = `
    <div class="back-to-top">
      <a href="#top" class="btn btn-primary rounded-circle" title="Top of page">
        <i class="fal fa-chevron-up" aria-hidden="true"></i>
      </a>
    </div>
    `;

    this.footer.insertAdjacentHTML('beforebegin', html);

    if (Drupal.behaviors.fa) {
      Drupal.behaviors.fa.attach(this.footer.parentNode);
    }
  }

  toggle() {
    const y = window.pageYOffset;
    const button = document.querySelector('.back-to-top');
    const offset = document.querySelector('.site-footer').offsetTop - window.innerHeight + 32;

    button.classList.toggle('show', y >= 200 || y >= offset);
    button.classList.toggle('position-absolute', y >= offset);
  }
}
