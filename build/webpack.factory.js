/**
 * Shared webpack config for roadmap-starter and its child themes.
 *
 *     // child theme webpack.config.js
 *     module.exports = require( '../roadmap-starter/build/webpack.factory.js' )( { themeDir: __dirname } );
 *
 * Everything is resolved from `themeDir` (sources, public/, node_modules) so a child builds into its
 * own public/ with its own toolchain. Sass looks in the theme's src/sass, its node_modules, and the
 * themes directory — so a child can `@import "roadmap-starter/src/sass/framework"` and the parent's
 * relative imports still resolve inside the parent. `@import "roadmap-blocks"` expands to every
 * block stylesheet: the child's, then any parent block the child does not override.
 */
const fs = require('fs');
const os = require('os');
const path = require('path');
const { createRequire } = require('module');

module.exports = function ( { themeDir, parentDir = path.resolve( __dirname, '..' ) } ) {
    // Build tooling comes from the theme being built, not from the parent's checkout.
    const req = createRequire( path.join( themeDir, 'package.json' ) );
    const webpack = req('webpack');
    const CleanWebpackPlugin = req('clean-webpack-plugin');
    const MiniCssExtractPlugin = req("mini-css-extract-plugin");
    const BrowserSyncPlugin = req('browser-sync-webpack-plugin');
    const ImageMinimizerPlugin = req('image-minimizer-webpack-plugin');
    const CopyPlugin = req('copy-webpack-plugin');
    const glob = req('glob');

    // Glob-aware Sass importer.
    //
    // NOTE: the `sass-glob-importer` package maps matched files through `map-files`,
    // which keys them by basename (sans extension). Every block stylesheet is named
    // `style.scss`, so they all collapse to a single key and only ONE block's styles
    // survive — silently dropping every other block. This replacement expands a glob
    // `@import` into an explicit `@import` per matched file (relative to the importing
    // file), so ALL matches compile. Non-glob imports are deferred to Sass/webpack.
    const toImports = function ( files ) {
        return files.map( function ( file ) {
            return '@import "' + file.replace( /\\/g, '/' ) + '";';
        } ).join( '\n' );
    };
    const globImporter = function () {
        return function ( url, prev ) {
            if ( url === 'roadmap-blocks' ) {
                return { contents: toImports( blockStylesheets() ) };
            }
            if ( url.indexOf( '*' ) === -1 ) {
                return null; // not a glob — let Sass/webpack resolve it normally
            }
            const basedir = path.dirname( prev );
            return {
                contents: toImports( glob.sync( url, { cwd: basedir } ).sort().map( function ( file ) {
                    return path.resolve( basedir, file );
                } ) ),
            };
        };
    };
    // Child blocks first; a parent block with the same (case-insensitive) name is overridden.
    const blockStylesheets = function () {
        const seen = {};
        const files = [];
        [ themeDir, parentDir ].filter( function ( dir, i, all ) {
            return all.indexOf( dir ) === i;
        } ).forEach( function ( dir ) {
            glob.sync( 'acf-blocks/Blocks/*/style.scss', { cwd: dir } ).sort().forEach( function ( file ) {
                const slug = path.basename( path.dirname( file ) ).toLowerCase();
                if ( ! seen[ slug ] ) {
                    seen[ slug ] = true;
                    files.push( path.resolve( dir, file ) );
                }
            } );
        } );
        return files;
    };

    // Records which parent version this child was compiled against (see inc/build-meta.php).
    const BuildMetaPlugin = {
        apply: function ( compiler ) {
            compiler.hooks.afterEmit.tap( 'RoadmapStarterBuildMeta', function () {
                const css = fs.readFileSync( path.join( parentDir, 'style.css' ), 'utf8' );
                const match = css.match( /^\s*Version:\s*(\S+)/m );
                fs.writeFileSync(
                    path.join( config.publicDir, 'build-meta.json' ),
                    JSON.stringify( { parentVersion: match ? match[1] : null }, null, 2 ) + '\n'
                );
            } );
        },
    };

    const config = {
        filenameFormat: '[path][name][ext]?v=[hash:7]',
        publicDir: path.resolve(themeDir, 'public'),
        srcDir: path.resolve(themeDir, 'src'),
        relative: '../',
        outputs: {
            images: 'images/[name][ext]?v=[hash:7]',
        }
    };
    
    return {
        context: themeDir,
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
                                            req('precss'),
                                            req('autoprefixer')
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
                                    includePaths: [path.resolve(themeDir, 'src/sass'), themeDir, path.resolve(themeDir, 'node_modules'), path.dirname(parentDir)]
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
                    include: path.resolve(themeDir, 'node_modules/'),
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
                    // include: path.resolve(themeDir, 'node_modules/'),
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
        resolve: {
            modules: [path.resolve(themeDir, 'node_modules'), 'node_modules'],
        },
        resolveLoader: {
            modules: [path.resolve(themeDir, 'node_modules'), 'node_modules'],
        },
        plugins: [
            new CleanWebpackPlugin([config.publicDir], {verbose: true, root: themeDir}),
    
            BuildMetaPlugin,
    
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
                    || 'http://' + path.basename(themeDir) + '.localhost/',
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
                                const bs = req('browser-sync').get('bs-webpack-plugin');
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
                        context: path.resolve(themeDir, 'src/images')
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
    };
