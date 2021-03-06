<template>
	<div class="container">
		<div class="container-request">
			<header>
				<h1>{{ t('libresign', 'Request Signatures') }}</h1>
				<p>{{ t('libresign', 'Choose the file to request signatures.') }}</p>
			</header>
			<div class="content-request">
				<File
					v-show="!isEmptyFile"
					:file="file"
					status="none"
					@sidebar="handleSidebar(true)" />
				<button class="icon icon-folder" @click="getFile()">
					{{ t('libresign', 'Choose from Files') }}
				</button>
			</div>
		</div>
		<AppSidebar v-if="getSidebar"
			ref="sidebar"
			:class="{'app-sidebar--without-background lb-ls-root': 'lb-ls-root'}"
			:title="file.name"
			:active="file.name"
			:subtitle="t('libresign', 'Enter the emails that will receive the request')"
			:header="false"
			name="sidebar"
			icon="icon-rename"
			@close="handleSidebar(false)">
			<EmptyContent v-show="canRequest" class="empty-content">
				<template #desc>
					<p>
						{{ t('libresign', 'Signatures for this document have already been requested') }}
					</p>
				</template>
			</EmptyContent>
			<AppSidebarTab
				v-show="!canRequest"
				id="request"
				:name="t('libresign', 'Add users')"
				icon="icon-rename">
				<Users ref="request" :fileinfo="file" @request:signatures="send" />
			</AppSidebarTab>
		</AppSidebar>
	</div>
</template>
<script>
import axios from '@nextcloud/axios'
import AppSidebar from '@nextcloud/vue/dist/Components/AppSidebar'
import EmptyContent from '@nextcloud/vue/dist/Components/EmptyContent'
import AppSidebarTab from '@nextcloud/vue/dist/Components/AppSidebarTab'
import { getFilePickerBuilder, showError, showSuccess } from '@nextcloud/dialogs'
import Users from '../Components/Request'
import { generateUrl } from '@nextcloud/router'
import File from '../Components/File/File.vue'
import { mapGetters } from 'vuex'

export default {
	name: 'Request',
	components: {
		AppSidebar,
		AppSidebarTab,
		Users,
		EmptyContent,
		File,
	},
	data() {
		return {
			loading: false,
			file: {},
			sidebar: false,
			signers: [],
		}
	},
	computed: {
		isEmptyFile() {
			return Object.keys(this.file).length === 0
		},
		canRequest() {
			return this.signers.length > 0
		},
		...mapGetters(['getSidebar']),
	},
	methods: {
		async getInfo(id) {
			try {
				const response = await axios.get(generateUrl(`/apps/libresign/api/0.1/file/validate/file_id/${id}`))
				this.signers = response.data.signatures
			} catch (err) {
				this.signers = []
			}
		},
		async send(users) {
			try {
				const response = await axios.post(generateUrl('/apps/libresign/api/0.1/sign/register'), {
					file: {
						fileId: this.file.id,
					},
					name: this.file.name.split('.pdf')[0],
					users,
				})
				this.clear()
				return showSuccess(response.data.message)
			} catch (err) {
				showError(err.response.data.message)
			}
		},
		clear() {
			this.file = {}
			this.handleSidebar(false)
			this.$refs.request.clearList()
		},
		getFile() {
			const picker = getFilePickerBuilder(t('libresign', 'Select your file'))
				.setMultiSelect(false)
				.setMimeTypeFilter('application/pdf')
				.setModal(true)
				.setType(1)
				.allowDirectories()
				.build()

			return picker.pick()
				.then(path => {
					OC.dialogs.filelist.forEach(file => {
						const indice = path.split('/').indexOf(file.name)
						if (path.startsWith('/')) {
							if (file.name === path.split('/')[indice]) {
								console.info('ifThen: ', file)
								this.file = file
								this.handleSidebar(true)
								this.getInfo(file.id)
							}
						}
					})
				})
		},
		changeTab(changeId) {
			this.tabId = changeId
		},
		handleSidebar(status) {
			this.$store.commit('setSidebar', status)
		},
	},
}
</script>

<style lang="scss" scoped>
.container{
	display: flex;
	flex-direction: row;
	justify-content: center;
	align-items: center;
	width: 100%;
	height: 100%;
}

.container-request {
	display: flex;
	flex-direction: column;
	align-items: center;
	justify-content: center;
	width: 100%;
	max-width: 100%;
	text-align: center;

	header {
		margin-bottom: 2.5rem;

		h1 {
			font-size: 45px;
			margin-bottom: 1rem;
		}

		p {
			font-size: 15px;
		}
	}

	.content-request{
		display: flex;
		flex-direction: column;
	}
}

.empty-content{
	p{
		margin: 10px;
	}
}

button {
	background-position-x: 8%;
	padding: 13px 13px 13px 45px;
}
</style>
