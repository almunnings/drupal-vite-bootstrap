# Drupal Vite Bootstrap theme

![NPM build](https://github.com/almunnings/drupal-vite-bootstrap/actions/workflows/npm-ci.yml/badge.svg?branch=main)

It's intended you alter this template as much as you want.

> If you plan to use Vite HMR via a Lando domain, you need to trust your [Lando SSL certificates](https://docs.lando.dev/core/v3/security.html#trusting-the-ca).


## Install and use

```bash
npm i
```

### Local development (live reloading)

```bash
npm run dev
drush cr
```

### Build production (static)

```bash
npm run build
drush cr
```

## Vite entry points

- Assets are added to Vite via `dvb.libraries.yml`
- The `vite: true` key is used enable Vite remapping
- Any js/css path is converted into an entry point

Vite assets within the yml should map to their location within the `assets/` dir.

```yml
# Example dvb.libraries.yml

app:
  vite: true
  version: 1.2.3
  dependencies:
    - core/drupal
    - core/drupalSettings
  css:
    theme:
      assets/scss/bootstrap.scss: { minified: true, preprocess: false }
      assets/scss/app.scss: { minified: true, preprocess: false }
  js:
    assets/js/app.js: { minified: true, preprocess: false, attributes: { type: 'module' } }
```

## Lando config

The Vite Utility is equipped to handle _node_ type services.

> If you plan to use a HMR theme via the Lando proxy, you need to trust your [Lando SSL certificates](https://docs.lando.dev/core/v3/security.html#trusting-the-ca).

```yml
# Example .lando.yml

services:
  node:
    type: node:18
    ssl: true
    sslExpose: false
    port: 3000
    scanner: false

proxy:
  node:
    - node.drupal-boilerplate.lndo.site:3000

tooling:
  theme:
    service: node
    description: Run NPM commands for the theme.
    dir: /app/web/themes/contrib/dvb
    cmd: npm
```

## Pending package.json issues

- Bootstrap has a deprecation for sass 1.65.0
  https://github.com/twbs/bootstrap/issues/39028
