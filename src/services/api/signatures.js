import axios from '@nextcloud/axios'
import { showError, showSuccess } from '@nextcloud/dialogs'

import { generateUrl } from '@nextcloud/router'

export const request = async(users, fileId, fileName, method) => {
	if (method === 'new') {
		try {

			const response = await axios.post(generateUrl('/apps/libresign/api/0.1/sign/register'), {
				file: {
					fileId,
				},
				name: fileName,
				users,
			})
			showSuccess(response.data.message)
			return response
		} catch (err) {
			return showError(err.response.data.message)
		}
	} else if (method === 'update') {
		try {

			const response = await axios.patch(generateUrl('/apps/libresign/api/0.1/sign/register'), {
				file: {
					fileId,
				},
				users,
			})
			showSuccess(response.data.message)
			return response
		} catch (err) {
			return showError(err.response.data.message)
		}
	}
}

export const sendNotification = async(email, fileId) => {
	try {
		const response = await axios.post(generateUrl('/apps/libresign/api/0.1/notify/signers'), {
			fileId,
			signers: [
				{
					email,
				},
			],
		})
		return showSuccess(response.data.message)
	} catch (err) {
		return showError(t('libresign', 'An error occorred while send notification'))
	}
}

export const deleteSignatureRequest = async(fileId, signatureId) => {
	try {
		const response = await axios.delete(generateUrl(`/apps/libresign/api/0.1/sign/file_id/${fileId}/${signatureId}`))
		return showSuccess(response.data.message)
	} catch (err) {
		return showError(err.response.data.message)
	}
}

export const validateSignature = async(uuid) => {
	try {
		const response = await axios.get(generateUrl(`/apps/libresign/api/0.1/file/validate/uuid/${uuid}`))
		showSuccess(t('libresign', 'This document is valid'))
		return response
	} catch (err) {
		err.response.data.errors.forEach(error => {
			showError(error)
		})
	}

}
