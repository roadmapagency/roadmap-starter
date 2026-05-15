const os = require('os');
const webpack = require('webpack');
const path = require('path');
const isdev = require('isdev');
const CleanWebpackPlugin = require('clean-webpack-plugin');
const MiniCssExtractPlugin = require("mini-css-extract-plugin");
const BrowserSyncPlugin = require('browser-sync-webpack-plugin');
const ImageminPlugin = require('imagemin-webpack-plugin').default;
const imageminMozjpeg = require('imagemin-mozjpeg');
const CopyPlugin = require('copy-webpack-plugin');
const glob = require('glob');
const globImporter = require('sass-glob-importer');

const config = {
    filenameFormat: '[path][name][ext]?v=[hash:7]',
    publicDir: path.resolve('./public'),
    srcDir: path.resolve('./src'),
    relative: '../',
    outputs: {
        images: 'images/[name][ext]?v=[hash:7]',
    }
};

module.exports = {
    entry: {
        theme: './src/theme.js',
        "style-editor": './src/style-editor.js'
    },
    devtool: "source-map",
    module: {
        rules: [
            {
                test: /\.(s*)css$/,
                use: [
                    MiniCssExtractPlugin.loader,
                    {
                        loader: "css-loader",
                        options: {sourceMap: true}
                    },
                    {
                        loader: "resolve-url-loader",
                        options: {sourceMap: true}
                    },
                    {
                        loader: 'postcss-loader', // Run post css actions
                        options: {
                            postcssOptions: {
                                plugins: function () { // post css plugins, can be exported to postcss.config.js
                                    return [
                                        require('precss'),
                                        require('autoprefixer')
                                    ];
                                },
                            },
                            sourceMap: true,
                        },
                    },
                    {
                        loader: "sass-loader",
                        options: {
                            sassOptions:{
                                importer: globImporter(),
                                includePaths: [path.resolve('./node_modules')]
                            },
                            sourceMap: true,

                        }
                    },
                ]
            },
            {
                test: /\.(jpe?g|png|gif|svg)$/i,
                include: path.resolve(config.srcDir, './images'),
                use: {
                    loader: 'file-loader',
                    options: {
                        context: path.resolve(config.srcDir, './images'),
                        publicPath: config.relative,
                        name: config.outputs.images,
                        emitFile: false,
                    }
                }
            },
            {
                test: /\.(png|jpe?g|gif|svg)$/,
                include: path.resolve(__dirname, 'node_modules/'),
                use: {
                    loader: 'file-loader',
                    options: {
                        name: config.filenameFormat,
                        publicPath: path.resolve(config.publicDir, './images'),
                    }
                }
            },
            {
                test: /\.(eot|ttf|woff|woff2|svg)(\?\S*)?$/,
                // include: path.resolve(__dirname, 'node_modules/'),
                use: {

                    loader: 'url-loader?limit=100000',
                    options: {
                        name: 'fonts/' + config.filenameFormat,
                        publicPath: config.relative,
                    }
                }
            },
            {
                test: /\.js$/,
                exclude: /(node_modules|bower_components)/,
                use: {
                    loader: 'babel-loader',
                    options: {
                        presets: ['@babel/preset-env'],
                    }
                }
            }
        ]
    },
    externals: {
        jquery: 'jQuery'
    },
    plugins: [
        new CleanWebpackPlugin([config.publicDir], {verbose: true}),

        new MiniCssExtractPlugin({
            // Options similar to the same options in webpackOptions.output
            // both options are optional
            filename: 'css/[name].min.css',
            chunkFilename: "[id].css"
        }),

        new BrowserSyncPlugin({
            // browse to http://localhost:4000/ during development,
            host: 'localhost',
            port: 4000,
            // TODO: update this to match your local development URL
            proxy: 'http://roadmap-starter.localhost/',
            https: {
                key: path.join(os.homedir(), '/.ssl/localhost.key'),
                cert: path.join(os.homedir(), '/.ssl/localhost.pem'),
            },
            files: [
                {
                    match: [
                        '**/*.php'
                    ],
                    fn: function(event, file) {
                        if (event === "change") {
                            const bs = require('browser-sync').get('bs-webpack-plugin');
                            bs.reload();
                        }
                    }
                }
            ]
        }),

        new webpack.ProvidePlugin({
            $: 'jquery',
            jQuery: 'jquery',
            'window.jQuery': 'jquery',
            Popper: ['popper.js', 'default']
        }),

        // Copy the images folder and optimize all the images
        new CopyPlugin({
            patterns: [
                {
                    from: `${path.resolve(config.srcDir, './images')}/**/*`,
                    to: './images/' + config.filenameFormat,
                    context: "src/images/"
                }
            ],
            // options: {
            //     context: config.srcDir,
            // }
        }),

        new ImageminPlugin({
            test: /\.(jpe?g|png|gif|svg)\?v\=[a-zA-Z0-9]{7,}$/i,
            optipng: {optimizationLevel: 3},
            gifsicle: {optimizationLevel: 3},
            pngquant: {quality: '65-90', speed: 4},
            svgo: {removeUnknownsAndDefaults: false, cleanupIDs: false},
            plugins: [
                imageminMozjpeg({
                    quality: 75,
                    progressive: true
                })
            ],
            externalImages: {
                context: 'src', // Important! This tells the plugin where to "base" the paths at
                sources: glob.sync('src/images/external/**/*.jpg'),
                destination: 'public/images/[name].[ext]',
            },
            cacheFolder: isdev ? path.resolve('./tmp') : null,
        }),
    ],
    output: {
        filename: '[name].min.js',
        path: config.publicDir,
        publicPath: config.publicDir,
    },
};

