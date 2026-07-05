<?php
/**
 * Uninstall routine for Betting Bookings.
 *
 * Runs only when the user deletes the plugin from the WordPress admin.
 * Removes the custom table and options this plugin created.
 *
 * @package Betting_Bookings
 */

// Exit if uninstall is not called by WordPress.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

global $wpdb;

// Drop the plugin's custom table.
$table_name = $wpdb->prefix . 'betting_bookings';
$wpdb->query( "DROP TABLE IF EXISTS `{$table_name}`" ); // phpcs:ignore WordPress.DB.PreparedSQL

// Remove the plugin's options.
delete_option( 'betting_bookings_currency' );
delete_option( 'betting_bookings_db_version' );
