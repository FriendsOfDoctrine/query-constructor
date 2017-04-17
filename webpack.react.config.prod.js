const webpack = require('webpack');
const { resolve } = require('path');

module.exports = {
  entry: [
    './src/client/index.js',
    'whatwg-fetch'
  ],
  output: {
    filename: 'fod.query-constructor.js',
    path: './assets'
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
    new webpack.optimize.UglifyJsPlugin({
      sourceMap: false
    }),
    new webpack.DefinePlugin({
      'process.env': {
        'NODE_ENV': '"production"'
      }
    })
  ]
}