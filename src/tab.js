/**
 * @copyright Copyright (c) 2021 Lyseon Techh <contato@lt.coop.br>
 *
 * @author Lyseon Tech <contato@lt.coop.br>
 * @author Vinicios Gomes <viniciosgomesviana@gmail.com>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */

import Vue from 'vue'
import Vuex from 'vuex'
import { translate as t, translatePlural as n } from '@nextcloud/l10n'
import LibresignTab from '@/pages/FileTab/LibresignTab'
import VTooltip from '@nextcloud/vue/dist/Directives/Tooltip'

import '@nextcloud/dialogs/styles/toast.scss'

Vue.prototype.t = t
Vue.prototype.n = n
Vue.use(Vuex)
Vue.directive('Tooltip', VTooltip)
if (!window.OCA.Libresign) {
	window.OCA.Libresign = {}
}

const isEnabled = function(fileInfo) {
	if (fileInfo && fileInfo.isDirectory()) {
		return false
	}

	window.OCA.Libresign.fileInfo = fileInfo

	const mimetype = fileInfo.get('mimetype') || ''
	if (mimetype === 'application/pdf') {
		return true
	}

	return false
}

const View = Vue.extend(LibresignTab)
let TabInstance = null

window.addEventListener('DOMContentLoaded', () => {
	if (OCA.Files && OCA.Files.Sidebar) {
		OCA.Files.Sidebar.registerTab(new OCA.Files.Sidebar.Tab({
			id: 'libresign',
			name: t('libresign', 'LibreSign'),
			icon: 'icon-rename',
			enabled: isEnabled,

			async mount(el, fileInfo, context) {
				if (TabInstance) {
					TabInstance.$destroy()
				}
				TabInstance = new View({
					// Better integration with vue parent component
					parent: context,
				})
				// Only mount after we hahve all theh info we need
				await TabInstance.update(fileInfo)
				TabInstance.$mount(el)
			},
			update(fileInfo) {
				TabInstance.update(fileInfo)
			},
			destroy() {
				TabInstance.$destroy()
				TabInstance = null
			},
		}))
	}
})
