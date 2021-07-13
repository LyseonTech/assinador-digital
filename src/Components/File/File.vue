<template>
	<div class="content-file" @click="openSidebar">
		<img :src="srcImg">
		<div class="enDot">
			<div :class="status!== 'none' ? 'dot ' + status : '' " />
			<span>{{ status !== 'none' ? statusToUppercase(status) : '' }}</span>
		</div>
		<h1>{{ file.name }}</h1>
	</div>
</template>

<script>
import ApplicationImagePdf from '@/assets/images/application-pdf.png'
export default {
	name: 'File',
	props: {
		file: {
			type: Object,
			default: () => { },
			required: true,
		},
		status: {
			type: String,
			required: true,
			default: 'done',
			validator: () => ['signed', 'canceled', 'pending', 'none'],
		},
	},
	data() {
		return {
			srcImg: ApplicationImagePdf,
		}
	},
	methods: {
		openSidebar() {
			this.$emit('sidebar', this.file)
		},
		statusToUppercase(status) {
			return status[0].toUpperCase() + status.substr(1)
		},
	},
}
</script>

<style lang="scss" scoped>
.content-file{
	display: flex;
	flex-direction: column;
	align-items: center;
	max-height: 197px;
	min-height: 197px;
	margin: 30px 40px 20px 20px;
	padding: 10px 20px 10px 20px;
	cursor: pointer;
	min-width: 187px;
	max-width: 187px;
	overflow: hidden;
	text-overflow: ellipsis;

	img{
		width: 90px;
		cursor: inherit;
	}

	.enDot{
		display: flex;
		flex-direction: row;
		align-content: center;
		margin: 5px;
		align-items: center;
		justify-content: center;
		cursor: inherit;

		.dot{
			width: 10px;
			height: 10px;
			border-radius: 50%;
			margin-right: 10px;
			cursor: inherit;
		}

		.signed{
			background: #008000;
		}

		.canceled{
			background: #ff0000;
		}

		.pending {
			background: #d67335
		}

		span{
			font-size: 14px;
			font-weight: normal;
			text-align: center;
			color: rgba(0,0,0,.7);
			cursor: inherit;
		}
	}

	h1{
		font-size: 23px;
		width: 100%;
		text-align: center;
		cursor: inherit;
		overflow: hidden;
		text-overflow: ellipsis;
	}
}
</style>
