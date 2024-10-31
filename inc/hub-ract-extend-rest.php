<?php
/**
 * Project: Hub5050 Ranking and Competitor Tracking (class.hub-ract-extend-rest.php)
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

/**
 * This is our callback function that embeds our phrase in a WP_REST_Response
 */
function hub_ract_get_endpoint_phrase_options() {
	$data = get_option('hub_ract_options');
	// rest_ensure_response() wraps the data we want to return into a WP_REST_Response, and ensures it will be properly returned.
	//return rest_ensure_response( "Hello World, this is the WordPress REST API" );
	return new WP_REST_Response( $data, 200 );
}

/**
 * This is our callback function that embeds our phrase in a WP_REST_Response
 */
function hub_ract_get_endpoint_phrase_data() {
	$data = get_option('hub_ract_data');
	return new WP_REST_Response( $data, 200 );
}

/**
 * This is our callback function that embeds our phrase in a WP_REST_Response
 */
function hub_ract_get_endpoint_phrase_logs() {
	$data = get_option('hub_ract_log');
	// rest_ensure_response() wraps the data we want to return into a WP_REST_Response, and ensures it will be properly returned.
	return new WP_REST_Response( $data, 200 );
}


/**
 * This function is where we register our routes for our example endpoint.
 * The call function for this route is https://creatorseo.com/wp-json/ract/v1/data
 */
function hub_ract_register_data_routes() {
	// register_rest_route() handles more arguments but we are going to stick to the basics for now.
	register_rest_route( 'ract/v1', '/options', array(
		// By using this constant we ensure that when the WP_REST_Server changes our readable endpoints will work as intended.
		'methods'  => WP_REST_Server::READABLE,
		// Here we register our callback. The callback is fired when this endpoint is matched by the WP_REST_Server class.
		'callback' => 'hub_ract_get_endpoint_phrase_options',
	) );

	register_rest_route( 'ract/v1', '/data', array(
		// By using this constant we ensure that when the WP_REST_Server changes our readable endpoints will work as intended.
		'methods'  => WP_REST_Server::READABLE,
		// Here we register our callback. The callback is fired when this endpoint is matched by the WP_REST_Server class.
		'callback' => 'hub_ract_get_endpoint_phrase_data',
	) );

	register_rest_route( 'ract/v1', '/logs', array(
		'methods'  => WP_REST_Server::READABLE,
		'callback' => 'hub_ract_get_endpoint_phrase_logs',
	) );

}
add_action( 'rest_api_init', 'hub_ract_register_data_routes' );


