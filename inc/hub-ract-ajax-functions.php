<?php
/**
 * Project: wordpress PhpStorm [dicap-ajax-functions.php]
 * Copyright: (C) 2019 clinton
 * Developer:  clinton
 * Created on 12/07/2018 [19:46]
 *
 * Description: Main Admin Dashboard shortcode
 *
 * This program is the property of the developer and is not intended for distribution. However, if the
 * program is distributed for ANY reason whatsoever, this distribution is WITHOUT ANY WARRANTEE or even
 * the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 *
 */

// ---------------------------- Admin Actions ----------------------------



//==================================== CHART ACTIONS ====================================

/**
 * Draw Social Charts
 *
 * @param $atts - attributes passed to the function
 */
function ract_social_chart1( $atts ) {
	$atts = shortcode_atts(
		array(
			'wrapper'=> 'dicap-wrap',
			'title' => 'Search Space Map',
			'engine' => 'all', //google, bing, all
			'records' => 10, //number of records to show
			'useDomain' => true, //true, false
			'percentage' => true, //true, false
			'json_output'=>false //true, false
		),
		$atts
	);
	$out = '';
	$data = array('type'=>'html');
	$out .= '<div>';
	$out .= '<h1>Hello There</h1>';
	$out .= '<p>You are through to the wonderful Wordpress AJAX service. We hope you enjoy your visit.</p>';
	$out .= '</div>';
	$data['output'] = $out;
	echo json_encode($data);
	wp_die(); //NB!! to prevent the whitespace error
}

/**
 * Prepare data for Social Engagement pie charts that show the split between different social media
 *
 * @param $atts chart attributes
 */
