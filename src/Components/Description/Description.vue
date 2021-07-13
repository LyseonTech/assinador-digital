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
	<div class="container-desc">
		<header>
			<img :src="image">
			<p>{{ t('libresign', pdfName) }}</p>
			<span>{{ t('libresign', pdfDescription) }}</span>
		</header>
		<div id="body">
			<form @submit="(e) => e.preventDefault()">
				<div v-show="signaturePath" class="form-group">
					<label for="password">{{
						t('libresign', 'Subscription password.')
					}}</label>
					<div class="form-ib-group">
						<input id="password"
							v-model="password"
							v-tooltip.left="{
								content: t('libresign', 'Create your password for signing PDF'),
								trigger: 'false',
								show: !havePfx
							}"
							:disabled="disableInput"
							type="password">
						<a :class="havePfx ? 'forgot' : 'create'" @click="handleModal(true)">
							{{ havePfx ? t('libresign', 'Forgot your password?') : t('libresign', 'Create password to sign document') }}
						</a>
						<button
							type="button"
							:value="buttonValue"
							:class="!updating ? 'primary' : 'primary loading'"
							:disabled="disableBtn"
							@click="sign">
							{{ t('libresign', 'Sign the document.') }}
						</button>
					</div>
				</div>
			</form>
			<Modal v-if="modal"
				size="normal"
				@close="handleModal(false)">
				<ResetPassword v-if="havePfx" class="modal-dialog" @close="handleModal(false)" />
				<CreatePassword v-if="!havePfx"
					@changePfx="changePfx"
					@close="handleModal(false)" />
			</Modal>
		</div>
	</div>
</template>

<script>
// Components
import Modal from '@nextcloud/vue/dist/Components/Modal'
import ResetPassword from '@/Components/Password/Reset/Reset.vue'
import CreatePassword from '@/Components/Password/Create/Create.vue'
import Image from '@/assets/images/application-pdf.png'
import { signInDocumentUuid } from '@/services/api/file'
import { getMe } from '@/services/api/user'

export default {
	name: 'Description',
	components: {
		Modal,
		ResetPassword,
		CreatePassword,
	},
	props: {
		pdfName: {
			type: String,
			required: true,
			default: 'PDF Name',
		},
		pdfDescription: {
			type: String,
			required: false,
			default: 'Description',
		},
		uuid: {
			type: String,
			required: true,
			default: '',
		},
	},

	data() {
		return {
			image: Image,
			updating: false,
			disableButton: false,
			signaturePath: '2',
			password: '',
			asign: true,
			buttonValue: t('libresign', 'Sign the document.'),
			modal: false,
			havePfx: false,
		}
	},

	computed: {
		hasSavePossible() {
			return !!this.password
		},
		disableInput() {
			return !this.havePfx || this.updating
		},
		disableBtn() {
			if (!this.havePfx) {
				return true
			}
			if (this.disableButton === true) {
				return true
			}
			return false
		},
	},

	watch: {
		havePfx(oldVal, newVal) {

		},
	},
	created() {
		this.getMe()
	},

	methods: {
		async sign() {
			this.updating = true
			this.disableButton = true

			try {
				const response = await signInDocumentUuid(this.password, this.uuid)
				console.info('paage: ', response)

				if (response.data.action === 350) {
					this.$router.push({ name: 'DefaultPageSuccess', uuid: this.uuid })
				}

				if (response.data.action === 400 && response.data.errors) {
					this.havePfx = false
					this.disableButton = true
				}

				this.updating = false
				this.disableButton = true
			} catch (err) {
				this.updating = false
				this.disableButton = true

			}
		},
		changePfx(value) {
			this.havePfx = value
			if (value === false) {
				this.disableButton = true
			}
		},
		async getMe() {
			const response = await getMe()
			this.havePfx = response.data.settings.hasSignatureFile
		},
		handleModal(status) {
			this.modal = status
		},
	},
}
</script>

<style lang="scss">
@import './styles';
</style>
