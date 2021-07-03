<template>
	<AppSidebar
		v-if="currentFile"
		ref="sidebar"
		:class="{'app-sidebar--without-background lb-ls-root' : 'lb-ls-root'}"
		:title="titleName"
		:subtitle="subTitle"
		:active="tabId"
		:header="false"
		name="sidebar"
		@update:active="updateActive"
		@close="closeSidebar">
		<AppSidebarTab
			id="signantures"
			:name="t('libresign', 'Signatures')"
			icon="icon-rename"
			:order="1">
			<SignaturesTab :items="currentFile.file.signers" @change-sign-tab="changeTab" />
		</AppSidebarTab>
		<AppSidebarTab
			v-if="hasSign"
			id="sign"
			:name="t('libresign', 'Sign')"
			icon="icon-rename"
			:order="2">
			<Sign ref="sign"
				:pfx="getHasPfx"
				:has-loading="loading"
				@sign:document="emitSign" />
		</AppSidebarTab>
	</AppSidebar>
</template>

<script>
import AppSidebar from '@nextcloud/vue/dist/Components/AppSidebar'
import AppSidebarTab from '@nextcloud/vue/dist/Components/AppSidebarTab'
import { mapGetters, mapState } from 'vuex'
import SignaturesTab from './SignaturesTab.vue'
import Sign from '../Sign'
import format from 'date-fns/format'

export default {
	name: 'Sidebar',
	components: {
		AppSidebar,
		AppSidebarTab,
		SignaturesTab,
		Sign,
	},
	props: {
		loading: {
			type: Boolean,
			default: false,
			required: false,
		},
	},
	data() {
		return {
			tabId: 'signatures',
		}
	},
	computed: {
		titleName() {
			return this.getCurrentFile.file.name ? this.getCurrentFile.file.name : ''
		},
		subTitle() {
			return t('libresign', 'Requested by {name}, at {date}', {
				name: this.getCurrentFile.file.requested_by.uid
					? this.getCurrentFile.file.requested_by.uid
					: '',
				date: format(new Date(this.getCurrentFile.file.request_date), 'dd/MM/yyyy'),
			})
		},
		hasSign() {
			return this.getCurrentFile.file.signers.filter(
				signer => signer.me !== false && signer.sign_date === null
			).length > 0
		},
		...mapState({
			currentFile: state => state.currentFile,
			sidebar: state => state.sidebar,
		}),
		...mapGetters(['getCurrentFile', 'getSidebar', 'getHasPfx']),
	},
	methods: {
		closeSidebar() {
			this.$emit('closeSidebar', true)
		},
		clearSignInput() {
			this.$refs.sign.clearInput()
		},
		emitSign(password) {
			this.$emit('sign:document', { password, fileId: this.getCurrentFile.file.file.nodeId })
		},
		updateActive(e) {
			this.changeTab(e)
		},
		changeTab(changeId) {
			this.tabId = changeId
		},
	},
}
</script>
