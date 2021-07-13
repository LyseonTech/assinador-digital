import { loadState } from '@nextcloud/initial-state'

export const getSettings = () => {
	return JSON.parse(loadState('libresign', 'config'))
}
