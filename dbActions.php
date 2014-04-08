<?php

/**
 * Object to perform actions in Affi table
 *
 * @since 0.9
 * @author Vagenas Panagiotis <pan.vagenas@gmail.com>
 * @copyright Tobias Ratschiller <tobias@senzalimiti.com>
 */
class dbActionsC {

	/**
	 * Get a single row form Affi table
	 *
	 * @param array $data
	 * @return array Result array
	 * @since 0.9
	 * @author Vagenas Panagiotis <pan.vagenas@gmail.com>
	 */
	function getRowFromAffi( Array $data ) {
		if ( empty( $data ) || !is_array( $data ) ) {
			return NULL;
		}
		global $wpdb;
		$where = '';
		foreach ( $data as $k => $v ) {
			$where .= ' ' . $k . '="' . $v . '" AND';
		}
		$where = substr( $where, 0, -3 );
		return $wpdb->get_row( 'SELECT * FROM ' . WPAT_AFFI_TABLE . ' WHERE ' . $where, ARRAY_A );
	}

	/**
	 * Inserts a single row in Affi table
	 *
	 * @param array $data
	 *        	Assosiative array with data to be insterted
	 * @return boolean or NULL
	 * @since 0.9
	 * @author Vagenas Panagiotis <pan.vagenas@gmail.com>
	 */
	function insertRecToAffi( $data ) {
		if ( empty( $data ) || !is_array( $data ) ) {
			return NULL;
		}
		global $wpdb;
		return $wpdb->insert( WPAT_AFFI_TABLE, $data );
	}

	/**
	 * Updates a record in affiliate table
	 *
	 * @param int $id
	 * @param array $data
	 * @return array or null. Updated record on success, NULL otherwise
	 * @since 0.9
	 * @author Vagenas Panagiotis <pan.vagenas@gmail.com>
	 */
	function updateAffiRec( $id, $data ) {
		if ( empty( $data ) || !is_array( $data ) ) {
			return NULL;
		}
		global $wpdb;

		$old = $this->getRowFromAffi(array('id'=>$id));
		$data['total_clicks'] = (int) $old['total_clicks'];
		$data['date'] =$old['date'];

		$result = $wpdb->update(WPAT_AFFI_TABLE, $data, array('id' => (int) $id) );

		if ($result){
			return $this->getRowFromAffi(array('id'=>$id));
		}
		return FALSE;
	}

	/**
	 * Fetch all data in Affi table
	 *
	 * @return Result array
	 * @since 0.9
	 * @author Vagenas Panagiotis <pan.vagenas@gmail.com>
	 */
	function getAffiData( ) {
		global $wpdb;
		return $wpdb->get_results( 'SELECT * FROM ' . WPAT_AFFI_TABLE, ARRAY_A );
	}

	/**
	 * Delete a record from Affi table
	 *
	 * @param int $id
	 * @return int or false. The number of rows updated, or false on error
	 * @since 0.9
	 * @author Vagenas Panagiotis <pan.vagenas@gmail.com>
	 */
	function deleteRecFromAffi( $id ) {
		if ( !is_int( $id ) ) {
			return FALSE;
		}
		global $wpdb;
		return $wpdb->delete( WPAT_AFFI_TABLE, array (
				'id' => $id
		) );
	}

	/**
	 * Get a single row form PPC table
	 *
	 * @param array $data
	 * @return array Result array
	 * @since 0.9
	 * @author Vagenas Panagiotis <pan.vagenas@gmail.com>
	 */
	function getRowFromPPC( Array $data ) {
		if ( empty( $data ) || !is_array( $data ) ) {
			return NULL;
		}
		global $wpdb;
		$where = '';
		foreach ( $data as $k => $v ) {
			$where .= " " . $k . "='" . $v . "' AND";
		}
		$where = substr( $where, 0, -3 );

		return $wpdb->get_row( 'SELECT * FROM ' . WPAT_PPC_TABLE . ' WHERE ' . $where, ARRAY_A );
	}

	/**
	 * Inserts a single row in PPC table
	 *
	 * @param array $data
	 *        	Assosiative array with data to be insterted
	 * @return boolean or NULL
	 * @since 0.9
	 * @author Vagenas Panagiotis <pan.vagenas@gmail.com>
	 */
	function insertRecToPPC( $data ) {
		if ( empty( $data ) || !is_array( $data ) ) {
			return NULL;
		}
		global $wpdb;
		return $wpdb->insert( WPAT_PPC_TABLE, $data );
	}

	/**
	 * Fetch all data in PPC table
	 *
	 * @return Result array
	 * @since 0.9
	 * @author Vagenas Panagiotis <pan.vagenas@gmail.com>
	 */
	function getPPCData( ) {
		global $wpdb;
		return $wpdb->get_results( 'SELECT * FROM ' . WPAT_PPC_TABLE, ARRAY_A );
	}

	/**
	 * Delete a record from PPC table
	 *
	 * @param int $id
	 * @return int or false. The number of rows updated, or false on error
	 * @since 0.9
	 * @author Vagenas Panagiotis <pan.vagenas@gmail.com>
	 */
	function deleteRecFromPPC( $ppc ) {
		global $wpdb;
		return $wpdb->delete( WPAT_PPC_TABLE, array (
				'ppc' => $ppc
		) );
	}

	/**
	 * Ads clicks to affi table
	 *
	 * @param int $id
	 *        	The record id in affi table
	 * @param number $clicks
	 *        	Optional the number of clicks to add, default is 1
	 * @return bool FALSE on fail, int # of affected rows otherwise
	 * @since 0.9
	 * @author Vagenas Panagiotis <pan.vagenas@gmail.com>
	 */
	function addClick( $id, $clicks = 1 ) {
		global $wpdb;
		$info = $this->getRowFromAffi( array (
				'id' => $id
		) );
		if ( !empty( $info ) ) {
			return $wpdb->update( WPAT_AFFI_TABLE, array (
					'total_clicks' => $info['total_clicks'] + $clicks
			), array (
					'id' => $info['id']
			) );
		}
		return FALSE;
	}

