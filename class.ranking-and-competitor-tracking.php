<?php
/**
 * Project: Hub5050 Ranking and Competitor Tracking (class.hub-ract-base.php)
 * Copyright: (C) 2011 Clinton
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
require_once (HUB_RACT_DIR.'inc/hub-ract-defines.php');
require_once (HUB_RACT_DIR.'inc/class.hub-ract-base.php');
require_once (HUB_RACT_DIR.'inc/class.hub-ract-audit.php');
require_once( HUB_RACT_DIR . 'inc/hub-ract-shortcodes.php' );
require_once( HUB_RACT_DIR . 'inc/hub-ract-extend-rest.php');
require_once (HUB_RACT_DIR.'inc/hub-ract-ajax-functions.php');
if (!class_exists( 'creator_chart_data' )) {
	require_once (HUB_RACT_DIR.'inc/class.creator-chart-library.php');
}
if (!function_exists('creator_color_picker')){
	require_once (HUB_RACT_DIR.'inc/creator-function-lib.php');
}

class hub_ract_metrics {
    protected $pluginloc;
	protected $homeInfo = array(); //scheme, domain etc.
	protected $options = array(
		'domain' => '',
		'license' => '',
		'level' => '1',
		'refresh' => '360', //seconds
		'zone' => 'ie',
		'page' => array(
			0 => array(
				'engines' => array(0=>'desktop.google.ie'),
				'keywords' => array(),
				'rivals' => array(
					'url' => array(),
					'domain' => array()
				)
			)
		)
	);
	protected $logfile = array(
		'LOGFILE' => array('2018-03-01 00:00:00'=>'Activate 123')
	);
	protected $seodata = array();
	protected $blink = array();
	protected $arr404 = array();

	/**
	 *
	 * NOTE - WHY DOES THIS RUM MORE THAN ONCE FOR A SINGLE REFRESH?
	 *
	 * Initialise the plugin class
	 * @param string $loc the full directory and filename for the plugin
	 */
	public function __construct($loc) {
		$this->pluginloc = strlen($loc)? $loc: __FILE__;
		$basename = plugin_basename($this->pluginloc);
		$this->homeInfo = wp_parse_url(home_url());
		//$this->options['domain'] = home_url(); // ** bring this back later **
		$this->options['domain'] = stristr(home_url(),'vhost9')? 'https://creatorseo.com': home_url();
		$this->options['postID'] = url_to_postid( home_url() );
		$this->options['page'][0]['rivals']['url'][0] = $this->options['domain'];
		$this->logfile = array('LOGFILE' => array(date('Y-m-d H:i:s') => 'Activate'));
		if (is_admin()){
			add_action('admin_enqueue_scripts', array($this, 'hub_ract_enqueue_admin'));
			add_action('admin_init',array($this, 'hub_ract_register_settings'));
			add_action('admin_menu', array($this, 'hub_ract_main_menu'));
			add_filter('plugin_action_links_'.$basename, array($this, 'hub_ract_settings_link'));
			//manage the stored variable and option values when registering or deactivating
			register_activation_hook($loc, array($this, 'hub_ract_load_options'));
			register_deactivation_hook($loc, array($this, 'hub_ract_unset_options'));
			//register_uninstall_hook ($loc, array($this, 'hub_ract_uninstall'));
			register_uninstall_hook ($loc, 'hub_ract_uninstall');
		} else {
			add_action('wp_enqueue_scripts', array($this, 'hub_ract_enqueue_main'));
		}
		//Load a function that runs after all plugins are registered to ensure that all plugin filters and actions are defined
		add_action('plugins_loaded', array($this, 'hub_ract_late_loader'));
		//set the ajax hooks to run when the license is to be updated
		add_action( 'wp_ajax_ract_set_license', array($this, 'hub_ract_license_ajax'));
		add_action( 'wp_ajax_ract_social_charts', 'ract_social_charts');
		//----- Create a scheduled cron hook -----
		add_filter('cron_schedules', array($this, 'hub_ract_cron_add_custom_time'));
		add_action('wp', array($this, 'hub_ract_cron_settings'));
		add_action('ract_cron_hook', array($this, 'hub_ract_rank_tests'));
//		creator_update_log_file('DEBUG', 'Clear debug logs','delete');
//		creator_update_log_file('RANK ERROR', 'Clear debug logs','delete');
//		creator_update_log_file('UPGRADE CHECK', 'Clear debug logs','delete');
//		creator_update_log_file('CRON RANK DESKTOP.GOOGLE.IE', 'Clear debug logs','delete');
//		creator_update_log_file('CRON RANK DESKTOP.BING.IE', 'Clear debug logs','delete');
	}

