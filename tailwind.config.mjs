/** @type {import('tailwindcss').Config} */
export default {
  content: [
    "./src/**/*.{js,ts,jsx,tsx}",
    "./src/styles/admin.css",
    "./src/styles/frontend.css",
    // "./includes/**/*.php",
  ],
  theme: {
    colors: {}, //deprecated, use @theme in css files
  },
  plugins: [],
}
