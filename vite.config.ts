import {defineConfig} from "vite";
import react from "@vitejs/plugin-react";
import {resolve} from "path";
import {fileURLToPath, URL} from "node:url";

const __dirname = fileURLToPath(new URL(".", import.meta.url));

export default defineConfig({
  plugins: [react()],
  build: {
    outDir: "assets/dist",
    emptyOutDir: true,
    sourcemap: true,
    rollupOptions: {
      input: {
        admin: resolve(__dirname, "./src/admin.tsx"),
        frontend: resolve(__dirname, "./src/frontend.tsx"),
      },
      output: {
        entryFileNames: "[name].js",
        chunkFileNames: "[name]-[hash].js",
        assetFileNames: "[name].[ext]",
      },
      external: [], // Don't externalize for now
    },
    manifest: true,
    write: true,
  },
  resolve: {
    alias: {
      "@": resolve(__dirname, "./src"),
    },
  },
});
