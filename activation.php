<?php

/**
 * Plugin activate class
 * @author Vagenas Panagiotis <pan.vagenas@gmail.com>
 * @since 0.9
 * @copyright Tobias Ratschiller <tobias@senzalimiti.com>
 */
class activatorC {

	public function __construct(){
		// Activate plugin when new blog is added
		add_action( 'wpmu_new_blog', array( $this, 'activate_new_site' ) );
	}

	function activate( $network_wide ) {
		if ( function_exists( 'is_multisite' ) && is_multisite() ) {

			if ( $network_wide  ) {

				// Get all blog ids
				$blog_ids = self::get_blog_ids();

				foreach ( $blog_ids as $blog_id ) {

					switch_to_blog( $blog_id );
					self::single_activate();
				}

				restore_current_blog();

			} else {
				self::single_activate();
			}

		} else {
			self::single_activate();
		}
	}

	private static function single_activate() {
		// Add the database tables
		activatorC::addDBTable();

		// Add options
		activatorC::addOptions();
	}

	private static function get_blog_ids() {

		global $wpdb;

		// get an array of blog ids
		$sql = "SELECT blog_id FROM $wpdb->blogs
		WHERE archived = '0' AND spam = '0'
		AND deleted = '0'";

		return $wpdb->get_col( $sql );

	}

	public function activate_new_site( $blog_id ) {

		if ( 1 !== did_action( 'wpmu_new_blog' ) ) {
			return;
		}

		switch_to_blog( $blog_id );
		self::single_activate();
		restore_current_blog();

	}

	private static function addOptions( ) {
		add_option( 'WPATOptTrackVars', array () );
		add_option( 'WPATOptPPCPre', 'ppc' );
		add_option( 'WPATOptOrgSrchPre', 'seo' );
		add_option( 'WPATOptTrID', 1 );
	}

	private static function addDBTable( ) {
		global $wpdb;
		require_once ( ABSPATH . 'wp-admin/includes/upgrade.php' );

		$affi_table_name = $wpdb->prefix . "WPAT_Affiliates";
		$ppc_table_name = $wpdb->prefix . "WPAT_PPC";

		$sqlAffi = "CREATE TABLE IF NOT EXISTS " . $affi_table_name . " (
			id mediumint(9) NOT NULL AUTO_INCREMENT,
			short_link VARCHAR(200) NOT NULL,
			affi_url VARCHAR(200) DEFAULT '' NOT NULL,
			total_clicks mediumint(9) DEFAULT 0 NOT NULL,
			date date DEFAULT '0000-00-00' NOT NULL,
			UNIQUE KEY id (id),
			PRIMARY KEY (id)
		);";

		$sqlPPC = "CREATE TABLE IF NOT EXISTS " . $ppc_table_name . " (
			ppc VARCHAR(20) NOT NULL UNIQUE,
			real_keyword VARCHAR(200) NOT NULL,
			short_link VARCHAR(200) NOT NULL,
			referer VARCHAR(50) NOT NULL,
			via TEXT NOT NULL,
			time TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (ppc)
		);";

		dbDelta( $sqlAffi );
		dbDelta( $sqlPPC );
	}
}