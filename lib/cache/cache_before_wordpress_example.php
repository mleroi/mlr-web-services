<?php
/**
 * Don't use this file. This is just an example of how to implement cache before Wordpress with mlr-web-services.
 */

/**
 * This file must be included in wp-content/advanced-cache.php or any pre wordpress 
 * cache file used by cache plugins.
 * The WP API is not loaded at this point, so we don't have access to plugins functions or options values.
 * So, the option "Cache deactivated" of this plugin won't be taken into account here : you'll have
 * to un-include this file by hand if you want to deactivate this "before wordpress" cache.
 */

/****************************
 * Services that will have "before wordpress" cache.
 * See rewrite rules in /lib/web-services/web_services.php::add_rewrite_tags_and_rules() 
 * and /lib/web-services/wweb_services_types to know how to fill in "identifiers".
 */
$cachable_services = array(
	'/^\/rest\/(.*?)\/synchronization\/?$/' => array(
													'slug' => 'synchronization',
													'identifiers' => array('ews_data'=>'synchronization','ews_action'=>'list'),
													'token' => '$matches[1]',
													'reserved_get_keys' => array() //use this for a specific token get param for example
											   ),
	'/^\/rest\/(.*?)\/post-single\/(\d+)\/?$/' => array(
													'slug' => 'post-single',
													'identifiers' => array('ews_data'=>'post-single','ews_action'=>'one','ews_id'=>'$matches[2]'),
													'token' => '$matches[1]'
											   ),
	'/^\/rest\/(.*?)\/comments\/post\/(\d+)\/?$/' => array(
													'slug' => 'comments',
													'identifiers' => array('ews_data'=>'comments','ews_action'=>'list','ews_subaction'=>'default','ews_subaction_data'=>'$matches[2]'),
													'token' => '$matches[1]'
											   ),
	'/^\/rest\/(.*?)\/posts-list\/(\d+)\/?$/' => array(
													'slug' => 'posts-list',
													'identifiers' => array('ews_data'=>'posts-list','ews_action'=>'one','ews_id'=>'$matches[2]'),
													'token' => '$matches[1]'
											   )
);

/*****************************
* Define a custom "No changes" answer (when using the "last_update" $_GET param).
* Do the same thing here as in the "mlrws_not_changed_answer" hook.
* Can be commented if not needed.
*/
function  mlrws_before_cache_get_not_changed_answer($cached_last_update){
	//Here we want a status=1 instead of native 2 :
	return json_encode((object)array('result' => (object)array('status'=>1,'message'=>''), 'last-update' => $cached_last_update ));
}


/*****************************
* Token functions, equivalent to the "mrlws_generate_token" and "mrlws_check_token" hooks.
* Those functions must be implemented if token is activated, and can be commented if not needed.
* NOTE : tokens generated via native wp nonce functions will not work here, as we don't have the WP API loaded at this point.
*/

//Generate token, in the same way we generate the token via the "mrlws_generate_token" hook : 
function mlrws_before_cache_generate_token($service){
	return my_function_to_generate_token($service);
}

/*
//Check token, in the same way we check the token via the "mrlws_check_token" hook : 
function mlrws_before_cache_check_token($token,$hooked_token,$service){
	$token_ok = true;
	return $token_ok;
}
*/

function mlrws_before_cache_wrong_token_answer($token,$service){
	return '{"result":{"status":0,"message":"Wrong security token"}}';
}

require_once(ABSPATH . 'wp-content/plugins/mlr-web-services/lib/cache/cache_before_wordpress.php');
