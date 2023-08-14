import fs from 'fs'
import YAML from 'yaml'
import { resolve } from 'path'
import { defineConfig, loadEnv } from 'vite'
import eslint from 'vite-plugin-eslint'
import liveReload from 'vite-plugin-live-reload'

// Resolve dirs.
const pwd = resolve(__dirname, '.')
const drupalPath = resolve(__dirname, '../../../../')
const [themePath] = pwd.match(/\/themes\/[^\/]+\/[^\/]+/i) || []
const [ymlPath] = fs.readdirSync(pwd).filter(fn => fn.endsWith('.libraries.yml')) || [];
const baseUrl = (themePath ? `${themePath}/dist/` : '/themes/contrib/dvb/dist')

// Find scss and js in the app yml
const yml = YAML.parse(fs.readFileSync(ymlPath, 'utf8'))

// Find any library with vite: true
const theme_input = Object.keys(yml)
  .filter(v => yml[v].vite)
  .map(v => [
    ...Object.keys(yml[v].css?.theme),
    ...Object.keys(yml[v].js),
  ])
  .flat()
  .filter(v => v.match(/^assets/))

// Anything not in the theme we want to add to the entrypoint.
const extra_input = [
  'assets/scss/ckeditor.scss',
]

// Change output pattern for specific files. (scss = css)
// Eg assets/ckeditor.css is not hashed.
const output_map = {
  'ckeditor.css': 'assets/[name].[ext]'
}

export default ({ mode }) => {
  const env = loadEnv(mode, drupalPath, '')

  const config = {
    plugins: [
      eslint(),
      liveReload(__dirname + '/**/*.(php|theme|twig|module)'),
    ],

    base: mode === 'development' ? '/' : baseUrl,

    build: {
      outDir: 'dist',
      manifest: true,
      rollupOptions: {
        input: [...theme_input, ...extra_input],
        output: {
          assetFileNames: (assetInfo) => {
            return output_map[assetInfo.name] || 'assets/[name].[hash].[ext]'
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
    },

    server: {
      https: false,
      host: true,
      port: 3000,
    },
  }

  // Enable mapping back to a Lando domain.
  const lando_app = env?.LANDO_APP_NAME

  if (lando_app) {
    // Get the URLs for this service.
    const lando_info = JSON.parse(env.LANDO_INFO)
    const lando_urls = (lando_info[env.LANDO_SERVICE_NAME].urls || []).map(url => new URL(url))

    // Prefer https host. Else first.
    const host = lando_urls.find(url => !!url.protocol.match(/^https/i)) || lando_urls.shift()

    // Set HMR to the node service proxy domain.
    // Use the lando proxy to do all SSL termination.
    config.server.hmr = {
      protocol: !!host.protocol.match(/^https/i) ? 'wss' : 'ws',
      host: host.hostname || 'localhost',
      port: host.port
    }

    // Proxy some common paths back to the default domain.
    config.server.proxy = {
      '^/(system|api|jsonapi|graphql)/.*': {
        target: `${host.protocol}//${lando_app}.lndo.site`,
        changeOrigin: true,
      },
    }
  }

  return defineConfig(config)
}
