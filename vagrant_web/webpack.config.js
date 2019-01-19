const path    = require('path');
const webpack = require('webpack');

const MiniCssExtractPlugin = require("mini-css-extract-plugin");
const devMode              = process.env.NODE_ENV !== 'production';
const VueLoaderPlugin      = require('vue-loader/lib/plugin');
const ASSET_PATH           = devMode ? 'http://localhost:8080/' /* In DEV Mode This is the VIRTUAL Path where the files will be served from memory. But also where the hot-reload stuff comes from. */ : '/';

// const MergeIntoSingleFilePlugin = require('webpack-merge-and-include-globally');


module.exports = {
    entry: {
        main: './resources/js/apps/main/index.js',
        //      dependencies: [
        //          './resources/js/dependencies.js',
        //      ],
    },
    output: {
        path: path.resolve(__dirname, './public/'),
        publicPath: ASSET_PATH, /* In DEV Mode This is the VIRTUAL Path where the files will be served from memory. But also where the hot-reload stuff comes from. */
        filename: 'js/[name]_build.js',
        chunkFilename: 'js/[name].bundle.js',
    },
    module: {
        rules: [
            {
                test: /\.js$/,
                loader: 'babel-loader',
                exclude: /node_modules/
            },
            {
                test: /\.(png|jpg|jpeg|gif)$/,
                loader: 'file-loader',
                options: {
                    name: '[name].[ext]?[hash]'
                }
            },
            {
                test: /\.svg$/,
                loader: 'vue-svg-loader', // `vue-svg` for webpack 1.x
                options: {
                    // optional [svgo](https://github.com/svg/svgo) options
                    svgo: {
                        plugins: [{cleanupAttrs: false},
                            {removeDoctype: true},
                            {removeXMLProcInst: true},
                            {removeComments: true},
                            {removeMetadata: true},
                            {removeTitle: true},
                            {removeDesc: true},
                            {removeUselessDefs: true},
                            {removeEditorsNSData: true},
                            {removeEmptyAttrs: true},
                            {removeHiddenElems: true},
                            {removeEmptyText: true},
                            {removeEmptyContainers: true},
                            {removeViewBox: false},
                            {cleanupEnableBackground: true},
                            {convertStyleToAttrs: false},
                            {convertColors: false},
                            {convertPathData: false},
                            {convertTransform: false},
                            {removeUnknownsAndDefaults: false}, // Don't change! Removes viewBox
                            {removeNonInheritableGroupAttrs: true},
                            {removeUselessStrokeAndFill: true},
                            {removeUnusedNS: true},
                            {cleanupIDs: false},
                            {cleanupNumericValues: false},
                            {moveElemsAttrsToGroup: false},
                            {moveGroupAttrsToElems: false},
                            {collapseGroups: true},
                            {removeRasterImages: false},
                            {mergePaths: false},
                            {convertShapeToPath: false},
                            {sortAttrs: true},
                            {removeDimensions: true},
                            {
                                removeAttrs: {attrs: '(stroke|fill)'},
                            }]
                    }
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
            },
            {
                test: /\.vue$/,
                use: 'vue-loader',
            },
        ]
    },
    plugins: [
        new VueLoaderPlugin(),

        new MiniCssExtractPlugin({
            // Options similar to the same options in webpackOptions.output
            // both options are optional
            filename: "css/[name].css",
            chunkFilename: "css/[id].css"
        }),


        new webpack.DefinePlugin({
            'process.env': {
                NODE_ENV: devMode ? '"development"' : '"production"'
            }
        }),

        /*
        new webpack.optimize.AggressiveMergingPlugin({
            moveToParents: true,
        }),
        */

        new (require('webpack-bundle-analyzer').BundleAnalyzerPlugin)({
            openAnalyzer: false
        })

    ],
    resolve: {
        extensions: ['*', '.js', '.vue', '.json'],//in webpack 2.2 default resolve .js .json
        alias: {
            'vue$': 'vue/dist/vue.esm.js' // Use the full build
            // 'vue$': 'vue/dist/vue.runtime.esm.js' // Use runtime only
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
    devtool: '#eval-source-map', // For Debugging while using sourcemaps: https://medium.com/@BjornKrols/a-basic-introduction-to-debugging-vue-applications-using-breakpoints-2ef76ce419f2
    optimization: {
        splitChunks: {
            cacheGroups: {
                commons: {
                    test: /[\\/](node_modules|vendor)[\\/]/,
                    name: "vendor",
                    chunks: "initial",
                },
            },
        },
    },
}

if (process.env.NODE_ENV === 'production') {
    // https://survivejs.com/webpack/building/source-maps/
    module.exports.devtool = '#source-map'
    // http://vue-loader.vuejs.org/en/workflow/production.html
    module.exports.plugins = (module.exports.plugins || []).concat([]);


}
