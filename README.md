# Notifications Tiny Tiny RSS Plugin

## Overview
This plugin adds a filter action that results in notifications.

## Installation
### Git
1. Clone the repo to **af_notifications** in your tt-rss **plugins.local** directory:

   `git clone https://git.tt-rss.org/fox/ttrss-af-notifications af_notifications`

2. Enable the plugin @ Preferences → Plugins
3. Click the link @ Preferences → Preferences → Notification Settings (af_notifications) to authorize
use of the [JavaScript Notification API](https://developer.mozilla.org/docs/Web/API/Notification).
4. Configure [tt-rss Filters](https://tt-rss.org/wiki/ContentFilters) with an `Invoke plugin` that invokes `Af_Notifications: JS Notifications API`.
