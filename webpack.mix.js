const mix = require('laravel-mix')

mix
    .js('./resources/js/shuttle.js', './resources/dist/js/shuttle.js')
    .setPublicPath('./resources/dist')
    .version()
