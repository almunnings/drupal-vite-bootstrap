# Drupal Vite Bootstrap theme

![NPM build](https://github.com/almunnings/drupal-vite-bootstrap/actions/workflows/npm-ci.yml/badge.svg?branch=main)

It's intended you alter this template as much as you want.

> If you plan to use Vite HMR via a Lando domain, you need to trust your [Lando SSL certificates](https://docs.lando.dev/core/v3/security.html#trusting-the-ca).

## Install and use

```bash
npm ci
```

### Local development (live reloading)

Enable developer mode, visit `/admin/appearance/settings/dvb` and check the enable option.

```bash
npm run dev
```

### Build production (static)

```bash
npm run build
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
    - node-drupal-boilerplate.lndo.site:3000

tooling:
  npm:
    service: node
```

You can now run `lando npm run dev` within the theme directory to start the Vite server.

## DDEV config

```yml
# Example .ddev/config.yaml

nodejs_version: '18'

web_extra_exposed_ports:
  - name: nodejs
    container_port: 3000
    https_port: 3333
```

You can now run `ddev npm run dev` within the theme directory to start the Vite server.
