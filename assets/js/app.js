// Global app JS
// Extracted CSS will be render blocking in the <head>.

import('./modules/fontawesome').then(({ default: Module }) => {
  new Module();
});

import('./modules/main-menu').then(({ default: Module }) => {
  new Module();
});

import('./modules/bootstrap').then(({ default: Module }) => {
  new Module();
});

import('./modules/mobile-menu').then(({ default: Module }) => {
  new Module();
});

import('./modules/back-top').then(({ default: Module }) => {
  new Module();
});

import('./modules/jquery-ui').then(({ default: Module }) => {
  new Module();
});
