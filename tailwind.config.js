/** @type {import('tailwindcss').Config} */
module.exports = {
  content: [
    './index.php',
    './logout.php',
    './views/**/*.php',
    './controllers/**/*.php',
    './public/js/**/*.js',
  ],
  theme: {
    extend: {
      fontFamily: {
        sans: ['Inter', 'sans-serif'],
      },
    },
  },
  plugins: [require('daisyui')],
  daisyui: {
    themes: ['light', 'wireframe'],
    darkTheme: false,
    base: true,
    styled: true,
    utils: true,
    logs: false,
  },
  safelist: [
    'loading',
    'loading-spinner',
    'loading-sm',
    'checkbox',
    'checkbox-sm',
    'checkbox-success',
    'btn',
    'input',
    'input-bordered',
    'divider',
  ],
};
