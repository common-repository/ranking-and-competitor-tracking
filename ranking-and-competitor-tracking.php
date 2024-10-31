<?php
/**
 * Plugin Name: Website ranking and competitor rank tracking
 * Plugin URI:  https://hub5050.com/wordpress-website-ranking-and-competitor-tracking-plugin-details/
 * Description: Ranking and competitor tracking plugin for site performance improvement
 * Version:     2.1.6
 * Author:		Clinton [CreatorSEO]
 * Author URI:  https://creatorseo.com
 * License:     GPLv3
 * Last change: 04 April 2024
 *
 * Copyright 2011-2024 CreatorSEO (email : info@creatorseo.com)
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License, version 3, as
 * published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You can find a copy of the GNU General Public License at the link
 * http://www.gnu.org/licenses/gpl.html or write to the Free Software
 * Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 */

//Security - abort if this file is called directly
if (!defined('WPINC')){
	die;
}

define( 'HUB_RACT_ROOT', __FILE__ );
define( 'HUB_RACT_DIR', plugin_dir_path( __FILE__ ) );
//require_once( HUB_RACT_DIR . 'inc/creator-function-lib.php');
require_once( HUB_RACT_DIR . 'class.ranking-and-competitor-tracking.php');

$pgf = new hub_ract_metrics(__FILE__);
