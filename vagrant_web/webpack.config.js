const path    = require('path');
const webpack = require('webpack');

const MiniCssExtractPlugin = require("mini-css-extract-plugin");
const devMode              = process.env.NODE_ENV !== 'production';
const {VueLoaderPlugin}    = require('vue-loader');
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
				test: /\.m?js$/,
				type: 'javascript/auto',
				loader: 'babel-loader',
				options: {
					plugins: [
						require.resolve('@babel/plugin-transform-nullish-coalescing-operator'),
						require.resolve('@babel/plugin-transform-optional-chaining'),
					],
				},
				exclude: /node_modules\/(?!(vue-router|epic-spinners)\/)/
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
				loader: [
					'babel-loader',
					{
						loader: path.resolve(__dirname, 'scripts/loaders/svg-vue-loader.cjs'),
					}
				],
			},
			{
				test: /\.(sa|sc|c)ss$/,
				use: [
					devMode ? 'style-loader' : {
						loader: MiniCssExtractPlugin.loader,
						options: {
							// you can specify a publicPath here
							// by default it use publicPath in webpackOptions.output
							publicPath: 'css/',

							// only enable hot in development
							// hmr: devMode,

						}
					},
					'css-loader',
					/* 'postcss-loader', */
					{
						loader: 'sass-loader',
						options: {
							implementation: require('sass'),
						},
					},
				],
			},
			{
				test: /\.vue$/,
				loader: 'vue-loader',
				options: {
					compilerOptions: {
						compatConfig: {
							MODE: 2,
						},
					},
				},
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
			__VUE_OPTIONS_API__: JSON.stringify(true),
			__VUE_PROD_DEVTOOLS__: JSON.stringify(false),
			__VUE_PROD_HYDRATION_MISMATCH_DETAILS__: JSON.stringify(false),
			'process.env': {
				NODE_ENV: devMode ? '"development"' : '"production"'
			}
		}),

		/*
		new webpack.optimize.AggressiveMergingPlugin({
			moveToParents: true,
		}),
		*/


	],
	resolve: {
		extensions: ['*', '.js', '.vue', '.json'],//in webpack 2.2 default resolve .js .json
		alias: {
			'@': path.resolve(__dirname, 'resources/js'),
			'@icons': path.resolve(__dirname, 'resources/icons'),
			'vue$': '@vue/compat/dist/vue.esm-bundler.js' // Vue 3 migration build
			// 'vue$': 'vue/dist/vue.runtime.esm.js' // Use runtime only
		}
	},
	devServer: {
		hot: true, // this enables hot reload
		contentBase: path.join(__dirname, "public"), // should point to the laravel public folder
		watchOptions: {
			poll: false // needed for homestead/vagrant setup
		},
		noInfo: false,
		overlay: true,
		disableHostCheck: true,
		headers: {
			'Access-Control-Allow-Origin': '*',
		},
		port: 8080,
		host: '0.0.0.0', // Which hosts are allowed to access the served content
	},
	performance: {
		hints: false
	},
	devtool: 'eval-source-map', // For Debugging while using sourcemaps: https://medium.com/@BjornKrols/a-basic-introduction-to-debugging-vue-applications-using-breakpoints-2ef76ce419f2
	optimization: {
		splitChunks: {
			cacheGroups: {},
		},
	},
};

// Analyzer only in DEV-Mode
// http://127.0.0.1:8888
if (process.env.ANALYZE === 'true') {
	module.exports.plugins.push(new (require('webpack-bundle-analyzer').BundleAnalyzerPlugin)({
		openAnalyzer: false
	}));
}


if (process.env.NODE_ENV === 'production') {
	// https://survivejs.com/webpack/building/source-maps/
	module.exports.devtool = '#source-map'
	// http://vue-loader.vuejs.org/en/workflow/production.html
	module.exports.plugins = (module.exports.plugins || []).concat([]);


}
