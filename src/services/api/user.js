import axios from '@nextcloud/axios'
import store from '@/store'
import { showError, showSuccess } from '@nextcloud/dialogs'

import { generateUrl } from '@nextcloud/router'

export const getMe = async() => {
	try {
		const respose = await axios.get(generateUrl('/apps/libresign/api/0.1/account/me'))
		return respose
	} catch (err) {
		return err
	}
}

export const createUser = async(uuid, email, password, signPassword) => {
	return await axios.post(generateUrl(`/apps/libresign/api/0.1/create/${uuid}`), {
		email,
		password,
		signPassword,
	})
}

export const createSignature = async(password) => {
	try {
		const passwordCreate = await axios.post(generateUrl('/apps/libresign/api/0.1/account/signature'), {
			signPassword: password,
		})
		if (store) {
			store.dispatch('user/getMe')
		}
		showSuccess(t('libresign', 'New password to sign documents has been created'))
		return passwordCreate
	} catch (err) {
		showError(t('libresign', 'Error creating new password, please contact the administrator'))
	}
}
