const mix = require('laravel-mix');

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

mix.js('resources/assets/js/app.js', 'public/js/app.js')
    .js('resources/assets/js/plugins.js', 'public/js/plugins.js')
    // .extract(['vue','lodash','axios'], 'public/js/vendor.js')
    .copy('node_modules/jquery-typeahead/dist/jquery.typeahead.min.css', 'public/css')
    .copy('node_modules/chart.js/dist/chart.min.js', 'public/js/plugins')
    .sass('resources/assets/scss/app.scss', 'public/css')
    .vue();


if (mix.inProduction()) {
    console.log('In Production - Versioning Files');
    mix.version();
}
