const webpack = require('webpack');
const path = require('path');

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
        new webpack.optimize.UglifyJsPlugin({
            sourceMap: false
        }),
        new webpack.DefinePlugin({
            'process.env': {
                'NODE_ENV': '"production"'
            }
        })
    ]
};