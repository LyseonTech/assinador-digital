
<!--
- @copyright Copyright (c) 2021 Lyseon Tech <contato@lt.coop.br>
-
- @author Lyseon Tech <contato@lt.coop.br>
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
- MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
- GNU Affero General Public License for more details.
-
- You should have received a copy of the GNU Affero General Public License
- along with this program. If not, see <http://www.gnu.org/licenses/>.
-
-->

<template>
	<div id="container">
		<div id="viewer" class="content">
			<PDFViewer :url="documentData.pdf.url === undefined ? documentData.pdf.base64 : documentData.pdf.url" />
		</div>
		<div id="description" class="content">
			<Description :uuid="uuid" :pdf-name="documentData.filename" :pdf-description="documentData.description" />
		</div>
	</div>
</template>

<script>
import Description from '@/Components/Description'
import PDFViewer from '@/Components/PDFViewer'
import { mapGetters } from 'vuex'

export default {
	name: 'SignPDF',
	components: { Description, PDFViewer },
	props: {
		uuid: {
			type: String,
			default: '',
		},
	},
	data() {
		return {
			desc: '',
			pdfData: '',
			name: '',
			user: '',
		}
	},
	computed: {
		...mapGetters({
			documentData: 'file/getFileData',
		}),
	},
}
</script>

<style lang="scss" scoped>
	#container {
		display: flex;
		flex-direction: row;
		width: 100%;
		height: 100%;
	}

	.content{
		display: flex;
		flex-direction: column;
		width: 100%;
		height: 100%;
	}

	#description{
		width: 38%;

		@media (max-width: 1024px){
			width: 40%;
		}
		@media (max-width: 650px) {
			width: 100%;
		}
	}

	#viewer{
		display: flex;
		justify-content: center;
		align-items: center;
		background: #cecece;

		@media (max-width: 1024px){
			width: 60%;
		}
		@media (max-width: 650px) {
			display: none;
		}
	}

</style>
