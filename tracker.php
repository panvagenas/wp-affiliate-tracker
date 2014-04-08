<?php

/**
 * Tracker
 * @author Vagenas Panagiotis <pan.vagenas@gmail.com>
 * @since 0.9
 * @copyright Tobias Ratschiller <tobias@senzalimiti.com>
 */
class WPATtrackerC {

	private $trackingVars = array();

	/**
	 * Constructor
	 *
	 * @author Vagenas Panagiotis <pan.vagenas@gmail.com>
	 * @since 0.9
	 */
	function __construct( ) {
		add_action( 'init', array (
				$this,
				'tracker'
		) );

		require_once plugin_dir_path( __FILE__ ) . 'dbActions.php';
	}

	/**
	 * Tracking logic
	 *
	 * @since 0.9
	 * @author Vagenas Panagiotis <pan.vagenas@gmail.com>
	 */
	function tracker( ) {
		if ( is_admin() ) {
			return;
		}

		$db = new dbActionsC();

		$wp_session = WP_Session::get_instance();

		// set session referer
		if ( !isset( $wp_session['referer'] ) && isset( $_SERVER['HTTP_REFERER'] ) ) {
			$wp_session['referer'] = $_SERVER['HTTP_REFERER'];
		} else if ( !isset( $wp_session['referer'] ) ) {
			$wp_session['referer'] = '';
		}

		if ( !isset( $wp_session['referer_name'] ) ) {
			$wp_session['referer_name'] = $this->getRefererName( isset( $_SERVER['HTTP_REFERER'] ) ? $_SERVER['HTTP_REFERER'] : '' );
		}

		$refererName = $this->getRefererName( isset( $_SERVER['HTTP_REFERER'] ) ? $_SERVER['HTTP_REFERER'] : '' );
		$request = $this->getRequestString();

		// Add request to request array
		if ( isset( $wp_session['request'] ) ) {
			$push = unserialize( $wp_session['request'] );
			$cut = strpos($request, '?') ? strpos($request, '?') : strlen($request);
			array_push( $push, substr($request, 0, $cut) );
			$wp_session['request'] = serialize( $push );
		} else {
			$cut = strpos($request, '?') ? strpos($request, '?') : strlen($request);
			$wp_session['request'] = $request ? serialize( array (
					substr($request, 0, $cut)
			) ) : array ();
		}

		if ( $refererName && $refererName !== 'local' && $refererName !== 'unknown' ) { // We have a referer and it is not local
		    // Check to see if we have a valid keyword
			$parsedReq = $this->parseSearchQuery( $request );
			$ckdKw = $this->checkForKeyword( $parsedReq );
			if ( $ckdKw ) {
				// We have a valid keyword so it must be a paid request
				$wp_session['paid'] = TRUE;
				$wp_session['real_keyword'] = implode(', ', $ckdKw);
				$wp_session['query'] = $ckdKw;
				// Check content to define if it includes shortlinks
				// Change shortlinks to include keyword in get param
				$this->kw = $this->getArgsString($ckdKw);
				add_filter( 'the_content', array (
						$this,
						'modConShortLinks'
				) );
			}
		}
			// check to see if it is a shortlink
			$shortLink = $this->isShortlink();

			if ( $shortLink ) {
				if ($this->is_bot()) {
					wp_redirect( home_url() ); exit;
				}

				$this->trackingVars = get_option('WPATOptTrackVars');

				// we have a shortlink
				if ( isset( $wp_session['paid'] ) && $wp_session['paid'] ) {
					// We have a click
					$query = $wp_session['query']->toArray();
					$ppc = array();

					$ppc['ppc'] = $db->addPPC( array (
							'short_link' => $shortLink['short_link'],
							'referer' => $wp_session['referer_name'],
							'real_keyword' => $query,
							'via' => $wp_session['request']
					) );

					$ppc['keywords'] = $query;

					// add click
					$db->addClick( $shortLink['id'] );
					// set redirect url
					$redir = $this->getAffiRedirUrl($shortLink['affi_url'], $ppc); //TODO $shortLink['affi_url'] . $ppc;
				} else {
					// We have a click
					$keyWord = $this->getKeywords( $wp_session['referer'] );

					$seo['ppc'] = $db->addSEO( array (
							'short_link' => $shortLink['short_link'],
							'referer' => $wp_session['referer_name'],
							'real_keyword' => ( empty( $keyWord ) || !$keyWord ) ? 'not provided' : $keyWord,
							'via' => $wp_session['request']
					) );
					$seo['keywords'] = $keyWord;
					// add click
					$db->addClick( $shortLink['id'] );
					// set redirect url
					$redir = $this->getAffiRedirUrl($shortLink['affi_url'], $seo);
				}
				// clear session
				$wp_session->reset();
				// redirect
				wp_redirect( $redir );
				exit();
			}
	}
	/**
	 * Formats and returns the affiliate url
	 * @param string $affiUrl
	 * @param string $incomingQuery
	 * @return string The affi url to redirect
	 * @author Vagenas Panagiotis <pan.vagenas@gmail.com>
	 * @since 0.9
	 */
	private function getAffiRedirUrl($affiUrl, $incomingQuery) {
		if (empty($incomingQuery['ppc'])){
			return $affiUrl;
		}
		parse_str(parse_url($affiUrl, PHP_URL_QUERY), $affiQuery);

		if(!$affiQuery){
			return $affiUrl;
		}

		foreach ($affiQuery as $k => $v){
			$ppc = str_replace(']', '', str_replace('[', '', $v));

			if (isset($incomingQuery['keywords'][$ppc])) {
				$affiQuery[$k] = $incomingQuery['keywords'][$ppc];
			} else {
				$affiQuery[$k] = '';
			}
		}
		if (isset($affiQuery['tid'])) {
			$affiQuery['tid'] = $incomingQuery['ppc'];
		}

		$queryString = http_build_query($affiQuery);

		return substr($affiUrl, 0, strpos($affiUrl, '?')+1) . $queryString;
	}

