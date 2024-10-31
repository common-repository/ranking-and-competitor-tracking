<?php
/**
 * Project: Hub5050 Ranking and Competitor Tracking (class.hub-ract-shortcodes.php)
 * Copyright: (C) 2011-2024 Clinton
 * Developer:  Clinton [CreatorSEO]
 * Created on 10 March 2018
 *
 * Description: Base class for Hub5050 Ranking and Competitor Tracking (ract)
 *
 * This program is the property of the developer and is not intended for distribution. However, if the
 * program is distributed for ANY reason whatsoever, this distribution is WITHOUT ANY WARRANTEE or even
 * the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 *
 */

require_once (plugin_dir_path(HUB_RACT_ROOT).'inc/class.hub-ract-ranking.php');

//-------------------------------------------------- RANKING PLUGIN --------------------------------------------------

/**
 * Shortcode function to display the status of test updates
 *
 * @param array $atts
 * @param text $content
 *
 * @return string
 */
function hub_ract_view_status( $atts, $content=null ) {
	$atts = shortcode_atts(
		array(
			'wrapper'=> 'hub-ract-wrap',
			'title' => 'Phrase Run Status',
			'class'=>'hub-ract-table'
		),
		$atts
	);
	$title = (is_null($content) || strlen($content)==0)? $atts['title']: $content;
	$options = get_option('hub_ract_options');
	$the_page = $options['page'][0]; // later when there are multiple pages, this will be an iterate step
	$se_wait_time = (defined('HUB_RACT_SE') && isset($options['refresh']) && $options['refresh']>HUB_RACT_SE)? (int) $options['refresh']: 360; //seconds - min time between search engine runs
	$kwd_wait_time = defined('HUB_RACT_KW')? HUB_RACT_KW : 5.001; //days - min time between tests on a phrase
	$seodata = get_option( 'hub_ract_data', 'Nothing to show' );
	$out = '<div class="'.$atts['wrapper'].'">';
	$out .= '<h2>'.$title.'</h2>';
	if (is_array($options) && is_array($seodata)){
		$uri = is_array($the_page['uri'])? $the_page['uri']: $options['domain'];
		if (isset($the_page['engines']) && count($the_page['engines'])){
			$out .= '<table class="' . $atts['class'] . '">';
			$out .= '<tbody>';
			$out .= '<tr>';
			$out .= '<th>Phrase</th>';
			$out .= '<th>Last Update</th>';
			$out .= '<th>Next Update</th>';
			$out .= '<th>Status</th>';
			$out .= '</tr>';
			foreach ($the_page['engines'] as $engine){
				if (isset( $seodata['page'][ $uri ][ $engine ]['last-run'] )){
					$lastUpdate = $seodata['page'][ $uri ][ $engine ]['last-run'];
					$nextUpdate = $seodata['page'][ $uri ][ $engine ]['last-run'] + ($se_wait_time);
				} else {
					$lastUpdate = 0;
					$nextUpdate = time();
				}
				$nxt = $nextUpdate - time();
				//the CRON job runs every 900 seconds
				//$status = $nxt>0? 'due in ' . number_format( ($nxt/60), 1 ) . ' minutes': 'Ready ' .($nxt<-900? '['.$nxt.']': '');
				$status = $nxt>0? 'Wait': 'Ready ';
				$out .= '<tr class="ract_table_hdr"><td>Engine: '.$engine.'</td>';
				$out .= '<td>'.($lastUpdate == 0? '--': date('Y-m-d H:i:s', $lastUpdate)).'</td>';
				$out .= '<td>'.($nextUpdate == 0? '--': date('Y-m-d H:i:s', $nextUpdate)).'</td>';
				$out .=  '<td>' . $status . '</td></tr>';
				if (is_array($the_page['keywords']) && count($the_page['keywords'])){
					foreach ($the_page['keywords'] as $phrase){
						if (isset($seodata['page'][$uri][$engine]['SERP'][$phrase]['timestamp'])){
							$lastUpdate = $seodata['page'][$uri][$engine]['SERP'][$phrase]['timestamp'];
							$nextUpdate = $seodata['page'][$uri][$engine]['SERP'][$phrase]['timestamp']+($kwd_wait_time * 86400);
						} else {
							$lastUpdate = 0;
							$nextUpdate = time();
						}
						$nxt = $nextUpdate - time();
						$status = $nxt>0? 'due in ' . number_format( ($nxt/86400), 1 ) . ' days': ($nxt<-86400? 'Overdue': 'Ready');
						$out .= '<tr><td>'.$phrase.'</td>';
						$out .= '<td>'.($lastUpdate == 0? '--': date('Y-m-d H:i:s', $lastUpdate)).'</td>';
						$out .= '<td>'.($nextUpdate == 0? '--': date('Y-m-d H:i:s', $nextUpdate)).'</td>';
						$out .= '<td>'.$status.'</td></tr>';
					}
				} else {
					$out .= '<h4>Data Error</h4><p>Key phrases not set</p>';
					$out .= '<tr><td colspan="4">Data Error: Key phrases not set</td></tr>';
				}
			}
			$out .= '</tbody>';
			$out .= '</table>';
			$out .= '<p><em>Note: Next Update depends on the CRON update</em></p>';
		} else {
			$out .= '<h4>Data Error</h4><p>Search engines not found</p>';
		}
		//$out .= '<h3>Option Data</h3><pre>'.var_export($options,true).'</pre><hr />';
		//$out .= '<h3>SEO Data</h3><pre>'.var_export($seodata,true).'</pre>';
	} else {
		$out .= '<p>Data file '.$atts['file'].' not found</p>';
	}
	$out .= '</div>';
	return $out;
}
//add_shortcode( 'hub_ract_view_status', 'hub_ract_view_status' );

