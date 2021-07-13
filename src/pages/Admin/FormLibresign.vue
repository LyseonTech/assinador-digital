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
	<div id="formLibresign" class="form-libresign">
		<div class="form-group">
			<label for="password">{{ t('libresign', 'Subscription password.') }}</label>
			<input
				id="signPassword"
				v-model="signature.signPassword"
				type="text"
				:disabled="updating">
		</div>
		<input
			type="button"
			class="primary"
			:value="t('libresign', 'Generate Subscription.')"
			:disabled="updating || !savePossible"
			@click="saveSignature">
		<Modal
			v-if="modal"
			dark=""
			@close="closeModal">
			<div class="modal_content">
				{{ t('libresign','Subscription generated and available at {path}!', { path: signature.path }) }}
			</div>
		</Modal>
	</div>
</template>

<script>
import '@nextcloud/dialogs/styles/toast.scss'
import { generateUrl } from '@nextcloud/router'
import axios from '@nextcloud/axios'
import { showError } from '@nextcloud/dialogs'
import Modal from '@nextcloud/vue/dist/Components/Modal'
import { translate as t } from '@nextcloud/l10n'
export default {
	name: 'FormLibresign',
	components: {
		Modal,
	},
	data() {
		return {
			signature: {
				path: '',
				signPassword: '',
			},
			updating: false,
			loading: true,
			modal: false,
		}
	},
	computed: {
		savePossible() {
			return (this.signature.signPassword !== '')
		},
	},
	async mounted() {
		this.loading = false
		this.$refs.hosts.focus()
	},

	methods: {
		async saveSignature() {
			this.updating = true
			try {
				const response = await axios.post(
					generateUrl('/apps/libresign/api/0.1/account/signature'),
					this.signature
				)
				if (!response.data || !response.data.signature) {
					throw new Error(response.data)
				}
				this.signature.path = response.data.signature
				this.showModal()
			} catch (e) {
				console.error(e)
				showError(t('libresign', 'Could not create signature.'))
			}
			this.updating = false
		},
		showModal() {
			this.modal = true
		},
		closeModal() {
			this.modal = false
			this.signature = {
				signPassword: '',
			}
		},
	},
}
</script>
<style scoped>
#formLibresign{
	width: 60%;
	text-align: left;
	margin: 20px;
}

.form-group > input[type='password'] {
	width: 100%;
}

.modal_content {
	text-align: center;
	margin: 40px;
}
</style>