	private function getArgsString($queryArray) {
		if (empty($queryArray) || !is_array($queryArray)) {
			return '';
		}
		$out = '';
		foreach ($queryArray as $k => $v){
			$out .= $k . '=' . $v . '&';
		}
		return substr($out, 0, -1);
	}

	/**
	 * Checks for shortlinks in content and modifieds the in order to include get params
	 *
	 * @param string $con
	 *        	The post default content
	 * @return string
	 * @since 0.9
	 * @author Vagenas Panagiotis <pan.vagenas@gmail.com>
	 */
	function modConShortLinks( $con ) {
		$db = new dbActionsC();
		$shortLinks = $db->getAffiData();
		if ( empty( $shortLinks ) || !$shortLinks || !isset( $this->kw ) || empty( $this->kw ) ) {
			return $con;
		}
		foreach ( $shortLinks as $k => $v ) {
			$con = str_replace( $v['short_link'], $v['short_link'] . '?' . $this->kw, $con );
		}
		return $con;
	}

	/**
	 * Get the referer string from server
	 *
	 * @return string $_SERVER['HTTP_REFERER'] if is set, empty string otherwise
	 * @since 0.9
	 * @author Vagenas Panagiotis <pan.vagenas@gmail.com>
	 */
	private function getRequestString( ) {
		return isset( $_SERVER['REQUEST_URI'] ) ? urldecode( $_SERVER['REQUEST_URI'] ) : '';
	}

	/**
	 * Get the referer
	 *
	 * @param string $refString
	 *        	The referer string ( $_SERVER [ 'HTTP_REFERER' ] like )
	 * @return string or boolean. The referer if found (local for local navigation) or false if ref not found
	 * @since 0.9
	 * @author Vagenas Panagiotis <pan.vagenas@gmail.com>
	 */
	private function getRefererName( $refString ) {
		if ( empty( $refString ) ) {
			return 'unknown';
		}
		$refString = urldecode( $refString );

		if ( strpos( $refString, "google" ) )
			return 'google';
		if ( strpos( $refString, "yahoo" ) )
			return 'yahoo';
		if ( strpos( $refString, "bing" ) )
			return 'bing';
		if ( strpos( $refString, "msn" ) && strpos( $refString, "results" ) )
			return 'msn';
		if ( is_int( strpos( $refString, site_url() ) ) )
			return 'local';

		$parsed = parse_url($refString);

		return isset($parsed['host']) ? $parsed['host'] : $refString;
	}

