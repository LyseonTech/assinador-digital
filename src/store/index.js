import Vue from 'vue'
import Vuex, { Store } from 'vuex'
import user from '@/store/modules/user'
import file from '@/store/modules/file'

Vue.use(Vuex)

export default new Store({

	state: {
		pdfData: {},
		user: {},
		files: [],
		sidebar: false,
	},

	mutations: {
		setUser(state, user) {
			this.state.user = user
		},
		setSidebar(state, sidebar) {
			this.state.sidebar = sidebar
		},
		setPdfData(state, pdfData) {
			if (pdfData.pdf.url) {
				Vue.set(state.pdfData, 'url', pdfData.pdf.url)
			} else {
				Vue.set(state.pdfData, 'base64', `data:application/pdf;base64,${pdfData.pdf.base64}`)
			}
			Vue.set(state.pdfData, 'description', pdfData.description)
			Vue.set(state.pdfData, 'filename', pdfData.filename)
		},
		setFiles(state, files) {
			state.files = files
		},
	},

	getters: {
		getSidebar(state) {
			return state.sidebar
		},
		getPdfData(state) {
			return state.pdfData
		},
		getUser(state) {
			return state.user
		},
		getFiles(state) {
			return state.files
		},
	},

	modules: {
		user,
		file,
	},
})
