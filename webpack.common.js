const { merge } = require('webpack-merge')
const path = require('path')
const webpackVueConfig = require('@nextcloud/webpack-vue-config')

const config = {
	mode: process.env.NODE_ENV,
	devtool: process.env.NODE_ENV === 'production' ? '#cheap-source-map' : '#source-map',
	entry: {
		tab: path.resolve(path.join('src', 'tab.js')),
		tab20: path.resolve(path.join('src', 'tab-20.js')),
		settings: path.resolve(path.join('src', 'settings.js')),
		external: path.resolve(path.join('src', 'external.js')),
		validation: path.resolve(path.join('src', 'validation.js')),
	},
}

webpackVueConfig.resolve.alias = {
	'@': path.resolve(path.join(__dirname, 'src')),
}

module.exports = merge(config, webpackVueConfig)
