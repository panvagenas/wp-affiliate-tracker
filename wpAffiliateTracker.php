<?php
/**
 * Plugin Name:	WP Affilate Tracker
 * Plugin URI:
 * Description: Easy track and cloack affiliate links.
 * Version:		0.9
 * Author:		Tobias Ratschiller
 * Author URI:	// TODO Company site
 * License:		GPLv3: see license.txt included in plugin folder.
 * Copyright Tobias Ratschiller <tobias@senzalimiti.com>
 */
global $wpdb;

$table_name = $wpdb->prefix . "WPAT_Affiliates";
define( 'WPAT_AFFI_TABLE', $table_name );

$table_name = $wpdb->prefix . "WPAT_PPC";
define( 'WPAT_PPC_TABLE', $table_name );

// ***************** Load session manager **************************
// let users change the session cookie name
if ( !defined( 'WP_SESSION_COOKIE' ) )
	define( 'WP_SESSION_COOKIE', '_wp_session' );

if ( !class_exists( 'Recursive_ArrayAccess' ) ) {
	require_once ( plugin_dir_path( __FILE__ ) . 'inc/class-recursive-arrayaccess.php' );
}

// Only include the functionality if it's not pre-defined.
if ( !class_exists( 'WP_Session' ) ) {
	require_once ( plugin_dir_path( __FILE__ ) . 'inc/class-wp-session.php' );
	require_once ( plugin_dir_path( __FILE__ ) . 'inc/wp-session.php' );
}
// ***************** ******************* **************************

/**
 * Register plugin scripts and stylesheets
 *
 * @package WordPress
 * @since 0.9
 * @author Panagiotis Vagenas <pan.vagenas@gmail.com>
 */
function wpAffiliateTracker_stylesheet_loader( ) {
	wp_register_script( 'WPAT_qtip', plugins_url( 'inc/js/jquery.qtip.min.js', __FILE__ ) );
	wp_register_script( 'WPAT_dataTables', plugins_url( 'inc/js/jquery.dataTables.min.js', __FILE__ ) );

	wp_register_style( 'WPAT_AdminStyle', plugins_url( 'inc/css/adminStyle.css', __FILE__ ) );
	wp_register_style( 'WPAT_qtipStyle', plugins_url( '/inc/css/jquery.qtip-min.css', __FILE__ ) );
	wp_register_style( 'WPAT_dataTablesStyle', plugins_url( '/inc/css/jquery.dataTables.css', __FILE__ ) );
}
add_action( 'admin_enqueue_scripts', 'wpAffiliateTracker_stylesheet_loader' );

// ***************** Load plugin scripts **************************
require_once plugin_dir_path( __FILE__ ) . 'tracker.php';
$tracker = new WPATtrackerC();

if ( is_admin() ) {
	require_once plugin_dir_path( __FILE__ ) . 'admin.php';
	$settings = new wpAffiliateTrackerSetingsRendererC();
}

// ***************** ******************* **************************

/**
 * Initialize options upon activation
 */
require_once plugin_dir_path( __FILE__ ) . 'activation.php';
register_activation_hook(  __FILE__,  array('activatorC', 'activate') );