<?php

/**
 * Admin settings manipulator
 * @author Vagenas Panagiotis <pan.vagenas@gmail.com>
 * @since 0.9
 * @copyright Tobias Ratschiller <tobias@senzalimiti.com>
 */
class wpAffiliateTrackerSetingsRendererC {

	/**
	 *
	 * @since 0.9
	 * @author Vagenas Panagiotis <pan.vagenas@gmail.com>
	 */
	function __construct( ) {
		add_action( 'admin_menu', array (
				$this,
				'settingsMenu'
		) );
		add_action( 'admin_init', array (
				$this,
				'adminInit'
		) );
		add_action( 'admin_enqueue_scripts', array (
				$this,
				'loadAdminScripts'
		) );
		add_action( 'admin_footer', array (
				$this,
				'WPATAjax'
		) );
		add_action( 'wp_ajax_WPATAjaxAddAfilink', array (
				$this,
				'WPATAjaxAddAfilink'
		) );
		add_action( 'wp_ajax_WPATAjaxDelAfiLink', array (
				$this,
				'WPATAjaxDelAfiLink'
		) );
		add_action( 'wp_ajax_WPATAjaxDelPPC', array (
		$this,
		'WPATAjaxDelPPC'
				) );
		add_action( 'wp_ajax_WPATAjaxDlCSV', array (
		$this,
		'WPATAjaxDlCSV'
				) );
		add_action( 'wp_ajax_WPATAjaxEditAffi', array (
		$this,
		'WPATAjaxEditAffi'
				) );
		require_once plugin_dir_path( __FILE__ ) . 'dbActions.php';
	}

