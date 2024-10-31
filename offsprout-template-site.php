<?php

/*
Plugin Name: Template Sites for Offsprout
Plugin URI: https://offsprout.com
Description: Gives you the ability to store all of your Offsprout page, section, and module templates in a single site and then use them on any Offsprout-powered site.
Version: 1.1
Author: Offsprout
License: GPL
*/

define( 'OCBTEMPLATE_PLUGIN_FILE', __FILE__ );

//Make sure that Offsprout is loaded before Template Sites, but load as quickly as possible
if( class_exists( 'Offsprout_Model' ) ){
    ocb_load_offsprout_template_site();
} else {
    add_action( 'after_offsprout_plugin_loaded', 'ocb_load_offsprout_template_site' );
}

//Contains the functions below that get us started
function ocb_load_offsprout_template_site(){
    require_once dirname(__FILE__) . '/class-offsprout-template-site-api.php';
}

if( is_multisite() && get_current_blog_id() == 1 )
    require_once dirname(__FILE__) . '/class-offsprout-template-site-multisite-menu.php';