function ract_social_charts( $atts ) {
	$atts = shortcode_atts(
		array(
			'wrapper'=> 'dicap-wrap',
			'title' => 'Social Engagement by Source',
			'show_all_social' => false,
			'show_histogram' => true, //social split histogram
			'show_combined' => true, //combined social split data pie
			'show_detail' => true, //social split by page pie
			'debug' => false
		),
		$atts
	);
	$social_sites = array(
		'Facebook' => 0,
		'Twitter' => 0,
		'Google+' => 0,
		'Pinterest' => 0,
		'LinkedIn' => 0,
		'Instagram' => 0,
		'WhatsApp' => 0,
		'Digg' => 0,
		'Reddit' => 0,
		'StumbleUpon' => 0,
		'Delicious' => 0,
		'Livejournal' => 0,
		'Tumblr' => 0,
		'VKontakte' => 0
	);
	$legends = array();
	$out = '';
	$type = (isset($atts['debug']) && $atts['debug'])? 'html': 'chart';
	$data = array('type'=>$type);
	if (isset($_POST['attrib'])){
		$attrib = explode('|', $_POST['attrib']);
		$atts['show_all_social'] = isset($attrib[0])? $attrib[0]: $atts['show_all_social'];
		$atts['show_histogram'] = isset($attrib[1])? $attrib[1]: $atts['show_histogram'];
		$atts['show_combined'] = isset($attrib[2])? $attrib[2]: $atts['show_combined'];
		$atts['show_detail'] = isset($attrib[3])? $attrib[3]: $atts['show_detail'];
	}
	if ( isset( $_POST['my_hash'] ) && strlen( $_POST['my_hash'] ) ) {
		$d_hash = sanitize_text_field($_POST['my_hash']); //not used at the moment
		$links = get_option('hub_ract_blink');
		$dat = hub_ract_set_referers($links);
		//$out .= (isset($atts['debug']) && $atts['debug'])? '<pre>'.var_export($dat['social'],true).'</pre><hr />': '';
		$rex = $dat['social'];
		if (is_array($rex) && count($rex)){
			//set the labels
			if (isset($atts['show_all_social']) && $atts['show_all_social']){
				$legends = $social_sites;
			} else {
				foreach ( $rex as $pg=>$arr1 ) {
					foreach ( $arr1 as $soc => $arr2 ){
						if (!isset($legends[$soc])){
							$legends[$soc] = 0;
						}
					}
				}
			}
			//----- Social Split Histogram -----
			if (isset($atts['show_histogram']) && $atts['show_histogram']){
				$plotData = array('id'=>'xx', 'title_1'=>'', 'title_2'=>'', 'title_3'=>'',);
				$plotData['id'] = 'chart_histogram';
				$plotData['title_1'] = isset($atts['title'])? esc_html($atts['title']): 'Social Engagement by Source';
				$plotData['title_2'] = 'Total Split for Period by Page';
				$date1 = strtotime("0 day"); //start time default
				$date2 = strtotime("-1 month"); //end time default
				$legends = (isset($atts['show_all_social']) && $atts['show_all_social'])? $social_sites: $legends;
				$cht = new creator_chart_data('bar');
				$cht->setChartTitle('Social Split for Period by Page');
				$cht->setChartLegend('right');
				$cht->setAxisTitle('xAxes', 'Social Media Platform');
				$cht->setAxisTitle('yAxes', 'Total Visits');
				$cht->setPointStyle( array( 'borderColor' => '#696969', 'borderWidth' => 2 ) );
				$i = 0;
				foreach ( $rex as $pg=>$arr1 ) {
					$post_id = substr($pg,2);
					$post = get_post( $post_id );
					if (isset($post->post_name)){
						$label = $post->post_name;
						$segments[$post_id] = (isset($atts['show_all_social']) && $atts['show_all_social'])? $social_sites: $legends;
						foreach ( $arr1 as $soc => $arr2 ) {
							//array with count by social and page
							if (isset($segments[$post_id][$soc])){
								$segments[$post_id][$soc] += (isset($arr2['n']) && $arr2['n'])? $arr2['n']: 0;
							} else {
								$segments[$post_id][$soc] = (isset($arr2['n']) && $arr2['n'])? $arr2['n']: 0;
							}
							$date1 = (isset($arr2['f']) && $arr2['f']<$date1)? $arr2['f']: $date1;
							$date2 = (isset($arr2['l']) && $arr2['l']>$date2)? $arr2['l']: $date2;
						}
						//$cht->addNewDataSet( $label, array_values( $segments[$post_id] ), false, $cht->colorSet2[$i] );
						$cht->addNewDataSet( $label, array_values( $segments[$post_id] ), false );
						$i++;
					}
				}
				$plotData['title_3'] = 'Period: '.date( 'Y-m-d H:i:s', $date1 ).' - '.date( 'Y-m-d H:i:s', $date2 );
				$cht->setDataLabels( array_keys( $legends ) );
				$cht->stackAxes('x');
				$cht->stackAxes('y');
				$plotData['data'] = $cht->get_chart_data();
				//$cht->clear();
				unset( $cht );
				$out .= (isset($atts['debug']) && $atts['debug'])? '<pre>'.var_export($plotData,true).'</pre><hr />': '';
				$data['sets'][] = $plotData;
				unset($segments);
			}
			//----- Combined data - across pages -----
			if (isset($atts['show_combined']) && $atts['show_combined']){
				$plotData = array('id'=>'xx', 'title_1'=>'', 'title_2'=>'', 'title_3'=>'',);
				$segments = (isset($atts['show_all_social']) && $atts['show_all_social'])? $social_sites: $legends;
				$plotData['id'] = 'chart_pie_combined';
				$plotData['title_1'] = isset($atts['title'])? esc_html($atts['title']): 'Social Engagement by Source';
				$plotData['title_2'] = 'Total Split for Period';
				$date1 = strtotime("0 day"); //start time default
				$date2 = strtotime("-1 month"); //end time default
				foreach ( $rex as $pg=>$arr1 ) {
					$post_id = substr($pg,2);
					//$out .= '<h3>Post: '.$post->post_name.'</h3><hr />';
					if (isset($post_id)){
						foreach ( $arr1 as $soc => $arr2 ) {
							$segments[$soc] += (isset($arr2['n']) && $arr2['n'])? $arr2['n']: 0;
							$date1 = (isset($arr2['f']) && $arr2['f']<$date1)? $arr2['f']: $date1;
							$date2 = (isset($arr2['l']) && $arr2['l']>$date2)? $arr2['l']: $date2;
						}
						$plotData['title_3'] = 'Period: '.date( 'Y-m-d H:i:s', $date1 ).' - '.date( 'Y-m-d H:i:s', $date2 );
						$cht = new creator_chart_data('pie');
						$cht->setChartTitle('Total Social Split');
						$cht->setChartLegend('right');
						$cht->setPointStyle( array( 'borderColor' => '#cdcdcd', 'borderWidth' => 1 ) );
						$pie_labels = array_keys( $segments );
						$cht->setDataLabels( $pie_labels );
						$cht->addNewDataSet( 'Contenders', array_values( $segments ), false, $cht->colorSet2 );
						$cht->setPieCutoutPercentage( 10 );
						$plotData['data'] = $cht->get_chart_data();
						//$cht->clear();
						unset( $cht );
						$out .= (isset($atts['debug']) && $atts['debug'])? '<pre>'.var_export($plotData,true).'</pre><hr />': '';
					}
				}
				$data['sets'][] = $plotData;
				unset($segments);
			}
			//----- Detailed data - by page -----
			if (isset($atts['show_detail']) && $atts['show_detail']){
				foreach ( $rex as $pg=>$arr1 ) {
					$plotData = array('id'=>'xx', 'title_1'=>'', 'title_2'=>'', 'title_3'=>'',);
					$post_id = substr($pg,2);
					$post   = get_post( $post_id );
					//$out .= '<h3>Post: '.$post->post_name.'</h3><hr />';
					if (isset($post->post_name)){
						$segments = (isset($atts['show_all_social']) && $atts['show_all_social'])? $social_sites: $legends;
						$date1 = strtotime("0 day"); //start time default
						$date2 = strtotime("-1 month"); //end time default
						foreach ( $arr1 as $soc => $arr2 ) {
							$segments[$soc] += (isset($arr2['n']) && $arr2['n'])? $arr2['n']: 0;
							$date1 = (isset($arr2['f']) && $arr2['f']<$date1)? $arr2['f']: $date1;
							$date2 = (isset($arr2['l']) && $arr2['l']>$date2)? $arr2['l']: $date2;
						}
						$slug   = $post->post_name;
						$plotData['id'] = 'chart_pie_'.$post_id;
						$plotData['title_1'] = isset($atts['title'])? esc_html($atts['title']): 'Social Engagement by Source';
						$plotData['title_2'] = 'Split for Page: '.$slug;
						$plotData['title_3'] = 'Period: '.date( 'Y-m-d H:i:s', $date1 ).' - '.date( 'Y-m-d H:i:s', $date2 );
						$cht = new creator_chart_data('pie');
						$cht->setChartTitle('Page: ' . $slug);
						$cht->setChartLegend('right');
						$cht->setPointStyle( array( 'borderColor' => '#cdcdcd', 'borderWidth' => 1 ) );
						$pie_labels = array_keys( $segments );
						$cht->setDataLabels( $pie_labels );
						$cht->addNewDataSet( 'Contenders', array_values( $segments ), false, $cht->colorSet2 );
						$cht->setPieCutoutPercentage( 10 );
						$plotData['data'] = $cht->get_chart_data();
						//$cht->clear();
						$data['sets'][] = $plotData;
						unset( $cht );
						$out .= (isset($atts['debug']) && $atts['debug'])? '<pre>'.var_export($plotData,true).'</pre><hr />': '';
					}
				}
				unset($segments);
			}
		} else {
			$data[0] = 'ERROR: No Records';
		}
	} else {
		$data[0] = 'ERROR: Hash not set';
	}

	if(isset($atts['debug']) && $atts['debug']){
		unset($data['sets']);
		$out .= '<div>';
		$out .= '<h1>Hello There</h1>';
		$out .= '<p>You are through to the wonderful Wordpress AJAX service. We hope you enjoy your visit.</p>';
		$out .= '</div>';
		$data['output'] = $out;
	}
	echo json_encode($data);
	wp_die(); //NB!! to prevent the whitespace error
}



