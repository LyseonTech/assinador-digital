<template>
	<Content app-name="libresign">
		<form @submit="e => e.preventDefault()">
			<header>
				<h1>{{ t('libresign', 'Password Creation') }}</h1>
				<p>{{ t('libresign', 'For security reasons, you must create a password to sign the documents. Enter your new password in the field below.') }}</p>
			</header>
			<div class="container">
				<div class="input-group">
					<label for="password">{{ t('libresign', 'Enter a password') }}</label>
					<Input v-model="password" type="password" />
				</div>
				<button :class="hasLoading? 'btn-load loading primary btn-confirm': 'primary btn-confirm'"
					@click="checkPasswordForConfirm">
					{{ t('libresign', 'Confirm') }}
				</button>
			</div>
		</form>
	</Content>
</template>

<script>
import confirmPassword from '@nextcloud/password-confirmation'
import Content from '@nextcloud/vue/dist/Components/Content'
import axios from '@nextcloud/axios'
import { generateUrl } from '@nextcloud/router'
import Input from '../Components/Input/Input'
import { showError, showSuccess } from '@nextcloud/dialogs'

export default {
	name: 'CreatePassword',
	components: {
		Content,
		Input,
	},
	data() {
		return {
			modal: false,
			hasLoading: false,
			password: '',
			hasPfx: false,
		}
	},
	methods: {
		checkPasswordForConfirm() {
			confirmPassword().then(() => {
				this.send()
			})
		},
		async send() {
			this.hasLoading = true
			try {
				await axios.post(generateUrl('/apps/libresign/api/0.1/account/signature'), {
					signPassword: this.password,
				})
				showSuccess(t('libresign', 'New password to sign documents has been created'))
				this.hasLoading = false
				if (this.$store) {
					this.$store.commit('setHasPfx', true)
				}
				this.clear()
				this.$emit('close', true)
				this.$emit('changePfx', true)
			} catch (err) {
				showError(t('libresign', 'Error creating new password, please contact the administrator'))
				this.hasLoading = false
				this.$emit('changePfx', false)
			}
		},
		clear() {
			this.password = ''
		},
	},
}
</script>
<style lang="scss" scoped>
	form{
		display: flex;
		flex-direction: column;
		width: 100%;
		max-width: 100%;
		justify-content: center;
		align-items: center;
		text-align: center;
		header{
			margin-bottom: 2.5rem;
		}
		h1{
			font-size: 45px;
			margin-bottom: 1rem;
		}
		p{
			font-size: 15px;
		}
	}

	.container {
		display: flex;
		flex-direction: column;
		justify-content: center;
		align-items: center;

		width: 100%;
		max-width: 420px;

		text-align: start;
	}

	.btn-confirm{
		width: 100%;
		max-width: 280px;
	}

	.btn-load{
		background-color: transparent !important;
		font-size: 0;
		pointer-events: none;
		cursor: not-allowed;
		margin-top: 10px;
		border: none;

	}

	.input-item{
		display: flex;
		flex-direction: row;
		border: 1px solid #cecece;
		border-radius: 10px;
		width: 100%;
		max-width: 380px;
		label{
			padding: 0 20px;
			border-radius: 10px 0 0 10px;
			background-color: #cecece;
		}
		input{
			border: none;
		}
		&:focus-within{
			border: thin solid #0082c9;
			box-shadow: inset 0 0 1em rgba(0, 130, 201, .5);
		}
	}

	.input-group{
		display: flex;
		flex-direction: column;
		margin: 10px;
		width: 100%;
		label:first-child{
			opacity: 0.7;
		}
		input{
			width: 100%;
			max-width: 370px;
		}
	}
</style>