// -------------------- Load styles and scripts --------------------

	function hub_ract_enqueue_main(){
		//only run the script if the user is not logged in
		if (!is_user_logged_in()){
			$loc = plugin_dir_path( __FILE__ );
			$options = get_option('hub_ract_options'); //loading parameters from the database pass to the AJAX call function
		}
		wp_enqueue_style('ranking-and-competitor-tracking-css', plugins_url('css/hub-ract.css', __FILE__));
	}

	/**
	 * @param $hook - admin_enqueue_scripts provides the $hook_suffix for the current admin page and this is used to load the scripts only for the admin pages
	 * HOOK: "toplevel_page_ract-insights"
	 * HOOK: "hub5050-insights_page_ract-ranking"
	 * HOOK: "hub5050-insights_page_ract-contenders"
	 * HOOK: "hub5050-insights_page_ract-trends"
	 * HOOK: "hub5050-insights_page_ract-setup"
	 */
	function hub_ract_enqueue_admin($hook){
		//creator_debug_log('HOOK', $hook); //check for the hook name to use
		if (stristr($hook, 'ract')) {
			wp_enqueue_style( 'ranking-and-competitor-tracking-css', plugins_url( 'css/hub-ract.css', __FILE__ ) );
			//wp_enqueue_script( 'ranking-and-competitor-tracking-chart', plugins_url( 'js/Chart.bundle.min.js', __FILE__ ), array( 'jquery' ), '1.3.2', false );
            wp_enqueue_script( 'ract_charts', 'https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.9.4/Chart.bundle.min.js', array( 'jquery' ), '2.9.4', true );
			wp_enqueue_script( 'ranking-and-competitor-tracking-admin-js', plugins_url( ( 'js/hub-ract-admin.js?x=' . rand( 5, 300 ) ), __FILE__ ), array( 'jquery' ), '1.0', true );
			wp_localize_script( 'ranking-and-competitor-tracking-admin-js', 'varz', array('ajax_url' => admin_url( 'admin-ajax.php' ), 'key_url'  => base64_encode( $this->options['domain'])) );
			wp_enqueue_script( 'ranking-and-competitor-tracking-js', plugins_url( ( 'js/hub-ract.js?x=' . rand( 5, 300 ) ), __FILE__ ), array( 'jquery' ), '1.0', true );
		}
	}

	/**
	 * Late loading function for actions that runs after all plugins are loaded
	 */
	function hub_ract_late_loader(){
		//NB!! DELETE THIS AT NEXT VERSION ROLL-OUT
        //remove the Bing results check
		$optx = get_option('hub_ract_options');
        //['page'][0]['engines'][1]
		if ( isset($optx['page'][0]['engines'][1])){
            unset($optx['page'][0]['engines'][1]);
            update_option( 'hub_ract_options', $optx );
        }
		unset($optx);
	}