//==================================== SEARCH SPACE ACTIONS ====================================


//==================================== LINK ACTIONS ====================================



//==================================== SITEMAP ACTIONS ====================================


//====================================  ====================================

/**
 * Template for new AJAX functions
 *
 * @param $atts - attributes passed to the function
 */
function ract_ajax_template( $atts ) {
	$data = array('type'=>'html');
	$out = '<div>';
	if ( isset( $_POST['my_hash'] ) && strlen( $_POST['my_hash'] ) ) {
		$d_hash = sanitize_text_field($_POST['my_hash']);
		$dash   = new dicap_dashboard( $d_hash, false );
		$info   = $dash->mapInfo[0]; //get the url to be analysed from the sitemap - usually the domain
		$data['url'] = esc_url($dash->userDomain, array('http', 'https')); //full domain with scheme
		$data['title'] = 'Contender Position by Key Phrase '.$data['url'];
		if ( $dash->userHash ) {
			// ---------- START: Data creation here ----------
			$out .= '<h4>Home URL</h4><p>'.home_url().'</p>';
			$out .= '<pre>'.var_export($info, true).'</pre>';
			$url = 'https://www.facebook.com:8080/creatorseo?a=17#other';
			$parts = wp_parse_url($url);
			$out .= '<h4>Home URL</h4><p>'.home_url().'</p>';
			//$out .= '<h5>Info</h5><pre>'.var_export($info, true).'</pre>';
			//$out .= '<h5>URL Parts</h5></h5><pre>'.var_export($parts, true).'</pre>';
			$a = array('sex'=>array('male', 'female'), 'animals'=>array('dog'=>17, 'chickin'=>7), 'colors'=>array('white'));
			$b = array('colors'=>array('red', 'green', 'blue'),'animals'=>array('cat'=>1, 'dog'=>9, 'pig'=>5));
			$c = array('animals'=>array('cat'=>22));
			$x = array_replace_recursive($b, $a, $c);
			$out .= '<h5>Array Check</h5><pre>'.var_export($x, true).'</pre>';
			// ---------- END: Data creation here ----------
		}
		unset ($dash);
	} else {
		$out .= '<h3>ERROR</h3><p>Hash not set</p>';
	}

	$out .= '</div>';
	$data['output'] = $out;
	echo json_encode($data);
	wp_die(); //NB!! to prevent the whitespace error
}


