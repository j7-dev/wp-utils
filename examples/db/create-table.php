<?php

/**
 * Activate
 *
 * @return void
 */
public function activate(): void {
	$this->create_database_table();
}

private function create_database_table() {
	global $wpdb;

	$table_name = $wpdb->prefix . self::LOG_TABLE_NAME;

	$charset_collate = $wpdb->get_charset_collate();

	$sql = "CREATE TABLE $table_name (
							id mediumint(9) NOT NULL AUTO_INCREMENT,
							title text NOT NULL,
							type tinytext NOT NULL,
							user_id bigint(20) NOT NULL,
							point_slug tinytext NOT NULL,
							point_changed tinytext NOT NULL,
							new_balance tinytext NOT NULL,
							modified_by bigint(20) NOT NULL,
							date datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
							PRIMARY KEY  (id)
					) $charset_collate;";

	require_once ABSPATH . 'wp-admin/includes/upgrade.php';
	$result = \dbDelta( $sql );
}
