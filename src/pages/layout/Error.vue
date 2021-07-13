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
	<div class="container">
		<div id="img" />
		<div class="content">
			<h1>404</h1>
			<h2 v-for="error in errorMessage" :key="error">
				{{ error }}
			</h2>
			<p>{{ paragth }}</p>
		</div>
	</div>
</template>

<script>
import { translate as t } from '@nextcloud/l10n'
import { mapGetters } from 'vuex'

export default {
	name: 'Error',

	data() {
		return {
			paragth: t('libresign', 'Sorry but the page you are looking for does not exist, has been removed, moved or is temporarily unavailable.'),
		}
	},

	computed: {
		...mapGetters(['user/getErrors']),
		errorMessage() {
			return this['user/getErrors'].length > 0
				? this['user/getErrors']
				: t('libresign', 'Page not found')
		},
	},
}
</script>

<style lang="scss" scoped>
.container{
	display: flex;
	flex-direction: row;
	align-items: center;
	justify-content: center;
	height: 100%;

	#img{
		background-image: url('../../assets/images/sad-face-in-rounded-square.svg');
		height: 140px;
		width: 140px;
		background-repeat: no-repeat;
		background-size: cover;
		line-height: 17.6px;

		&::before {
			content: '';
			background-color: rgb(0, 245, 248);
			border: 50%;
		}
	}

}

.content{
	box-sizing: border-box;
	font-family: 'Nunito', sans-serif;
	text-transform: uppercase;
	color: rgb(21, 23, 25);
	max-width: 560px;
	padding-left: 50px;

	h1{
		font-size: 65px;
		font-weight: 700;
		line-height: 71.5px;
		margin-bottom: 10px;
		margin-top: 0px;
	}

	h2{
		font-size: 21px;
		font-weight: 400;
		line-height: 23.1px;
		margin: 0px;
		margin-bottom: 10px;
	}
	p{
		color: rgb(153, 159, 165);
		font-weight: 400;
		line-height: 17.6px;
	}
}
</style>