/**
 * Run tests and stores the results to the option file.
 * This function is called by the CRON job to update the ranking
 *
 * @param array $atts - attributes that can be passed to the shortcode
 * @return string
 */
function hub_ract_run_rank_test($atts){
	$atts = shortcode_atts(
		array(
			'title' => 'CRON Job Run Results',
			'display'=> false //hide or show the results of the run
		),
		$atts
	);
	$saveURL = false; //record the SE URL
	$options = get_option('hub_ract_options');
	$the_page = $options['page'][0]; // later when there are multiple pages, this will be an iterate step
	$seodata = get_option('hub_ract_data');
	$out = ''; $log_info = ''; $print_info = ''; $error_info = '';
	$seStatus = 1;
	//The following options need to be set for this to run: domain, keywords, rivals
	if (isset($options['domain']) && isset($the_page['keywords']) && isset($the_page['rivals']['url'])){
		//the page uri must include the domain - working with a single page for now
		$uri = stristr($the_page['rivals']['url'][0], $options['domain'])? $the_page['rivals']['url'][0]: home_url();
		$se_wait_time = (isset($options['refresh']) && $options['refresh']>HUB_RACT_SE)? (int) $options['refresh']: HUB_RACT_SE; //seconds - minimum time between search engine runs
		$kwd_wait_time = defined('HUB_RACT_KW')? HUB_RACT_KW : 5.001; //days - minimum time between tests on a phrase
		//$se_wait_time = 90; //seconds - minimum time between search engine runs TEST ONLY
		//$kwd_wait_time = .01; //days - minimum time between tests on a phrase TEST ONLY
		//get a list of the engines, phrases and rivals
		$engines =  creator_count($the_page['engines'])? $the_page['engines']: array();
		$phrases = creator_count($the_page['keywords'])? $the_page['keywords']: array();
		$competitors = creator_count($the_page['rivals']['url'])? $the_page['rivals']['url']: array();
		$seodata['license'] = isset($options['license'])? $options['license']: '00000'; //record the license number
		if (is_array($the_page['engines']) && count($the_page['engines'])){
			// -- delete any obsolete engines --
			if (is_array($seodata['page'][ $uri ])) {
				$arrDiff = array_diff( array_keys( $seodata['page'][ $uri ] ), $engines );
				if ( count( $arrDiff ) ) {
					foreach ( $arrDiff as $emt ) {
						unset( $seodata['page'][ $uri ][ $emt ] );
					}
				}
			}
			foreach ($the_page['engines'] as $engine){
				$log_info .= ' | ENGINE: '.$engine;
				$tmp = explode('.', $engine);
				$techno = isset($tmp[0])? $tmp[0]: '';
				$engineX = isset($tmp[1])? $tmp[1]: '';
				$region = (isset($tmp[2])? $tmp[2]: '').(isset($tmp[3])? $tmp[3]: '').(isset($tmp[4])? $tmp[4]: '');
				unset($tmp);
				if (strlen($techno) && strlen($engineX) && strlen($region)) {
					$ract = new hub_ract_ranking( false ); //******************************** set debug to false
					// -- delete any obsolete phrases --
					if (is_array($seodata['page'][ $uri ][ $engine ]['SERP'])) {
						$arrDiff = array_diff( array_keys( $seodata['page'][ $uri ][ $engine ]['SERP'] ), $phrases );
						if ( count( $arrDiff ) ) {
							foreach ( $arrDiff as $emt ) {
								unset( $seodata['page'][ $uri ][ $engine ]['SERP'][ $emt ] );
								$log_info .= ' | UNSET: '.$emt;
							}
						}
					}
					$phrase = $ract->selectPhraseX( $phrases, $seodata['page'][ $uri ][ $engine ]['SERP'] );
					$se_time_diff = isset( $seodata['page'][ $uri ][ $engine ]['last-run'] ) ? ( time() - $seodata['page'][ $uri ][ $engine ]['last-run'] ) : 9999;
					$kwd_time_diff = isset( $seodata['page'][ $uri ][ $engine ]['SERP'][ $phrase ]['timestamp'] ) ? ( time() - $seodata['page'][ $uri ][ $engine ]['SERP'][ $phrase ]['timestamp'] ) : 999999;
					$log_info .= ' | ' . $uri . ' | ' . $phrase;
					//check that the search engine has not run for at least the $se_wait_time and that the keyword has not run for at least the $kwd_wait_time
					if ( ( $se_time_diff > $se_wait_time ) && ( $kwd_time_diff > ( $kwd_wait_time * 86400 ) ) && strlen( $phrase ) ) {
						$log_info .= ' | UPDATE DUE';
						$seodata['updated'] = time(); //this is the date of the current change to the record
						unset( $seodata['page'][ $uri ][ $engine ]['SERP'][ $phrase ] );
						$ract->getSERPData( $uri, $engineX, $region, $phrase, $competitors, 10, false );
						$log_info .= $atts['display']? ' | -- Engine: '.$engineX.' | -- Region: '.$region.' | -- Phrase: '.$phrase.' | -- Competitor Count: '.creator_count($competitors): '';
						if (strlen($ract->error)){
							$log_info .= ' | SEARCH ERRORS: '.$ract->error;
							$error_info .= $ract->error;
							$seStatus = 0;
						} else {
							$seStatus = 1;
						}
						$seodata['page'][ $uri ][ $engine ]['last-run'] = time(); //set the phrase timestamp
						$seodata['page'][ $uri ][ $engine ]['status'] = $seStatus; //set the phrase timestamp
						if ($seStatus){
							//only update the results if a valid test was carried out
							$seodata['page'][ $uri ][ $engine ]['SERP'][ $phrase ] = $ract->results; //save the results for the property url/phrase/engine/region
							$seodata['page'][ $uri ][ $engine ]['SERP'][ $phrase ]['timestamp'] = time(); //set the phrase timestamp
							$log_info .= ' | RANKING: ' . (isset($ract->results['rank'])? $ract->results['rank']: 'Missing') ;
						}
						if ( update_option( 'hub_ract_data', $seodata ) === false ) {
							$log_info .= ' | ERROR: OPTION VALUE COULD NOT BE SET';
						} else {
							$log_info .= ' | TEST COMPLETED AND UPDATED';
						}
					} else {
						if ( $se_time_diff <= $se_wait_time ) {
							$nxt = $se_wait_time - $se_time_diff;
							$log_info .= ' | TEST NOT YET DUE: Search Engine Constraint [due in ' . number_format( $nxt, 0 ) . ' seconds]';
						} elseif ( $kwd_time_diff <= ( $kwd_wait_time * 86400 ) ) {
							$nxt = $kwd_wait_time - ( $kwd_time_diff / 86400 );
							$log_info .= ' | TEST NOT YET DUE: Phrase Constraint [due in ' . number_format( $nxt, 2 ) . ' days]';
						} else {
							$log_info .= ' | ERROR: KEY PHRASE NOT SET';
						}
					}
					creator_update_log_file( 'CRON RANK ' . $engine, $log_info, 'append', true, 20 );
					$print_info .= ' | ----------' . $log_info;
					//this recording of the search url can be removed once it has been checked
					if ($saveURL && isset($ract->searchURL) && strlen($ract->searchURL)){
						creator_update_log_file( 'SURL ' . $engine, $ract->searchURL, 'append', true, 20 );
					}
					unset( $ract );
					//creator_debug_log($log_info, 'UPDATE LOG');
				} else {
					$error_info .= ' | SETUP ERROR: Search technology definition '.$engine;
				}
			}
		} else {
			$error_info .= ' | SETUP ERROR: Search engines not set';
		}
	} else {
		$error_info .= ' | SETUP ERROR: Main parameters not set';
	}
	if (strlen($error_info)){
		creator_update_log_file( 'RANK ERROR', $error_info, 'append', true, 20 );
		//creator_debug_log($error_info, 'ERROR RECORD);
	}
	if ($atts['display']) {
		$out = '<h2>'.esc_html($atts['title']).'</h2>';
		$info = explode(' | ',$print_info);
		$out .= "<h3>Logs</h3><pre>".var_export($info,true)."</pre>";
		$out .= "<h3>Errors</h3><p>" . ( strlen($error_info)? $error_info: 'None' ) . "</p>";
		return $out;
	}
}
add_shortcode('hub_ract_run_rank_test', 'hub_ract_run_rank_test');

