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
	<AppSidebar class="app-sidebar--without-background lb-ls-root" title="LibreSign" :header="false">
		<AppSidebarTab
			id="libresign-tab"
			icon="icon-rename"
			:name="t('libresign', 'LibreSign')">
			<div v-show="showButtons" class="lb-ls-buttons">
				<button v-if="hasSign" class="primary" @click="option('sign')">
					{{ t('libresign', 'Sign') }}
				</button>
				<button
					v-if="canRequestSign"
					class="primary"
					@click="option('request')">
					{{ t('libresign', 'Request') }}
				</button>
				<button v-if="haveRequest" @click="option('signatures')">
					{{ t('libresign', 'Status') }}
				</button>
			</div>

			<Sign v-show="signShow"
				ref="sign"
				:disabled="disabledSign"
				:pfx="hasPfx"
				:has-loading="loadingInput"
				@sign:document="signDocument">
				<template slot="actions">
					<button class="lb-ls-return-button" @click="option('sign')">
						{{ t('libresign', 'Return') }}
					</button>
				</template>
			</Sign>

			<Request v-show="requestShow"
				ref="request"
				:fileinfo="fileInfo"
				@request:signatures="requestSignatures">
				<template slot="actions">
					<button class="lb-ls-return-button" @click="option('request')">
						{{ t('libresign', 'Return') }}
					</button>
				</template>
			</Request>

			<div v-if="signaturesShow" id="signers" class="container-signers">
				<div class="content-signers">
					<ul>
						<li v-for="signer in signers" :key="signer.uid">
							<div class="signer-content">
								<div class="container-dot">
									<div class="icon-signer icon-user" />
									<span>
										{{ getName(signer) }}
									</span>
								</div>
								<div class="container-dot">
									<div :class="'dot ' + (signer.signed === null ? 'pending' : 'signed')" />
									<span>
										{{ signer.signed === null ? t('libresign', 'Pending') : t('libresign','Signed') }}
									</span>
								</div>
								<div v-if="showDivButtons(signer)" class="container-dot container-btn">
									<button v-if="showSignButton(signer)" class="primary" @click="changeToSign">
										{{ t('libresign', 'Sign') }}
									</button>
									<button v-if="showNotifyButton(signer)" class="primary" @click="resendEmail(signer.email)">
										{{ t('libresign', 'Send reminder') }}
									</button>
									<Actions v-if="showDelete(signer)">
										<ActionButton icon="icon-delete" @click="deleteUserRequest(signer)" />
									</Actions>
								</div>
							</div>
						</li>
					</ul>
					<button class="lb-ls-return-button" @click="option('signatures')">
						{{ t('libresign', 'Return') }}
					</button>
				</div>
			</div>
		</AppSidebarTab>
	</AppSidebar>
</template>

<script>
// Services
import { getMe } from '@/services/api/user'
import { getInfo, signInDocument } from '@/services/api/file'
import { deleteSignatureRequest, request, sendNotification } from '@/services/api/signatures'

// Toast
import { showError, showSuccess } from '@nextcloud/dialogs'

// Components
import AppSidebar from '@nextcloud/vue/dist/Components/AppSidebar'
import Actions from '@nextcloud/vue/dist/Components/Actions'
import ActionButton from '@nextcloud/vue/dist/Components/ActionButton'
import AppSidebarTab from '@nextcloud/vue/dist/Components/AppSidebarTab'
import Sign from '@/Components/Sign'
import Request from '@/Components/Request'

