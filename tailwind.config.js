/**
 * @type {import('@types/tailwindcss/tailwind-config').TailwindConfig}
 */
module.exports = {
  purge: {
    content: ['./resources/ts/**/*.tsx'],
    options: {
      safelist: [
        /^bg-/,
        /^hover:bg-/,
        /^text-/,
        /^hover:text-/,
        /^space-x-/,
        /^space-y-/,
      ],
    },
  },
  darkMode: false,
  theme: {
    extend: {
      padding: {
        '7/10': '70%',
        '9/26': '56.25%',
      },
      zIndex: {
        55: 55,
        60: 60,
        70: 70,
        80: 80,
        90: 90,
        100: 100,
      },
    },
  },
  variants: {
    extend: {
      margin: ['first', 'last', 'active'],
      borderWidth: ['first', 'last'],
      outline: ['hover', 'active'],
    },
  },
  plugins: [],
};
