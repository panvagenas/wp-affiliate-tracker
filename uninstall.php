<?php
/**
 * This is the plugin uninstall script.
 * If user choose to remove data dyring uninstall proccess then plugin option and tracking data will be deleted too.
 * @copyright Tobias Ratschiller <tobias@senzalimiti.com>
 */

/* #? Check that this comes from admin panel */
if ( !defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit( 'It Seems We Have a Security Issue!' );
}

$uninstall = new uninstalC();
$uninstall->uninstall();


class uninstalC {

	function uninstall( $network_wide ) {
		if ( function_exists( 'is_multisite' ) && is_multisite() ) {

			if ( $network_wide  ) {

				// Get all blog ids
				$blog_ids = self::get_blog_ids();

				foreach ( $blog_ids as $blog_id ) {

					switch_to_blog( $blog_id );
					self::single_uninstall();
				}

				restore_current_blog();

			} else {
				self::single_uninstall();
			}

		} else {
			self::single_uninstall();
		}
	}

	private static function single_uninstall() {
		// Drop database tables
		uninstalC::removeDBTable();

		// Delete options
		uninstalC::removeOptions();
	}

	private static function get_blog_ids() {

		global $wpdb;

		// get an array of blog ids
		$sql = "SELECT blog_id FROM $wpdb->blogs
		WHERE archived = '0' AND spam = '0'
		AND deleted = '0'";

		return $wpdb->get_col( $sql );

	}

	private static function removeOptions( ) {
		// Delete Plugin Options
		delete_option( 'WPATOptTrackVars' );
		delete_option( 'WPATOptPPCPre' );
		delete_option( 'WPATOptOrgSrchPre' );
		delete_option( 'WPATOptTrID' );
	}

	private static function removeDBTable( ) {
		global $wpdb;
		$affi_table_name = $wpdb->prefix . "WPAT_Affiliates";
		$ppc_table_name = $wpdb->prefix . "WPAT_PPC";
		// Delete plugin data tables
		$wpdb->query( 'DROP TABLE IF_EXISTS ' . $affi_table_name );
		$wpdb->query( 'DROP TABLE IF_EXISTS ' . $ppc_table_name );
	}
}