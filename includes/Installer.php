<?php
// filepath: includes/Installer.php

namespace SpiderBoxes;

use SpiderBoxes\Database\DatabaseManager;

/**
 * Plugin Installer
 */
class Installer {

	/**
	 * Plugin activation
	 */
	public static function activate(): void {

		// Flush rewrite rules
		flush_rewrite_rules();

		do_action( 'spider_boxes_activated' );
	}

	/**
	 * Plugin deactivation
	 */
	public static function deactivate(): void {

		// Flush rewrite rules
		flush_rewrite_rules();

		do_action( 'spider_boxes_deactivated' );
	}
}
