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
					Dieses Tool ermöglicht das Herunterladen von Layern in den aktuellen
					Ordner über einen Web Feature Service (WFS).
				</NcNoteCard>
				<NcTextField v-model="linkView.url"
					label="Link"
					:label-visible="true"
					placeholder="https://geodienste.sachsen.de/aaa/public_alkis/vereinf/wfs" />

				<NcNoteCard v-if="linkView.submitError" type="error">
					{{ linkView.submitError }}
				</NcNoteCard>

				<div class="buttons">
					<NcButton type="primary"
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
				<NcNoteCard type="info">
					Bitte wählen Sie mindestens einen Layer aus. Die Layer werden heruntergeladen und im aktuellen Ordner platziert.
				</NcNoteCard>

				<div class="scrollable-list">
					<div v-for="(layer, index) in layerView.layerNames" :key="index" class="checkbox-container">
						<NcCheckboxRadioSwitch v-model="layerView.layerStatesMap[layer]" type="checkbox">
							{{ layer }}
						</NcCheckboxRadioSwitch>
					</div>
				</div>

				<NcNoteCard v-if="layerView.downloadMessage" :type="layerView.noteType">
					{{ layerView.downloadMessage }}
				</NcNoteCard>

				<div class="buttons spaced-buttons">
					<NcButton @click="showCapabilities = false">
						Back
					</NcButton>
					<NcButton type="primary" :disabled="layerView.isDownloading" @click="download">
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

function initialLinkView() {
	return {
		url: '',
		submitError: '',
	}
}

function initialLayerView() {
	return {
		layerNames: null,
		layerStatesMap: {},
		isDownloading: false,
		downloadMessage: '',
		noteType: 'error',
	}
}

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
			visible: false,
			showCapabilities: false,
			linkView: initialLinkView(),
			layerView: initialLayerView(),
		}
	},

	computed: {
		isValid() {
			return this.linkView.url.trim() !== ''
		},
	},

	methods: {
		open() {
			this.visible = true
		},
		close() {
			this.visible = false
			this.showCapabilities = false
			this.linkView = initialLinkView()
			this.layerView = initialLayerView()
		},

		async submit() {
			this.linkView.submitError = ''
			this.layerView.downloadMessage = ''
			// eslint-disable-next-line no-console
			console.log(`Getting layers from: ${this.linkView.url}`)

			const apiUrl = generateUrl('/ocs/v2.php/apps/wfs_downloader/capabilities')
			const wfsUrl = encodeURIComponent(
				`${this.linkView.url}?service=WFS&request=GetCapabilities`,
			)
			const backendUrl = `${apiUrl}?format=json&url=${wfsUrl}`
			// eslint-disable-next-line no-console
			console.log(`Calling backend: ${backendUrl}`)

			try {
				const response = await fetch(backendUrl)

				if (!response.ok) {
					throw new Error(`Server error: ${response.status}`)
				}
				const rawResp = await response.json()
				// eslint-disable-next-line no-console
				console.log(rawResp)
				const xmlText = rawResp.ocs.data
				const xmlParser = new DOMParser()
				const xmlDoc = xmlParser.parseFromString(xmlText, 'text/xml')

				const parser = new WFSCapabilities()
				const parsedCapabilities = parser.read(xmlDoc.documentElement)
				this.layerView.layerNames = parsedCapabilities.FeatureTypeList.map(
					ft => ft.Name,
				)
				this.layerView.layerStatesMap = Object.fromEntries(
					this.layerView.layerNames.map(name => [name, false]),
				)
				this.showCapabilities = true
			} catch (error) {
				console.error('Error fetching capabilities:', error)
				this.linkView.submitError = 'Fehler beim Abrufen der Layer. Bitte überprüfen Sie den Link.'
			} finally {
				this.layerView.isDownloading = false
			}
		},

		async download() {
			this.layerView.noteType = 'error'
			this.layerView.downloadMessage = ''
			this.layerView.isDownloading = true

			const chosenLayers = Object.entries(this.layerView.layerStatesMap)
				.filter(([, isActive]) => isActive)
				.map(([name]) => name)

			if (chosenLayers.length === 0) {
				this.layerView.downloadMessage = 'Bitte wählen Sie mindestens einen Layer aus.'
				this.layerView.isDownloading = false
				return
			}

			const apiUrl = generateUrl('/ocs/v2.php/apps/wfs_downloader/download')
			// eslint-disable-next-line no-console
			console.log(`Requesting to download layers ${chosenLayers}`)

			try {
				const response = await fetch(apiUrl, {
					method: 'POST',
					headers: {
						'Content-Type': 'application/json',
						Accept: 'application/json',
					},
					body: JSON.stringify({
						url: this.linkView.url,
						dir: decodeURIComponent(
							new URLSearchParams(window.location.search).get('dir') || '/',
						),
						layers: chosenLayers,
					}),
				})

				if (!response.ok) {
					throw new Error(`Server error: ${response.status}`)
				}

				const json = await response.json()
				this.layerView.noteType = 'success'
				this.layerView.downloadMessage = json.download_message
					|| `${chosenLayers.length} Layer werden heruntergeladen. Dies kann etwas dauern, aber Sie können das Fenster schließen.`
			} catch (error) {
				console.error('Download error:', error)
				this.layerView.downloadMessage = 'Ein Fehler ist aufgetreten beim Herunterladen.'
			}
		},
	},
}
</script>

<style scoped>
h2,
.modal-content {
    display: flex;
    flex-direction: column;
    gap: calc(var(--default-grid-baseline) * 4);
    margin: calc(var(--default-grid-baseline) * 4);
}

.scrollable-list {
    max-height: 40rem;
    flex: 1 1 auto;
    overflow-y: auto;
    padding-right: 1rem;
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

.checkbox-label {
    cursor: pointer;
}
</style>
