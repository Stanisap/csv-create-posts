<?php
/*
Plugin Name: Create posts of blog from CSV files
Plugin URI: https://github.com/Stanisap/
Description: Imports post names, contents of posts, categories and data, from simple csv file.
Author: Stanislav Polushin
Author URI: https://github.com/Stanisap
Text Domain: scv-create-posts
Version: 1.0
*/

if ( !defined('WP_LOAD_IMPORTERS') )
	return;

// Load Importer API
require_once ABSPATH . 'wp-admin/includes/import.php';

if ( !class_exists( 'WP_Importer' ) ) {
	$class_wp_importer = ABSPATH . 'wp-admin/includes/class-wp-importer.php';
	if ( file_exists( $class_wp_importer ) )
		require_once $class_wp_importer;
}

// Load Helpers
require dirname( __FILE__ ) . '/classes/CSVImporter.php';
require dirname( __FILE__ ) . '/classes/CSVFileWorker.php';
require dirname( __FILE__ ) . '/classes/CSVImportPost.php';

/**
 * CSV Importer
 *
 * @package WordPress
 * @subpackage Importer
 */
if ( class_exists( 'WP_Importer' ) ) {
	
// Initialize
	function csv_cp_init() {
		load_plugin_textdomain( 'csv-create-posts', false, dirname( plugin_basename(__FILE__) ) . '/languages' );

		$csv_cp_importer = new CSVImporter();
		register_importer('csv', __('CSV', 'csv-create-posts'), __('Imports post names, contents of posts, categories and data, from csv file.', 'csv-create-posts'), array ($csv_cp_importer, 'dispatch'));
	}
	add_action( 'plugins_loaded', 'csv_cp_init' );

//	function csv_cp_enqueue($hook) {
//		if ( 'admin.php' != $hook ) {
//			return;
//		}
//
//		wp_enqueue_script( 'csv_cp_admin_script', plugin_dir_url( __FILE__ ) . 'assets/main.js', array(), false, true );
//	}
//	add_action( 'admin_enqueue_scripts', 'csv_cp_enqueue' );

} // class_exists( 'WP_Importer' )

