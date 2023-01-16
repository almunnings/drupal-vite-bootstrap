import fs from 'fs'
import YAML from 'yaml'
import { resolve } from 'path'
import { defineConfig, loadEnv } from 'vite'
import eslint from 'vite-plugin-eslint'
import basicSsl from '@vitejs/plugin-basic-ssl'
import liveReload from 'vite-plugin-live-reload'

// Pull config from dvb.libraries.yml
const yml = YAML.parse(fs.readFileSync('./dvb.libraries.yml', 'utf8'))
const { outDir,scheme,host,port } = yml.app.vite

// Find scss and js in the app yml
const theme_input = [
  ...Object.keys(yml.app.css.theme),
  ...Object.keys(yml.app.js),
].filter(v => v.match(/^assets/))

// Anything not in the theme we wantto add to the entrypoint.
const extra_input = [
  'assets/scss/ckeditor.scss',
];

// Change output hashing for specific files. (scss = css)
const output_map = {
  'assets/scss/ckeditor.css': 'assets/[name].[ext]'
}

export default ({ mode }) => {
  const env = loadEnv(mode, resolve(__dirname, '../../../../'), '')

  return defineConfig({
    plugins: [
      eslint(),
      scheme === 'https' ? basicSsl() : null,
      liveReload(__dirname+'/**/*.(php|theme|twig|module)'),
    ],

    base: mode === 'development' ? '/' : `/themes/custom/dvb/${outDir}/`,

    build: {
      outDir,
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
      host: true,
      strictPort: true,
      https: scheme === 'https',
      origin: `${scheme}://${host}:${port}`,
      hmr: {
        protocol: scheme === 'https' ? 'wss' : 'ws',
      },
      proxy: {
        '^/(system|api|jsonapi|graphql)/.*': {
          target: `${scheme}://${env.COMPOSE_PROJECT_NAME}.lndo.site`,
          changeOrigin: true,
        },
      }
    },
  })
}
