const {mix} = require('laravel-mix');

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

mix.js('resources/assets/js/app.js', 'public/js')
    .js('resources/assets/js/searchbar.js', 'public/js')
    .js('resources/assets/js/media.js', 'public/js')
    .sass('resources/assets/sass/app.scss', 'public/css')
    .sass('resources/assets/sass/media.scss', 'public/css')
    //     .extract(['select2', 'select2-bootstrap-theme'])
    .copyDirectory('node_modules/octicons/build/svg', 'public/img/octicons')
    .version()
    .browserSync()
;

// , 'tether', 'axios', 'jquery'