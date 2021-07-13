<template>
	<div class="container-sign">
		<div class="avatar-local">
			<Avatar id="avatar" :user="userName" />
			<span>{{ userName }}</span>
		</div>

		<InputAction
			ref="input"
			class="input"
			:type="'password'"
			:disabled="disabledButton"
			:loading="hasLoading"
			@submit="sign" />
		<a class="forgot-sign" @click="handleModal(true)">
			{{ messageForgot }}
		</a>
		<EmptyContent class="emp-content">
			<template #desc>
				<p v-if="havePfx">
					{{ t('libresign', 'Enter your password to sign this document') }}
				</p>
				<p v-else>
					{{
						t('libresign',
							'You need to create a password to sign this document. Click "Create password to sign document" and create a password.')
					}}
				</p>
			</template>
			<template #icon>
				<img v-if="havePfx" :src="icon">
				<div v-else class="icon icon-rename" />
			</template>
		</EmptyContent>
		<slot name="actions" />
		<Modal v-if="modal" size="large" @close="handleModal(false)">
			<ResetPassword v-if="havePfx" @close="handleModal(false)" />
			<CreatePassword v-if="!havePfx" @changePfx="changePfx" @close="handleModal(false)" />
		</Modal>
	</div>
</template>

<script>
// Utils
import { getCurrentUser } from '@nextcloud/auth'

// Components
import Modal from '@nextcloud/vue/dist/Components/Modal'
import Avatar from '@nextcloud/vue/dist/Components/Avatar'
import EmptyContent from '@nextcloud/vue/dist/Components/EmptyContent'
import ResetPassword from '@/Components/Password/Reset/Reset.vue'
import CreatePassword from '@/Components/Password/Create/Create.vue'
import InputAction from '@/Components/InputAction'
import Icon from '@/assets/images/signed-icon.svg'

export default {
	name: 'Sign',
	components: {
		Avatar,
		InputAction,
		EmptyContent,
		Modal,
		ResetPassword,
		CreatePassword,
	},
	props: {
		disabled: {
			type: Boolean,
			require: false,
		},
		hasLoading: {
			type: Boolean,
			required: false,
			default: false,
		},
		pfx: {
			type: Boolean,
			required: false,
			default: false,
		},
	},
	data() {
		return {
			icon: Icon,
			modal: false,
		}
	},
	computed: {
		userName() {
			return getCurrentUser().uid
		},
		havePfx() {
			return this.pfx ? this.pfx : false
		},
		messageForgot() {
			return this.havePfx ? t('libresign', 'Forgot your password?') : t('libresign', 'Create password to sign document')
		},
		disabledButton() {
			if (this.havePfx) {
				if (this.hasLoading) {
					return true
				}
				return false
			}
			return true
		},
	},
	methods: {
		clearInput() {
			this.$refs.input.clearInput()
		},
		sign(param) {
			this.clearInput()
			this.$emit('sign:document', param)
		},
		changePfx(value) {
			this.pfx = value
			this.emit('change-pfx', true)
		},
		handleModal(state) {
			this.modal = state
		},
	},
}
</script>

<style lang="scss">
@import './styles';
</style>
