/**
 * DVB main-menu.js
 */

import AccessibleSubmenu from 'accessible-submenu';

export default class Module {
  constructor() {
    this.menuElement = document.querySelector('.region-primary-menu .nav');

    if (this.menuElement) {
      const items = this.menuElement.children;

      [...items].forEach((li) => {
        new AccessibleSubmenu(li, {});

        this.bindTouch(li.querySelector(':scope > a'));
      });

      this.bindTouchWindow();
    }
  }

  /**
   * Close all other dropdowns on window touches outside of a double-tap element.
   */
  bindTouchWindow() {
    document.addEventListener('touchstart', (event) => {
      const linkTouched = this.elementTouchedLink(event.target, 0);
      const dropdownTouched = this.elementTouchedDropdown(event.target, 0);
      const tapped = this.menuElement.querySelectorAll('[data-double-tap="true"]');

      if (dropdownTouched) {
        return;
      }

      [...tapped].forEach((a) => {
        if (a !== linkTouched) {
          a.dataset.doubleTap = false;
        }
      });
    });
  }

  /**
   * Bind the touch events.
   *
   * @param {Element} link Link element on top level.
   */
  bindTouch(link) {
    if (!link.parentNode.querySelectorAll('.js-submenu').length) {
      return;
    }

    link.addEventListener('touchend', (event) => {
      if (!this.doubleTapped(event)) {
        event.preventDefault();
      }
    });
  }

  /**
   * Check if its been double tapped and open accordingly.
   *
   * @param {Event} event Click event
   *
   * @return {boolean} Event target is double tapped.
   */
  doubleTapped(event) {
    const target = event.currentTarget;

    if (target.dataset.doubleTap) {
      return true;
    }

    target.dataset.doubleTap = true;

    return false;
  }

  /**
   * Find the element target parents, so we don't close it on the touchstart.
   *
   * @param {Element} element A HTML element to check.
   * @param {integer} depth Recursive depth
   *
   * @return {mixed} Element or false.
   */
  elementTouchedLink(element, depth) {
    if (depth < 5) {
      if (element?.dataset?.doubleTap) {
        return element;
      }

      if (element.parentNode) {
        return this.elementTouchedLink(element.parentNode, depth + 1);
      }
    }

    return false;
  }

  /**
   * Check if we tapped inside a dropdown.
   *
   * @param {Element} element A HTML element to check.
   * @param {integer} depth Recursive depth
   *
   * @return {mixed} Element or false.
   */
  elementTouchedDropdown(element, depth) {
    if (depth < 10) {
      if (element.classList && element.classList.contains('js-submenu')) {
        return element;
      }

      if (element.parentNode) {
        return this.elementTouchedDropdown(element.parentNode, depth + 1);
      }
    }

    return false;
  }
}
