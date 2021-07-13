<!--
- @copyright Copyright (c) 2021 Lyseon Tech <contato@lt.coop.br>
-
- @author Lyseon Tech <contato@lt.coop.br>
- @author Vinicios Gomes <viniciusgomesvaian@gmail.com>
-
- @license GNU AGPL version 3 or any later version
-
- This program is free software: you can redistribute it and/or modify
- it under the terms of the GNU Affero General Public License as
- published by the Free Software Foundation, either version 3 of the
- License, or (at your option) any later version.
-
- This program is distributed in the hope that it will be useful,
- but WITHOUT ANY WARRANTY; without even the implied warranty of
- MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
- GNU Affero General Public License for more details.
-
- You should have received a copy of the GNU Affero General Public License
- along with this program.  If not, see <http://www.gnu.org/licenses/>.
-
-->

<template>
	<SettingsSection :title="title" :description="description">
		<MulutiSelect
			:key="idKey"
			v-model="groupsSelected"
			:options="groups"
			:close-on-select="false"
			:multiple="true"
			@input="saveGroups" />
	</SettingsSection>
</template>

<script>
import { translate as t } from '@nextcloud/l10n'
import SettingsSection from '@nextcloud/vue/dist/Components/SettingsSection'
import MulutiSelect from '@nextcloud/vue/dist/Components/Multiselect'
import axios from '@nextcloud/axios'
import { generateOcsUrl } from '@nextcloud/router'

export default {
	name: 'AllowedGroups',
	components: {
		SettingsSection,
		MulutiSelect,
	},

	data: () => ({
		title: t('libresign', 'Webhook'),
		description: t('libresign', 'Select authorized groups.'),
		groupsSelected: [],
		groups: [],
		idKey: 0,
	}),

	created() {
		this.get()
		this.getData()
	},

	methods: {
		async getData() {
			const response = await axios.get(
				generateOcsUrl('/apps/provisioning_api/api/v1', 2) + 'config/apps' + '/' + 'libresign' + '/' + 'webhook_authorized', {}
			)
			this.groupsSelected = JSON.parse(response.data.ocs.data.data)
		},

		saveGroups() {
			const listOfInputGroupsSelected = JSON.stringify(this.groupsSelected)
			OCP.AppConfig.setValue('libresign', 'webhook_authorized', listOfInputGroupsSelected)
			this.idKey += 1
		},

		async get() {
			const response = await axios.get(generateOcsUrl('cloud/groups?', 3))
			this.groups = response.data.ocs.data.groups
		},
	},

}
</script>
