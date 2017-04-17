const webpack = require('webpack');
const { resolve } = require('path');

module.exports = {
  devtool: 'cheap-module-eval-source-map',
  entry: [
    './src/client/index.js',
    'whatwg-fetch'
  ],
  output: {
    filename: 'fod.query-constructor.dev.js',
    path: resolve(__dirname, './assets'),
    // https://github.com/gaearon/react-hot-loader/issues/92
    publicPath: process.env.WEBPACK_DEV_SERVER_PATH
      ? process.env.WEBPACK_DEV_SERVER_PATH + '/assets'
      : '/assets'
  },
  module: {
    preLoaders: [
      {
        test: /\.js$/,
        loader: 'eslint',
        include: resolve(__dirname, './src/client'),
        exclude: /node_modules/
      }
    ],
    loaders: [
      {
        test: /\.js$/,
        include: resolve(__dirname, './src/client'),
        loader: 'babel',
        exclude: /node_modules/
      }
    ]
  },
  plugins: [
    new webpack.NoErrorsPlugin(),
    new webpack.DefinePlugin({
      'process.env': {
        'NODE_ENV': '"development"'
      }
    })
  ]
}