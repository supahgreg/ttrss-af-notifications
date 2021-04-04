/* global __, Plugins */

Plugins.Af_Notifications = {
	_node: null,
	setPrefNode: (node) => { Plugins.Af_Notifications._node = node },

	refreshPrefNodeContent: () => {
		let content = '';
		switch (Notification.permission) {
			case 'granted':
				content += `<div class='alert alert-info'>${__('You have accepted receiving notifications.  Add a filter action in Filters to get started!')}</div>`;
				break;
			case 'denied':
				content += `<div class='alert'>${__('Receiving notifications from this site has been denied.  ' +
					'Remove the explicit block in your browser and click the link below to continue.')}</div>`;
			// eslint-disable-next-line no-fallthrough
			default:
				content += '<div class="alert alert-info"><a href="#" onclick="Plugins.Af_Notifications.requestPermission(); return false">' +
					__('Click here to accept receiving notifications from this site.') +
					'</a></div>';
		}
		Plugins.Af_Notifications._node.attr('content', content);
	},

	requestPermission: () => {
		Notification.requestPermission(() => {
			Plugins.Af_Notifications.refreshPrefNodeContent();
		});
	},
};