	/**
	 * Renders the options page
	 *
	 * @since 0.9
	 * @author Vagenas Panagiotis <pan.vagenas@gmail.com>
	 */
	public function optPageRenderer( ) {
		screen_icon();

		$dbActions = new dbActionsC();
		$affiTableData = $dbActions->getAffiData();
		$ppcTableData = $dbActions->getPPCData();
		?>
<h2>WP Affiliate Tracker Configuration</h2>
<div id=affiTabs>
	<ul class="nav nav-tabs" id="affiTab">
		<li><a href="#reports" data-toggle="tab">Reports</a></li>
		<li><a href="#affiLinks" data-toggle="tab">Affiliate Links</a></li>
		<li><a href="#options" data-toggle="tab">Options</a></li>
		<li><a href="#help" data-toggle="tab">Help</a></li>
	</ul>
	<?php /* --------------------------------------------------------------------------------------------- */?>
	<div class="tab-content">
		<div class="tab-pane active" id="reports">
			<article class="affi-holder">
				<span class="opt-head">Click Reports<img id="wpatReload" alt="reload" src="<?php echo plugins_url( 'inc/images/exchange32.png', __FILE__ ); ?>"></span>
				<div style="padding: 10px; margin: 0 0 20px;">
					<table id="report-table">
						<thead>
							<tr>
								<td>Tracking ID</td>
								<td>Real Keyword</td>
								<td>Short Link</td>
								<td>Via</td>
								<td>Date</td>
								<td style="display: none;"></td>
								<td>Referer</td>
								<td></td>
								<td style="display: none;"></td>
								<td style="display: none;"></td>
							</tr>
						</thead>
						<tbody>
							<?php // Generate the report table body
							foreach ( $ppcTableData as $k => $v ) {
			?>
								<tr>
								<td><?php echo $v['ppc']; ?></td>
								<td><?php echo $v['real_keyword']; ?></td>
								<td><?php echo $v['short_link']; ?></td>
								<td><?php
								$via = unserialize($v['via']);
								if (count($via) === 2 ){
									echo $via[0];
								} else if (count($via) === 1) {
									echo 'direct';
								} else if (count($via) > 2 ) {
									$tip = '';
									foreach ($via as $viaK => $viaV) {
										$tip .=  $viaV . '<br>';
									}
									echo count($via) . ' pages <em class="show-v-pages" data-show="' . $tip . '">(show)</em>';
								}
								?></td>
								<td><?php echo date('F d Y \a\t h:i a', strtotime($v['time'])); ?></td>
								<td style="display: none;"><?php echo strtotime($v['time']); ?></td>
								<td><?php echo $v['referer']; ?></td>
								<td><a href="#" class="delete-ppc"
									ppc="<?php echo $v['ppc']; ?>">Delete</a></td>
								<td style="display: none;"><?php echo preg_replace("/[^a-zA-Z]/","",$v['ppc']); ?></td>
								<td style="display: none;"><?php echo preg_replace("/[^0-9]/","",$v['ppc']); ?></td>
							</tr>
								<?php
		}
		?>
						</tbody>
					</table>
				</div>
				<p class="download-csv-p">
					<span class="download-csv" style="">Download as .csv</span>
				</p>
			</article>
		</div>
		<?php /* --------------------------------------------------------------------------------------------- */?>
		<div class="tab-pane" id="affiLinks">

			<article class="affi-holder">
				<span class="opt-head">Add New Affiliate Link</span>
				<table class="affi-table">
					<tr>
						<td>Short link <img class="wpat-helper"
							data-help="The short link is your pretty, short URL that you use in your offers instead of the lonk affiliate link"
							alt="help" style="width: 0.8em;"
							src="<?php echo plugins_url( 'inc/images/help.png', __FILE__ ); ?>">:
						</td>
						<td><span style="display: inline;"><code><?php echo get_bloginfo('url'); ?>/</code>
						</span><input
							style="display: inline; width: auto;"
							type="text" id="short-link-input" placeholder="short-link"
							required="required"></td>
					</tr>
					<tr>
						<td>Affiliate URL <img class="wpat-helper"
							data-help="The affiliate URL is your affiliate system's tracking link
							that forwards to the vendor's page. In ClickBank, this is called
							the HopLink. You can use the code [tid] in your
							affiliate URL - this code will be replaced by the internal ID
							that represents the keyword. This is the essence of the click
							tracking mechanism."
							alt="help" style="width: 0.8em;"
							src="<?php echo plugins_url( 'inc/images/help.png', __FILE__ ); ?>">:
						</td>
						<td><input type="text" id="affi-url-input"
							placeholder="http://xxx.hop.clickbank.net?tid=[[tid]]"
							required="required"></td>
					</tr>
					<tr>
						<td colspan="3"><button class="add-affi-button">Add Affiliate Link</button></td>
					</tr>
				</table>
			</article>
			<article class="affi-holder">
				<span class="opt-head">Current Affiliate Links</span>
				<div style="padding: 10px;">
					<table id="affi-links-table">
						<thead>
							<tr>
								<td>Short Link</td>
								<td>Affiliate Link</td>
								<td>Total Clicks</td>
								<td></td>
							</tr>
						</thead>
						<tbody>
							<?php
		foreach ( $affiTableData as $k => $v ) {
			$sl = get_bloginfo('url') . '/' . $v['short_link'];
			?>
								<tr>
								<td><a href="<?php echo $sl; ?>" target="blank"><?php echo $sl; ?></a></td>
								<td><?php echo $v['affi_url']; ?></td>
								<td><?php echo $v['total_clicks']; ?></td>
								<td>
									<a href="#"
										class="edit-affi-url"
										data-sl="<?php echo $v['short_link']; ?>"
										data-url="<?php echo $v['affi_url']; ?>"
										data-id="<?php echo $v['id']; ?>"
										>Edit</a> |
									<a href="#" class="delete-affi" recid="<?php echo $v['id']; ?>" data-sl="<?php echo $v['short_link']; ?>">Delete</a>
								</td>
							</tr>
								<?php
		}
		?>
						</tbody>
					</table>
				</div>
				<div id="edit-affi-dialog-form" title="Edit affiliate link">
					  <p class="validateTips">Please fill in the new values.</p>
					  <form>
						  <fieldset>
						  		<table style="width: auto;">
						  			<tr>
									    <td><label for="edit-affi-short-link">Short link: </label></td>
									    <td><input style="min-width: 400px;" type="text" name="short-link" id="edit-affi-short-link" class="text ui-widget-content ui-corner-all" /></td>
								    </tr>
								    <tr>
									    <td><label for="edit-affi-url">Affiliate URL: </label></td>
									    <td><input style="min-width: 400px;" type="text" name="affi-url" id="edit-affi-url" value="" class="text ui-widget-content ui-corner-all" /></td>
									</tr>
							    </table>
								<input type="hidden" name="id" id="edit-affi-id" value="" class="text ui-widget-content ui-corner-all" />
						  </fieldset>
					  </form>
				</div>
			</article>
		</div>
		<?php /* --------------------------------------------------------------------------------------------- */?>
		<div class="tab-pane" id="options">
			<form method="post" action="admin-post.php">
				<input type="hidden" name="action" value="save_WPATOpts" />
				<table class="affi-table">
					<tbody>
						<tr>
							<td>Incoming tracking variables <img class="wpat-helper"
								data-help="These are the names of the GET parameters that can pass tracking data to your cloaked short
									links. These can be passed directly to your cloaked short link
									or via referrer.
									Example:
									keyword
									In Google AdWords, you would then include the keyword parameter
									in the destination URL like this: Destination URL:
									http://www.yourdomain.com/yournicelink?{keyword:nil}"
								alt="help" style="width: 0.8em;"
								src="<?php echo plugins_url( 'inc/images/help.png', __FILE__ ); ?>">:<br>
								(enter one per line)
							</td>
							<td><textarea name="tracking-vars" rows="10" cols="30"
									id="tracking-vars"><?php
		$trackVars = get_option( 'WPATOptTrackVars' );
		if ( !empty( $trackVars ) ) {
			foreach ( $trackVars as $k => $v ) {
				print ( $v . PHP_EOL ) ;
			}
		}
		?></textarea></td>
						</tr>
					</tbody>
				</table>
				<article class="affi-holder">
					<span class="opt-head">Tracking IDs</span>
					<div style="padding: 10px;">
						<table class="affi-table">
							<tbody>
								<tr>
									<td>PPC prefix <img class="wpat-helper"
										data-help="These prefixes will be used as
										identifier when forwarding your user to the sales page, for
										example ClickBank tracking IDs would then look like: ppc1
										or seo1"
										alt="help" style="width: 0.8em;"
										src="<?php echo plugins_url( 'inc/images/help.png', __FILE__ ); ?>">:
									</td>
									<td><input name="ppc-prefix" type="text" placeholder="ppc"
										required="required"
										value="<?php echo get_option('WPATOptPPCPre'); ?>"></td>
								</tr>
								<tr>
									<td>Organic search prefix <img class="wpat-helper"
										data-help="These prefixes will be used as
										identifier when forwarding your user to the sales page, for
										example ClickBank tracking IDs would then look like: ppc1
										or seo1"
										alt="help" style="width: 0.8em;"
										src="<?php echo plugins_url( 'inc/images/help.png', __FILE__ ); ?>">:
									</td>
									<td><input name="org-search-prefix" type="text"
										value="<?php echo get_option('WPATOptOrgSrchPre'); ?>"
										placeholder="seo" required="required"></td>
								</tr>
								<tr>
									<td>Tracking ID <img class="wpat-helper"
										data-help="This defines how the tracking IDs are contructed"
										alt="help" style="width: 0.8em;"
										src="<?php echo plugins_url( 'inc/images/help.png', __FILE__ ); ?>">:
									</td>
									<td><?php $trID = (int)get_option('WPATOptTrID'); ?>
										<label><input name="track-id" type="radio"
											<?php checked( $trID, 1 ) ?> value="1" style="width: auto;">
											incremental <img class="wpat-helper"
											data-help="means that an incrementing
												number will be appended to the prefix defined above.
												Example: ppc1"
											alt="help" style="width: 0.8em;"
											src="<?php echo plugins_url( 'inc/images/help.png', __FILE__ ); ?>"></label><br>
										<label><input name="track-id" type="radio"
											<?php checked( $trID, 2 )?> value="2" style="width: auto;">
											random string <img class="wpat-helper"
											data-help="means that randomized, unique strings will be used. Example ppc4930x900602d23"
											alt="help" style="width: 0.8em;"
											src="<?php echo plugins_url( 'inc/images/help.png', __FILE__ ); ?>"></label><br>
										<label><input name="track-id" type="radio"
											<?php checked( $trID, 3 ) ?> value="3" style="width: auto;">
											shortended incoming tracking variable <img
											class="wpat-helper"
											data-help="means that the incoming tracking variable (for example the
												AdWords keywords) will be cleaned and shortened. For
												example, if the incoming tracking variable is 'how to gain
												muscle fast', the tracking ID would be:
												ppchowtogainmusclefast"
											alt="help" style="width: 0.8em;"
											src="<?php echo plugins_url( 'inc/images/help.png', __FILE__ ); ?>"></label>
									</td>

