<template>
	<div class="container-timeline">
		<div class="content-timeline">
			<div class="filtered">
				<a :class="filterActive === 'allFiles' ? 'allFiles active' : 'allFiles'" @click="changeFilter(3)">
					{{ t('libresign', 'All Files') }}
				</a>
				<a :class="filterActive === 'pending' ? 'pending active': 'pending'" @click="changeFilter(1)">
					{{ t('libresign', 'Pending') }}
				</a>
				<a :class="filterActive === 'signed' ? 'signed active' : 'signed'" @click="changeFilter(2)">
					{{ t('libresign', 'Signed') }}
				</a>
			</div>
			<ul>
				<File
					v-for="file in filterFile"
					:key="file.uuid"
					class="file-details"
					:status="file.status"
					:file="file"
					@sidebar="setSidebar" />
			</ul>
		</div>
		<Sidebar v-if="sidebar"
			ref="sidebar"
			:loading="loading"
			@update="getData"
			@sign:document="signDocument"
			@closeSidebar="closeSidebar" />
	</div>
</template>

<script>
import axios from '@nextcloud/axios'
import { generateUrl } from '@nextcloud/router'
import { mapGetters, mapState } from 'vuex'
import File from '../Components/File'
import Sidebar from '../Components/File/Sidebar.vue'
import { showError, showSuccess } from '@nextcloud/dialogs'

export default {
	name: 'Timeline',
	components: {
		File,
		Sidebar,
	},
	data() {
		return {
			sidebar: false,
			loading: false,
			fileFilter: this.files,
			filterActive: 'allFiles',
		}
	},

	computed: {
		...mapState({
			files: state => state.files,
		}),
		...mapGetters(['getFiles']),
		pendingFilter() {
			return this.files.slice().filter(
				(a) => (a.status === 'pending')).sort(
				(a, b) => (a.request_date < b.request_date) ? 1 : -1)
		},
		signedFilter() {
			return this.files.slice().filter(
				(a) => (a.status === 'signed')).sort(
				(a, b) => (a.request_date < b.request_date) ? 1 : -1)
		},
		filterFile: {
			get() {
				if (this.fileFilter === undefined || '') {
					return this.files.slice().sort(
						(a, b) => (a.request_date < b.request_date) ? 1 : -1
					)
				}
				return this.fileFilter.slice().sort(
					(a, b) => (a.request_date < b.request_date) ? 1 : -1
				)
			},
			set(value) {
				this.fileFilter = value
			},
		},

	},

	created() {
		this.getData()
	},

	methods: {
		changeFilter(filter) {
			switch (filter) {
			case 1:
				this.filterFile = this.pendingFilter
				this.filterActive = 'pending'
				break
			case 2:
				this.filterFile = this.signedFilter
				this.filterActive = 'signed'
				break
			case 3:
				this.filterFile = this.files.sort(
					(a, b) => (a.request_date < b.request_date) ? 1 : -1
				)
				this.filterActive = 'allFiles'
				break
			default:
				break
			}
		},
		async getData() {
			try {
				const response = await axios.get(generateUrl('/apps/libresign/api/0.1/file/list'))
				this.$store.commit('setFiles', response.data.data)
			} catch (err) {
				showError('An error occurred while fetching the files')
			}
		},
		openSidebar() {
			this.sidebar = true
		},
		setSidebar(objectFile) {
			this.closeSidebar()
			this.$store.commit('setCurrentFile', objectFile)
			this.openSidebar()
		},
		closeSidebar() {
			this.sidebar = false
		},
		async signDocument(param) {
			try {
				this.loading = true
				const response = await axios.post(generateUrl(`/apps/libresign/api/0.1/sign/file_id/${param.fileId}`), {
					password: param.password,
				})
				this.getData()
				this.closeSidebar()
				this.loading = false
				return showSuccess(response.data.message)
			} catch (err) {
				this.loading = false
				err.response.data.errors.map(
					error => {
						showError(error)
					}
				)
			}
		},
	},
}
</script>
<style lang="scss" scoped>
.container-timeline{
	display: flex;
	width: 100%;
	flex-direction: row;

	.content-timeline{
		display: flex;
		width: 100%;
		flex-direction: column;

		.filtered {
			display: flex;
			width: 100%;
			justify-content: flex-end;
			padding: 10px;

			a {
				padding: 6px;
				background: rgba(206, 206, 206, 0.3);
			}

			.signed{
				border-radius: 0 10px 10px 0;
			}

			.allFiles{
				border-radius: 10px 0 0 10px;
			}

			.active {
				background: darken(rgba(206, 206, 206, 0.3), 20%)
			}
		}

		ul{
			display: flex;
			width: 100%;
			flex-wrap: wrap;
		}

		.file-details:hover {
			background: darken(#fff, 10%);
			border-radius: 10px;
		}
	}
}
</style>