	/**
	 * Parses a string and returns the PHP_URL_QUERY vars as assosiative array
	 *
	 * @param string $request
	 * @return array An assosiative array containing the search vars
	 * @since 0.9
	 * @author Vagenas Panagiotis <pan.vagenas@gmail.com>
	 */
	private function parseSearchQuery( $request ) {
		parse_str( parse_url( $request, PHP_URL_QUERY ), $out );
		return $out;
	}

	/**
	 * Gets the referer string as provided form $_SERVER['HTTP_REFERER'] var
	 *
	 * @return string $_SERVER['HTTP_REFERER'] if isset, empty string otherwise
	 * @since 0.9
	 * @author Vagenas Panagiotis <pan.vagenas@gmail.com>
	 */
	private function getRefererString( ) {
		return isset( $_SERVER['HTTP_REFERER'] ) ? $_SERVER['HTTP_REFERER'] : '';
	}

	/**
	 * Checks if specified keywords are present in search query
	 *
	 * @param array $searchQuery
	 *        	The array containg the PHP_URL_QUERY vars
	 * @return boolean or array. False if no keyword found, an array containing the keyword and values otherwise ($keyword => $value).
	 * @since 0.9
	 * @author Vagenas Panagiotis <pan.vagenas@gmail.com>
	 */
	private function checkForKeyword( $searchQuery ) {
		$kw = get_option( 'WPATOptTrackVars' );

		if ( empty( $searchQuery ) || !is_array( $searchQuery ) || $kw === FALSE ) {
			return FALSE;
		}
		$out = array();
		foreach ( $searchQuery as $k => $v ) {
			if ( in_array( $k, $kw ) ) {
				$out[$k] = $v;
			}
		}
		return empty($out) ? FALSE : $out;
	}

	/**
	 * Checks if the requsted URL is a shortlink
	 *
	 * @return FALSE if not a shortlink, the shortlink info otherwise
	 * @since 0.9
	 * @author Vagenas Panagiotis <pan.vagenas@gmail.com>
	 */
	private function isShortlink( ) {
		global $wp;

		$path = parse_url( home_url() . $_SERVER['REQUEST_URI'], PHP_URL_PATH );
		$pathFragments = explode( '/', $path );
		$end = end( $pathFragments );
		$end = empty( $end ) ? $pathFragments[count( $pathFragments ) - 2] : $end;
		$db = new dbActionsC();
		$shortLinkInfo = $db->getRowFromAffi( array (
				'short_link' => $end
		) );
		return empty( $shortLinkInfo ) ? FALSE : $shortLinkInfo;
	}

	/**
	 * Searches for the referer and returns the search term if found
	 *
	 * @param string $referer
	 *        	A $_SERVER [ 'HTTP_REFERER' ] like string
	 * @return string or boolean. The keyword if found, false otherwise
	 * @since 0.9
	 * @author Vagenas Panagiotis <pan.vagenas@gmail.com>
	 */
	private function getKeywords( $ref ) {
		$referer = parse_url( $ref );
		$host = $referer['host'];
		$referer = $referer['query'];

		if ( strstr( $host, 'google' ) ) {
			$match = preg_match( '/&q=([a-zA-Z0-9+-]+)/', $referer, $output );
			$querystring = $output[0];
			$querystring = str_replace( '&q=', '', $querystring );
			$querystring = str_replace( '+', ' ', $querystring );
			return $querystring;
		} elseif ( strstr( $host, 'yahoo' ) ) {
			$match = preg_match( '/p=([a-zA-Z0-9+-]+)/', $referer, $output );
			$querystring = $output[0];
			$querystring = str_replace( 'p=', '', $querystring );
			$querystring = str_replace( '+', ' ', $querystring );
			return $querystring;
		} elseif ( strstr( $host, 'msn' ) ) {
			$match = preg_match( '/q=([a-zA-Z0-9+-]+)/', $referer, $output );
			$querystring = $output[0];
			$querystring = str_replace( 'q=', '', $querystring );
			$querystring = str_replace( '+', ' ', $querystring );
			return $querystring;
		} elseif ( strstr( $host, 'bing' ) ) {
			$match = preg_match( '|q=([^&]+)|is', $referer, $output );
			$querystring = $output[0];
			$querystring = str_replace( 'q=', '', $querystring );
			$querystring = str_replace( '+', ' ', $querystring );
			return $querystring;
		} else {
			return FALSE;
		}
	}

