<?php
class Af_Notifications extends Plugin {
	private PluginHost $host;
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


	/**
	 * @param PluginHost $host
	 *
	 * @return void
	 **/
	function init($host) {
		$this->host = $host;
		$host->add_hook($host::HOOK_PREFS_TAB, $this);
		$host->add_hook($host::HOOK_UNSUBSCRIBE_FEED, $this);

		$host->add_filter_action($this, 'action_js_api_notify', __('JS Notifications API'));
	}


	function get_js() {
		return file_get_contents(__DIR__ . '/init.js');
	}


	function get_prefs_js() {
		return file_get_contents(__DIR__ . '/init_prefs.js');
	}


	/**
	 * @param string $tab
	 * @return void
	 */
	function hook_prefs_tab($tab) {
		if ($tab != 'prefPrefs') return;
		?>

		<div dojoType='dijit.layout.AccordionPane'
			title='<i class="material-icons">extension</i> <?= __('Notification Settings (af_notifications)') ?>'>
			<script type='dojo/method' event='onSelected' args='evt'>
				if (!this.domNode.querySelector('.loading')) {
					return;
				}

				Plugins.Af_Notifications.setPrefNode(this);
				Plugins.Af_Notifications.refreshPrefNodeContent();
			</script>
			<span class='loading'><?= __('Loading, please wait...') ?></span>
		</div>
		<?php
	}


	/**
	 * @param array<string,mixed> $article
	 * @param string $action
	 * @return array<string,mixed> ($article)
	 */
	function hook_article_filter_action($article, $action) {
		switch ($action) {
			case 'action_js_api_notify':
				$this->add_notification($article, 'js_api');
				break;
		}
		return $article;
	}


	function get_notifications(): void {
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


	/**
	 * @param int $feed_id
	 * @param int $owner_uid
	 * @return bool
	 */
	function hook_unsubscribe_feed($feed_id, $owner_uid) {
		return $this->remove_feed_notifications($feed_id);
	}


	/**
	 * @param array<string, mixed> $article
	 */
	private function add_notification(array $article, string $notification_type): void {
		$notifications = $this->get_stored_array('notifications');

		$notification = [
			'type' => $notification_type,
			'article_guid_hashed' => $article['guid_hashed'],
			'article_title' => $article['title'],
			'feed_id' => $article['feed']['id'],
		];

		array_unshift($notifications, $notification);

		// TODO: allow customizing the number of notifications stored?
		$this->host->set($this, 'notifications',
			array_slice($notifications, 0, self::DEFAULT_MAX_NOTIFICATIONS_STORED));
	}


	private function remove_feed_notifications(int $feed_id): bool {
		$notifications = array_values(array_filter(
			$this->get_stored_array('notifications'), function($n) use ($feed_id) {
				return $n['feed_id'] != $feed_id;
			}
		));

		$this->host->set($this, 'notifications', $notifications);

		return true;
	}


	/**
	 * @return array<int, array{'type': string, 'article_guid_hashed': string, 'article_title': string, 'feed_id': int}>
	 */
	private function get_stored_array(string $name): array {
		$tmp = $this->host->get($this, $name);
		return is_array($tmp) ? $tmp : [];
	}
}