// -------------------- Options and Variables - Admin Settings Form Definition --------------------

	function hub_ract_main_menu() {
		//add_menu_page( $page_title, $menu_title, $capability, $menu_slug, $function, $icon_url, $position );
		add_menu_page( 'Ranking and Competitor Tracking', 'Hub5050 Insights', 'manage_options', 'ract-insights', array($this,'hub_ract_options_page'),
			plugins_url('/images/ract-icon.png',__FILE__),30);
		//add_submenu_page( $parent_slug, $page_title, $menu_title, $capability, $menu_slug, $function );
		add_submenu_page( 'ract-insights' , 'Ranking Results by Key Phrase' , 'Ranking Results', 'manage_options', 'ract-ranking' , 'hub_ract_results_page');
		add_submenu_page( 'ract-insights' , 'Ranking Trends Over Time' , 'Rank Trends', 'manage_options', 'ract-trends' , 'hub_ract_trends_page');
		add_submenu_page( 'ract-insights' , 'The Real Competition by Key Phrase' , 'Competition', 'manage_options', 'ract-contenders' , 'hub_ract_contenders_page');
		add_submenu_page( 'ract-insights' , 'Market Leader Map' , 'Market Leaders', 'manage_options', 'ract-spacemap' , 'hub_ract_spacemap_page');
		add_submenu_page( 'ract-insights' , 'Status Check / Next Run Check' , 'Status Check', 'manage_options', 'ract-status' , 'hub_ract_status_page');
		add_submenu_page( 'ract-insights' , __( 'Ranking and Competitor Tracking' ) , __( 'Setup' ), 'manage_options', 'ract-setup' , array($this,'hub_ract_options_page'));
        //add_submenu_page( 'ract-insights' , 'View log file' , 'Log file', 'manage_options', 'ract-log' , 'hub_ract_log_page');
	}

	function hub_ract_admin_menu() {
		add_management_page('Ranking and Competitor Tracking', 'Ranking Performance', 'manage_options', 'ranking-and-competitor-tracking',
			array($this,'hub_ract_options_page'));
	}

	function hub_ract_settings_link($links) {
		//$url = get_admin_url().'tools.php?page=ranking-and-competitor-tracking';
		$url = get_admin_url().'admin.php?page=ract-setup';
		$settings_link = '<a href="'.$url.'">' . __("Settings") . '</a>';
		array_unshift( $links, $settings_link );
		return $links;
	}

	function hub_ract_register_settings() {
		register_setting('hub_ract_group', 'hub_ract_options', array($this, 'hub_ract_validate'));
	}

	/**
	 * Validate and transform the values submitted to the options form
	 * @param array $input - options results from the form submission
	 * @return array|false - validated and transformed options results
	 */
	function hub_ract_validate($input){
		//enter validation and transformation here
        $input['domain'] = esc_url_raw($input['domain']);
        if ( filter_var($input['domain'], FILTER_VALIDATE_URL) && strlen($input['license'])<=5 ){
            $url_parse = wp_parse_url($input['domain']);
            //$output['domain'] = $url_parse['host'];
            $output['domain'] = $url_parse['scheme'].'://'.$url_parse['host'];
            $output['postID'] = isset($this->options['postID'])? (int) $this->options['postID']: 0;
            $output['license'] = sanitize_text_field($input['license']);
            $output['level'] = (int) $input['level'];
            $output['refresh'] = (int) $input['refresh'];
            $output['zone'] = strlen($input['zone'])==2? sanitize_text_field($input['zone']): 'ie';
            $input['rivals']['url'][0] = $input['domain']; //already sanitized
            $input['rivals']['domain'][0] = $url_parse['host'];
            $output['page'][0]['uri'] = $output['domain']; //local domain must be record 0
            //$output['page'][0]['engines'] = array(0=>'desktop.google.'.$output['zone'],1=>'desktop.bing.'.$output['zone']);
            $output['page'][0]['engines'] = array(0=>'desktop.google.'.$output['zone']);
            foreach ($input['keywords'] as $i => $keyword) {
                if (strlen($keyword)){
                    $output['page'][0]['keywords'][$i] = sanitize_text_field($keyword);
                }
            }
            //$input['url'] = $input['rivals']['url'][0];
            foreach ($input['rivals']['url'] as $i => $rival) {
                $rival = substr($rival,0,4)=='http'? $rival: 'http://'.$rival;
                if (wp_http_validate_url( $rival )){
                    $output['page'][0]['rivals']['url'][$i] = esc_url_raw($rival);
                    $url_parse = wp_parse_url($input['rivals']['url'][$i]);
                    $output['page'][0]['rivals']['domain'][$i] = $url_parse['host'];
                }
            }
            //add message above the form
            add_settings_error('hub_ract_notice', 'hub_ract_notice', __('Settings updated.'), 'info');
        } else {
            $output = array();
            //add message above the form
            add_settings_error('hub_ract_notice', 'hub_ract_notice', __('Settings fail.'), 'error');
        }
		return $output;
	}

	function hub_ract_options_page() {
		$options = get_option('hub_ract_options');
		$options = is_array($options)? array_merge($this->options,$options): $this->options;
		$the_page = $options['page'][0];
		$my_domain = (!is_null($options['domain']) && strlen($options['domain']))? $options['domain'] : home_url();
		$my_domain = substr($my_domain,0,4)=='http'? $my_domain: 'http://'.$my_domain; //assume http if not supplied
		$my_api_number = get_option('hub_ract_api', '0000');
		if(current_user_can('manage_options')) {
			// get the domain for the host site
			$url = $options['domain']; //** later **
			//echo "<h1>".$this->options['domain']."</h1>";
			echo "<div class='wrap'>";
				echo "<h2>".esc_html( get_admin_page_title() )."</h2>";
				echo "<h3>Setup / Settings</h3>";
				//add update message
				//echo '<div class="notice notice-success is-dismissible"><p><strong>Settings saved.</strong></p></div>';
				echo "<div><button id='hub_ract_get_api_btn' class='button button-secondary'>CLICK HERE to Start</button></div>";
				echo "<div id='hub-ract-main-api-form'>";
					//allow settings notification
					settings_errors();
//					echo "<h3>DEBUG</h3><pre>".var_export($options,true)."</pre>"; //*****************
					echo "<form action='options.php' method='post'>";
						settings_fields('hub_ract_group'); //This line must be inside the form tags!!
						//do_settings_fields('hub_ract_group');
						echo "<table class='form-table'>";
						echo "<tr style='vertical-align: top;' class='hub-ract-input-hdr'><th colspan='3'>GENERAL SETTINGS</th></tr>";
						echo "<tr style='vertical-align: top;'><th scope='row'>DOMAIN</th>";
						echo "<td>".esc_url($my_domain)."<input type='hidden' name='hub_ract_options[domain]' value='".esc_url($my_domain)."' /></td>";
						echo '<td><a href="#" class="ract_link"><span class="dashicons dashicons-editor-help"></span></a>
			            <div class="ract_tooltip">Your domain is the domain assigned to this site</div></td></tr>';
						echo "<tr style='vertical-align: top;'><th scope='row'>LICENSE</th>";
						echo "<td><span id='hub-ract-api-key-value'>".esc_attr($my_api_number)."</span>";
						echo "<input id='hub_ract_form_api_value' name='hub_ract_options[license]' type='hidden' value='".$my_api_number."' /> ";
						echo "<input id='hub_ract_form_level' name='hub_ract_options[level]' type='hidden' value='".esc_attr($options['level'])."' /> ";
						echo "<span id='hub_ract_btn_confirm'></span>";
						echo '<td><a href="#" class="ract_link"><span class="dashicons dashicons-editor-help"></span></a>
			            <div class="ract_tooltip">Your 5 digit licence assigned to your domain - this was created using the button above</div></td></tr>';
						echo "<input type='hidden' name='hub_ract_options[refresh]' value='360' />";
						echo "<input type='hidden' name='hub_ract_options[rivals][url][0]' value='".esc_attr($the_page['rivals']['url'][0])."' />";
						$arrz = hub_ract_base::$se_regions;
						$control = creator_dynamic_options_att($arrz,'hub_ract_options[zone]',esc_attr($options['zone']), 'country','',false);
						echo "<tr style='vertical-align: top;'><th scope='row'>Search Zone</th><td>".$control."</td>";
						echo '<td><a href="#" class="ract_link"><span class="dashicons dashicons-editor-help"></span></a>
			            <div class="ract_tooltip">Search Ranking is dependent on location - select search your location from the list</div></td></tr>';
						//------- Search Terms -------
						echo "<tr style='vertical-align: top;' class='hub-ract-input-hdr'><th colspan='3'>SEARCH TERMS</th></tr>";
						echo "<tr style='vertical-align: top;'><td colspan='3'><strong>TIP: </strong><em>Use 3 or more words in each search phrase. ";
						echo "More descriptive search phrases are far more likely to convert to sales than general generic 1 to 2 word searches.</em></td></tr>\n";
						$imax = isset($options['level']) && $options['level']>=5? 30: 10;
						for ($i=0; $i<$imax; $i++){
							$ix = $i+1;
							$value = isset($the_page['keywords'][$i])? esc_attr($the_page['keywords'][$i]): '';
                            $def = array('type'=>'text', 'name'=>('hub_ract_options[keywords]['.$i.']'), 'value'=>$value, 'min'=>0,'max'=>32);
							$control = creator_input_field($def, false);
							echo '<tr style="vertical-align: top;"><th scope="row">Search Phrase ['.$ix.']</th><td>'.$control.'</td>';
							echo '<td><a href="#" class="ract_link"><span class="dashicons dashicons-editor-help"></span></a>';
							echo '<div class="ract_tooltip">Enter a search phrase that you would like to monitor for ranking</div></td></tr>'."\n";
						}
						//------- Competitors (first record is the site) -------
						echo "<tr style='vertical-align: top;' class='hub-ract-input-hdr'><th colspan='3'>COMPETITORS</th></tr>\n";
						$imax = isset($options['level']) && $options['level']>=5? 4: 3;
						for ($i=1; $i<=$imax; $i++){
							$value = isset($the_page['rivals']['url'][$i])? esc_attr($the_page['rivals']['url'][$i]): '';
                            $def = array('type'=>'text', 'name'=>('hub_ract_options[rivals]['.$i.']'), 'value'=>$value, 'min'=>0,'max'=>32);
							$control = creator_input_field($def, false);
							echo '<tr style="vertical-align: top;"><th scope="row">Competitor ['.$i.']</th><td>'.$control.'</td>';
							echo '<td><a href="#" class="ract_link"><span class="dashicons dashicons-editor-help"></span></a>';
							echo '<div class="ract_tooltip">Enter the full url for a competitor (for the phrases entered)</div></td></tr>'."\n";
						}
						echo "</table>";
						submit_button();
					echo "</form>";
				echo "</div>";
				//echo (hub_ract_display_rank_table(array('wrapper'=>'hub-ract-admin', 'class'=>'hub-ract-table', 'title'=>'Latest Ranking Results')));
				//echo "<hr />";
				//echo (hub_ract_display_contender_table(array('wrapper'=>'hub-ract-admin', 'class'=>'hub-ract-table', 'link'=>'nofollow', 'title'=>'Top Contenders')));
				//echo "<hr />";
				//echo (hub_ract_display_contender_table(array('wrapper'=>'hub-ract-admin', 'class'=>'hub-ract-table', 'type'=>'social', 'link'=>'nofollow', 'title'=>'Social Media Contenders')));
			echo "</div><hr />";
		} else {
			wp_die(__('You do not have sufficient permissions to access this page.'));
		}
		//$options = get_option('hub_ract_options');
	}