								</tr>
							</tbody>
						</table>
					</div>
				</article>
				<?php echo get_submit_button( 'Update options', 'primary large', 'Save' ); ?>
			</form>
		</div>
		<?php /* --------------------------------------------------------------------------------------------- */?>
		<div class="tab-pane" id="help">
			<h3>Quick Help</h3>
			<p>
				<ol>
					<li>
						Create a short link (your-domain.com/short-link) that makes your ugly affiliate link
						(http://2f082c2nspnhrr2omqjb-5ph4g.hop.clickbank.net/?tid=) look good.
						This is the tracking link that you will use to redirect users to an vendor's page.
					</li>
					<li>
						You can use the short link directly in your advertising, for example in mailings.
						Alternatively, create a landing page, with a call-to-action, for example a button, that references the short link.
					</li>
					<li>
						To track keywords or click sources, you set up an incoming tracking variable.
						You can use this tracking variable either directly on your short link (your-domain.com/affiliate-offer?kw=keyword)
						or on the landing page (your-domain.com/blog/landing-page.html?kw=keyword).
						Wp affiliate tracker will substitute the keyword with a tracking ID before forwarding to the vendor page.
					</li>
				</ol>
			</p>
			<h3>Example Scenario</h3>
			<p>
				Let's say you want to promote a revolutionary weight loss product that you have found on ClickBank.
				The product's hop link with your affiliate ID is http://2f082c2nspnhrr2omqjb-5ph4g.hop.clickbank.net/?tid=[tid].
			</p>
			<p>
				Note the ?tid=[tid] in the URL. The tid is the ClickBank tracking ID. The first step is to create a short link to hide the ugly long URL:
			</p>
			<img class="wpatHelpImg" alt="" src="<?php echo plugins_url( 'inc/images/shortlink.png', __FILE__ ); ?>">
			<p>
				Next, you are going to create a landing page to promote the product. The landing page is called /blog/landing-page.html and looks like this:
			</p>
			<img class="wpatHelpImg" alt="" src="<?php echo plugins_url( 'inc/images/landingpage.png', __FILE__ ); ?>">
			<p>
				The call-to-action button ("try now") links to to your short link.
			</p>
			<p>
				You have decided that you want to drive traffic to your landing page by advertising on Google Adwords.
				You create a campaign and an ad that looks something like this:
			</p>
			<img class="wpatHelpImg" alt="" src="<?php echo plugins_url( 'inc/images/adwords.png', __FILE__ ); ?>">
			<p>
				Note the "?kw={keyword:nil}" part in the destination URL. This is a Google AdWords feature called Dynamic Keyword Insertion.
				It allows you to dynamically update your ad text or in our case destination URL with the keyword
				(search term( that's used to target your ad. This allows us to pass the search term to the landing page to track which search terms converts best.
			</p>
			<p>
				To start, you want to try to advertise on five different search terms:
				<ul>
					<li>"grow muscle"</li>
					<li>"muscle building"</li>
					<li>"effective muscle growth"</li>
					<li>"visual impact muscle building"</li>
					<li>"muscle growth"</li>
				</ul>
			</p>
			<p>
				To track the keywords, add an incoming tracking variable to the Wp Affiliate Tracker setup:
			</p>
			<img class="wpatHelpImg" alt="" src="<?php echo plugins_url( 'inc/images/keywordoptions.png', __FILE__ ); ?>">
			<p>
				As you can see, the incoming tracking variable is "kw", the same as we used in the AdWords destination URL.
				Voila, that's it! Whenever a user now clicks on the AdWords ad, the search term is tracked;
				when he clicks subsequently on the call-to-action button, two things happen:
				<ul style="list-style: disc; list-style-position: inside;">
					<li>
						The keyword is replaced with a anonymized tracking ID, for example ppc1 - this tracking ID will show up as
						tid in the ClickBank reporting.
					</li>
					<li>
						The click is stored in the wp affiliate tracker reports, showing the tracking ID (for example "ppc1") and the original keyword (for example "muscle growth").
						This gives you the insight which keyword (as matched to the tid) converts best.
					</li>
				</ul>
			</p>
		</div>
	</div>
</div>


<script type="text/javascript">
		function tiper(parent, content) {
			jQuery(parent).qtip({
				content : {
					text : content
				},
				position : {
					at : 'right',
					adjust : {
						x : 10
					}
				},
				style : {
					name: 'light',//classes : 'qtip-blue qtip-shadow',
					tip : {
						corner : 'left top',
						mimic : 'left center'
					}
				},
				show : 'mouseover',
				hide : 'mouseleave'
			});
		}

		jQuery(document).ready(function($) {
			$('#wpatReload').click(function(){
				location.reload();
			});
			$('.show-v-pages').each(function(){
				tiper($(this), $(this).attr('data-show'));
			});

			$('.wpat-helper').each(function(){
				tiper($(this), $(this).attr('data-help'));
			});

			bindDelAffi();
			bindDelPPC();
			$('#affiTabs').tabs();
			ppcTable = $('#report-table').dataTable({
				"aaSorting": [[4, 'desc']],
				"aLengthMenu": [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],
				"iDisplayLength" : 25,
				"sPaginationType": "full_numbers",
				"sDom": '<"ppc-table"<"table-info"if><t><"table-footer"<"table-length"l>pr>>',
				"aoColumns": [
				              {"aDataSort": [ 8, 9 ] },
				              null,
				              null,
				              null,
				              {"iDataSort": 5},
				              null,
				              null,
				              { "bSortable": false, bSearchable: false },
				              null,
				              null
				            ]
			});
			affiTable = $('#affi-links-table').dataTable({
				"sPaginationType": "full_numbers",
				"sDom": '<"affiliates-table"<"table-info"if><t><"table-footer"<"table-length"l>pr>>',
				"aoColumns": [
				              null,
				              null,
				              null,
				              { "bSortable": false, bSearchable: false }
				            ]
			});
		});
</script>
<?php
	}

	/**
	 * Prepares the data to exported as csv.
	 * Echoes a json 2d array.
	 *
	 * @since 0.9
	 * @author Vagenas Panagiotis <pan.vagenas@gmail.com>
	 */
	function WPATAjaxDlCSV(){
		if ( !is_admin() ) {
			echo json_encode( false );
			die();
		}
		$dbAction = new dbActionsC();
		$data = $dbAction->getPPCData();
		$dim2 = array();
		$dim2[0] = array('ppc', 'real keyword', 'short link', 'referer', 'time');
		foreach ($data as $k => $v){
			$dim2[$k+1] = array($v['ppc'], $v['real_keyword'], $v['short_link'], $v['referer'], $v['time']);
		}
		echo json_encode($dim2);
		die();
	}

	/**
	 * Deletes a record from affi table
	 *
	 * @since 0.9
	 * @author Vagenas Panagiotis <pan.vagenas@gmail.com>
	 */
	function WPATAjaxDelAfiLink( ) {
		if ( empty( $_POST [ 'id' ] ) ) {
			echo json_encode( false );
			die();
		}

		$dbAction = new dbActionsC();

		if ( $dbAction->deleteRecFromAffi( ( int ) $_POST [ 'id' ] ) ) {
			echo json_encode( array (
					'id' => ( int ) $_POST [ 'id' ]
			) );
		} else {
			echo json_encode( array (
					'error' => 'Record not found'
			) );
		}
		die();
	}

	/**
	 * Deletes a record from ppc table
	 *
	 * @since 0.9
	 * @author Vagenas Panagiotis <pan.vagenas@gmail.com>
	 */
	function WPATAjaxDelPPC( ) {
		if ( empty( $_POST [ 'ppc' ] ) ) {
			echo json_encode( false );
			die();
		}

		$dbAction = new dbActionsC();

		if ( $dbAction->deleteRecFromPPC( $_POST [ 'ppc' ] ) ) {
			echo json_encode( array (
					'ppc' => $_POST [ 'ppc' ]
			) );
		} else {
			echo json_encode( array (
					'error' => 'Record not found'
			) );
		}
		die();
	}

	/**
	 * Add affiliate link by ajax request
	 *
	 * @since 0.9
	 * @author Vagenas Panagiotis <pan.vagenas@gmail.com>
	 */
	function WPATAjaxAddAfilink( ) {
		if ( empty( $_POST [ 'shortLink' ] ) || empty( $_POST [ 'affiUrl' ] ) ) {
			echo json_encode( array (
					'error' => 'No valid data commited. Please try again.'
			) );
			die();
		}

		$dbAction = new dbActionsC();

		$shortLink = $this->checkShortLink( $_POST [ 'shortLink' ] );

		$affiUrl = $this->checkAffiUrl( $_POST [ 'affiUrl' ] );

		if ( $shortLink !== FALSE && $affiUrl !== FALSE ) {
			// TODO Add to database
			$data = array (
					'short_link' => $shortLink,
					'affi_url' => $affiUrl,
					'total_clicks' => 0,
					'date' => date( 'Y-m-d' )
			);

			if ( !$dbAction->getRowFromAffi( array (
					'short_link' => $shortLink
			) ) && $dbAction->insertRecToAffi( $data ) ) { // Successfull input
				$record = $dbAction->getRowFromAffi( array (
						'short_link' => $shortLink
				) );
				if ( $record ) {
					echo json_encode( $record );
				} else {
					echo json_encode( array (
							'error' => 'There was an error while inserting data in DB.'
					) );
				}
			} else {
				echo json_encode( array (
						'error' => 'This short link exists already. Please enter a new one.'
				) );
			}
		} else {
			if ( $shortLink === FALSE ) {
				echo json_encode( array (
						'error' => 'The shortlink you provided is invalid. Please retry.'
				) );
			} else if ( $affiUrl === FALSE ) {
				echo json_encode( array (
						'error' => 'The affiliate url you provided is invalid. Please retry.'
				) );
			}
		}
		die();
	}

	/**
	 * Edit affiliate link
	 *
	 * @since 0.9
	 * @author Vagenas Panagiotis <pan.vagenas@gmail.com>
	 */
	function WPATAjaxEditAffi( ) {
		if ( empty( $_POST [ 'shortLink' ] ) || empty( $_POST [ 'affiUrl' ] ) || empty( $_POST [ 'affiId' ] ) ) {
			echo json_encode( array (
					'error' => 'No valid data provided. Please try again.'
			) );
			die();
		}

		$dbAction = new dbActionsC();

		$shortLink = $this->checkShortLink( $_POST [ 'shortLink' ] );

		$affiUrl = $this->checkAffiUrl( $_POST [ 'affiUrl' ] );

		if ( $shortLink !== FALSE && $affiUrl !== FALSE ) {
			// TODO Add to database
			$data = array (
					'short_link' => $shortLink,
					'affi_url' => $affiUrl
			);

			$result = $dbAction->updateAffiRec($_POST [ 'affiId' ], $data);

			if ( $result ) { // Successfull input
				echo json_encode($result);
			} else {
				echo json_encode( array (
						'error' => 'There was an error while inserting data in DB.'
				) );
			}
		} else {
			if ( $shortLink === FALSE ) {
				echo json_encode( array (
						'error' => 'Not a valid short link.'
				) );
			} else if ( $affiUrl === FALSE ) {
				echo json_encode( array (
						'error' => 'Not a valid affiliate URL.'
				) );
			} else {
				echo json_encode( array (
						'error' => 'No valid data provided. Please try again.'
				) );
			}
		}
		die();
	}

	/**
	 * Ads ajax functionalities in plugin settings page
	 *
	 * @since 0.9
	 * @author Vagenas Panagiotis <pan.vagenas@gmail.com>
	 */
	public function WPATAjax( ) {
		?>
<script type="text/javascript">

		function bindDelAffi() {
			jQuery('.delete-affi').unbind( "click" ).click(function(){
				var data = {
					action: 'WPATAjaxDelAfiLink',
					id: jQuery(this).attr('recid')
					};

				var r=confirm("Are you sure you want to delete "+jQuery(this).attr('data-sl')+"? \nThis action can't be undone.")
				if (r==false) {
				  return false;
				}

				jQuery.post(ajaxurl, data, function(response) {
					if (response['error'] != undefined){
						alert('Operation finished with error: '+ response['error']);
					} else {
						var pos = affiTable.fnGetPosition( jQuery('.delete-affi[recid|="'+response['id']+'"]').parent().parent()[0] );
						affiTable.fnDeleteRow( pos, bindDelAffi(), true );
					}
				}, 'json');
			});
		}

		function bindDelPPC() {
			jQuery('.delete-ppc').unbind( "click" ).click(function(){
				var data = {
					action: 'WPATAjaxDelPPC',
					ppc: jQuery(this).attr('ppc')
					};

				var r=confirm("Are you sure you want to delete this PPC? This action can't be undone.")
				if (r==false) {
				  return false;
				}

				jQuery.post(ajaxurl, data, function(response) {
					if (response['error'] != undefined){
						alert('Operation finished with error: '+ response['error']);
					} else {
						var pos = ppcTable.fnGetPosition( jQuery('.delete-ppc[ppc|="'+response['ppc']+'"]').parent().parent()[0] );

						ppcTable.fnDeleteRow( pos, bindDelPPC(), true );
					}
				}, 'json');
			});
		}

		function bindEditAffi() {
			jQuery( ".edit-affi-url" )
			.unbind( "click" )
		  	.click(function() {
		      	var sl = jQuery(this).attr('data-sl');
		      	var url = jQuery(this).attr('data-url');
		      	var id = jQuery(this).attr('data-id');
		      	jQuery( "#edit-affi-dialog-form" ).data( "details", { shortLink: sl, affiUrl: url, affiId: id } ).dialog( "open" );
		  	});
		}

jQuery(document).ready(function($) {

	$( "#edit-affi-dialog-form" ).dialog({
	      autoOpen: false,
	      height: 300,
	      width: 'auto',
	      modal: true,
	      buttons: {
	        "Update": function() {
		        if ($('#edit-affi-short-link').val() === $(this).data('details').shortLink && $(this).data('details').affiUrl === $('#edit-affi-url').val() ){
		        	$(this).children('input').val( "" ).removeClass( "ui-state-error" );
			        $( this ).dialog( "close" );
			        return true;
		        }
	        	var data = {
	    				action: 'WPATAjaxEditAffi',
	    				shortLink: $('#edit-affi-short-link').val(),
	    				affiUrl: $('#edit-affi-url').val(),
	    				affiId: $('#edit-affi-id').val()
	    			};
				var that = $(this);
	        	$.post(ajaxurl, data, function(response) {
	        		if (response['error'] != undefined){
						alert('Operation finished with error: '+ response['error']);
					} else {
						var pos = affiTable.fnGetPosition( jQuery('.delete-affi[recid|="'+response['id']+'"]').parent().parent()[0] );
						affiTable.fnDeleteRow( pos, bindDelAffi(), true );

						var editAffiString = '<a href="#" class="edit-affi-url" data-sl="'+response['short_link']+'" data-url="'+response['affi_url']+'" data-id="'+response['id']+'" >Edit</a> | ';
						var delAffiString = '<a href="#" class="delete-affi" recid="'+parseInt(response['id'])+'" data-sl="'+response['short_link']+'">Delete</a>';

						affiTable.fnAddData(["<?php echo bloginfo('url').'/';?>"+response['short_link'],response['affi_url'],response['total_clicks'],editAffiString+delAffiString]);

						bindEditAffi();
						bindDelAffi();

						that.children('input').val( "" ).removeClass( "ui-state-error" );
						that.dialog( "close" );
					}
	    		}, 'json');
	          },
	      	Cancel: function() {
	      		$(this).children('input').val( "" ).removeClass( "ui-state-error" );
	          $( this ).dialog( "close" );
	        }
	      },
      	close: function() {
        	$(this).children('input').val( "" ).removeClass( "ui-state-error" );
      	},
      	open: function( event, ui ) {
      		$('#edit-affi-short-link').val( $(this).data('details').shortLink );
      		$('#edit-affi-url').val( $(this).data('details').affiUrl );
      		$('#edit-affi-id').val( $(this).data('details').affiId );
        }
    });

	bindEditAffi();

	// ---------------------------------------------------------------------------------------------------------------------------


	$('.download-csv').click(function(){
		var data = {
				action: 'WPATAjaxDlCSV'
			};

		$.post(ajaxurl, data, function(response) {
			var A = response;
			var csvRows = [];

			for(var i=0; i<A.length; i++){
				for(var j=0; j<A[i].length; j++){
				    A[i][j] = '"'+A[i][j]+'"';
				}
			    csvRows.push(A[i].join(','));
			}
			var csvString = csvRows.join("\n");
			var a         = document.createElement('a');
			a.href        = 'data:attachment/csv,' + encodeURI(csvString);
			a.target      = '_blank';
			a.download    = '<?php echo date('Y-m-d'); ?>'+'.csv';

			document.body.appendChild(a);
			a.click();
			a.remove();
		}, 'json');
	});


	$('.add-affi-button').click(function(){
		var slink = $(this).parent().parent().parent().children().children().children('#short-link-input');
		var url = $(this).parent().parent().parent().children().children().children('#affi-url-input');

		var data = {
				action: 'WPATAjaxAddAfilink',
				shortLink: slink.val(),
				affiUrl: url.val()
			};

		$.post(ajaxurl, data, function(response) {
			if (response['error'] != undefined){
				alert('Operation finished with error: \n'+ response['error']);
			} else {
				var editAffiString = '<a href="#" class="edit-affi-url" data-sl="'+response['short_link']+'" data-url="'+response['affi_url']+'" data-id="'+response['id']+'" >Edit</a> | ';
				var delAffiString = '<a href="#" class="delete-affi" recid="'+parseInt(response['id'])+'" data-sl="'+response['short_link']+'">Delete</a>';

				affiTable.fnAddData(["<?php echo bloginfo('url').'/';?>"+response['short_link'],response['affi_url'],response['total_clicks'],editAffiString+delAffiString]);

				bindEditAffi();
				bindDelAffi();

				slink.val('');
				url.val('');
			}
		}, 'json');
	});

});
</script>
<?php
	}

	/**
	 * Checks if submited short link is valid (alphanumeric, dashes and underscores only)
	 *
	 * @param string $shortLink
	 *        	The input string
	 * @return string or boolean Input string if valid, FALSE otherwise.
	 * @since 0.9
	 * @author Vagenas Panagiotis <pan.vagenas@gmail.com>
	 */
	private function checkShortLink( $shortLink ) {
		if ( preg_match( '/^[a-zA-Z0-9_-]+$/', $shortLink ) ) {
			return $shortLink;
		}
		return FALSE;
	}

	/**
	 * Checks for a valid url with a flag query
	 *
	 * @param unknown $affiUrl
	 * @return boolean or string. Input string if valid, FALSE otherwise.
	 * @since 0.9
	 * @author Vagenas Panagiotis <pan.vagenas@gmail.com>
	 */
	private function checkAffiUrl( $affiUrl ) {
		if ( !filter_var( $affiUrl, FILTER_VALIDATE_URL, FILTER_FLAG_QUERY_REQUIRED ) ) {
			return FALSE;
		}
		return $affiUrl;
	}

	/**
	 * Proccess options to be saved
	 *
	 * @since 0.9
	 * @author Vagenas Panagiotis <pan.vagenas@gmail.com>
	 */
	public function optsProcessing( ) {
		if ( !current_user_can( 'manage_options' ) ) {
			wp_die( 'Not allowed' );
		}

		// TODO Proccess tracking-vars
		if ( isset( $_POST [ 'tracking-vars' ] ) ) {
			$vars = explode( PHP_EOL, $_POST [ 'tracking-vars' ] );
			if ( !empty( $vars ) ) {
				$newVars = array ();
				foreach ( $vars as $k => $v ) {
					$newVars [ $k ] = preg_replace( '/\s+/', '', wp_strip_all_tags( $v ) );
					if ( empty( $newVars [ $k ] ) ) {
						unset( $newVars [ $k ] );
					}
				}
				sort( $newVars );
				update_option( 'WPATOptTrackVars', $newVars );
			}
		}

		if ( isset( $_POST [ 'ppc-prefix' ] ) ) {
			update_option( 'WPATOptPPCPre', wp_strip_all_tags( $_POST [ 'ppc-prefix' ] ) );
		}

		if ( isset( $_POST [ 'org-search-prefix' ] ) ) {
			update_option( 'WPATOptOrgSrchPre', wp_strip_all_tags( $_POST [ 'org-search-prefix' ] ) );
		}

		if ( isset( $_POST [ 'track-id' ] ) ) {
			update_option( 'WPATOptTrID', ( int ) $_POST [ 'track-id' ] );
		}

		wp_redirect( add_query_arg( array (
				'page' => 'wpAffiliateTracker#options'
		), admin_url( 'options-general.php' ) ) );

		exit();
	}

	/**
	 * Enqueue admin panel related scripts and styles
	 *
	 * @since 0.9
	 * @author Vagenas Panagiotis <pan.vagenas@gmail.com>
	 */
	public function loadAdminScripts( ) {
		// js
		wp_enqueue_script( 'jquery' );
		wp_enqueue_script( 'jquery-ui-core' );
		wp_enqueue_script( 'jquery-ui-tabs' );
		wp_enqueue_script( 'jquery-ui-dialog' );
		wp_enqueue_script( 'WPAT_qtip' );
		wp_enqueue_script( 'WPAT_dataTables' );

		// css
		wp_enqueue_style( 'WPAT_AdminStyle' );
		wp_enqueue_style( 'WPAT_qtipStyle' );
		wp_enqueue_style( 'WPAT_dataTablesStyle' );
	}

	/**
	 * Adds the settings menu
	 *
	 * @since 0.9
	 * @author Vagenas Panagiotis <pan.vagenas@gmail.com>
	 */
	public function settingsMenu( ) {
		add_options_page( 'WP Affiliate Tracker Configuration', 'WP Affiliate Tracker', 'manage_options', 'wpAffiliateTracker', array (
				$this,
				'optPageRenderer'
		) );
	}

	/**
	 * Initialize options save function
	 *
	 * @since 0.9
	 * @author Vagenas Panagiotis <pan.vagenas@gmail.com>
	 */
	public function adminInit( ) {
		add_action( 'admin_post_save_WPATOpts', array (
				$this,
				'optsProcessing'
		) );
	}
}
