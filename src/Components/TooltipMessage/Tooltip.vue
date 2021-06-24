<template>
	<div v-show="hasVisible" :class="computedClass">
		<span>{{ message }}</span>
		<span :class="'icon ' + icon" />
	</div>
</template>

<script>
export default {
	name: 'Tooltip',
	props: {
		icon: {
			type: String,
			required: false,
			default: 'icon-details-white',
			validator: (value) => {
				return ['icon-error-white', 'icon-details-white'].includes(value)
			},
		},
		type: {
			type: String,
			required: false,
			default: 'info',
			validator: (value) => {
				return ['info', 'error'].includes(value)
			},
		},
		time: {
			type: Number,
			default: 10,
			required: false,
		},
		message: {
			type: String,
			required: true,
			default: 'Error',
		},
	},
	data() {
		return {
			hasVisible: true,
		}
	},
	computed: {
		computedClass() {
			return this.type === 'error' ? 'container error' : 'container'
		},
	},
	created() {
		if (this.time !== 0) {
			this.timerVisible()
		}
	},
	methods: {
		enabled() {
			this.hasVisible = true
			this.timerVisible()
		},
		timerVisible() {
			setTimeout(() => {
				this.hasVisible = false
			}, this.time * 1000)
		},
	},
}
</script>

<style lang="scss" scoped>
	.container {
		display: flex;
		flex-direction: row;
		width: 100%;
		padding: 16px;
		border: 1px solid #cecece;
		border-radius: 15px;
		margin-bottom: 10px;
		background: rgba(0, 0, 0, .05);

		span.icon {
			background-color: #000;
			border-radius: 50%;
			width: 16px;
			height: 16px;
			align-self: flex-start;
			justify-self: flex-start;
			margin-left: 10px;
		}
	}

	.error {
		border: 1px solid rgb(255, 0, 0) !important;

		span.icon{
			background-color: rgba(255, 0, 0, 0.5);
		}
	}
</style>