// -------------------- Actions and Filters --------------------

// -------------------- AJAX Call Function --------------------

	/**
	 * The AJAX call function is defined below
	 * This function is defined by
	 * 		add_action( 'wp_ajax_nopriv_ract_set_license', array($this, 'hub_ract_license_ajax'));
	 *		add_action( 'wp_ajax_ract_set_license', array($this, 'hub_ract_license_ajax'));
	 * as the ajax program to run for visitors and administrators respectively
	 */
	function hub_ract_license_ajax() {
		$jsn['error'] = '';
		$jsn['success'] = false;
		if (isset($_POST['api_license']) && strlen($_POST['api_license'])>0){
			$jsn['api'] = $_POST['api_license'];
			if ( update_option( 'hub_ract_api', $jsn['api'] ) === false ) {
				$jsn['error'] = 'WARNING: API value was not changed';
			} else {
				$options  = get_option( 'hub_ract_options' );
				$options['license'] = $jsn['api'];
				if ( update_option( 'hub_ract_options', $options ) === false ) {
					$jsn['error'] = 'WARNING: Option value was not changed';
				} else {
					$data  = get_option( 'hub_ract_data' );
					$data['license'] = $jsn['api'];
					if ( update_option( 'hub_ract_data', $data ) === false ) {
						$jsn['error'] = 'WARNING: Option value was not changed';
					} else {
						$jsn['success'] = true;
					}
				}
			}
		} else {
			$jsn['error'] = 'ERROR: API value not received or incorrect';
		}
		$status = 'API: '.(strlen($jsn['api'])? $jsn['api']: 'Not Set');
		$status .= strlen($jsn['error'])? ' | '.$jsn['error']: '';
		$status .= $jsn['success']? ' | Licence Created Successfully': '';
		creator_update_log_file( 'LICENSE CREATE OR REFRESH' , sanitize_text_field($status), 'append', true, 3 );
		return $jsn['success'];
	}