/**
 * Run rank tests on the search engine results - data is not stored
 *
 * @param array $atts - attributes that can be passed to the shortcode
 * @return string
 */
function hub_ract_engine_check($atts){
	$atts = shortcode_atts(
		array(
			'title' => 'TEST Search Engine RegEx',
			'domain' => 'https://creatorseo.com',
			'engine' => 'google',
			//'engine' => 'bing',
			'keyword' => 'SEO Dublin',
			'sandbox' => true,
			'rival' => 'www.proseo.ie'
		),
		$atts
	);
	$debug = array(); $out = '';
	$sandbox = $atts['sandbox'];
	$atts['engine'] = strtolower($atts['engine']);
	if (strlen($atts['title'])>0){
		$out .= '<h4>'.$atts['title'].'</h4>';
	}
	if (isset($atts['domain']) && isset($atts['engine']) && isset($atts['keyword'])){
		$domain = $atts['domain'];
		$engine = $atts['engine']=='bing'? 'desktop.bing.ie': 'desktop.google.ie';
		$tmp = explode('.', $engine);
		$techno = isset($tmp[0])? $tmp[0]: '';
		$engineX = isset($tmp[1])? $tmp[1]: '';
		$region = (isset($tmp[2])? $tmp[2]: '').(isset($tmp[3])? $tmp[3]: '').(isset($tmp[4])? $tmp[4]: '');
		$competitors = array($atts['rival']);
		unset($tmp);
		if (strlen($techno) && strlen($engineX) && strlen($region)) {
			$phrase = $atts['keyword'];
			$debug = array('domain' => $domain, 'engine' => $engineX, 'region' => $region, 'Phrase' => $phrase, 'Rival' => $competitors[0]);
			$debug['ENVIRONMENT'] = $sandbox? 'Sandbox': 'Live';
			$ract = new hub_ract_ranking( true ); //******************************** set debug to false
			$ract->getSERPData( $domain, $engineX, $region, $phrase, $competitors, 10, $sandbox );
			$debug['USER AGENT'] = $ract->userAgent;
			$debug['SEARCH STRING'] = $ract->searchURL;
			$debug['ENGINE UID'] = $ract->engine['matchurl'];
			$debug['ENGINE TID'] = $ract->engine['matchtxt'];
			if (strlen($ract->error)){
				$debug['SEARCH ERRORS'] = $ract->error;
				$seStatus = 0;
			} else {
				$debug['SEARCH ERRORS'] = 'None';
				$seStatus = 1;
			}
			if ($seStatus){
				$debug['RESULTS'] = $ract->results; //save the results for the property url/phrase/engine/region
			}
			if ($ract->debugMe){
				$debug['RACT'] = $ract->debug;
				$debug['ERRORS'] = $ract->error;
				//$debug['THE HTML AS IT IS SEEN BY THE PROGRAM'] = htmlentities(substr($ract->html,0,2500));
			}
			$tmpHTML = substr($ract->html,0,100000);
			unset( $ract );
		} else {
			$debug['ENGINE ERROR'] = 'Engine not set correctly';
		}
	} else {
		$debug['INFORMATION ERROR'] = 'SETUP ERROR: Main parameters not set';
	}
	$out .= creator_count($debug)? ('<hr /><pre>'.print_r($debug,true).'</pre>'): ('<p>No data to show</p>');
	$out .= '<hr /><div class=""MYTESTDATA"><h2>SEARCH OUTPUT</h2><p>'.$tmpHTML.'</p><div>';
	unset( $ract );
	return $out;
}
add_shortcode('hub_ract_engine_check', 'hub_ract_engine_check');



