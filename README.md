# Drupal Vite Bootstrap theme

![NPM build](https://github.com/almunnings/drupal-vite-bootstrap/actions/workflows/npm-ci.yml/badge.svg?branch=main)

It's intended you alter this template as much as you want.

> If using Lando and a node service, prefix the following commands with lando. Example: `lando npm i`

## Install and use

```bash
npm i
```

### Local development (live reloading)

```bash
npm run dev
```

### Build production (static)

```bash
npm run build
```

## Lando config

```yml
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
```
