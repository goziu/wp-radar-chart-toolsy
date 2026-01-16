const defaultConfig = require('@wordpress/scripts/config/webpack.config');
const path = require('path');

// デフォルトのエントリーを取得
const defaultEntry = typeof defaultConfig.entry === 'function' 
    ? defaultConfig.entry() 
    : defaultConfig.entry;

module.exports = {
    ...defaultConfig,
    entry: {
        ...defaultEntry,
        'frontend': path.resolve(__dirname, 'src/frontend.js'),
    },
};
