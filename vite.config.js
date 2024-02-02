import fs from 'fs'
import YAML from 'yaml'
import path from 'path'
import { defineConfig, loadEnv } from 'vite'
import autoprefixer from 'autoprefixer'
import eslint from 'vite-plugin-eslint'
import liveReload from 'vite-plugin-live-reload'
import { viteExternalsPlugin } from 'vite-plugin-externals'

// Resolve dirs.
const pwd = path.resolve(__dirname, '.')
const drupalPath = path.resolve(__dirname, '../../../../')
const themePath = pwd.match(/\/themes\/[^\/]+\/[^\/]+/i)[0]
const basePath = `${themePath}/dist/`

// Extract YML as object.
const yaml = (filename) => {
  const path = fs.readdirSync(pwd).find(fn => fn.endsWith(filename));
  return YAML.parse(fs.readFileSync(path, 'utf8'))
}

// Find any library with vite: true
const librariesYaml = yaml('.libraries.yml')
const librariesInput = Object.keys(librariesYaml)
  .filter(v => librariesYaml[v].vite)
  .map(v => [
    ...Object.keys(librariesYaml[v].css?.theme),
    ...Object.keys(librariesYaml[v].js),
  ])
  .flat()
  .filter(v => v.match(/^assets/))

// Find any CSS for ckeditor to statically map.
const infoYaml = yaml('.info.yml')
const ckeditorInput = (infoYaml['ckeditor5-stylesheets'] || [])
  .filter(v => v.match(/\.css$/))
  .map(v => v.replace('.css', '.scss'))
  .map(v => v.replace('dist/assets', 'assets/scss'))

// Statically rename the output file.
const outputMap = {}
ckeditorInput.forEach(v => {
  outputMap[path.basename(v).replace('.scss', '.css')] = 'assets/[name].[ext]'
})

export default ({ mode }) => {
  const env = loadEnv(mode, drupalPath, '')

  const config = {
    plugins: [
      eslint(),
      liveReload(__dirname + '/**/*.(php|theme|twig|module)'),
      viteExternalsPlugin({
        jquery: 'jQuery',
        Drupal: 'Drupal',
        once: 'once',
        drupalSettings: 'drupalSettings',
      }),
    ],

    base: mode === 'development' ? '/' : basePath,

    build: {
      outDir: 'dist',
      manifest: true,
      rollupOptions: {
        input: [...librariesInput, ...ckeditorInput],
        output: {
          assetFileNames: (assetInfo) => {
            return outputMap[assetInfo.name] || 'assets/[name].[hash].[ext]'
          },
        }
      },
    },

    css: {
      devSourcemap: true,
      preprocessorOptions: {
        scss: {
          additionalData: `@import '/assets/scss/variables.scss';`,
        },
      },
      postcss: {
        plugins: [
          autoprefixer(),
        ],
      },
    },

    server: {
      https: false,
      host: true,
      port: 3000,
    },
  }

  let proxyTarget = null

  if (env.LANDO_APP_NAME) {
    proxyTarget = `https://${env.LANDO_APP_NAME}.${env.LANDO_DOMAIN}`
  }

  if (env.DDEV_PRIMARY_URL) {
    proxyTarget = env.DDEV_PRIMARY_URL
  }

  if (proxyTarget && mode === 'development') {
    config.server.proxy = {
      '^/(system|api|jsonapi|graphql|oauth)/.*': {
        target: proxyTarget,
        changeOrigin: true,
      },
    }
  }

  return defineConfig(config)
}
