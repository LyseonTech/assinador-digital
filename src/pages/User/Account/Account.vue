<template>
	<Content class="container-account" app-name="libresign">
		<div class="content-account">
			<div class="user">
				<div class="user-image">
					<div class="user-image-label">
						<h1>{{ t('libresign', 'Profile picture') }}</h1>
						<div class="icons icon-contacts-dark" />
					</div>
					<Avatar :show-user-status="false"
						:size="145"
						class="user-avatar"
						:user="user.uid"
						:display-name="user.displayName" />
				</div>
				<div class="details">
					<div class="user-details">
						<h3>{{ t('libresign', 'Details') }}</h3>
						<div class="user-display-name icon-user">
							<p>{{ user.displayName }}</p>
						</div>
					</div>
					<div class="user-password">
						<h3>{{ t('libresign', 'Password & Security') }}</h3>
						<div class="user-display-password icon-password">
							<button v-if="!hasSignature" @click="handleModal(true)">
								{{ t('libresign', 'Create password key') }}
							</button>
							<button v-else @click="handleModal(true)">
								{{ t('librsign', 'Reset password') }}
							</button>
						</div>
						<Modal v-if="modal" :size="'large'" @close="handleModal(false)">
							<CreatePassword v-if="!hasSignature" @close="handleModal(false)" />
							<ResetPassword v-if="hasSignature" @close="handleModal(false)" />
						</Modal>
					</div>
				</div>
			</div>
		</div>
	</Content>
</template>

<script>
// Utils
import { getCurrentUser } from '@nextcloud/auth'
import { mapGetters } from 'vuex'

// Components
import Modal from '@nextcloud/vue/dist/Components/Modal'
import Avatar from '@nextcloud/vue/dist/Components/Avatar'
import Content from '@nextcloud/vue/dist/Components/Content'
import CreatePassword from '@/Components/Password/Create/Create.vue'
import ResetPassword from '@/Components/Password/Reset/Reset.vue'

export default {
	name: 'Account',
	components: {
		Content,
		Avatar,
		Modal,
		CreatePassword,
		ResetPassword,
	},
	data() {
		return {
			user: getCurrentUser(),
			modal: false,
		}
	},
	computed: {
		...mapGetters({
			hasSignature: 'user/getPfx',
		}),
	},
	methods: {
		handleModal(status) {
			this.modal = status
		},
	},
}
</script>
<style lang="scss">
.modal-wrapper--large .modal-container[data-v-3e0b109b]{
	width: 100%;
	height: 100%;
}

.container-account{
	display: flex;
	flex-direction: row;

	.content-account{
		width: 100%;
		margin: 10px;
		display: flex;
		height: 100%;

		.user{
			width: 25%;
			display: flex;
			flex-direction: column;
			align-items: center;

			.user-image {
				display: flex;
				width: 100%;
				flex-direction: column;
				align-items: center;

				h1{
					align-self: flex-start;
				}

				.user-image-label{
					display: flex;
					flex-direction: row;
					align-self: flex-start;
					margin-bottom: 20px;

					h1{
						margin-right: 10px;
					}

					.icons{
						opacity: 0.7;
					}
				}
			}

			.details{
				display: flex;
				flex-direction: column;
				width: 100%;
				padding: 10px;
				border: 0;
			}

			.user-details{
				display: flex;
				flex-direction: column;
				width: 100%;
				border: 0;

				.user-display-name[class*='icon']{
					width: 100%;
					background-position: 0px 4px;
					opacity: 0.7;
					margin-right: 10%;
					margin-bottom: 12px;
					margin-top: 12px;
					margin-left: 12px;
					padding-left: 22px;
				}
			}

			.user-password{
				display: flex;
				flex-direction: column;

				.user-display-password[class*='icon']{
					display: flex;
					background-position: 0px 10px;
					opacity: 0.7;
					margin-right: 10%;
					margin-bottom: 12px;
					margin-top: 12px;
					width: 100%;
					padding-left: 30px;
					margin-left: 15px;
					align-items: center;

					button {
						min-width: 150px;
					}
				}
			}
		}

	}
}
</style>
