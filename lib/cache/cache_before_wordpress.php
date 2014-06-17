<?php
/**
 * Common treatments to include in a "before Wordpress" cache implementation (see cache_before_wordpress_example.php)
 */

require_once(dirname(__FILE__) .'/web_services_cache.php');

$requested_url = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

$allowed_identifiers = array(
		'ews_data' => '',
		'ews_id' => '',
		'ews_action' => '',
		'ews_subaction' => '',
		'ews_subaction_data' => ''
);

$found_identifiers = array();
$slug = '';
$token = '';
$reserved_keys = array();
$service = array();

foreach($cachable_services as $regexp => $ws_data){
	if( preg_match($regexp,$requested_url,$matches) ){
		
		foreach($ws_data['identifiers'] as $identifier => $value){
			
			if( array_key_exists($identifier,$allowed_identifiers) ){
				if( preg_match('/^\$matches\[(\d+)\]$/',$value,$matches_identifier) ){
					$found_identifiers[$identifier] = $matches[$matches_identifier[1]];
				}else{
					$found_identifiers[$identifier] = $value;
				}
			}
		}
		
		if( !empty($found_identifiers) ){
			
			$service = $ws_data;
			
			$slug = $ws_data['slug'];
			
			if( !empty($ws_data['token']) ){
				if( preg_match('/^\$matches\[(\d+)\]$/',$ws_data['token'],$matches_token) ){
					$token = $matches[$matches_token[1]];
				}else{
					$token = $ws_data['token'];
				}
			}
			
			$reserved_keys = isset($ws_data['reserved_get_keys']) ? $ws_data['reserved_get_keys'] : array();
		
			break;
		}
	}
}

if( !empty($found_identifiers) ){

	//Check token (see MlrwsWebServices::check_token() ):
	$token_ok = true;
	if( !empty($token) ){
		$token_ok = false;
		$hooked_token = function_exists('mlrws_before_cache_generate_token') ? mlrws_before_cache_generate_token($service) : '';
		if( !empty($hooked_token) ){
			if( function_exists('mlrws_before_cache_check_token') ){
				$token_ok = mlrws_before_cache_check_token($token,$hooked_token,$service);
			}else{
				$token_ok = ($token == $hooked_token);
			}
		}else{
			//We don't have the wp_verify_nonce() wordpress API function here...
			$token_ok = false;
		}
	}
	
	if( $token_ok ){
		$cache_id = MlrwsCache::build_web_service_cache_id($slug,$found_identifiers,$_GET,$reserved_keys);
		
		$cached_webservice = MlrwsCache::get_cached_web_service(
				$cache_id,
				isset($_GET['force_reload']) && is_numeric($_GET['force_reload']) && $_GET['force_reload'] == 1,
				isset($_GET['last_update']) && (is_numeric($_GET['last_update']) || $_GET['last_update'] === 'get' ) ? $_GET['last_update'] : 0,
				function_exists('mlrws_before_cache_get_not_changed_answer') ? 'mlrws_before_cache_get_not_changed_answer' : null
		);
		
		if( !empty($cached_webservice) ){
			header('Content-type: application/json');
			header('Access-Control-Allow-Origin: *');
			$callback = !empty($_GET['callback']) ? $_GET['callback'] : '';
			if( $callback ){
				echo $callback .'('. $cached_webservice .')';
			}else{
				echo $cached_webservice;
			}
			exit();
		}
	}else{
		header('Content-type: application/json');
		header('Access-Control-Allow-Origin: *');
		if( function_exists('mlrws_before_cache_wrong_token_answer') ){
			echo mlrws_before_cache_wrong_token_answer($token,$service);
		}else{
			echo '{"result":{"status":0,"message":"Wrong security token"}}';
		}
		exit();
	}
}
