/** @type {import('tailwindcss').Config} */
export default {
  content: [
    "./index.html",
    "./src/**/*.{js,ts,jsx,tsx}",
  ],
  theme: {
    extend: {
      colors: {
        medical: {
            fontFamily: {
        // هنا نضيف الخط. 'Tajawal' هو الاسم الذي سنستخدمه في الكلاسات
        sans: ['Tajawal', 'sans-serif'],
      },
          DEFAULT: '#72A6BB',
          light: '#E3F0F5',
          dark: '#58889B',
        }
      }
    },
  },
  plugins: [],
}