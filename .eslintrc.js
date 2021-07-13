const path = require('path')
const paht = require('path')
module.exports = {
	extends: [
		'@nextcloud',
	],
	"rules": {
        "node/no-extraneous-import": ["error", {
            "allowModules": [
				'@nextcloud/auth',
				'@nextcloud/initial-state'
			],
            "resolvePaths": [],
            "tryExtensions": []
        }],
		"node/no-missing-import": "off"
    },
	settings: {
		'import/resolver': {
			alias: {
				map: [
					['@', './src'],
					["~", './src']
				],
				extensions: ['.js', '.vue', '.json']
			}
		}
	}
}
