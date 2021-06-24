<!--
- @copyright Copyright (c) 2021 Lyseon Tech <contato@lt.coop.br>
-
- @author Lyseon Tech <contato@lt.coop.br>
- @author Vinicios Gomes <viniciusgomesvaian@gmail.com>
-
- @license GNU AGPL version 3 or any later version
-
- This program is free software: you can redistribute it and/or modify
- it under the terms of the GNU Affero General Public License as
- published by the Free Software Foundation, either version 3 of the
- License, or (at your option) any later version.
-
- This program is distributed in the hope that it will be useful,
- but WITHOUT ANY WARRANTY; without even the implied warranty of
- MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
- GNU Affero General Public License for more details.
-
- You should have received a copy of the GNU Affero General Public License
- along with this program.  If not, see <http://www.gnu.org/licenses/>.
-
-->

<template>
	<AppSidebar :class="{'app-sidebar--without-background lb-ls-root' : 'lb-ls-root'}" title="LibreSign" :header="false">
		<AppSidebarTab
			id="libresign-tab"
			icon="icon-rename"
			:name="t('libresign', 'LibreSign')">
			<Tooltip v-show="tooltip.show"
				:message="tooltip.message"
				:icon="tooltip.icon"
				:type="tooltip.type"
				:time="tooltip.time" />

			<div v-show="showButtons" class="lb-ls-buttons">
				<button class="primary"
					:disabled="!hasSign"
					@click="option('sign')">
					{{ t('libresign', 'Sign') }}
				</button>
				<button
					:disabled="!canRequestSign"
					class="primary"
					@click="option('request')">
					{{ t('libresign', 'Request subscription') }}
				</button>
				<button v-if="hasSignatures" @click="option('verify')">
					{{ t('libresign', 'Verify signatures') }}
				</button>
			</div>

			<Sign v-show="signShow"
				ref="sign"
				:disabled="disabledSign"
				@sign:document="signDocument">
				<template slot="actions">
					<button class="lb-ls-return-button" @click="option('sign')">
						{{ t('libresign', 'Return') }}
					</button>
				</template>
			</Sign>

			<Request v-show="requestShow"
				ref="request"
				:fileinfo="info"
				@request:signatures="requestSignatures">
				<template slot="actions">
					<button class="lb-ls-return-button" @click="option('request')">
						{{ t('libresign', 'Return') }}
					</button>
				</template>
			</Request>
		</AppSidebarTab>
	</AppSidebar>
</template>

<script>
import AppSidebar from '@nextcloud/vue/dist/Components/AppSidebar'
import AppSidebarTab from '@nextcloud/vue/dist/Components/AppSidebarTab'
import { showError, showSuccess } from '@nextcloud/dialogs'
import axios from '@nextcloud/axios'
import { generateUrl } from '@nextcloud/router'
import Sign from '../Components/Sign'
import Request from '../Components/Request'
import Tooltip from '../Components/TooltipMessage/Tooltip'

export default {
	name: 'LibresignTab',

	components: {
		AppSidebar,
		AppSidebarTab,
		Sign,
		Tooltip,
		Request,
	},

	mixins: [],

	data() {
		return {
			showButtons: true,
			signShow: false,
			requestShow: false,
			disabledSign: false,
			canRequestSign: false,
			canSign: false,
			fileInfo: null,
			tooltip: {
				show: false,
				message: '',
				icon: '',
				type: '',
			},
			errorMessage: '',
		}
	},

	computed: {
		activeTab() {
			return this.$parent.activeTab
		},
		hasSignatures() {
			return !!(this.canRequestSign && this.signatures)
		},
		hasSign() {
			return !!this.canSign
		},
	},

	created() {
		this.fileInfo = window.OCA.Libresign.fileInfo
		this.getInfo()
	},

	methods: {
		/**
		 * Update current fileInfo and fetch new data
		 * @param {Object} fileInfo the current file FileInfo
		 */
		async update(fileInfo) {
			this.fileInfo = fileInfo
			this.resetState()
		},

		/**
		 * Reset the current view to its default state
		 */
		resetState() {
			this.showButtons = true
			this.signShow = false
		},

		async getInfo() {
			try {
				const response = await axios.get(generateUrl(`/apps/libresign/api/0.1/file/validate/file_id/${this.fileInfo.id}`))
				this.canRequestSign = response.data.settings.canRequestSign
				this.canSign = response.data.settings.canSign

				if (response.data.settings.canSign === false) {
					this.tooltip = {
						message: t('libresign', 'There are no subscription requests for this document'),
						icon: 'icon-details-white',
						type: 'info',
						show: true,
					}
				}

				if (response.data.settings.canRequestSign === false) {
					this.tooltip = {
						message: t('libresign', 'You cannot request signature for this document, please contact your administrator'),
						icon: 'icon-details',
						type: 'info',
						show: true,
					}
				}

				console.info(response)
			} catch (err) {
				this.tooltip = {
					type: 'error',
					message: t('libresign', 'Sorry, but you are not authorized to use LibreSign, please contact the administrator'),
					icon: 'icon-error-white',
					show: true,
				}

				this.canRequestSign = err.response.data.settings.canRequestSign
				this.canSign = err.response.data.settings.canSign
			}
		},

		async signDocument(param) {
			try {
				const response = await axios.post(generateUrl(`apps/libresign/api/0.1/sign/file_id/${this.fileInfo.id}`), {
					password: param,
				})
				this.option('sign')
				return showSuccess(response.data.message)
			} catch (err) {
				if (err.response.data.action === 400) {
					window.location.href = generateUrl('/apps/libresign/reset-password?redirect=CreatePassword')
				}
				return showError(err.response.data.errors[0])
			}
		},

		async requestSignatures(users, fileInfo) {
			try {
				const response = await axios.post(generateUrl('/apps/libresign/api/0.1/webhook/register'), {
					file: {
						fileId: this.fileInfo.id,
					},
					name: this.fileInfo.name.split('.pdf')[0],
					users,
				})
				this.option('request')
				this.clearRequestList()
				return showSuccess(response.data.message)
			} catch (err) {
				if (err.response.data.errors) {
					return showError(err.response.data.errors[0])
				}
				return showError(err.response.data.message)
			}
		},

		option(value) {
			if (value === 'sign') {
				this.showButtons = !this.showButtons
				this.signShow = !this.signShow
			} else if (value === 'request') {
				this.showButtons = !this.showButtons
				this.requestShow = !this.requestShow
			}
		},
		clearSiginPassword() {
			this.$refs.sign.clearInput()
		},
		clearRequestList() {
			this.$refs.request.clearList()
		},
	},
}
</script>
<style lang="scss">
.lb-ls-root{
	width: 100% !important;

	.app-sidebar-header {
		display: none !important;
	}
}

.lb-ls-buttons{
	display: flex;
	flex-direction: column;
	width: 100%;

	button{
		width: 100%
	}
}

.lb-ls-return-button{
	width: 80%;
	align-self: center;
	position:absolute;
	bottom: 10px;
}
</style>
