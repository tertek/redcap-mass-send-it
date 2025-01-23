import { defineConfig } from 'vite'
import path from "path";
import { svelte } from '@sveltejs/vite-plugin-svelte'

// https://vite.dev/config/
export default defineConfig({
  plugins: [svelte()],
  build: {
    lib: {
      formats: ["es"],
      fileName: (format)=>`main.js`,
      entry: path.resolve(__dirname, "src/main.ts"),
    },
  }
})
