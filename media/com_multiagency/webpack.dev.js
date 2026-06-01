const common = require("./webpack.common");
const merge  = require("webpack-merge");

module.exports = merge(common, {
    mode: "development",
    output: {
        filename: '[name].js',
        libraryTarget: 'umd',
        library: 'multiAgency'
    }
});
