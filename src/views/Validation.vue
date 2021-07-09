<template>
	<Content app-name="libresign" class="jumbotron">
		<div class="container">
			<div class="image">
				<img :src="image" draggable="false">
			</div>
			<div id="dataUUID">
				<form v-show="!hasInfo" @submit="(e) => e.preventDefault()">
					<h1>{{ title }}</h1>
					<h3>{{ legend }}</h3>
					<input v-model="myUuid" type="text">
					<button :class="hasLoading ? 'btn-load primary loading':'btn'" @click.prevent="validateByUUID(myUuid)">
						{{ buttonTitle }}
					</button>
				</form>
				<div v-if="hasInfo" class="infor-container">
					<div class="infor-bg">
						<div class="infor">
							<div class="header">
								<img class="icon" :src="infoIcon">
								<h1>{{ infoDocument }}</h1>
							</div>
							<div class="info-document">
								<p>
									<b>{{ document.name }}</b>
								</p>
								<a class="button" :href="linkToDownload(document.file)"> {{ t('libresign', 'View') }} </a>
							</div>
						</div>
					</div>
					<div class="infor-bg signed">
						<div class="header">
							<img class="icon" :src="signatureIcon">
							<h1>{{ t('libresign', 'Subscriptions:') }}</h1>
						</div>
						<div v-for="item in document.signers"
							id="sign"
							:key="item.fululName"
							class="scroll">
							<div class="subscriber">
								<span><b>{{ getName(item) }}</b></span>
								<span v-if="item.signed" class="data-signed">{{ formatData(item.signed) }} </span>
								<span v-else>{{ noDateMessage }}</span>
							</div>
						</div>
					</div>
					<button type="primary" class="btn- btn-return" @click.prevent="changeInfo">
						{{ t('libresign', 'Return') }}
					</button>
				</div>
			</div>
		</div>
	</Content>
</template>

<script>
import axios from '@nextcloud/axios'
import Content from '@nextcloud/vue/dist/Components/Content'
import BackgroundImage from '../assets/images/bg.png'
import iconA from '../../img/info-circle-solid.svg'
import iconB from '../../img/file-signature-solid.svg'
import { generateUrl } from '@nextcloud/router'
import { showError, showSuccess } from '@nextcloud/dialogs'
import { translate as t } from '@nextcloud/l10n'
import { fromUnixTime } from 'date-fns'

export default {
	name: 'Validation',

	components: {
		Content,
	},

	props: {
		uuid: {
			type: String,
			required: false,
			default: '',
		},
	},

	data() {
		return {
			image: BackgroundImage,
			infoDocument: t('libresign', 'Document Informations'),
			infoIcon: iconA,
			signatureIcon: iconB,
			title: t('libresign', 'Validate Subscription.'),
			legend: t('libresign', 'Enter the UUID of the document to validate.'),
			buttonTitle: t('libresign', 'Validation'),
			noDateMessage: t('libresign', 'No date'),
			myUuid: this.uuid ? this.uuid : '',
			hasInfo: false,
			hasLoading: false,
			document: {},
			documentUuid: '',
		}
	},
	watch: {
		'$route.params'(toParams, previousParams) {
			this.validateByUUID(toParams.uuid)
		},
	},
	methods: {
		async validateByUUID(uuid) {
			this.hasLoading = true

			try {
				const response = await axios.get(generateUrl(`/apps/libresign/api/0.1/file/validate/uuid/${uuid}`))
				showSuccess(t('libresign', 'This document is valid'))
				console.info(response)
				this.document = response.data
				this.hasInfo = true
				this.hasLoading = false
			} catch (err) {
				this.hasLoading = false
				showError(err.response.data.errors[0])
			}
		},
		getName(user) {
			if (user.fullName) {
				return user.fullName
			} else if (user.displayName) {
				return user.displayName
			} else if (user.email) {
				return user.email
			}

			return 'None'
		},
		linkToDownload(val) {
			return val
		},
		changeInfo() {
			this.hasInfo = !this.hasInfo
			this.uuid = ''
		},
		formatData(data) {
			try {
				return fromUnixTime(data).toLocaleDateString()
			} catch {
				return this.noDateMessage
			}
		},
	},
}
</script>

<style lang="scss" scoped>
@import '../assets/styles/validation.scss';
@import '../assets/styles/loading.scss';
</style>