//-------------------------------------------- RANK DATA TABLES AND CHARTS --------------------------------------------

/**
 * Display a ranking table based on the stored ranking results
 *
 * @param array $atts - attributes that can be passed to the shortcode
 * @param string $content - string to be added at the top of the output
 * @return string formatted table of results
 */
function hub_ract_display_rank_table($atts, $content=null){
	$atts = shortcode_atts(
		array(
			'wrapper'=> 'hub-ract-wrap',
			'class'=>'hub-ract-table',
			'title' => 'Ranking Results',
			'engine' => 'google', //google, bing, all
			'link' => 'none', //none, follow, nofollow
			'showdate' => 0, //0 - false 1 - true
			'rest' => 'hide' //show, hide
		),
		$atts
	);
	$keys = array();
	$seodata = get_option('hub_ract_data');
	$out = '';
	if (strlen($atts['title'])>0){
		$out .= '<h2>'.$atts['title'].'</h2>';
	}
	if (!is_null($content)){
		$out .= '<div class="hub_ract_notes">'.$content.'</div>';
	} else {
		$out .= '<div class="hub_ract_notes"><h4>Notes</h4><li>The key to the column headings is shown at the bottom of the table</li>'.
		           '<li>A ranking value of 0 means that the site is not in the top 50 results for the search engine</li></div>';
	}
	$out .= '<div class="'.$atts['wrapper'].'">';
	if (isset($seodata['page'])){
		//$data = json_decode($seodata['uri']);
		foreach ($seodata['page'] as $url => $arr1) {
			//$out .= '<h4>'.(strlen($url)? $url: 'ERROR: URL Not Set').'</h4>';
			foreach ($arr1 as $engine => $arr2) {
				if (strtolower($atts['engine']) == 'all' || stristr($engine, $atts['engine'])) {
					$out .= '<h4>' . ( strlen( $engine ) ? $engine : 'ERROR: Engine Not Set' ) . '</h4>';
					$out .= '<table class="' . $atts['class'] . '">';
					$out .= '<tbody>';
					$out .= '<tr>';
					$out .= '<th>Phrase</th>';
					$out .= '<th>My Site [0]</th>';
					$out .= '<th>Rival [1]</th>';
					$out .= '<th>Rival [2]</th>';
					$out .= '<th>Rival [3]</th>';
					$out .= $atts['showdate']? '<th>Updated</th>': '';
					$out .= '</tr>';
					foreach ( $arr2['SERP'] as $phrase => $arr3 ) {
						$out .= '<tr><td>' . ( strlen( $phrase ) ? $phrase : '--' ) . '</td>';
						$i   = 1;
						if ( is_array( $arr3['rivals'] ) && count( $arr3['rivals'] ) ) {
							foreach ( $arr3['rivals'] as $rival => $rank ) {
								// 3 columns for rivals - need to improve on this approach
								if ( $i <= 4 ) {
									//add the key into the array
									if ( ! in_array( $rival, $keys ) ) {
										$keys[] = $rival;
									}
									$out .= '<td>' . ( $rank > 0 ? $rank : 0 ) . '</td>';
									$i ++;
								}
							}
							$lastUpdate = $seodata['page'][$url][$engine]['SERP'][$phrase]['timestamp'];
							$out .= $atts['showdate']? '<td>'.date('Y-m-d H:i:s', $lastUpdate).'</td>': '';
						} else {
							$out .= '<td colspan="4">Competitors not set or ranking yet to run!</td>';
						}

						$out .= '</tr>';
					}
					$out .= '<tr><td colspan='. ($atts['showdate']? '6': '5') .' style="font-size: small">';
					$out .= '<strong>Key to Sites Compared</strong><br />';
					foreach ( $keys as $k => $uri ) {
						if ( strtolower( $atts['link'] ) == 'follow' || strtolower( $atts['link'] ) == 'nofollow' ) {
							$link = '<a href="' . $uri . '" ' . ( strtolower( $atts['link'] ) == 'nofollow' ? 'rel="nofollow"' : '' ) . ' target="_blank">' . $uri . '</a>';
						} else {
							$link = $uri;
						}
						$out .= ' ' . $k . ' - ' . $link . '<br />';
					}
					$out .= '</td></tr>';
					$out .= '</tbody>';
					$out .= '</table>';
				}
			}
		}
		if (strtolower($atts['rest']) == 'show'){
			$lnk = get_site_url().'/wp-json/ract/v1/data';
			$out .= '<h4>REST Link</h4><p class="hub_ract_block">Link to REST Data -> <a href="'.$lnk.'" target="_blank">Link</a></p>';
		}
		$out .= '</div>';
		unset($arr1);
		unset($arr2);
		unset($arr3);
	} else {
		$out .= '<p>Results not yet generated. Please check again later.</p><p><img src="https://hub5050.com/img/Table_hold.jpg" alt="Ranking" /></p>';
	}
	return ($out);
}
//add_shortcode( 'hub_ract_display_rank_table', 'hub_ract_display_rank_table' );


