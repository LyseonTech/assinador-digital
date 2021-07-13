import { getMe } from '@/services/api/user'
import { getSettings } from '@/services/initialState'

const state = () => ({
	pfx: false,
	hableToRequestSign: false,
	me: {},
	errors: [],
	action: null,
})

const getters = {
	getPfx: (state, getters, rootState) => {
		return state.pfx
	},
	getHableToRequestSign: (state, getters, rootState) => {
		return state.hableToRequestSign
	},
	getAction: (state) => {
		return state.action
	},
	getErrors: (state) => {
		return state.errors
	},
}

const actions = {
	async getMe({ commit }) {
		const response = await getMe()
		commit('setPfx', response.data.settings.hasSignatureFile)
		commit('setMe', response.data)
	},
	getSettings({ commit }) {
		const settings = getSettings()
		if (settings.errors) {
			commit('setErrors', settings.errors)
		}

		if (settings.action) {
			commit('setAction', settings.action)
		}

		commit('setPfx', settings.hasSignatureFile)
	},
}

const mutations = {
	setHableToRequestSign(state, resp) {
		state.hableToRequestSign = resp
	},
	setPfx(state, newData) {
		state.pfx = newData
	},
	setMe(state, me) {
		state.me = me
	},
	setAction(state, action) {
		state.action = action
	},
	setErrors(state, error) {
		state.errors = error
	},
}

export default {
	namespaced: true,
	state,
	getters,
	actions,
	mutations,
}
