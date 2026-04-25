// webpack.config.js
var Encore = require('@symfony/webpack-encore');

Encore
  .enableSingleRuntimeChunk()

  // the project directory where all compiled assets will be stored
  .setOutputPath('build/')

  // the public path used by the web server to access the previous directory
  .setPublicPath('/wp-content/themes/enfold-child/build')

  .addEntry('main', [
    './src/js/main.js'
  ])
  .addStyleEntry('style', './src/sass/style.scss')
  .addStyleEntry('custom-editor-style', './src/sass/custom-editor-style.scss')

  .configureBabel(function (babelConfig) {

  }, {
    // set optional Encore-specific options, for instance:

    // change the rule that determines which files
    // won't be processed by Babel
    //exclude: /bower_components/,

    // ...or keep the default rule but only allow
    // *some* Node modules to be processed by Babel
    includeNodeModules: ['swiper', 'dom7'],

    // automatically import polyfills where they
    // are needed
    useBuiltIns: 'usage',

    // if you set useBuiltIns you also have to add
    // core-js to your project using Yarn or npm and
    // inform Babel of the version it will use.
    corejs: 3
  })

  // allow legacy applications to use $/jQuery as a global variable
  //.autoProvidejQuery()

  // allow sass/scss files to be processed
  .enablePostCssLoader()
  .enableSassLoader()

  .copyFiles({
    from: './src/images',
    pattern: /\.(png|jpg|jpeg|svg)$/,
    to: 'images/[path][name].[ext]'
  })

  .copyFiles({
    from: './src/videos',
    to: 'videos/[path][name].[ext]'
  })

  // enable source maps during development
  .enableSourceMaps(!Encore.isProduction())

  // empty the outputPath dir before each build
  .cleanupOutputBeforeBuild()

  // show OS notifications when builds finish/fail
  //.enableBuildNotifications()

  // create hashed filenames (e.g. app.abc123.css)
  .enableVersioning(Encore.isProduction())

  .addLoader({
    test: /\.(gif|png|jpe?g|svg)$/i,
    loader: 'image-webpack-loader',
    options: {
      disable: !Encore.isProduction()
    }
  })

;

// export the final configuration
module.exports = Encore.getWebpackConfig();