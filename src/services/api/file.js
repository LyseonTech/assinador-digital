import axios from '@nextcloud/axios'
import { showError, showSuccess } from '@nextcloud/dialogs'

import { generateUrl } from '@nextcloud/router'

export const getFileList = async() => {
	try {
		const response = await axios.get(generateUrl('/apps/libresign/api/0.1/file/list'))
		return response.data.data
	} catch (err) {
		showError(t('libresign', 'An error occorred while fetching the files'))
	}
}

export const signInDocument = async(password, fileID) => {
	try {
		const response = await axios.post(
			generateUrl(`/apps/libresign/api/0.1/sign/file_id/${fileID}`),
			{ password })

		// Reload Files
		OCA.Files.App.fileList.reload()

		showSuccess(response.data.message)
		return response
	} catch (err) {
		showError(err.response.data.message)
		return err.response
	}
}

export const signInDocumentUuid = async(password, uuid) => {
	try {
		const response = await axios.post(
			generateUrl(`/apps/libresign/api/0.1/sign/uuid/${uuid}`), { password })
		showSuccess(response.data.message)
		return response
	} catch (err) {
		return err.response
	}

}

export const getInfo = async(fileId) => {
	try {
		const response = await axios.get(generateUrl(`/apps/libresign/api/0.1/file/validate/file_id/${fileId}`))
		return response
	} catch (err) {
		return err.response
	}
}
