/** @type {import('tailwindcss').Config} */
module.exports = {
  darkMode: 'class',
  /**
   * This tells Tailwind to look for class names
   * in all .twig and .php files within the views, app, and core directories.
   * This ensures that classes generated in PHP strings are also detected.
   */
  content: [
    "./views/**/*.{twig,php}",
    "./app/**/*.{twig,php}",
    "./core/**/*.{twig,php}",
  ],
  theme: {
    extend: {},
  },
  plugins: [
    require('postcss-import'),
    require('tailwindcss'),
    // require('autoprefixer'),
  ],
}