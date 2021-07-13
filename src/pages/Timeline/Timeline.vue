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
			@update="getAllFiles"
			@sign:document="signDocument"
			@closeSidebar="closeSidebar" />
	</div>
</template>

<script>
// Utils
import { mapState, mapActions } from 'vuex'
import { showError } from '@nextcloud/dialogs'

// Services
import { signInDocument } from '@/services/api/file'

// Components
import File from '@/Components/File/File.vue'
import Sidebar from '@/Components/File/Sidebar.vue'

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
			files: state => state.file.files,
		}),
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
		this.$store.dispatch('file/getAllFiles')
		this.$store.dispatch('user/getMe')

	},

	methods: {
		...mapActions('file', ['getAllFiles']),
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
		openSidebar() {
			this.sidebar = true
		},
		setSidebar(objectFile) {
			this.closeSidebar()
			this.$store.dispatch('file/setCurrentFile', objectFile)
			this.openSidebar()
		},
		closeSidebar() {
			this.sidebar = false
		},
		async signDocument(param) {
			try {
				this.loading = true
				await signInDocument(param.password, param.fileId)
				this.$store.dispatch('file/getAllFiles')
				this.closeSidebar()
				this.loading = false
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
