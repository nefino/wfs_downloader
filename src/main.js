import Icon from '@mdi/svg/svg/download-network.svg'
import '@nextcloud/dialogs/style.css'
import { addNewFileMenuEntry } from '@nextcloud/files'
import Vue from 'vue'
import WFSModalView from './components/WFSModalView.vue'

const vueMountElement = document.createElement('div')
document.body.append(vueMountElement)

const vueMount = new Vue({
	el: vueMountElement,
	render: h => h(WFSModalView),
})

addNewFileMenuEntry({
	id: 'wfs_downloader',
	displayName: 'Layer von WFS herunterladen',
	iconSvgInline: Icon,
	order: -1,

	// Only display in folders where this user has permission to create files
	// if: context => (context.permissions & Permission.CREATE) !== 0,

	async handler(context, content) {
		vueMount.$children[0].open(context)
	},

})
