<?php
class Af_Notifications extends Plugin {
	private $host;
	private const DEFAULT_MAX_NOTIFICATIONS_STORED = 20;


	function api_version() {
		return 2;
	}


	function about() {
		return [
			null, // version
			'Adds a filter action to receive JavaScript-based notifications.', // description
			'wn', // author
			false, // is system
			'https://www.github.com/supahgreg/ttrss-af-notifications', // more info URL
		];
	}


	function init(PluginHost $host) {
		$this->host = $host;

		$host->add_hook($host::HOOK_PREFS_TAB, $this);
		$host->add_hook($host::HOOK_UNSUBSCRIBE_FEED, $this);

		$host->add_filter_action($this, 'action_js_api_notify', __('JS Notifications API'));
	}


	function get_js() {
		return file_get_contents(__DIR__ . '/init.js');
	}


	function hook_prefs_tab($args) {
		if ($args != 'prefPrefs') return;
		?>

		<div dojoType='dijit.layout.AccordionPane'
			title='<i class="material-icons">extension</i> <?= __('Notification Settings (af_notifications)') ?>'>
			<script type='dojo/method' event='onSelected' args='evt'>
				if (!this.domNode.querySelector('.loading')) {
					return;
				}

				// TODO: why isn't "Af_Notifications" accessible when in a separate script block?
				window.Af_Notifications = {
					_node: null,
					setPrefNode: (node) => { Af_Notifications._node = node },

					refreshPrefNodeContent: () => {
						let content = '';
						switch (Notification.permission) {
							case 'granted':
								content += '<?= format_notice(__('You have accepted receiving notifications.  Add a filter action in Filters to get started!')) ?>';
								break;
							case 'denied':
								content += '<?= format_warning(__('Receiving notifications from this site has been denied.  ' .
									'Remove the explicit block in your browser and click the link below to continue.')) ?>';
							default:
								content += '<?= format_notice('<a href="#" onclick="Af_Notifications.requestPermission(); return false">' .
									__('Click here to accept receiving notifications from this site.') .
									'</a>') ?>';
						}
						Af_Notifications._node.attr('content', content);
					},

					requestPermission: () => {
						Notification.requestPermission((permission) => {
							Af_Notifications.refreshPrefNodeContent();
						});
					},
				};

				Af_Notifications.setPrefNode(this);
				Af_Notifications.refreshPrefNodeContent();
			</script>
			<span class='loading'><?= __('Loading, please wait...') ?></span>
		</div>
		<?php
	}


	function hook_article_filter_action(array $article, string $action) {
		switch ($action) {
			case 'action_js_api_notify':
				$this->add_notification($article, 'js_api');
				break;
		}
		return $article;
	}


	function get_notifications() {
		$feed_title_cache = [];
		$notifications = $this->get_stored_array('notifications');

		if (count($notifications)) {
			$this->host->set($this, 'notifications', []);
		}

		foreach ($notifications as &$notification) {
			$feed_id = $notification['feed_id'];
			if (!array_key_exists($feed_id, $feed_title_cache)) {
				$feed = ORM::for_table('ttrss_feeds')
					->select('title')
					->find_one($feed_id);

				$feed_title_cache[$feed_id] = $feed ? $feed->title : __('Unsubscribed Feed');
			}
			$notification['feed_title'] = $feed_title_cache[$feed_id];
		}

		print json_encode([
			'notifications' => $notifications,
		]);
	}


	function hook_unsubscribe_feed($feed_id, $owner_uid) {
		$this->remove_feed_notifications($feed_id);
	}


	private function add_notification(array $article, string $notification_type) {
		$notifications = $this->get_stored_array('notifications');

		// TODO: check for duplicates
		$notification = [
			'type' => $notification_type,
			'article_guid_hashed' => $article['guid_hashed'],
			'article_title' => $article['title'],
			'feed_id' => $article['feed']['id'],
		];

		array_unshift($notifications, $notification);

		// TODO: allow customizing the number of notifications stored
		$this->host->set($this, 'notifications',
			array_slice($notifications, 0, self::DEFAULT_MAX_NOTIFICATIONS_STORED));
	}


	private function remove_feed_notifications($feed_id) {
		$notifications = array_values(array_filter(
			$this->get_stored_array('notifications'), function($n) use ($feed_id) {
				return $n['feed_id'] != $feed_id;
			}
		));

		$this->host->set($this, 'notifications', $notifications);
	}


	private function get_stored_array(string $name) {
		$tmp = $this->host->get($this, $name);
		return is_array($tmp) ? $tmp : [];
	}
}
