import {defineConfig} from "vite";
import react from "@vitejs/plugin-react";
import {resolve} from "path";
import {fileURLToPath, URL} from "node:url";

const __dirname = fileURLToPath(new URL(".", import.meta.url));

export default defineConfig({
  plugins: [react()],
  server: {
    watch: {
      // Only watch frontend source files in dev mode
      include: ["**/src/**/*"],
      ignored: [
        "**/*.php",
        "**/includes/**",
        "**/vendor/**",
        "**/tests/**",
        "**/node_modules/**",
        "**/assets/dist/**",
        "**/.git/**",
        "**/composer.json",
        "**/composer.lock",
        "**/phpcs.xml",
        "**/phpunit.xml",
      ],
    },
  },
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
      external: [],
    },
    manifest: true,
    write: true,
    // Configure build watch mode to exclude PHP files
    watch: {
      include: ["src/**/*"],
      exclude: [
        "**/*.php",
        "**/includes/**",
        "**/vendor/**",
        "**/tests/**",
        "**/node_modules/**",
        "**/assets/dist/**",
        "**/.git/**",
        "**/composer.json",
        "**/composer.lock",
      ],
    },
  },
  resolve: {
    alias: {
      "@": resolve(__dirname, "./src"),
    },
  },
});
