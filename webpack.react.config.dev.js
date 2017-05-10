const webpack = require('webpack');
const path = require('path');

module.exports = {
    devtool: 'cheap-module-eval-source-map',
    entry: [
        './src/client/index.js',
        'whatwg-fetch'
    ],
    output: {
        filename: 'fod.query-constructor.dev.js',
        path: path.join(__dirname, './assets'),
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
                include: path.join(__dirname, './src/client'),
                exclude: /node_modules/
            }
        ],
        loaders: [
            {
                test: /\.js$/,
                include: path.join(__dirname, './src/client'),
                loader: 'babel',
                exclude: /node_modules/
            },
            {test: /\.css$/, loader: "style-loader!css-loader"}
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
};