import {defineConfig} from "vite";
import react from "@vitejs/plugin-react";
import {federation} from "@module-federation/vite";

export default defineConfig({
  plugins: [
    react(),
    federation({
      name: "games",
      filename: "remoteEntry.js",
      exposes: {
        "./routes": "./src/index.ts"
      },
      shared: {
        react: {singleton: true, import: false},
        "react-dom": {singleton: true, import: false},
        "react-router": {singleton: true, import: false},
        "@mantine/core": {singleton: true, import: false},
        "@mantine/hooks": {singleton: true, import: false},
        "@mantine/form": {singleton: true, import: false},
        "@mantine/dates": {singleton: true, import: false},
        "dayjs": {singleton: true, import: false},
        "@mantine/notifications": {singleton: true, import: false},
        "@tanstack/react-query": {singleton: true, import: false},
        clsx: {singleton: true, import: false}
      }
    })
  ],
  base: "/static/modules/games/",
  build: {
    outDir: "../../../static/modules/games",
    emptyOutDir: true,
    target: "esnext",
    rollupOptions: {
      input: {}
    }
  },
  css: {
    modules: {
      localsConvention: "camelCaseOnly"
    }
  }
});