// -------------------- Set up the CRON jobs --------------------

	/**
	 * Add half hourly to the CRON schedules - interval in seconds
	 */
	function hub_ract_cron_add_custom_time( $schedules ) {
		$schedules['ract_time'] = array(
			'interval' => 1800,
			'display' => 'Ract Interval'
		);
		return $schedules;
	}


	/**
	 * Create a hook called ract_cron_hook for this plugin and set the time interval
	 * for the hook to fire through CRON
	 */
	function hub_ract_cron_settings() {
		//verify event has not already been scheduled
		if ( !wp_next_scheduled( 'ract_cron_hook' ) ) {
			//schedule the event to run hourly
			wp_schedule_event( time(), 'ract_time', 'ract_cron_hook' );
		}
	}

	/**
	 * function to execute when the cron job runs
	 */
	function hub_ract_rank_tests() {
		//write to the log file
		//$txt = 'Time = '.date('l jS \of F Y h:i:s A');
		hub_ract_run_rank_test(array());
	}

// -------------------- Define actions to be taken when installing and uninstalling the Plugin --------------------

	function hub_ract_load_options() {
		add_option('hub_ract_options', $this->options);
		add_option('hub_ract_data', $this->seodata);
		add_option('hub_ract_log', $this->logfile);
		add_option('hub_ract_api', '');
	}

	function hub_ract_unset_options() {
		delete_option('hub_ract_log');
		//Unschedule any CRON jobs
		$timestamp = wp_next_scheduled('ract_cron_hook');
		if ($timestamp){
			wp_unschedule_event($timestamp, 'ract_cron_hook');
		}
	}

}

