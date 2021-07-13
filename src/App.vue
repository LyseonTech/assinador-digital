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
	<Content app-name="libresign">
		<AppNavigation>
			<template #list>
				<AppNavigationItem
					:to="{ name: 'signFiles' }"
					:title="t('libresign', 'Files')"
					icon="icon-files-dark"
					exact />
				<AppNavigationItem
					:to="{name: 'requestFiles'}"
					:title="t('libresign', 'Request')"
					icon="icon-rename"
					exact />
				<AppNavigationItem
					:to="{name: 'validation'}"
					:title="t('libresign', 'Validate')"
					icon="icon-file" />
			</template>
			<template #footer>
				<AppNavigationSettings :title="t('libresign', 'Settings')">
					<CroppedLayoutSettings />
				</AppNavigationSettings>
			</template>
		</AppNavigation>
		<AppContent :class="{'icon-loading' : loading }">
			<router-view v-show="!loading" :loading.sync="loading" />
			<EmptyContent v-show="isRoot" class="emp-content">
				<template #icon>
					<img :src="icon">
				</template>
				<template #desc>
					<p>
						{{ t('libresign', 'LibreSign, digital signature app for Nextcloud.') }}
					</p>
				</template>
			</EmptyContent>
		</AppContent>
	</Content>
</template>

<script>
import Content from '@nextcloud/vue/dist/Components/Content'
import AppNavigation from '@nextcloud/vue/dist/Components/AppNavigation'
import AppNavigationItem from '@nextcloud/vue/dist/Components/AppNavigationItem'
import AppNavigationSettings from '@nextcloud/vue/dist/Components/AppNavigationSettings'
import AppContent from '@nextcloud/vue/dist/Components/AppContent'
import EmptyContent from '@nextcloud/vue/dist/Components/EmptyContent'
import Icon from '@/assets/images/signed-icon.svg'
import CroppedLayoutSettings from '@/Components/Settings/CroppedLayoutSettings.vue'

export default {
	name: 'App',
	components: {
		Content,
		AppNavigation,
		AppNavigationItem,
		AppNavigationSettings,
		AppContent,
		EmptyContent,
		CroppedLayoutSettings,
	},
	data() {
		return {
			loading: false,
			icon: Icon,
		}
	},
	computed: {
		isRoot() {
			return this.$route.path === '/'
		},
	},
}
</script>

<style lang="scss" scoped>
.emp-content{
	display: flex;
	align-items: center;
	justify-content: center;
	width: 100%;
	height: 70%;

	margin-top: 10vh;
	p{
		opacity: .6;
	}

	img{
		width: 400px;
	}
}
</style>
