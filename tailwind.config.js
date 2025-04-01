/** @type {import('tailwindcss').Config} */
export default {
  content: [
    "./resources/**/*.js",
    "./resources/**/*.blade.php",
  ],
  theme: {
    extend: {
      colors: {
        primary: {
          DEFAULT: '#6482AD',
          light: '#7FA1C3',
          dark: '#466591'
        },
        cream: {
          dark: '#d0c0c0',
          DEFAULT: '#E2DAD6',
          light: '#F5EDED'
        },
        white: {
          DEFAULT: '#FDFDFD',
          light: '#FFFFFF',
          medium: '#D3D3D3',
          dark: '#909090'
        },
        black: {
          DEFAULT: '#222222',
          light: '#444444',
          dark: '#000000'
        },
        success: {
          DEFAULT: '#1F7D53',
          dark: '#255F38'
        },
        danger: {
          DEFAULT: '#BE3144',
          dark: '#872341'
        }
      },
      fontFamily: {
        poppins: ['Poppins', 'Arial', 'sans-serif'],
        roboto: ['Roboto', 'Arial', 'sans-serif']
      },
      animation: {
        blink: 'blink 1s infinite',
        marquee: 'marquee 1s linear infinite',
        'fade-in': 'fade-in 1s ease-in',
        'fade-out': 'fade-out 1s ease-out',
      },
      keyframes: {
        blink: {
          '0%': { opacity: '0' },
          '50%': { opacity: '1' },
          '100%': { opacity: '0' }
        },
        marquee: {
          '0%': { transform: 'translateX(100%)' },
          '100%': { transform: 'translateX(-100%)' },
        },
        "fade-in": {
          '0%': { opacity: 0 },
          '100%': { opacity: 1 }
        },
        "fade-out": {
          '0%': { opacity: 1 },
          '100%': { opacity: 0 }
        },
      },
    },
  },
  plugins: [],
}

