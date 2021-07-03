<template>
	<div class="container">
		<div id="image">
			<Avatar
				id="avatar"
				:is-guest="true"
				:show-user-status="false"
				:display-name="user.displayName"
				:user="user.email" />
		</div>
		<div id="content">
			<p class="title">
				{{ user.displayName ? user.displayName : user.email }}
			</p>
			<span class="description"> {{ description }}</span>
		</div>
		<div v-if="hasOptions" id="options">
			<Actions>
				<ActionButton icon="icon-delete" @click="removeUser(user)" />
			</Actions>
		</div>
	</div>
</template>

<script>
import Avatar from '@nextcloud/vue/dist/Components/Avatar'
import Actions from '@nextcloud/vue/dist/Components/Actions'
import ActionButton from '@nextcloud/vue/dist/Components/ActionButton'

export default {
	name: 'ListItem',
	components: {
		Avatar,
		Actions,
		ActionButton,
	},
	props: {
		user: {
			type: Object,
			require: true,
			default: null,
		},
		description: {
			type: String,
			require: false,
			default: null,
		},
		hasOptions: {
			type: Boolean,
			default: true,
			require: false,
		},
	},
	methods: {
		removeUser(user) {
			this.$emit('remove-user', user)
		},
	},
}
</script>
<style lang="scss" scoped>
@import './styles';
</style>
