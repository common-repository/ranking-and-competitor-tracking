<?php
/**
 * Runs on Uninstall of Hub5050 Ranking and Competitor Tracking
 *
 * @package   Hub5050 Ranking and Competitor Tracking
 * @author    Clinton [CreatorSEO]
 * @license   GPLv3
 * @link      http://www.creatorseo.com
 *
 * This program is the property of the developer and is not intended for distribution. However, if the
 * program is distributed for ANY reason whatsoever, this distribution is WITHOUT ANY WARRANTEE or even
 * the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 */

// Check that we should be doing this
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit; // Exit if accessed directly
}

// Delete Options
$options = array(
	'hub_ract_options',
	'hub_ract_data',
	'hub_ract_log',
	'hub_ract_api',
);
foreach ( $options as $option ) {
	if ( get_option( $option ) ) {
		delete_option( $option );
	}
}

$timestamp = wp_next_scheduled('ract_cron_hook');
if ($timestamp){
	wp_unschedule_event($timestamp, 'ract_cron_hook');
}

