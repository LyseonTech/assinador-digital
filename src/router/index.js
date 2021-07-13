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
import Router from 'vue-router'
import { generateUrl } from '@nextcloud/router'
import routes from './router'
import store from '../store'
import { getSettings } from '@/services/initialState'

Vue.use(Router)

const settings = getSettings()

const router = new Router({
	mode: 'history',
	base: generateUrl('/apps/libresign/', ''),
	linkActiveClass: 'active',
	routes,
})

router.beforeEach((to, from, next) => {
	if (to.query.redirect === 'CreatePassword') {
		next({ name: 'CreatePassword' })
	}

	if (settings) {
		if (settings.sign) {
			store.dispatch('file/newFileData', settings.sign)
			store.commit('setPdfData', settings.sign)
		}
		if (settings.user) {
			store.commit('setUser', settings.user)
		}
		if (settings.errors) {
			store.commit('setError', settings.errors)
		}
	}
	next()
})

export default router
