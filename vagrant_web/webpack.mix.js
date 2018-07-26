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
    .js('resources/assets/js/dependencies.js', 'public/js')
    .js('resources/assets/js/keywords/keywords.js', 'public/js')
    .sass('resources/assets/sass/app.scss', 'public/css')
    .sass('resources/assets/sass/dependencies.scss', 'public/css')
    .sass('resources/assets/sass/media.scss', 'public/css')
    .copyDirectory('node_modules/octicons/build/svg', 'public/img/octicons')
;


mix.version();

if (mix.inProduction()) {

} else {
    mix.browserSync({
        proxy: 'materialpool.test',
        notify: false,
        open: false,
    });

   //  mix.js('resources/assets/js/app.js', 'public/js')
   //     .sass('resources/assets/sass/app.scss', 'public/css');

}

// , 'tether', 'axios', 'jquery'