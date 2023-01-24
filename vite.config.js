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
const theme_input = [
  ...Object.keys(yml.app.css.theme),
  ...Object.keys(yml.app.js),
].filter(v => v.match(/^assets/))

// Anything not in the theme we wantto add to the entrypoint.
const extra_input = [
  'assets/scss/ckeditor.scss',
]

// Change output hashing for specific files. (scss = css)
const output_map = {
  'assets/scss/ckeditor.css': 'assets/[name].[ext]'
}

export default ({ mode }) => {
  const env = loadEnv(mode, drupalPath, '')
  const lando = env?.LANDO_APP_NAME

  return defineConfig({
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
          assetFileNames: (assetInfo) => output_map[assetInfo.name] || 'assets/[name].[hash].[ext]',
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
      hmr: {
        protocol: lando ? 'wss' : 'ws',
        host: lando ? `node.${lando}.lndo.site` : 'localhost',
      },
      proxy: {
        '^/(system|api|jsonapi|graphql)/.*': {
          target: `https://${lando}.lndo.site`,
          changeOrigin: true,
        },
      }
    },
  })
}
