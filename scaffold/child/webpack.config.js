/**
 * Build config from the roadmap-starter parent theme (set ROADMAP_STARTER_DIR if it isn't a sibling).
 */
const path = require( 'path' );
const parentDir = process.env.ROADMAP_STARTER_DIR || path.resolve( __dirname, '../roadmap-starter' );

module.exports = require( path.join( parentDir, 'build/webpack.factory.js' ) )( { themeDir: __dirname, parentDir } );
