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
	<SettingsSection
		:title="title">
		<AdminFormLibresign />
		<UrlValidation />
		<AllowedGroups />
	</SettingsSection>
</template>

<script>
import AdminFormLibresign from './AdminFormLibresign.vue'
import AllowedGroups from './AllowedGroups'
import UrlValidation from './UrlValidation'
import SettingsSection from '@nextcloud/vue/dist/Components/SettingsSection'
import { translate as t } from '@nextcloud/l10n'

export default {
	name: 'Settings',
	components: {
		AdminFormLibresign,
		SettingsSection,
		UrlValidation,
		AllowedGroups,
	},
	data() {
		return {
			loading: true,
			title: t('libresign', 'LibreSign'),
		}
	},
	computed: {},
	async mounted() {},

	methods: {},
}

</script>
<style scoped>
#libresign-admin-settings {
	width: 100vw;
	padding: 20px;
	padding-top: 70px;
	display: flex;
	flex-direction: column;
	flex-grow: 1;
	align-items: center;
}
</style>
