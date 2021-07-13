import { getFileList, getInfo } from '@/services/api/file'

const state = () => ({
	files: [],
	currentFile: {},
	signers: [],
	fileData: {},
})

const getters = {
	getFiles: (state, getters, rootState) => {
		return state.files
	},
	getCurrentFile: (state) => {
		return state.currentFile
	},
	getSigners: (state) => {
		return state.signers
	},
	getFileData: (state) => {
		return state.fileData
	},
}

const actions = {
	setCurrentFile({ commit, state }, file) {
		commit('setCurrentFile', file)
		commit('setSigners', file.signers)
	},

	async getSignersFile({ commit, state }, fileId) {
		const response = await getInfo(fileId)
		if (response.data.signers) {
			commit('setSigners', response.data.signers)
		} else if (response.data.errors) {
			commit('setSigners', [])
		}
	},

	async getAllFiles({ commit }) {
		const files = await getFileList()

		commit('setFiles', files)
	},

	newFileData({ commit }, data) {
		commit('setFileData', data)
	},
}

const mutations = {
	setFiles(state, files) {
		state.files = files
	},
	setCurrentFile(state, currentFile) {
		state.currentFile = currentFile
	},
	setSigners(state, signers) {
		state.signers = signers
	},
	setFileData(state, data) {
		state.fileData = data
	},
}

export default {
	namespaced: true,
	state,
	getters,
	actions,
	mutations,
}
