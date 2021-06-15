import { shallowMount, createLocalVue } from '@vue/test-utils'
import App from '../../App.vue'

let wrapper
const localVue = createLocalVue()

beforeEach(() => {
	wrapper = shallowMount(App, {
		localVue,
		stubs: ['router-linkn', 'router-view'],
	})
})

afterEach(() => {
	wrapper.destroy()
})

describe('App', () => {
	test('Is a Vue Instance', () => {
		expect(wrapper.vm).toBeTruthy()
	})
})