/**
 * Display a ranking table based on the stored ranking results
 *
 * @param array $atts - attributes that can be passed to the shortcode
 * @param string $content - string to be added at the top of the output
 * @return string formatted table of results
 */
function hub_ract_display_contender_table($atts, $content=NULL){
	$atts = shortcode_atts(
		array(
			'wrapper'=> 'hub-ract-wrap',
			'class'=>'hub-ract-table',
			'title' => 'Ranking Results',
			'engine' => 'google', //google, bing, all
			'type' => 'site', //site or social
			'link' => 'none' //none, follow, nofollow

		),
		$atts
	);
	$seodata = get_option('hub_ract_data');
	$out = '<div class="'.$atts['wrapper'].'">';
	if (strlen($atts['title'])>0){
		$out .= '<h2>'.$atts['title'].'</h2>';
	}
	if (!is_null($content)){
		$out .= '<p>'.$content.'</p>';
	}
	if (isset($seodata['page'])){
		foreach ($seodata['page'] as $url => $arr1) {
			$out .= '<h3>Site: '.(strlen($url)? $url: 'ERROR: Not Set').'</h3>';
			foreach ($arr1 as $engine => $arr2) {
				if (strtolower($atts['engine']) == 'all' ||stristr($engine, $atts['engine'])) {
					$out .= '<h4>Engine: ' . ( strlen( $engine )? $engine : 'ERROR: Not Set' ) . '</h4>';
					foreach ( $arr2['SERP'] as $phrase => $arr3 ) {
						$contenders = $atts['type'] == 'social' ? $arr3['social'] : $arr3['contenders'];
						if (is_array($contenders)) {
							$out .= '<h5>Search Phrase: ' . ( strlen( $phrase ) ? $phrase : 'ERROR: Not Set' ) . '</h5>';
							$out .= '<ol>';
							foreach ( $contenders as $contender => $x ) {
								if ( strlen( $contender ) ) {
									if ( strtolower( $atts['link'] ) == 'follow' || strtolower( $atts['link'] ) == 'nofollow' ) {
										$out .= '<li><a href="'.$contender.'" '.( strtolower( $atts['link'] ) == 'nofollow' ? 'rel="nofollow"' : '' ).' target="_blank">'.$contender.'</a></li>';
									} else {
										$out .= '<li>' . $contender . '</li>';
									}
								}
							}
							$out .= '</ol>';
						}
					}
				}
			}
		}
		$out .= '</div>';
		unset($arr1);
		unset($arr2);
		unset($arr3);

	} else {
		$out .= '<p>Results not yet generated. Please check again later.</p><p><img src="https://hub5050.com/img/Table_hold.jpg" alt="Ranking" /></p>';
	}
	return ($out);
}
//add_shortcode( 'hub_ract_display_contender_table', 'hub_ract_display_contender_table' );