export default {
	name: 'LibresignTab',

	components: {
		AppSidebar,
		Actions,
		ActionButton,
		AppSidebarTab,
		Sign,
		Request,
	},

	mixins: [],

	data() {
		return {
			showButtons: true,
			signShow: false,
			requestShow: false,
			signaturesShow: false,
			disabledSign: false,
			signers: {},
			loadingInput: false,
			canRequestSign: false,
			haveRequest: false,
			canSign: false,
			fileInfo: null,
			hasPfx: false,
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

	watch: {
		fileInfo() {
			this.getInfo()
			this.getMe()
			this.signShow = false
			this.requestShow = false
			this.signaturesShow = false
		},

		signers() {
			if (this.signers.length <= 0) {
				this.haveRequest = false
			}
		},
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

		showSignButton(user) {
			if (user.me) {
				if (user.signed) {
					return false
				}
				return true
			}
		},

		showNotifyButton(user) {
			if (!user.me) {
				if (user.signed) {
					return false
				}
				return true
			}
			return false
		},
		showDelete(user) {
			if (user.signed) {
				return false
			}
			return true
		},
		showDivButtons(user) {
			return !!(this.showSignButton(user) || this.showNotifyButton(user) || this.showDelete(user))
		},
		/**
		 * Reset the current view to its default state
		 */
		resetState() {
			this.showButtons = true
			this.signShow = false
		},

		getName(user) {
			if (user.displayName) {
				return user.displayName
			} else if (user.fullName) {
				return user.fullName
			} else if (user.email) {
				return user.email
			}
			return t('libresign', 'Account not exist')
		},
		async getMe() {
			const response = await getMe()
			this.hasPfx = response.data.settings.hasSignatureFile
			this.canRequestSign = response.data.settings.canRequestSign
		},

		async getInfo() {
			try {
				const response = await getInfo(this.fileInfo.id)
				this.canSign = response.data.settings.canSign
				if (response.data.signers) {
					this.haveRequest = true
					this.canRequestSign = true
					this.signers = response.data.signers
				} else {
					this.signers = []
				}
			} catch (err) {
				this.canSign = false
			}
		},

		changeToSign() {
			this.option('signatures')
			this.option('sign')
		},

		async signDocument(param) {
			try {
				this.loadingInput = true
				this.disabledSign = true
				await signInDocument(param, this.fileInfo.id)
				this.getInfo()
				this.option('sign')
				this.option('signatures')
				this.canSign = false
				this.loadingInput = false
			} catch (err) {
				this.disabledSign = false
				this.loadingInput = false
			}
		},

		async deleteUserRequest(user) {
			const result = confirm(t('libresign', 'Are you sure you want to exclude user {email} from the request?', { email: user.email }))
			if (result === true) {
				try {
					await deleteSignatureRequest(this.fileInfo.id, user.signatureId)
					if (this.signers.length <= 0) {
						this.option('signatures')
					}

					this.getInfo()
				} catch (err) {
					showError(err)
				}
			}
		},

		async resendEmail(email) {
			try {
				const response = await sendNotification(email, this.fileInfo.id)

				showSuccess(response.data.message)
			} catch (err) {
				if (err.response.data.messages) {
					err.response.data.messages.forEach(error => {
						showError(error.message)
					})
				} else {
					showError(t('libresign', 'There was an error completing your request'))
				}

			}
		},

		async requestSignatures(users) {
			try {
				const update = this.haveRequest ? 'update' : 'new'

				await request(
					users,
					this.fileInfo.id,
					this.fileInfo.name.split('.pdf')[0],
					update,
				)

				this.option('request')
				this.clearRequestList()
				this.getInfo()
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
			} else if (value === 'signatures') {
				this.showButtons = !this.showButtons
				this.signaturesShow = !this.signaturesShow
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
	height: calc(100vh - 223px) !important;
	min-width: 289px !important;

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

.container-signers{
	display: flex;
	width: 100%;

	.content-signers{
		width: 100%;
		display: flex;
		flex-direction: column;
		justify-content: center;
		align-items: center;

		ul {
			display: flex;
			width: 100%;
			overflow-x: scroll;
			border: 1px solid #cecece;
			border-radius: 10px;
			padding: 10px;
			height: calc(100vh - 300px);
			flex-direction: column;
			padding-bottom: 50px;

			li{
				padding: 10px;
				border: 1px solid #cecece;
				border-radius: 10px;
				display: flex;
				flex-direction: column;
				margin-bottom: 12px;

				.signer-user{
					display: flex;
					flex-direction: row;
					margin: 3px;
				}

				.container-dot{
					margin: 3px;
					display: flex;
					flex-direction: row;
					align-items: center;
					justify-content: flex-start;
					width: 100%;
					margin-bottom: 6px;
					min-height: 26px;
					cursor: inherit;

					.dot{
						width: 10px;
						height: 10px;
						border-radius: 50%;
						margin-right: 10px;
						margin-left: 3px;
						cursor: inherit;
					}

					.signed{
						background: #008000;
					}

					.pending{
						background: #d85a0b;
					}

					span{
						font-size: 14px;
						font-weight: normal;
						text-align: center;
						color: rgba(0,0,0,.7);
						cursor: inherit;
						margin-left: 5px;
					}

					button{
						min-width: 130px;
					}
				}

				.container-btn{
					display: flex;
					justify-content: space-between;
					align-items: center;
				}

			}
		}
	}
}
</style>
