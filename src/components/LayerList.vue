<template>
	<div class="accordion">
		<div
			v-for="(item, index) in topics"
			:key="item.name"
			class="accordion-item">
			<div class="accordion-header">
				<!-- Entire clickable area for checkbox + label -->
				<div
					class="checkbox-label-container"
					role="checkbox"
					:aria-checked="layerStates[item.name]"
					tabindex="0"
					@click="toggleLayer(item.name)"
					@keydown.enter.prevent="toggleLayer(item.name)"
					@keydown.space.prevent="toggleLayer(item.name)">
					<NcCheckboxRadioSwitch
						:checked="layerStates[item.name]"
						type="checkbox"
						@change.stop="toggleLayer(item.name)" />
					<span class="layer-label-text">{{ item.name }}</span>
				</div>

				<!-- Toggle details button -->
				<button
					class="toggle-button"
					:aria-expanded="openItem === index ? 'true' : 'false'"
					:aria-controls="'details-' + item.name"
					:title="openItem === index ? 'Details verbergen' : 'Details anzeigen'"
					@click.stop="toggleAccordion(index)">
					{{ openItem === index ? '▲' : '▼' }}
				</button>
			</div>

			<div
				v-if="openItem === index"
				:id="'details-' + item.name"
				class="accordion-body">
				<p>{{ item?.abstract?.value || 'Keine Beschreibung vorhanden.' }}</p>
			</div>
		</div>
	</div>
</template>

<script>
import { NcCheckboxRadioSwitch } from '@nextcloud/vue'

export default {
	name: 'LayerList',
	components: {
		NcCheckboxRadioSwitch,
	},
	props: {
		topics: {
			type: Array,
			required: true,
		},
		layerStates: {
			type: Object,
			required: true,
		},
	},
	emits: ['update:layer-states'],
	data() {
		return {
			openItem: null,
		}
	},
	methods: {
		toggleAccordion(index) {
			this.openItem = this.openItem === index ? null : index
		},
		toggleLayer(name) {
			const updated = {
				...this.layerStates,
				[name]: !this.layerStates[name],
			}
			this.$emit('update:layer-states', updated)
		},
	},
}
</script>

<style scoped>
.accordion {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}
.accordion-item {
  border: 1px solid #ccc;
  border-radius: 4px;
}
.accordion-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 0.5rem 1rem;
}

/* The whole area around checkbox + label is clickable */
.checkbox-label-container {
  display: flex;
  align-items: center;
  cursor: pointer;
  user-select: none;
  flex-grow: 1;
}
.layer-label-text {
  margin-left: 0.5rem;
}

.toggle-button {
  background: none;
  border: none;
  cursor: pointer;
  font-size: 1rem;
  user-select: none;
  padding: 0 0.25rem;
}

.accordion-body {
  padding: 0.5rem 1rem;
  border-top: 1px solid #ccc;
  white-space: pre-wrap;
}
</style>
