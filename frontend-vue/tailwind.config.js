/** @type {import('tailwindcss').Config} */
export default {
  content: [
    "./index.html",
    "./src/**/*.{vue,js,ts,jsx,tsx}",
  ],
  theme: {
    extend: {
      colors: {
        blueprint: {
          dark: '#081C36',
          DEFAULT: '#0D2E5C',
          light: '#1B4984',
          accent: '#FFB627',
          paper: '#F7F4EC',
          paperBorder: '#D1C7B7',
          paperDark: '#EDE8D0',
        }
      },
      fontFamily: {
        sans: ['"Be Vietnam Pro"', 'system-ui', 'sans-serif'],
        mono: ['"IBM Plex Mono"', 'Courier New', 'monospace'],
      }
    },
  },
  plugins: [],
}