	/**
	 * Add record to PPC array with ppc prefix
	 *
	 * @param array $data
	 *        	Must contain real_keyword, short_link, referer
	 * @return boolean or string. False on dailure, ppc keyword otherwise
	 * @since 0.9
	 * @author Vagenas Panagiotis <pan.vagenas@gmail.com>
	 */
	function addPPC( $data ) {
		if ( !is_array( $data ) || empty( $data ) ) {
			return FALSE;
		}
		global $wpdb;

		$pre = get_option( 'WPATOptPPCPre' );

		$preOption = get_option( 'WPATOptTrID' );

		$pre = empty( $pre ) ? 'ppc' : $pre;

		$preList = $wpdb->get_results( "SELECT ppc FROM " . WPAT_PPC_TABLE . " WHERE ppc LIKE '" . $pre . "%'", ARRAY_A );

		if ( $preOption == 1 ) {
			$preList = $wpdb->get_results( "SELECT ppc FROM " . WPAT_PPC_TABLE . " WHERE ppc LIKE '" . $pre . "%'", ARRAY_A );
			$postFix = empty( $preList ) ? 1 : $this->getIncremental($pre, $preList);
		} else if ( $preOption == 2 ) {
			$postFix = uniqid();
		} else if ( $preOption == 3 && !empty( $data['real_keyword'] ) ) {
			if (is_array($data['real_keyword'])) {
				$realKw = implode('', $data['real_keyword']);
			}
			$kw = strtolower( str_replace( ' ', '', isset($realKw) ? $realKw : $data['real_keyword'] ) );
			$preList = $wpdb->get_results( "SELECT ppc FROM " . WPAT_PPC_TABLE . " WHERE ppc LIKE '" . $pre . $kw . "%'", ARRAY_A );
			$postFix = empty( $preList ) ? $kw : $kw.$this->getIncremental($pre.$kw, $preList);
		} else {
			$postFix = uniqid();
		}

		$result = $this->insertRecToPPC( array (
				'ppc' => $pre . $postFix,
				'real_keyword' => is_array($data['real_keyword']) ? implode(', ', $data['real_keyword']) : $data['real_keyword'],
				'short_link' => $data['short_link'],
				'referer' => $data['referer'],
				'via' => $data['via']
		) );

		return $result ? $pre . $postFix : FALSE;
	}

	/**
	 * Add record to PPC array with seo prefix
	 *
	 * @param array $data
	 *        	Must contain real_keyword, short_link, referer
	 * @return boolean or string. False on dailure, ppc keyword otherwise
	 * @since 0.9
	 * @author Vagenas Panagiotis <pan.vagenas@gmail.com>
	 */
	function addSEO( $data ) {
		if ( !is_array( $data ) || empty( $data ) ) {
			return FALSE;
		}
		global $wpdb;

		$pre = get_option( 'WPATOptOrgSrchPre' );

		$preOption = get_option( 'WPATOptTrID' );

		$pre = empty( $pre ) ? 'seo' : $pre;

		$preList = $wpdb->get_results( "SELECT ppc FROM " . WPAT_PPC_TABLE . " WHERE ppc LIKE '" . $pre . "%'", ARRAY_A );

		if ( $preOption == 1 ) {
			$preList = $wpdb->get_results( "SELECT ppc FROM " . WPAT_PPC_TABLE . " WHERE ppc LIKE '" . $pre . "%'", ARRAY_A );
			$postFix = empty( $preList ) ? 1 : $this->getIncremental($pre, $preList);
		} else if ( $preOption == 2 ) {
			$postFix = uniqid();
		} else if ( $preOption == 3 && !empty( $data['real_keyword'] ) ) {
			if (is_array($data['real_keyword'])) {
				$realKw = implode('', $data['real_keyword']);
			}
			$kw = strtolower( str_replace( ' ', '', isset($realKw) ? $realKw : $data['real_keyword'] ) );
			$preList = $wpdb->get_results( "SELECT ppc FROM " . WPAT_PPC_TABLE . " WHERE ppc LIKE '" . $pre . $kw . "%'", ARRAY_A );
			$postFix = empty( $preList ) ? $kw : $this->getIncremental($pre.$kw, $preList);
		} else {
			$postFix = uniqid();
		}

		$result = $this->insertRecToPPC( array (
				'ppc' => $pre . $postFix,
				'real_keyword' => is_array($data['real_keyword']) ? implode(', ', $data['real_keyword']) : $data['real_keyword'],
				'short_link' => $data['short_link'],
				'referer' => $data['referer'],
				'via' => $data['via']
		) );

		return $result ? $pre . $postFix : FALSE;
	}
	/**
	 * Returns an incremental number
	 * @param string $preFix prefix of string
	 * @param array $arrayOfStrings array of strings containing used ppcs
	 * @return number the number to use for the next ppc
	 * @author Vagenas Panagiotis <pan.vagenas@gmail.com>
	 * @since 0.9
	 */
	private function getIncremental($preFix, $arrayOfStrings) {
		$numAr = array ();
		foreach ( $arrayOfStrings as $k => $v ) {
			$sub = substr( $v['ppc'], strlen( $preFix ) - strlen( $v['ppc'] ) );
			if (preg_match('/[a-zA-z]/', $sub) === 0) {
				$numAr[$k] = ( int ) $sub;
			}
		}
		sort( $numAr );
		return empty($numAr) ? 1 : end( $numAr ) + 1;
	}
}