/**
 * Display ranking trend charts
 *
 * @return string - debug text
 */
function hub_ract_display_trend_charts( $atts, $content=null ){
	$atts = shortcode_atts(
		array(
			'wrapper'=> 'hub-ract-wrap',
			'title' => '',
			'width' => 0,
			'height' => 0,
			'show' => true
		),
		$atts
	);
	if ($atts['width']>0 || $atts['height']>0){
		$canvas_size = 'style="';
		$canvas_size .= $atts['width']>0? ' width:'.$atts['width'].'px;': '';
		$canvas_size .= $atts['height']>0? ' height:'.$atts['height'].'px;': '';
		$canvas_size .= '"';
	} else {
		$canvas_size = '';
	}
	$out = '<div class="'.$atts['wrapper'].'">';
	if (strlen($atts['title'])>0){
		$out .= '<h2>'.$atts['title'].'</h2>';
	}
	if (!is_null($content)){
		$out .= '<div class="hub_ract_notes"><p>'.$content.'</p></div>';
	} else {
		$out .= '<div class="hub_ract_notes"><h4>Notes</h4><li>Best ranking of 1 is shown at the top of the chart, worst at the bottom </li>'.
		       '<li>Ranking values greater than 50 are displayed on the charts as 50 (i.e. no rank)</li></div>';
	}
	$out .= '<div id="hub_ract_chart_container" '.$canvas_size.'><h3>Charts loading...</h3></div>';
	$out .= '</div>';
	return ($out);
}
//add_shortcode('hub_ract_display_trend_charts', 'hub_ract_display_trend_charts' );

/**
 * Display ranking trend charts
 *
 * @param array $atts - attributes that can be passed to the shortcode
 * @param string $content - string to be added at the top of the output
 * @return string - debug text
 */
function hub_ract_display_market_leaders( $atts, $content=null ){
	$atts = shortcode_atts(
		array(
			'wrapper'=> 'hub-ract-wrap',
			'title' => '',
			'width' => 0,
			'height' => 0,
			'show' => true
		),
		$atts
	);
	if ($atts['width']>0 || $atts['height']>0){
		$canvas_size = 'style="';
		$canvas_size .= $atts['width']>0? ' width:'.$atts['width'].'px;': '';
		$canvas_size .= $atts['height']>0? ' height:'.$atts['height'].'px;': '';
		$canvas_size .= '"';
	} else {
		$canvas_size = '';
	}
	$out = '<div class="'.$atts['wrapper'].'">';
	if (strlen($atts['title'])>0){
		$out .= '<h2>'.$atts['title'].'</h2>';
	}
	if (!is_null($content)){
		$out .= '<div class="hub_ract_notes"><p>'.$content.'</p></div>';
	} else {
		$out .= '<div class="hub_ract_notes"><h4>Market Leaders taking into account all the search terms (key phrases) added</h4>'.
		     '<li>The space is defined by the keywords, the search engine and the region chosen</li>'.
		     '<li>The top sites for your chosen keywords are shown for each search engine and region</li>'.
		     '<li>These sites are the Market Leaders for the keywords, search engine and region chosen</li></div>';
	}
	$out .= '<div id="hub_ract_pie_container" '.$canvas_size.'><h3>Charts loading...</h3></div>';
	$out .= '</div>';
	return ($out);
}
//add_shortcode('hub_ract_display_market_leaders', 'hub_ract_display_market_leaders' );




