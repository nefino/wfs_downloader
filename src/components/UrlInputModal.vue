<!-- eslint-disable dot-notation -->
<template>
	<NcModal v-if="visible" name="Layer über Web Feature Service herunterladen" @close="close">
		<h2 v-if="!showCapabilities">
			Layer über Web Feature Service herunterladen
		</h2>
		<h2 v-else>
			Layer Auswahl
		</h2>

		<div class="modal-content">
			<template v-if="!showCapabilities">
				<NcNoteCard type="info">
					<p>Dieses Tool ermöglicht das Herunterladen von Layern in den akutellen Ordner über einen Web Feature Service.</p>
				</NcNoteCard>
				<NcTextField
					v-model="url"
					label="Link"
					:label-visible="true"
					placeholder="https://geodienste.sachsen.de/aaa/public_alkis/vereinf/wfs" />

				<NcNoteCard v-if="submitError" type="error">
					{{ submitError }}
				</NcNoteCard>

				<div class="buttons">
					<NcButton
						type="primary"
						native-type="submit"
						:disabled="!isValid"
						@click="submit">
						<template #icon>
							<NcIconSvgWrapper :svg="LayersIcon" />
						</template>
						Layer auswählen
					</NcButton>
				</div>
			</template>

			<template v-else>
				<div v-for="(layer, index) in layerNames" :key="index" class="checkbox-container">
					<label style="display: flex; align-items: center; gap: 6px; cursor: pointer;">
						<NcCheckboxRadioSwitch
							type="checkbox"
							:checked="layerStates.includes(layer)"
							@update:checked="checked => toggleLayer(layer, checked)" />
						<span>{{ layer }}</span>
					</label>
				</div>

				<NcNoteCard v-if="downloadMessage" type="error">
					{{ downloadMessage }}
				</NcNoteCard>

				<div class="buttons spaced-buttons">
					<NcButton @click="showCapabilities = false">
						Back
					</NcButton>
					<NcButton
						type="primary"
						:disabled="isDownloading"
						@click="download">
						<template #icon>
							<NcIconSvgWrapper :svg="Icon" />
						</template>
						Layer herunterladen
					</NcButton>
				</div>
			</template>
		</div>
	</NcModal>
</template>

<script>
import Icon from '@mdi/svg/svg/download-network.svg'
import LayersIcon from '@mdi/svg/svg/layers-search.svg'
import { generateUrl } from '@nextcloud/router'
import {
	NcButton,
	NcCheckboxRadioSwitch,
	NcIconSvgWrapper,
	NcModal,
	NcNoteCard,
	NcTextField,
} from '@nextcloud/vue'
import WFSCapabilities from 'ol-wfs-capabilities'

export default {
	components: {
		NcButton,
		NcIconSvgWrapper,
		NcModal,
		NcNoteCard,
		NcTextField,
		NcCheckboxRadioSwitch,
	},

	data() {
		return {
			LayersIcon,
			Icon,
			url: '',
			visible: false,
			showCapabilities: false,
			layerNames: null,
			layerStates: [],
			isDownloading: false,
			downloadMessage: '',
			submitError: '',

		}
	},

	computed: {
		isValid() {
			return this.url.trim() !== ''
		},
	},

	methods: {
		toggleLayer(layer, checked) {
			if (checked) {
				if (!this.layerStates.includes(layer)) {
					this.layerStates.push(layer)
				}
			} else {
				this.layerStates = this.layerStates.filter(l => l !== layer)
			}
			// eslint-disable-next-line no-console
			console.log(this.layerStates)
		},
		isSelected(layer) {
			return this.layerStates.includes(layer)
		},
		open() {
			this.url = ''
			this.visible = true
		},
		close() {
			this.visible = false
		},

		async submit() {
			this.submitError = ''

			// eslint-disable-next-line no-console
			console.log(`Getting layers from ${this.url}`)

			const apiUrl = generateUrl('/ocs/v2.php/apps/wfs_downloader/capabilities')
			const wfsUrl = encodeURIComponent(`${this.url}?service=WFS&request=GetCapabilities`)
			const backendUrl = `${apiUrl}?format=json&url=${wfsUrl}`
			// eslint-disable-next-line no-console
			console.log(backendUrl)

			try {
				const response = await fetch(backendUrl)

				if (!response.ok) {
					throw new Error(`Server error: ${response.status}`)
				}
				const rawResp = await response.json()
				// eslint-disable-next-line no-console
				console.log(rawResp)
				// eslint-disable-next-line no-console, dot-notation
				const xmlText = rawResp.ocs['data']
				// parse xml
				const xmlParser = new DOMParser()
				const xmlDoc = xmlParser.parseFromString(xmlText, 'text/xml')
				// eslint-disable-next-line no-console
				console.log(xmlDoc.documentElement)

				const parser = new WFSCapabilities()
				const parsedCapabilities = parser.read(xmlDoc.documentElement)
				// eslint-disable-next-line dot-notation
				this.layerNames = parsedCapabilities['FeatureTypeList'].map(ft => ft.Name)
				this.showCapabilities = true
			} catch (error) {
				console.error('Error fetching capabilities:', error)
				this.submitError = 'Fehler beim Abrufen der Layer. Bitte überprüfen Sie den Link.'
			} finally {
				this.isDownloading = false
			}

		},
		async download() {
			if (this.layerStates.length === 0) {
				this.downloadMessage = 'Bitte wählen Sie mindestens einen Layer aus.'
				this.isDownloading = false
				return
			}

			this.isDownloading = true
			this.downloadMessage = ''

			const apiUrl = generateUrl('/ocs/v2.php/apps/wfs_downloader/download')

			try {
				const response = await fetch(apiUrl, {
					method: 'POST',
					headers: {
						'Content-Type': 'application/json',
						Accept: 'application/json',
					},
					body: JSON.stringify({
						url: this.url,
						layers: this.layerStates,
					}),
				})

				if (!response.ok) {
					throw new Error(`Server error: ${response.status}`)
				}

				const json = await response.json()
				this.downloadMessage = json.ocs?.meta?.message || 'Download request sent.'
			} catch (error) {
				console.error('Download error:', error)
				this.downloadMessage = 'Ein Fehler ist aufgetreten beim Herunterladen.'
			} finally {
				this.isDownloading = false
			}
		},

	},
}
</script>

<style scoped>
h2,
.modal-content {
	margin: calc(var(--default-grid-baseline) * 4);
}

.modal-content {
	display: flex;
	flex-direction: column;
	gap: calc(var(--default-grid-baseline) * 4);
}

.row {
	display: flex;
	align-items: baseline;
	gap: calc(var(--default-grid-baseline) * 4);
}

.short {
	width: 12em !important;
}

.notecard {
	margin: 0 !important;
}

.buttons {
	display: flex;
	justify-content: end;
	gap: calc(var(--default-grid-baseline) * 2);
}
</style>
