<template>
	<div class="container-signatures-tab">
		<ul>
			<li v-for="sign in getSigners" :key="sign.uid">
				<div class="user-name">
					<div class="icon-sign icon-user" />
					<span class="name">
						{{ getName(sign) }}
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
					<div v-if="sign.email" class="container-dot">
						<div class="icon icon-mail" />
						<span>{{ sign.email }}</span>
					</div>
					<div v-if="showDivButtons(sign)" class="container-actions">
						<div v-if="showSignButton(sign)" class="container-dot container-btn">
							<button class="primary" @click="changeToSignTab">
								{{ t('libresign', 'Sign') }}
							</button>
						</div>
						<div v-show="showNotifyButton(sign)" class="container-dot container-btn">
							<button :class="!disableBtn ? 'secondary' : 'loading'" :disabled="disableBtn" @click="sendNotify(sign.email)">
								{{ t('libresign', 'Send reminder') }}
							</button>
						</div>
						<div>
							<Actions v-if="showDelete(sign)">
								<ActionButton icon="icon-delete" @click="deleteUserRequest(sign)" />
							</Actions>
						</div>
					</div>
				</div>
			</li>
		</ul>
	</div>
</template>

<script>
// Utils
import { format } from 'date-fns'
import { mapGetters } from 'vuex'

// Services
import { deleteSignatureRequest, sendNotification } from '@/services/api/signatures'

// Components
import Actions from '@nextcloud/vue/dist/Components/Actions'
import ActionButton from '@nextcloud/vue/dist/Components/ActionButton'

export default {
	name: 'SignaturesTab',
	components: {
		Actions,
		ActionButton,
	},
	data() {
		return {
			disableBtn: false,
		}
	},
	computed: {
		...mapGetters('file', ['getSigners', 'getCurrentFile']),
	},
	methods: {
		hasStatus(item) {
			if (item.sign_date) {
				return item.sign_date ? 'signed' : 'pending'
			} else {
				return 'pending'
			}
		},

		update() {
			this.$emit('update')
		},

		async deleteUserRequest(user) {
			const result = confirm(t('libresign', 'Are you sure you want to exclude user {email} from the request?', { email: user.email }))
			if (result === true) {
				await deleteSignatureRequest(
					this.getCurrentFile.file.nodeId,
					user.signatureId,
				)
				this.update()
			}
		},

		async sendNotify(email) {
			try {
				this.disableBtn = true
				await sendNotification(
					email,
					this.getCurrentFile.file.nodeId,
				)
				this.disableBtn = false
			} catch (err) {
				this.disableBtn = false
			}
		},

		uppercaseString(string) {
			return string[0].toUpperCase() + string.substr(1)
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

		timestampsToDate(date) {
			return format(new Date(date), 'dd/MM/yyyy HH:mm')
		},

		showButton(signPerson) {
			return !!(signPerson.me && !signPerson.sign_date)
		},

		changeToSignTab() {
			this.$emit('change-sign-tab', 'sign')
		},

		showSignButton(user) {
			if (user.me) {
				if (user.sign_date) {
					return false
				}
				return true
			}
		},

		showNotifyButton(user) {
			if (!user.me) {
				if (user.sign_date) {
					return false
				}
				return true
			}
			return false
		},

		showDelete(user) {
			if (user.sign_date) {
				return false
			}
			return true
		},

		showDivButtons(user) {
			return !!(this.showSignButton(user) || this.showNotifyButton(user) || this.showDelete(user))
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

				.container-actions{
					display: flex;
					flex-direction: row;
					justify-content: space-between;
					align-items: center;
					width: 100%;
					padding: 0 10px;
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

			.container-dot{
				display: flex;
				flex-direction: row;
				align-items: center;
				justify-content: flex-start;
				/* width: 32%; */
				margin-bottom: 6px;
				min-height: 26px;
				cursor: inherit;
				margin-right: 10px;

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
