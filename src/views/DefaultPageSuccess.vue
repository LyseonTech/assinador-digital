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
	<div id="container">
		<div id="bg">
			<img :src="logo">
			<h2>{{ subtitle }}</h2>

			<button class="secondary btn-primary" @click="sendToView">
				{{ t('libresign', 'See Document Validation') }}
			</button>
		</div>
	</div>
</template>

<script>
import icon from '../assets/images/logo-white.png'
import { translate as t } from '@nextcloud/l10n'
export default {
	name: 'DefaultPageSuccess',
	props: {
		uuid: {
			type: String,
			default: '',
			required: true,
		},
	},
	data() {
		return {
			logo: icon,
			subtitle: t('libresign', 'Congratulations you have digitally signed a document using LibreSign'),
		}
	},
	methods: {
		sendToView() {
			this.$router.push(`/validation/${this.uuid}`)
		},
	},
}
</script>

<style lang="scss" scoped>
#container{
	width: 100%;
	height: 100%;
}

#bg{
	background-image: url('../../img/frame4.png');
	box-sizing: border-box;
	-webkit-backgroung-size: cover;
	-moz-background-size: cover;
	-o-background-size: cover;
	background-size: cover;
	background-position: center center;
	background-repeat: no-repeat;
	background-attachment: fixed;

	width: 100%;
	height: 100%;

	img {
		position: absolute;
		top: 10%;
		left: 56px;
	}

	h1{
		position: absolute;
		top: 10%;
		left: 56px;
		line-height: normal;
		font-size: 2.1rem;
		font-weight: 800;
		text-align: left;
		color: #FFFFFF;
		width: 30%;
		text-shadow: 2px 2px rgba(0, 0, 0, .8);

	}
	h2{
		position: absolute;
		top: 30%;
		left: 56px;
		color: #FFFFFF;
		font-size: 1.6rem;
		font-weight: 600;
		text-align: left;
		line-height: normal;
		width: 40%;
		text-shadow: 2px 2px 3px rgba(0, 0, 0, 1);
	}

	button.btn-primary{
		position: absolute;
		top: 90%;
		left: 56px;
	}

	span{
		position: absolute;
		top: 92%;
		color: #fefefe;
		left: 45%;
		font-size: 1rem;
		font-weight: 500;
		text-align: center;
	}
	@media (max-width: 550px) {
		h2{
			width: 68%;
		}
	}
	@media (max-width: 450px) {
		h1{
			font-size: 1.5rem;
		}
		h2{
			font-size: 1.1rem;
			width: 68%;
		}
	}
}

</style>
