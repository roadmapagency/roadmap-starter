const os = require('os');
const webpack = require('webpack');
const path = require('path');
const isdev = require('isdev');
const CleanWebpackPlugin = require('clean-webpack-plugin');
const MiniCssExtractPlugin = require("mini-css-extract-plugin");
const BrowserSyncPlugin = require('browser-sync-webpack-plugin');
const ImageMinimizerPlugin = require('image-minimizer-webpack-plugin');
const CopyPlugin = require('copy-webpack-plugin');
const glob = require('glob');

// Glob-aware Sass importer.
//
// NOTE: the `sass-glob-importer` package maps matched files through `map-files`,
// which keys them by basename (sans extension). Every block stylesheet is named
// `style.scss`, so they all collapse to a single key and only ONE block's styles
// survive — silently dropping every other block. This replacement expands a glob
// `@import` into an explicit `@import` per matched file (relative to the importing
// file), so ALL matches compile. Non-glob imports are deferred to Sass/webpack.
const globImporter = function () {
    return function ( url, prev ) {
        if ( url.indexOf( '*' ) === -1 ) {
            return null; // not a glob — let Sass/webpack resolve it normally
        }
        const basedir = path.dirname( prev );
        const contents = glob
            .sync( url, { cwd: basedir } )
            .sort()
            .map( function ( file ) {
                return '@import "' + path.resolve( basedir, file ).replace( /\\/g, '/' ) + '";';
            } )
            .join( '\n' );
        return { contents: contents };
    };
};

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
        "style-editor": './src/style-editor.js',
        admin: './src/admin.js'
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
            // Dev proxy URL derived from the theme directory name so each
            // clone targets its own local domain automatically. Override by
            // setting WP_DEV_URL=http://yoursite.localhost/ in your shell.
            proxy: process.env.WP_DEV_URL
                || 'http://' + path.basename(__dirname) + '.localhost/',
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

        // Copy the images folder. NOTE: no `?v=[hash]` query on the output name —
        // ImageMinimizerPlugin detects format via path.extname(), which a query
        // suffix breaks (".jpg?v=abc123" → unsupported → skipped). The copied files
        // are referenced by plain path from templates, so the query was cosmetic.
        new CopyPlugin({
            patterns: [
                {
                    from: `${path.resolve(config.srcDir, './images')}/**/*`,
                    to: './images/[path][name][ext]',
                    context: "src/images/"
                }
            ],
            // options: {
            //     context: config.srcDir,
            // }
        }),

        // Image optimization via sharp (raster) + svgo (svg). Both are native to
        // arm64 and ship/install cleanly here, unlike the legacy `*-bin` imagemin
        // packages that have no arm64 build. Runs on every build, no binary guard.
        new ImageMinimizerPlugin({
            test: /\.(jpe?g|png|gif|svg)(\?.*)?$/i,
            minimizer: [
                {
                    implementation: ImageMinimizerPlugin.sharpMinify,
                    filter: (source, name) => /\.(jpe?g|png|gif)(\?.*)?$/i.test(name),
                    options: {
                        encodeOptions: {
                            // mozjpeg mode (matches the previous imagemin-mozjpeg
                            // pipeline) — better compression and, unlike the default
                            // libjpeg encoder, it won't inflate already-compact JPEGs.
                            jpeg: { quality: 75, mozjpeg: true },
                            png: { compressionLevel: 9, palette: true },
                            gif: {},
                        },
                    },
                },
                {
                    implementation: ImageMinimizerPlugin.svgoMinify,
                    filter: (source, name) => /\.svg(\?.*)?$/i.test(name),
                    options: {
                        encodeOptions: {
                            multipass: true,
                            plugins: [
                                { name: 'preset-default', params: { overrides: { removeViewBox: false } } },
                            ],
                        },
                    },
                },
            ],
        }),
    ],
    output: {
        filename: '[name].min.js',
        path: config.publicDir,
        publicPath: config.publicDir,
    },
};

