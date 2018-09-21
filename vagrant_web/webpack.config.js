const path    = require('path');
const webpack = require('webpack');

const MiniCssExtractPlugin = require("mini-css-extract-plugin");
const devMode              = process.env.NODE_ENV !== 'production';

// const MergeIntoSingleFilePlugin = require('webpack-merge-and-include-globally');


module.exports = {
    entry: {
        app: './resources/assets/js/app.js',
        dependencies: [
            './resources/assets/js/dependencies.js',
        ],
        materialApp: './resources/assets/js/apps/MaterialEdit/main.js',
        searchApp: './resources/assets/js/apps/SearchApp/main.js'
    },
    output: {
        path: path.resolve(__dirname, './public/'),
        publicPath: 'http://localhost:8080/', /* In DEV Mode This is the VIRTUAL Path where the files will be server from memory. But also where the hot-reload stuff comes from. */
        filename: 'js/[name]_build.js'
    },
    module: {
        rules: [
            {
                test: /\.vue$/,
                loader: 'vue-loader',
                options: {
                    loaders: {}
                    // other vue-loader options go here
                }
            },
            {
                test: /\.js$/,
                loader: 'babel-loader',
                exclude: /node_modules/
            },
            {
                test: /\.(png|jpg|gif|svg)$/,
                loader: 'file-loader',
                options: {
                    name: '[name].[ext]?[hash]'
                }
            },
            {
                test: /\.(sa|sc|c)ss$/,
                use: [
                    devMode ? 'style-loader' : {
                        loader: MiniCssExtractPlugin.loader, options: {
                            // you can specify a publicPath here
                            // by default it use publicPath in webpackOptions.output
                            publicPath: 'css/'
                        }
                    },
                    'css-loader',
                    /* 'postcss-loader', */
                    'sass-loader',
                ],
            }
        ]
    },
    plugins: [
        /*new MergeIntoSingleFilePlugin({

            "bundle.js": [
                path.resolve(__dirname, 'src/util.js'),
                path.resolve(__dirname, 'src/index.js')
            ],

            "dependencies.css": [
                path.resolve(__dirname, 'src/css/main.css'),
                path.resolve(__dirname, 'src/css/local.css')
            ]
        })
*/
        new MiniCssExtractPlugin({
            // Options similar to the same options in webpackOptions.output
            // both options are optional
            filename: "css/[name].css",
            chunkFilename: "css/[id].css"
        }),
        new webpack.LoaderOptionsPlugin({
            options: {
                context: process.cwd() // or the same value as `context`
            }
        })
    ],
    resolve: {
        extensions: ['*', '.js', '.vue', '.json'],//in webpack 2.2 default resolve .js .json
        alias: {
            'vue$': 'vue/dist/vue.esm.js'
        }
    },
    devServer: {
        hot: true, // this enables hot reload
        contentBase: path.join(__dirname, "public"), // should point to the laravel public folder
        watchOptions: {
            poll: false // needed for homestead/vagrant setup
        },
        historyApiFallback: false,
        noInfo: false,
        overlay: true,
        disableHostCheck: true,
        headers: {
            'Access-Control-Allow-Origin': '*',
        },
    },
    performance: {
        hints: false
    },
    devtool: '#eval-source-map' // For Debugging while using sourcemaps: https://medium.com/@BjornKrols/a-basic-introduction-to-debugging-vue-applications-using-breakpoints-2ef76ce419f2
}

if (process.env.NODE_ENV === 'production') {
    module.exports.devtool = '#source-map'
    // http://vue-loader.vuejs.org/en/workflow/production.html
    module.exports.plugins = (module.exports.plugins || []).concat([
        new webpack.DefinePlugin({
            'process.env': {
                NODE_ENV: '"production"'
            }
        }),
        /*
        new webpack.optimize.UglifyJsPlugin({
            sourceMap: true,
            compress: {
                warnings: false
            }
        }),
        */
        new webpack.LoaderOptionsPlugin({
            minimize: true
        })
    ])
}
