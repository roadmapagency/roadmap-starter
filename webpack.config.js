/**
 * Build config — shared with child themes via build/webpack.factory.js.
 */
module.exports = require( './build/webpack.factory.js' )( { themeDir: __dirname } );
