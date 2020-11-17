const path = require('path');
const webpack = require('webpack');
const MiniCssExtractPlugin = require('mini-css-extract-plugin');
const CssMinimizerPlugin = require('css-minimizer-webpack-plugin');
const { CleanWebpackPlugin } = require('clean-webpack-plugin');

module.exports = (env, argv) => {
  const { mode } = argv;

  return {
    devtool: mode === 'production'
      ? 'source-map'
      : 'cheap-module-eval-source-map',
    entry: {
      'fieldmanager': [
        './js/fieldmanager.js',
        './css/fieldmanager.css',
        './css/fieldmanager-options.css',
      ],
      'fieldmanager-autocomplete': './js/fieldmanager-autocomplete.js',
      'fieldmanager-colorpicker': './js/fieldmanager-colorpicker.js',
      'fieldmanager-datepicker': './js/fieldmanager-datepicker.js',
      'fieldmanager-draggablepost': [
        './js/fieldmanager-draggablepost.js',
        './css/fieldmanager-draggablepost.css',
      ],
      'fieldmanager-group-tabs': [
        './js/fieldmanager-group-tabs.js',
        './css/fieldmanager-group-tabs.css',
      ],
      'fieldmanager-media': './js/media/fieldmanager-media.js',
      'fieldmanager-select': './js/fieldmanager-select.js',
      'fieldmanager-quickedit': './js/fieldmanager-quickedit.js',
      'fieldmanager-options': './css/fieldmanager-options.css',
      'richtext': './js/richtext.js',
    },
    output: {
      filename: mode === 'production'
        ? 'js/[name].bundle.min.js'
        : 'js/[name].js',
      path: path.join(__dirname, 'build'),
    },
    module: {
      rules: [
        {
          test: /.css$/,
          use: [
            MiniCssExtractPlugin.loader,
            'css-loader',
            {
              loader: 'postcss-loader',
              options: {
                postcssOptions: {
                  plugins: [
                    [
                      // postcss-preset-env includes autoprefixer
                      'postcss-preset-env',
                      {
                        browsers: 'last 2 versions',
                      },
                    ],
                  ],
                },
              },
            },
          ],
        },
      ],
    },
    plugins: [
      ...(mode === 'production'
        ? [
          new MiniCssExtractPlugin({
            filename: 'css/[name].min.css',
          }),
          new CleanWebpackPlugin(),
          new webpack.ProvidePlugin({
            $: 'jquery',
            jQuery: 'jquery',
          }),
        ] : []
      ),
    ],
    optimization: {
      minimize: true,
      minimizer: [
        '...', // Load existing minimizers in order to minimize JS with terser-webpack-plugin.
        new CssMinimizerPlugin(),
      ],
    },
    resolve: {
      extensions: ['.js', '.css'],
    },
    externals: {
      // Enable require('jquery') where jquery is already a global
      jquery: 'jQuery',
    },
  };
};