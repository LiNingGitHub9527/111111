/* eslint-disable @typescript-eslint/no-var-requires */
const mix = require('laravel-mix');
const tailwindcss = require('tailwindcss');
require('laravel-mix-purgecss');

/*
 |--------------------------------------------------------------------------
 | Mix Asset Management
 |--------------------------------------------------------------------------
 |
 | Mix provides a clean, fluent API for defining some Webpack build steps
 | for your Laravel application. By default, we are compiling the Sass
 | file for the application as well as bundling up all the JS files.
 |
 */

mix
  .js('resources/js/app.js', 'public/js')
  .sass('resources/sass/app.scss', 'public/css');

mix
  .ts('resources/ts/user/booking/index.ts', 'public/js/user/booking.js')
  .sass('resources/sass/user/booking.scss', 'public/css/user')
  .options({
    processCssUrls: false,
    postCss: [
      tailwindcss('./tailwind.config.js'),
      require('autoprefixer')({
        grid: true,
      }),
    ],
  })
  .purgeCss()
  .version();

mix
  .ts(
    'resources/ts/user/booking/search_panel/index.ts',
    'public/js/user/booking/search_panel.js'
  )
  .version();

  
mix
.ts(
  'resources/ts/user/booking/search/index.ts',
  'public/js/user/booking/search.js'
  )
  .version();
  
  mix
  .ts(
    'resources/ts/user/other/search_panel/index.ts',
    'public/js/user/other/search_panel.js'
    )
.version();
  
mix
  .ts(
    'resources/ts/user/other/admin_search_panel/index.ts',
    'public/js/user/other/admin_search_panel.js'
  )
  .version();