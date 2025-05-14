<template>
	<NcModal v-if="visible" name="Layer über Web Feature Service herunterladen" @close="close">
		<h2>Upload by link</h2>

		<div class="modal-content">
			<NcTextField
				v-model="url"
				label="Link"
				:label-visible="true"
				placeholder="https://geodienste.sachsen.de/aaa/public_alkis/vereinf/wfs" />

			<NcNoteCard type="info">
				<p>Some websites provide a checksum in addition to the file. This is used after the transfer to verify that the file is not corrupted.</p>
			</NcNoteCard>

			<div class="buttons">
				<NcButton
					type="primary"
					native-type="submit"
					:disabled="!isValid"
					@click="submit">
					<template #icon>
						<NcIconSvgWrapper :svg="TransferSvg" />
					</template>
					Upload
				</NcButton>
			</div>
		</div>
	</NcModal>
</template>

<script>
import {
	NcButton,
	NcIconSvgWrapper,
	NcModal,
	NcNoteCard,
	NcTextField,
} from '@nextcloud/vue'
import WFSCapabilities from 'ol-wfs-capabilities'
import { generateUrl } from '@nextcloud/router'

import TransferSvg from '@mdi/svg/svg/cloud-upload.svg'

export default {
	components: {
		NcButton,
		NcIconSvgWrapper,
		NcModal,
		NcNoteCard,
		NcTextField,
	},

	data() {
		return {
			TransferSvg,
			url: '',
			chosenName: '',
			chosenExtension: '',
			hashAlgo: null,
			hash: '',
			currentDirectory: null,
			visible: false,
		}
	},

	computed: {
		isValid() {
			return (
				this.url.trim() !== ''
			)
		},
	},

	methods: {
		open(context) {
			this.url = ''
			this.chosenName = ''
			this.chosenExtension = ''
			this.hashAlgo = null
			this.hash = ''
			this.currentDirectory = context.path
			this.visible = true
		},

		close() {
			this.visible = false
		},
		loadExample(file) {
			const xhttp = new XMLHttpRequest()
			xhttp.open('GET', file, false)
			xhttp.send()

			const xml = xhttp.responseText
			return xml
		},
		async submit() {
			// eslint-disable-next-line no-console
			// eslint-disable-next-line no-console
			console.log(`Getting layers from ${this.url}`)
			// this.close()

			const backendUrl = generateUrl('/apps/wfsdownloader/capabilities') + '?url=' + encodeURIComponent(this.url)

			try {
				const response = await fetch(backendUrl)

				if (!response.ok) {
					throw new Error(`Server error: ${response.status}`)
				}

				const xmlText = await response.text()
				// eslint-disable-next-line no-console
				console.log('Capabilities:', xmlText)
				// Optionally: parse XML, update state, etc.

			} catch (error) {
				console.error('Error fetching capabilities:', error)
			}

			// eslint-disable-next-line no-unused-vars
			const parser = new WFSCapabilities()
			// const data = this.loadExample(`${this.url}?service=WFS&request=GetCapabilities`)
			// const parsedCapabilities = parser.read(data)
			// // eslint-disable-next-line no-console
			// console.log(parsedCapabilities)
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
}
</style>
