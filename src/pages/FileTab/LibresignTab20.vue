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
	<AppSidebarTab
		:id="id"
		:icon="icon"
		:name="name">
		<div v-show="showButtons" class="buttons">
			<button class="primary" :disabled="!hasSign" @click="option('sign')">
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
				<button class="return-button" @click="option('sign')">
					{{ t('libresign', 'Return') }}
				</button>
			</template>
		</Sign>

		<Request v-show="requestShow"
			ref="request"
			:fileinfo="info"
			@request:signatures="requestSignatures">
			<template slot="actions">
				<button class="return-button" @click="option('request')">
					{{ t('libresign', 'Return') }}
				</button>
			</template>
		</Request>
	</AppSidebarTab>
</template>

<script>
import AppSidebarTab from '@nextcloud/vue/dist/Components/AppSidebarTab'
import { showError, showSuccess } from '@nextcloud/dialogs'
import axios from '@nextcloud/axios'
import { generateUrl } from '@nextcloud/router'
import Sign from '@/Components/Sign'
import Request from '@/Components/Request'

export default {
	name: 'LibresignTab20',

	components: {
		AppSidebarTab,
		Sign,
		Request,
	},

	mixins: [],

	props: {
		fileInfo: {
			type: Object,
			default: () => {},
			required: true,
		},
	},

	data() {
		return {
			icon: 'icon-rename',
			name: t('libresign', 'LibreSign'),
			showButtons: true,
			signShow: false,
			requestShow: false,
			disabledSign: false,
			info: this.fileInfo,
			canRequestSign: false,
			canSign: false,
		}
	},

	computed: {
		id() {
			return 'libresignTab'
		},
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
		this.getInfo()
	},

	methods: {
		async getInfo() {
			try {
				const response = await axios.get(generateUrl(`/apps/libresign/api/0.1/file/validate/file_id/${this.fileInfo.id}`))
				this.canRequestSign = response.data.settings.canRequestSign
				this.canSign = response.data.settings.canSign

			} catch (err) {
				this.canRequestSign = err.response.data.settings.canRequestSign
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
				console.error(err.response)
				return showError(err.response.data.errors[0])
			}
		},

		async requestSignatures(users) {
			try {
				const response = await axios.post(generateUrl('/apps/libresign/api/0.1/sign/register'), {
					file: {
						fileId: this.info.id,
					},
					name: this.info.name.split('.pdf')[0],
					users,
				})
				this.option('request')
				this.clearRequestList()
				return showSuccess(response.data.message)
			} catch (err) {
				return showError(err.response.data.errors[0])
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
<style lang="scss" scoped>
.buttons{
	display: flex;
	flex-direction: column;
	width: 100%;
	button{
		width: 100%
	}
}

.return-button{
	width: 80%;
	align-self: center;
	position:absolute;
	bottom: 10px;
}
</style>
