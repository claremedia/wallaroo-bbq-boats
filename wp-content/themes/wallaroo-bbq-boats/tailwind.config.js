/** @type {import('tailwindcss').Config} */
module.exports = {
  content: [
    './*.php',
    './templates/**/*.php',
    './inc/**/*.php',
    './src/**/*.css',
  ],
  theme: {
    extend: {
      colors: {
        'brand-navy':  '#0A2A5E',
        'brand-white': '#FFFFFF',
        'brand-red':   '#D32027',
        'brand-sky':   '#3FA9DC',
        'brand-cream': '#F2E8D5',
      },
      fontFamily: {
        heading: ['Anton', 'sans-serif'],
        body:    ['Inter', 'sans-serif'],
      },
      borderRadius: {
        'xl':  '12px',
        '2xl': '16px',
        '3xl': '24px',
      },
      boxShadow: {
        'card': '0 4px 24px rgba(0,0,0,0.08)',
        'card-hover': '0 8px 32px rgba(0,0,0,0.14)',
      },
    },
  },
  plugins: [],
};
