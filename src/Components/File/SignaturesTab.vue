<template>
	<div class="container-signatures-tab">
		<ul>
			<li v-for="sign in signers" :key="sign.uid">
				<div class="user-name">
					<div class="icon-sign icon-user" />
					<span class="name">
						{{ sign.displayName ? sign.displayName : t('libresign', 'Account not exist') }}
					</span>
				</div>
				<div class="content-status">
					<div class="container-dot">
						<div :class="'dot ' + hasStatus(sign)" />
						<span class="statusDot">{{ uppercaseString(hasStatus(sign)) }}</span>
					</div>
					<div class="container-dot">
						<div class="icon icon-calendar-dark" />
						<span v-if="sign.sign_date">{{ timestampsToDate(sign.sign_date) }}</span>
					</div>
					<div v-if="showSignButton" v-show="showButton(sign)" class="container-dot container-btn">
						<button class="primary" @click="changeToSignTab">
							{{ t('libresign', 'Sign') }}
						</button>
					</div>
					<div v-show="!showButton(sign)" class="container-dot container-btn">
						<button :class="!disableBtn ? 'secondary' : 'loading'" :disabled="disableBtn" @click="sendNotify(sign.email)">
							{{ t('libresign', 'Resubmit request') }}
						</button>
					</div>
				</div>
			</li>
		</ul>
	</div>
</template>

<script>
import axios from '@nextcloud/axios'
import { format } from 'date-fns'
import { mapState } from 'vuex'
import { generateUrl } from '@nextcloud/router'
import { showError, showSuccess } from '@nextcloud/dialogs'

export default {
	name: 'SignaturesTab',
	props: {
		showSignButton: {
			type: Boolean,
			required: false,
			default: true,
		},
	},
	data() {
		return {
			disableBtn: false,
		}
	},
	computed: {
		...mapState({
			signers: state => state.currentFile.file.signers,
			fileId: state => state.currentFile.file.file.nodeId,
		}),
	},
	methods: {
		hasStatus(item) {
			if (item.sign_date) {
				return item.sign_date ? 'signed' : 'pending'
			} else {
				return 'pending'
			}
		},
		async sendNotify(email) {
			try {
				this.disableBtn = true
				const response = await axios.post(generateUrl('/apps/libresign/api/0.1/notify/signers'), {
					fileId: this.fileId,
					signers: [
						{
							email,
						},
					],
				})
				this.disableBtn = false
				showSuccess(response.data.message)
			} catch (err) {
				console.error(err)
				this.disableBtn = false
				showError(err)
			}
		},
		uppercaseString(string) {
			return string[0].toUpperCase() + string.substr(1)
		},
		timestampsToDate(date) {
			return format(new Date(date), 'dd/MM/yyyy')
		},
		showButton(signPerson) {
			return !!(signPerson.me && !signPerson.sign_date)
		},
		changeToSignTab() {
			this.$emit('change-sign-tab', 'sign')
		},
	},
}
</script>
<style lang="scss" scoped>
.container-signatures-tab{

	ul{
		display: flex;
		flex-wrap: wrap;
		flex-direction: row;
		padding: 10px;
		border-radius: 10px;

		li{
			display: flex;
			width: 100%;
			flex-direction: column;
			border: 1px solid #cecece;
			margin: 3px;
			border-radius: 10px;
			padding: 5px;
			align-items: flex-start;
			overflow: hidden;
			text-overflow: ellipsis;

			.content-status{
				display: flex;
				flex-direction: row;
				align-items: center;
				flex-wrap: wrap;
				width: 100%;

				.container-btn {
					width: 50% !important;
				}

				@media screen and (max-width: 1600px) {
					.container-dot{
						width: 100%;

						button{
							width: 100%;
						}
					}
				}

			}

			.icon-sign{
				margin-right: 8px;
			}

			.user-name{
				display: flex;
				flex-direction: row;

				.name{
					font-size: 14px;
					font-style: normal;
				}
			}

			.container-dot:first-child{
				margin-right: 10px;
			}

			.container-dot{
				display: flex;
				flex-direction: row;
				align-items: center;
				justify-content: flex-start;
				width: 32%;
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

				.signed {
					background: #008000;
				}

				.canceled{
					background: #ff0000;
				}

				.pending {
					background: #d85a0b
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
		}
	}
}

</style>
