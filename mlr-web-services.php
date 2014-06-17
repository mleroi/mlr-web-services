<?php
/*
Plugin Name: MLR web services
Description: Customizable WordPress JSON Web Services.
Version: 0.5
*/

if( !class_exists('MlrWebServices') ){
	
require_once(dirname(__FILE__) .'/lib/bo_settings.php');
require_once(dirname(__FILE__) .'/lib/bo_import_export.php');
require_once(dirname(__FILE__) .'/lib/web-services/web_services.php');
require_once(dirname(__FILE__) .'/lib/mappings/mappings.php');

class MlrWebServices{
	
	const resources_version = '1.0';
	
	public static function hooks(){
		register_activation_hook( __FILE__, array(__CLASS__,'on_activation') );
		add_action('init',array(__CLASS__,'init'));
		add_action('template_redirect',array(__CLASS__,'template_redirect'),5);
	}
	
	public static function on_activation(){
		flush_rewrite_rules();
	}
	
	public static function init(){
		MlrwsWebServices::add_rewrite_tags_and_rules();
	}
	
	public static function template_redirect(){
		MlrwsWebServices::template_redirect();
	}
	
}

MlrWebServices::hooks();

}