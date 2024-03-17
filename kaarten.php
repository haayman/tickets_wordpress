<?php

/**
 * Plugin Name: Kaarten
 * Plugin URI: https://jatheater.nl
 * Description: Overzicht kaarten
 * Version: 1.
 * Author: Arjen Haayman
 **/

// If this file is called directly, abort.
if (!defined('WPINC')) {
	die;
}

require_once(dirname(__FILE__) . '/short_code.php');
add_shortcode('kaarten', 'kaarten_shortcode'); 