//----------------------------------------------------- UTILITIES -----------------------------------------------------

/**
 * Display option setting values
 * @return string
 */
function hub_ract_display_option_values($atts=array(), $content=null){
	$atts = shortcode_atts(
		array(
			'title' => 'Stored Variables',
			'licence'=> 1,
			'options'=> 1,
			'data'=>0,
			'links' => 0
		),
		$atts
	);
	$out = '<h2>'.(strlen($atts['title'])? $atts['title']: 'Stored Variables').'</h2>';
	if ($atts['licence']){
		$api = get_option('hub_ract_api');
		$out .= '<h2>License</h2>';
		$out .= '<div>';
		$out .= '<p>API Value: '.$api.'</p>';
		$out .= '</div>';
	}
	if ($atts['options']){
		$options = get_option('hub_ract_options');
		$out .= '<h2>Option Settings</h2>';
		$out .= '<div>';
		$out .= '<pre>'.var_export($options,true).'</pre>';
		$out .= '<hr /></div>';
	}
	if ($atts['data']){
		$results = get_option('hub_ract_data');
		$out .= '<h2>Latest Results</h2>';
		$out .= '<div>';
		if (is_array($results)){
			$out .= '<pre>'.var_export($results,true).'</pre>';
		} else {
			$out .= '<p>Data not yet available</p>';
		}
		$out .= '<hr /></div>';
	}
	if ($atts['links']){
		$results = get_option('hub_ract_blink');
		$out .= '<h2>Link Data</h2>';
		$out .= '<div>';
		if (is_array($results)){
			$out .= '<pre>'.var_export($results,true).'</pre>';
		} else {
			$out .= '<p>Data not yet available</p>';
		}
		$out .= '<hr /></div>';
	}
	return ($out);
}
add_shortcode('hub_ract_display_option_values', 'hub_ract_display_option_values');

/**
 * Shortcode function to display the log file captured for this plugin
 *
 * @param array $atts
 * @param text $content
 *
 * @return string
 */
function hub_ract_view_log_file( $atts, $content=null ) {
	$atts = shortcode_atts(
		array(
			'title' => 'DEBUG INFORMATION',
			'file' => 'hub_ract_log',
			'attribute' => 'ALL',
			'encode' => true
		),
		$atts
	);
	$title = (is_null($content) || strlen($content)==0)? $atts['title']: $content;
	$data = get_option( $atts['file'], 'Nothing to show' );
	$attribute = strtoupper($atts['attribute']);
	$out = '<h3>Attribute: '.$attribute.'</h3>';
	$out .= '<h5>'.$title.'</h5>';
	if (is_array($data)){
		if ($attribute=='ALL'){
			$out .= '<pre>'.var_export($data,true).'</pre>';
		} elseif( in_array($attribute,$data) ){
			$out .= '<pre>'.var_export($data[$attribute],true).'</pre>';
		} else {
			$out .= '<p>Attribute - '.$attribute.' not found in the data</p>';
		}
	} else {
		$out .= '<p>Data file '.$atts['file'].' not found</p>';
	}
	return $out;
}
add_shortcode( 'hub_ract_view_log_file', 'hub_ract_view_log_file' );

//----------------------------------------- CRON JOB INFORMATION -----------------------------------------

/**
 * Get a list of all tyhe CRON jobs that are set up for on this site.
 * The values are for information only and indicate whether the expected CRON jobs are in place.
 * @param array $atts (e.g.
 * 	'wrapper' =>  'hub-ract-wrap',
 *  'class'   =>  'hub-ract-table'
 * @param text $content

 * @return string
 */
function hub_ract_view_cron_settings($atts=array(), $content=null) {
	$atts = shortcode_atts(
		array(
			'wrapper'=> 'hub-ract-wrap',
			'title' => 'Cron Events Scheduled',
			'class'=>'widefat fixed'
		),
		$atts
	);
	$title = (is_null($content) || strlen($content)==0)? $atts['title']: $content;
	$cron = _get_cron_array();
	$schedules = wp_get_schedules();
	$date_format = 'M j, Y @ G:i';

	$out = '<div class="'.$atts['wrapper'].'" id="cron-gui">';
	$out .= '<h2>'.$title.'</h2>';

	$out .= '<table>';
		$out .= '<thead>';
			$out .= '<tr>';
				$out .= '<th scope="col">Next Run (GMT/UTC)</th>';
				$out .= '<th scope="col">Schedule</th>';
				$out .= '<th scope="col">Hook Name</th>';
			$out .= '</tr>';
		$out .= '</thead>';
		$out .= '<tbody>';
			foreach ( $cron as $timestamp => $cronhooks ) {
				foreach ( (array) $cronhooks as $hook => $events ) {
					foreach ( (array) $events as $event ) {
						$out .= '<tr>';
						$out .= '<td>';
						$out .= date_i18n( $date_format, wp_next_scheduled( $hook ) );
						$out .= '</td>';
						$out .= '<td>';
						if ( $event[ 'schedule' ] ) {
							$out .= $schedules[ $event[ 'schedule' ] ][ 'display' ];
						} else {
							$out .= 'One-time';
						}
						$out .= '</td>';
						$out .= '<td>'.$hook.'</td>';
						$out .= '</tr>';
					}
				}
			}
		$out .= '</tbody>';
	$out .= '</table>';
    $out .= '</div>';
	return ($out);
}
add_shortcode( 'hub_ract_view_cron_settings', 'hub_ract_view_cron_settings' );