function hub_ract_uninstall() {
	delete_option('hub_ract_options');
	delete_option('hub_ract_data');
	delete_option('hub_ract_log');
	delete_option('hub_ract_api');
	//Unschedule any CRON jobs
	$timestamp = wp_next_scheduled('ract_cron_hook');
	if ($timestamp){
		wp_unschedule_event($timestamp, 'ract_cron_hook');
	}
}

// -------------------- FUNCTIONS CALLED IN THE CLASS THAT DO NOT NEED TO BE PART OF THE CLASS --------------------

function hub_ract_status_page() {
	echo "<div class='wrap'>";
	echo "<h2>".esc_html( get_admin_page_title())."</h2>";
	echo wp_kses_post(hub_ract_view_status(array('wrapper'=>'hub-ract-admin', 'class'=>'hub-ract-table', 'title'=>'Update Status by Phrase')));
	echo "</div>";
}

//function hub_ract_log_page() {
//    echo "<div class='wrap'>";
//    echo "<h2>".esc_html( get_admin_page_title())."</h2>";
//    $arr = creator_fetch_log_attribute( '*', 'hub_ract_log', true );
//    echo "<pre>".var_export($arr,true)."</pre>";
//    echo "</div>";
//}

function hub_ract_results_page() {
	echo "<div class='wrap'>";
	echo "<h2>".esc_html( get_admin_page_title())."</h2>";
	echo wp_kses_post(hub_ract_display_rank_table(
	    array('wrapper'=>'hub-ract-admin', 'class'=>'hub-ract-table', 'title'=>'Latest Ranking Results')
    ));
	echo "</div>";
}

function hub_ract_contenders_page() {
	echo "<div class='wrap'>";
	echo "<h2>".esc_html( get_admin_page_title())."</h2>";
	echo '<div class="hub_ract_notes"><h4>Notes</h4>'.
	     '<li>Contenders are the top performers by search phrase</li>'.
	     '<li>Links are provided to the ranking page for comparison</li>'.
	     '<li>Social media contenders also are shown if these are in the top 10 ranking</li><hr /></div>';
	echo wp_kses_post(hub_ract_display_contender_table(
	    array('wrapper'=>'hub-ract-admin', 'class'=>'hub-ract-table', 'link'=>'nofollow', 'title'=>'Top Contenders')
    ));
	echo "<hr />";
	echo wp_kses_post(hub_ract_display_contender_table(
	    array('wrapper'=>'hub-ract-admin', 'class'=>'hub-ract-table', 'type'=>'social', 'link'=>'nofollow', 'title'=>'Social Media Contenders')
    ));
	echo "</div>";
}

function hub_ract_trends_page() {
	echo '<div class="wrap">';
	echo '<h2>'.esc_html( get_admin_page_title()).'</h2>';
	echo '<div class="hub_ract_notes"><h4>Notes</h4>'.
	     '<li>Best ranking of 1 is shown at the top of the chart, worst at the bottom</li>'.
	     '<li>Ranking values greater than 50 are displayed on the charts as 50 (i.e. no rank)</li>'.
	     '<li>Values are updated about every 3 days</li></div>';
	echo '<div id="hub_ract_chart_container"><h3>Charts loading...</h3><div class="ract_spinner"></div></div>';
	echo '</div>';
}

function hub_ract_spacemap_page() {
	echo '<div class="wrap">';
	echo '<h2>'.esc_html( get_admin_page_title()).'</h2>';
	echo '<div class="hub_ract_notes"><h4>Market Leaders taking into account all the search terms (key phrases) added</h4>'.
	     '<li>The space is defined by the keywords, the search engine and the region chosen</li>'.
	     '<li>The top sites for your chosen keywords are shown for each search engine and region</li>'.
	     '<li>These sites are the Market Leaders for the keywords, search engine and region chosen</li></div>';
	echo '<div id="hub_ract_pie_container"><h3>Charts loading...</h3><div class="ract_spinner"></div></div>';
	echo '</div>';
}
