<?php
/**
 * Plugin Name:     Blocks in Database
 * Plugin URI:      https://github.com/windyjonas/blocks-in-db
 * Description:     List blocks in database
 * Author:          windyjonas
 * Author URI:      https://github.com/windyjonas
 * Text Domain:     blocks-in-db
 * Domain Path:     /languages
 * Version:         1.0.0
 *
 * @package         Blocks_In_Db
 */

defined( 'ABSPATH' ) || die( 'No soup for you' );

require_once plugin_dir_path( __FILE__ ) . 'classes/class-bu-plugin-base.php';
require_once plugin_dir_path( __FILE__ ) . 'classes/class-bu-blocks-in-db.php';

Bu_Blocks_In_Db::Init();