//----------------------------------------- FIXES -----------------------------------------
/**
 * Update the search engine format in options
 * DELETE THIS FUNCTION AT THE NEXT VERSION
 */
function hub_ract_fix_engine_definition() {
	$options  = get_option( 'hub_ract_options' );
	$engines = $options['page'][0]['engines'];
	foreach ( $engines as $k => $engine ) {
		if (stristr($engine, '|')){
			list($engineX, $region, $techno) = explode('|', $engine);
			$newEngine = $techno . '.' . $engineX . '.' . $region;
			$options['page'][0]['engines'][$k] = $newEngine;
		}
	}
	update_option( 'hub_ract_options', $options );
}
//add_shortcode('hub_ract_fix_engine_definition', 'hub_ract_fix_engine_definition');


/**
 * Check and fix the URL values in the options files if necessary.
 * @return string - update checks advice
 */
function hub_ract_fix_options($atts) {
	$atts = shortcode_atts(
		array(
			'display'=> true //hide or show the results of the run
		),
		$atts
	);
	$out = '';
	$log_info = '';
	$domain = home_url();
	//$domain = stristr(home_url(),'vhost9')? 'https://creatorseo.com': home_url();
	//$domain = 'https://hub5050.com';
	$options  = get_option( 'hub_ract_options' );

	$out .= '<h3>Start Options</h3><pre>'.var_export($options,true).'</pre>';
	//check that the options table has the correct domain value
	if ($options['domain'] <> $domain){
		$log_info .= ' | Domains do not match - update';
		$options['domain'] = $domain;
		$options['page'][0]['rivals']['url'][0] = $domain;
		$url_parse = wp_parse_url($domain);
		$options['page'][0]['rivals']['domain'][0] = $url_parse['host'];
		if ( update_option( 'hub_ract_options', $options ) === false ) {
			$log_info .= ' | ERROR: Options could not be updated';
		} else {
			$log_info .= ' | SUCCESS: Options updated';
		}
	} else {
		$log_info .= ' | Domains match - no change needed';
	}
	$out .= '<h3>Updated</h3><pre>'.var_export($options,true).'</pre><hr />';
	//check that the SEO data values match
	$seodata  = get_option( 'hub_ract_data' );
	$out .= '<h3>Start Data</h3><pre>'.var_export($seodata,true).'</pre>';
	$pages = $seodata['page'];
	$n = 0;
	foreach ( $pages as $uri => $page ) {
		if ($n == 0){
			if ($uri <> $domain) {
				$seodata['page'][ $domain ] = $page;
				$log_info .= ' | URI Created ['.$domain.']';
				unset( $seodata['page'][ $uri ] );
				$log_info .= ' | URI Removed ['.$uri.']';
			} else {
				$log_info .= ' | URI match found ['.$uri.']';
			}
		} else {
			unset($seodata['page'][$uri]);
			$log_info .= ' | URI Removed ['.$uri.']';;
		}
	}
	if ( update_option( 'hub_ract_data', $seodata ) === false ) {
		$log_info .= ' | ERROR: Data could not be updated';
	} else {
		$log_info .= ' | SUCCESS: Data updated';
	}
	$out .= '<h3>Updated</h3><pre>'.var_export($seodata,true).'</pre><hr />';
	$debug = explode(' | ', $log_info);
	$out .= '<h3>Log</h3><pre>'.var_export($debug, true).'</pre><hr />';
	creator_update_log_file( 'UPDATE DOMAIN', $log_info, 'append', true, 3 );
	return ($atts['display']? $out: true);
}
//add_shortcode('hub_ract_fix_options', 'hub_ract_fix_options');



//-----------------------------------------  -----------------------------------------

/**
 * Just a sandbox to test some of the functionality
 * @return string - update checks advice
 */
function hub_ract_sandbox() {
	$out = '';
	$out .= '<h3>Hi from us</h3>';
	$out .= '<p>We hope you are having a good day!</p>';
	return $out;
}
add_shortcode('hub_ract_sandbox', 'hub_ract_sandbox');
