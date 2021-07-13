/**
 * @copyright Copyright (c) 2021 Lyseon Techh <contato@lt.coop.br>
 *
 * @author Lyseon Tech <contato@lt.coop.br>
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

import { translate as t } from '@nextcloud/l10n'
import SelectAction from '@/helpers/SelectAction'
import store from '@/store'

store.dispatch('user/getSettings')

const routes = [
	{
		path: '#timeline/files',
		component: () => import('@/pages/Timeline/Timeline'),
		name: 'signFiles',
	}, {
		path: '#request',
		component: () => import('@/pages/Request/Request'),
		name: 'requestFiles',
	}, {
		path: '/sign/:uuid',
		redirect: { name: SelectAction(store.getters['user/getAction']) },
	}, {
		path: '/sign/:uuid#sign',
		component: () => import('@/pages/Sign/SignPDF'),
		props: (route) => ({ uuid: route.params.uuid, redirect: false }),
		name: 'SignPDF',
	}, {
		path: '/sign/:uuid#create',
		component: () => import('@/pages/User/Create'),
		name: 'CreateUser',
		props: (route) => ({
			messageToast: t('libresign', 'You need to create an account to sign this file.'),
		}),
	}, {
		path: '/sign/:uuid#error',
		component: () => import('@/pages/layout/Error'),
		name: 'DefaultPageError',
	}, {
		path: '/sign/:uuid#success',
		component: () => import('@/pages/layout/Success'),
		name: 'DefaultPageSuccess',
		props: (route) => ({ uuid: route.params.uuid }),
	}, {
		path: '#validation',
		component: () => import('@/pages/Validation/Validation'),
		name: 'validation',
		children: [
			{
				path: ':uuid',
				component: () => import('@/pages/Validation/Validation'),
				name: 'validationFile',
				props: (route) => ({
					uuid: route.params.uuid,
				}),
			},
		],
	}, {
		path: '#account',
		component: () => import('@/pages/User/Account/Account.vue'),
		name: 'Account',
		children: [
			{
				path: 'reset-password',
				component: () => import('@/Components/Password/Reset'),
				name: 'ResetPassword',
			}, {
				path: 'create-password',
				component: () => import('@/Components/Password/Create'),
				name: 'CreatePassword',

			},
		],
	},
]

export default routes