	/**
	 * Checks if user agent is a bot
	 * @return boolean True if agent is a bot
	 * @author Vagenas Panagiotis <pan.vagenas@gmail.com>
	 * @since 0.9
	 */
	private function is_bot( ) {
		$spiders = array (
				"abot",
				"dbot",
				"ebot",
				"hbot",
				"kbot",
				"lbot",
				"mbot",
				"nbot",
				"obot",
				"pbot",
				"rbot",
				"sbot",
				"tbot",
				"vbot",
				"ybot",
				"zbot",
				"bot.",
				"bot/",
				"_bot",
				".bot",
				"/bot",
				"-bot",
				":bot",
				"(bot",
				"crawl",
				"slurp",
				"spider",
				"seek",
				"accoona",
				"acoon",
				"adressendeutschland",
				"ah-ha.com",
				"ahoy",
				"altavista",
				"ananzi",
				"anthill",
				"appie",
				"arachnophilia",
				"arale",
				"araneo",
				"aranha",
				"architext",
				"aretha",
				"arks",
				"asterias",
				"atlocal",
				"atn",
				"atomz",
				"augurfind",
				"backrub",
				"bannana_bot",
				"baypup",
				"bdfetch",
				"big brother",
				"biglotron",
				"bjaaland",
				"blackwidow",
				"blaiz",
				"blog",
				"blo.",
				"bloodhound",
				"boitho",
				"booch",
				"bradley",
				"butterfly",
				"calif",
				"cassandra",
				"ccubee",
				"cfetch",
				"charlotte",
				"churl",
				"cienciaficcion",
				"cmc",
				"collective",
				"comagent",
				"combine",
				"computingsite",
				"csci",
				"curl",
				"cusco",
				"daumoa",
				"deepindex",
				"delorie",
				"depspid",
				"deweb",
				"die blinde kuh",
				"digger",
				"ditto",
				"dmoz",
				"docomo",
				"download express",
				"dtaagent",
				"dwcp",
				"ebiness",
				"ebingbong",
				"e-collector",
				"ejupiter",
				"emacs-w3 search engine",
				"esther",
				"evliya celebi",
				"ezresult",
				"falcon",
				"felix ide",
				"ferret",
				"fetchrover",
				"fido",
				"findlinks",
				"fireball",
				"fish search",
				"fouineur",
				"funnelweb",
				"gazz",
				"gcreep",
				"genieknows",
				"getterroboplus",
				"geturl",
				"glx",
				"goforit",
				"golem",
				"grabber",
				"grapnel",
				"gralon",
				"griffon",
				"gromit",
				"grub",
				"gulliver",
				"hamahakki",
				"harvest",
				"havindex",
				"helix",
				"heritrix",
				"hku www octopus",
				"homerweb",
				"htdig",
				"html index",
				"html_analyzer",
				"htmlgobble",
				"hubater",
				"hyper-decontextualizer",
				"ia_archiver",
				"ibm_planetwide",
				"ichiro",
				"iconsurf",
				"iltrovatore",
				"image.kapsi.net",
				"imagelock",
				"incywincy",
				"indexer",
				"infobee",
				"informant",
				"ingrid",
				"inktomisearch.com",
				"inspector web",
				"intelliagent",
				"internet shinchakubin",
				"ip3000",
				"iron33",
				"israeli-search",
				"ivia",
				"jack",
				"jakarta",
				"javabee",
				"jetbot",
				"jumpstation",
				"katipo",
				"kdd-explorer",
				"kilroy",
				"knowledge",
				"kototoi",
				"kretrieve",
				"labelgrabber",
				"lachesis",
				"larbin",
				"legs",
				"libwww",
				"linkalarm",
				"link validator",
				"linkscan",
				"lockon",
				"lwp",
				"lycos",
				"magpie",
				"mantraagent",
				"mapoftheinternet",
				"marvin/",
				"mattie",
				"mediafox",
				"mediapartners",
				"mercator",
				"merzscope",
				"microsoft url control",
				"minirank",
				"miva",
				"mj12",
				"mnogosearch",
				"moget",
				"monster",
				"moose",
				"motor",
				"multitext",
				"muncher",
				"muscatferret",
				"mwd.search",
				"myweb",
				"najdi",
				"nameprotect",
				"nationaldirectory",
				"nazilla",
				"ncsa beta",
				"nec-meshexplorer",
				"nederland.zoek",
				"netcarta webmap engine",
				"netmechanic",
				"netresearchserver",
				"netscoop",
				"newscan-online",
				"nhse",
				"nokia6682/",
				"nomad",
				"noyona",
				"nutch",
				"nzexplorer",
				"objectssearch",
				"occam",
				"omni",
				"open text",
				"openfind",
				"openintelligencedata",
				"orb search",
				"osis-project",
				"pack rat",
				"pageboy",
				"pagebull",
				"page_verifier",
				"panscient",
				"parasite",
				"partnersite",
				"patric",
				"pear.",
				"pegasus",
				"peregrinator",
				"pgp key agent",
				"phantom",
				"phpdig",
				"picosearch",
				"piltdownman",
				"pimptrain",
				"pinpoint",
				"pioneer",
				"piranha",
				"plumtreewebaccessor",
				"pogodak",
				"poirot",
				"pompos",
				"poppelsdorf",
				"poppi",
				"popular iconoclast",
				"psycheclone",
				"publisher",
				"python",
				"rambler",
				"raven search",
				"roach",
				"road runner",
				"roadhouse",
				"robbie",
				"robofox",
				"robozilla",
				"rules",
				"salty",
				"sbider",
				"scooter",
				"scoutjet",
				"scrubby",
				"search.",
				"searchprocess",
				"semanticdiscovery",
				"senrigan",
				"sg-scout",
				"shai'hulud",
				"shark",
				"shopwiki",
				"sidewinder",
				"sift",
				"silk",
				"simmany",
				"site searcher",
				"site valet",
				"sitetech-rover",
				"skymob.com",
				"sleek",
				"smartwit",
				"sna-",
				"snappy",
				"snooper",
				"sohu",
				"speedfind",
				"sphere",
				"sphider",
				"spinner",
				"spyder",
				"steeler/",
				"suke",
				"suntek",
				"supersnooper",
				"surfnomore",
				"sven",
				"sygol",
				"szukacz",
				"tach black widow",
				"tarantula",
				"templeton",
				"/teoma",
				"t-h-u-n-d-e-r-s-t-o-n-e",
				"theophrastus",
				"titan",
				"titin",
				"tkwww",
				"toutatis",
				"t-rex",
				"tutorgig",
				"twiceler",
				"twisted",
				"ucsd",
				"udmsearch",
				"url check",
				"updated",
				"vagabondo",
				"valkyrie",
				"verticrawl",
				"victoria",
				"vision-search",
				"volcano",
				"voyager/",
				"voyager-hc",
				"w3c_validator",
				"w3m2",
				"w3mir",
				"walker",
				"wallpaper",
				"wanderer",
				"wauuu",
				"wavefire",
				"web core",
				"web hopper",
				"web wombat",
				"webbandit",
				"webcatcher",
				"webcopy",
				"webfoot",
				"weblayers",
				"weblinker",
				"weblog monitor",
				"webmirror",
				"webmonkey",
				"webquest",
				"webreaper",
				"websitepulse",
				"websnarf",
				"webstolperer",
				"webvac",
				"webwalk",
				"webwatch",
				"webwombat",
				"webzinger",
				"wget",
				"whizbang",
				"whowhere",
				"wild ferret",
				"worldlight",
				"wwwc",
				"wwwster",
				"xenu",
				"xget",
				"xift",
				"xirq",
				"yandex",
				"yanga",
				"yeti",
				"yodao",
				"zao/",
				"zippp",
				"zyborg",
				"...."
		);

		foreach ( $spiders as $spider ) {
			// If the spider text is found in the current user agent, then return true
			if ( stripos( $_SERVER [ 'HTTP_USER_AGENT' ], $spider ) !== false )
				return true;
		}
		// If it gets this far then no bot was found!
		return false;
	}

}