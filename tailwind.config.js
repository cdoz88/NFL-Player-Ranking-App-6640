/** @type {import('tailwindcss').Config} */
export default {
  content: [
    "./index.html",
    "./src/**/*.{js,ts,jsx,tsx}",
  ],
  theme: {
    extend: {
      colors: {
        nfl: {
          blue: '#013369',
          red: '#D50A0A',
          silver: '#869397'
        }
      },
      fontFamily: {
        'nfl': ['Inter', 'system-ui', 'sans-serif']
      }
    },
  },
  plugins: [],
}