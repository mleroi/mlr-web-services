<?php

require_once(dirname(__FILE__) .'/web_service_type_abstract.php');
require_once(dirname(__FILE__) .'/web_services_bo_settings.php');
require_once(dirname(__FILE__) .'/../cache/web_services_cache.php');
require_once(dirname(__FILE__) .'/../cache/cache_update_management.php');

class MlrwsWebServices{

	public static function add_rewrite_tags_and_rules(){
		
		add_rewrite_tag('%ews%','([012])');
		add_rewrite_tag('%ews_type%','([^&]+)');
		add_rewrite_tag('%ews_data%','([^&]+)');
		add_rewrite_tag('%ews_id%','([0-9]+)');
		add_rewrite_tag('%ews_action%','([^&]+)');
		add_rewrite_tag('%ews_subaction%','([^&]+)'); 
		add_rewrite_tag('%ews_subaction_data%','([^&]+)');
		add_rewrite_tag('%ews_mapping%','([^&]+)');
		add_rewrite_tag('%ews_token%','([^&]+)');
		
		$web_services_types = MlrwsWebServiceType::get_web_service_types();
		
		//For direct access by web service slug :
		foreach($web_services_types as $web_service_type){
			if( method_exists($web_service_type, 'add_per_slug_prefix_rules') ){
				$web_service_type->add_per_slug_prefix_rules();
			}
		}
		add_rewrite_rule('^'. MlrwsBoSettings::get_web_service_per_slug_prefix() . '/(.+?)/(.+?)/([0-9]+)/?$', 'index.php?ews=2&ews_token=$matches[1]&ews_data=$matches[2]&ews_id=$matches[3]&ews_action=one', 'top');
		add_rewrite_rule('^'. MlrwsBoSettings::get_web_service_per_slug_prefix() . '/(.+?)/([0-9]+)/?$', 'index.php?ews=2&ews_data=$matches[1]&ews_id=$matches[2]&ews_action=one', 'top');
		add_rewrite_rule('^'. MlrwsBoSettings::get_web_service_per_slug_prefix() . '/(.+?)/(.+?)/?$', 'index.php?ews=2&ews_token=$matches[1]&ews_data=$matches[2]&ews_action=list', 'top');
		add_rewrite_rule('^'. MlrwsBoSettings::get_web_service_per_slug_prefix() . '/(.+?)/?$', 'index.php?ews=2&ews_data=$matches[1]&ews_action=list', 'top');
		
		//For access via standard REST urls defined in web services types :
		foreach($web_services_types as $web_service_type){
			$tags_and_rules = $web_service_type->get_rewrite_tags_and_rules();
			foreach($tags_and_rules['rules'] as $rule => $target){
				$token_target = preg_replace('/matches\[(\d+)\]/e', '"matches[". ("$1"+1) ."]"', $target);
				$token_target = str_replace('index.php?','index.php?ews_token=$matches[1]&',$token_target);
				add_rewrite_rule('^'. MlrwsBoSettings::get_web_service_prefix() . '/(.+?)'. $rule, $token_target, 'top');
				add_rewrite_rule('^'. MlrwsBoSettings::get_web_service_prefix() . $rule, $target, 'top');
			}
		}
		
	}
	
	public static function template_redirect(){
		global $wp_query;
		
		if( isset($wp_query->query_vars['ews']) && !empty($wp_query->query_vars['ews']) ){
			
			if( $wp_query->query_vars['ews'] == 2 ){ //Direct access by web service slug :
				
				if( !empty($wp_query->query_vars['ews_data']) ){
					$web_service_slug = $wp_query->query_vars['ews_data'];
					$services = MlrwsWebServicesStorage::get_web_services();
					foreach($services as $service){
						if( $service['slug'] == $web_service_slug ){
							if( self::check_token($service) ){
								self::exit_handle_request($service,MlrwsWebServiceType::get_web_service_type($service['type']),$wp_query->query_vars['ews_action'],$wp_query->query_vars['ews_id']);
							}else{
								self::exit_sending_error(__('Wrong security token'));
							}
							break;
						}
					}
				}
				
				//No web service recognised > exit with 404
				self::set_404();
				
			}elseif( $wp_query->query_vars['ews'] == 1 ){ //Access via standard REST urls defined in web services types :
				
				if( !empty($wp_query->query_vars['ews_type']) ){ 
				
					$services = MlrwsWebServicesStorage::get_web_services();
					foreach($services as $service){
						foreach(MlrwsWebServiceType::get_web_service_types() as $web_service_type){
							
							if( $web_service_type->query_vars_service_identification($service) ){
								if( self::check_token($service) ){
									self::exit_handle_request($service,$web_service_type,$wp_query->query_vars['ews_action'],$wp_query->query_vars['ews_id']);
								}else{
									self::exit_sending_error(__('Wrong security token'));
								}
								break;
							}
						}
					}
					
				}
				
				//No web service recognised > exit with 404
				self::set_404();
			}
		}
	}
	
	private static function exit_handle_request($service,$web_service_type,$action,$id=0){
		global $wp_query;
		
		self::log($_SERVER['REQUEST_METHOD'] .' : '. $action .' : '. print_r($_REQUEST,true));
	
		if( self::cache_on() ){
			$cached_webservice = MlrwsCache::get_cached_web_service(
									self::get_web_service_cache_id($service),
									isset($_GET['force_reload']) && is_numeric($_GET['force_reload']) && $_GET['force_reload'] == 1,
									isset($_GET['last_update']) && (is_numeric($_GET['last_update']) || $_GET['last_update'] === 'get' ) ? $_GET['last_update'] : 0
								 );
			if( !empty($cached_webservice) ){
				self::exit_sending_web_service_content($cached_webservice);
			}
		}
		
		//Some browsers or viewports on mobile devices cache HTTP resquests, we don't want this!
		header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
		header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); // Some time in the past
	
		if($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
			header('Allow: GET, PUT, DELETE, POST');
			header('Access-Control-Allow-Origin: *');
			header('Access-Control-Allow-Methods: GET, PUT, DELETE, POST');
			header('Access-Control-Allow-Headers: origin, content-type, accept, x-http-method-override');
			header('Access-Control-Allow-Credentials: true');
			exit;
		}
	
		$service_answer = null;
	
		switch($action){
			case 'list':
	
				if( $_SERVER['REQUEST_METHOD'] == 'POST' ) {
						
					$headers = function_exists('apache_request_headers') ? apache_request_headers() : array();
	
					$is_emulate_json = (!empty($headers['Content-Type']) && strpos($headers['Content-Type'],'application/x-www-form-urlencoded') !== false)
									   || (!empty($_SERVER['CONTENT_TYPE']) && strpos($_SERVER['CONTENT_TYPE'],'application/x-www-form-urlencoded') !== false);
	
					if( $is_emulate_json ){
						self::log('EMULATE JSON');
						$json = stripslashes($_POST['model']); //TODO : this is maybe specific to backbone's "emulateJSON"
						$sent = json_decode($json);
					}else{
						$json = file_get_contents("php://input");
						$sent = json_decode($json);
					}
					
					$service_answer = $web_service_type->create($service,$sent);
						
				}elseif( $_SERVER['REQUEST_METHOD'] == 'GET' ){
						
					$service_answer = $web_service_type->read($service,$wp_query->query_vars);
					
				}
				
				break;
	
			case 'one':
	
				if($_SERVER['REQUEST_METHOD'] == 'GET') {
						
					$service_answer = $web_service_type->read_one($service,$id);
						
				} elseif($_SERVER['REQUEST_METHOD'] == 'PUT') {
						
					$json = file_get_contents("php://input");
					$new = json_decode($json);
						
					$service_answer = $web_service_type->update($service,$new);
					
				}elseif( $_SERVER['REQUEST_METHOD'] == 'DELETE' ) {
						
					$service_answer = $web_service_type->delete($service,$id);
					
				}elseif( $_SERVER['REQUEST_METHOD'] == 'POST' ){
						
					$http_method_override_method = '';
						
					$headers = function_exists('apache_request_headers') ? apache_request_headers() : array();
						
					if( !empty($headers['X-HTTP-Method-Override']) ){
						$http_method_override_method = $headers['X-HTTP-Method-Override'];
					}elseif( !empty($_SERVER['HTTP_X_HTTP_METHOD_OVERRIDE']) ){
						$http_method_override_method = $_SERVER['HTTP_X_HTTP_METHOD_OVERRIDE'];
					}
						
					$is_emulate_json = (!empty($headers['Content-Type']) && strpos($headers['Content-Type'],'application/x-www-form-urlencoded') !== false)
									   || (!empty($_SERVER['CONTENT_TYPE']) && strpos($_SERVER['CONTENT_TYPE'],'application/x-www-form-urlencoded') !== false);
						
					//self::log('$_SERVER : '. print_r($_SERVER,true));
						
					self::log('X-HTTP-Method-Override : '. $http_method_override_method);
					if( $is_emulate_json ){
						self::log('EMULATE JSON');
					}
						
					if( !empty($http_method_override_method) ){
	
						if( $http_method_override_method == 'PUT' ){
								
							if( $is_emulate_json ){
	
								$json = stripslashes($_POST['model']); //TODO : this is maybe specific to backbone's "emulateJSON"
								$new = json_decode($json);
	
								self::log('PUT one (X-HTTP-Method-Override + emulateJSON) : '. $id .' - json :'. $json .' - _POST : '. print_r($_POST,true));
	
							}else{
								$data = file_get_contents("php://input");
								$new = json_decode($data);
								self::log('PUT one (X-HTTP-Method-Override) : '. $id .' : '. $data);
							}
								
							if( $new !== null ){
								$service_answer = $web_service_type->update($service,$new);
							}
								
						}elseif( $http_method_override_method == 'DELETE' ){
							self::log('DELETE one (X-HTTP-Method-Override) : '. $id);
							$service_answer = $web_service_type->delete($service,$id);
						}
					}
				}
	
				break;
		}
		
		//Simulate delay : 
		//time_nanosleep(rand(0,1), (floatval(rand(20,100))/100) * 1000000000);
		
		if( $service_answer !== null ){
			self::exit_sending_answer($service_answer,$service);
		}
	
		exit(__('Error : Web service not recognised'));
	}
	
	public function build_result_info($status=1,$message='',$service=array()){
		
		$result_info = (object)array(
			'status' => !empty($status) ? 1 : 0,
			'message' => $message
		);
		
		$result_info = apply_filters('mrlws_build_result_info',$result_info,$status,$message,$service);
		
		return $result_info;
	}
	
	private function exit_sending_error($error, $service=array(), $type='json'){
		$result_info = self::build_result_info(0,$error,$service);
		$result_attribute = self::get_result_attribute($result_info,$error,$service);
		echo json_encode(array($result_attribute=>$result_info));
		exit();
	}
	
	private function build_final_answer($service_answer, $service){
		$final_answer = null;
		
		$result_info = array();
		
		$error = '';
		if( is_array($service_answer) ){
			$error = array_key_exists('error',$service_answer) ? $service_answer['error'] : '';
			unset($service_answer['error']);
		}else{
			$error = isset($service_answer->error) ? $service_answer->error : '';
			unset($service_answer->error);
		}
		
		if( !empty($error) ){
			$result_info = self::build_result_info(0,$error,$service);
		}else{
			$result_info = self::build_result_info(1,'',$service);
		}
		
		$result_attribute = self::get_result_attribute($result_info,$service_answer,$service);
		
		if( is_array($service_answer) ){
			$service_answer = (object)array('items'=>$service_answer,$result_attribute=>$result_info);
		}else{
			$service_answer->{$result_attribute} = $result_info;
		}
		
		$timestamp = time();
		if( is_array($service_answer) ){
			$final_answer = (object)array('items'=>$service_answer,'last-update'=>$timestamp);
		}else{
			$service_answer->{'last-update'} = $timestamp;
			$final_answer = $service_answer;
		}
		
		return array('answer'=>$final_answer,'timestamp'=>$timestamp);
	}
	
	private function exit_sending_answer($service_answer, $service, $type = 'json'){
		
		$final_answer_raw = self::build_final_answer($service_answer, $service);
		$final_answer = json_encode($final_answer_raw['answer']);
		
		if( self::cache_on() ){
			MlrwsCache::cache_web_service_result(self::get_web_service_cache_id($service), $final_answer, $final_answer_raw['timestamp']);
		}
		
		if( !WP_DEBUG ){
			$content_already_echoed = ob_get_contents();
			if( !empty($content_already_echoed) ){
				//TODO : allow to add $content_already_echoed in the answer as a JSON data for debbuging
				ob_end_clean();
			}
		}
		
		header('Content-type: application/json');
		header('Access-Control-Allow-Origin: *');
		
		$callback = !empty($_GET['callback']) ? $_GET['callback'] : '';
		
		if( $callback ){
			echo $callback .'('. $final_answer .')';
		}else{
			echo $final_answer;
		}
		
		exit();
	}
	
	private function exit_sending_web_service_content($web_service_content, $type='json'){
		header('Content-type: application/json');
		header('Access-Control-Allow-Origin: *');
		$callback = !empty($_GET['callback']) ? $_GET['callback'] : '';
		if( $callback ){
			echo $callback .'('. $web_service_content .')';
		}else{
			echo $web_service_content;
		}
		exit();
	}
	
	private static function get_result_attribute($result_info=null,$service_answer=array(),$service=array()){
		return apply_filters('mrlws_result_attribute','result',$result_info,$service_answer,$service);
	}
	
	private static function check_token($service){
		global $wp_query;
		$token_ok = true;
		if( !empty($service['token_activated'] ) ){
			
			$token = '';
			if( $service['token_type'] == 'url' && !empty($wp_query->query_vars['ews_token']) ){
				$token = $wp_query->query_vars['ews_token'];
			}elseif( $service['token_type'] == 'get' && !empty($service['token']) && !empty($_GET[$service['token']]) ){
				$token = $_GET[$service['token']];
			}
			
			if( !empty($token) ){
				$hooked_token = apply_filters('mrlws_generate_token','',$service);
				if( !empty($hooked_token) ){
					$token_ok = apply_filters('mrlws_check_token',null,$token,$hooked_token,$service);
					if( $token_ok === null ){
						$token_ok = ($token == $hooked_token);
					}
				}else{
					$token_nonce_key = apply_filters('mrlws_token_nonce_key','web_service_token_'. $service['slug'],$service);
					$token_ok = wp_verify_nonce($token,$token_nonce_key);
				}
			}else{
				$token_ok = false;
			}
		}
		return $token_ok;
	}
	
	public static function create_token($service){
		$token = apply_filters('mrlws_generate_token','',$service);

		if( empty($token) ){
			$token = wp_create_nonce('web_service_token_'. $service['slug']);
		}
		
		return $token;
	}
	
	private static function set_404(){
		global $wp_query;
		$wp_query->set_404();
		status_header(404);
	}

	private static function cache_on(){
		return $_SERVER['REQUEST_METHOD'] == 'GET' && MlrwsBoSettings::cache_is_activated();
	}
	
	public static function log($message){
		$message = date('Y-m-d H:i:s') .' : '. $message ."\n";
		//Log deactivated : file_put_contents(dirname(__FILE__) .'/logs.txt',$message,FILE_APPEND);
	}
	
	public static function get_web_service_cache_id($service){
		$cache_id = '';
		
		global $wp_query;
		
		$identifiers = array(
			'ews_data' => $wp_query->query_vars['ews_data'],
			'ews_id' => $wp_query->query_vars['ews_id'],
			'ews_action' => $wp_query->query_vars['ews_action'],
			'ews_subaction' => $wp_query->query_vars['ews_subaction'],
			'ews_subaction_data' => $wp_query->query_vars['ews_subaction_data']
		);
		
		$reserved_keys = array();
		if( $service['token_activated'] && $service['token_type'] == 'get' ){
			$reserved_keys[] = $service['token'];
		}
		
		$cache_id = MlrwsCache::build_web_service_cache_id($service['slug'],$identifiers,$_GET,$reserved_keys);
				
		return $cache_id;
	}
	
	/**
	 * Manually computes web services answers by web service slug : use this to manually reload a web service cache for example.
	 * @param string $web_service_slug
	 * @param string $ews_data
	 * @param string $ews_id
	 * @param string $ews_action
	 * @param string $ews_subaction
	 * @param string $ews_subaction_data
	 * @param boolean $no_cache
	 * @return array Empty if fails. If success : Data about the generated web service. 
	 */
	public static function manually_compute_and_cache_web_service_answer($web_service_slug,$ews_action,$ews_id='',$ews_subaction='',$ews_subaction_data='',$no_cache=false){
		
		$generated_cache = array();
		
		$services = MlrwsWebServicesStorage::get_web_services();
		foreach($services as $service){
			if( $service['slug'] == $web_service_slug ){
		
				$web_service_type = MlrwsWebServiceType::get_web_service_type($service['type']);
				if( !empty($web_service_type) ){
					
					$identifiers = array(
							'ews_data' => $web_service_slug,
							'ews_id' => $ews_id,
							'ews_action' => $ews_action,
							'ews_subaction' => $ews_subaction,
							'ews_subaction_data' => $ews_subaction_data
					);
					
					$service_answer = array();
					if( $ews_action == 'list' ){
						$service_answer = $web_service_type->read($service,$identifiers);
					}elseif( $ews_action == 'one' ){
						$service_answer = $web_service_type->read_one($service,$ews_id);
					}
					
					$final_answer_raw = self::build_final_answer($service_answer, $service);
					$final_answer = json_encode($final_answer_raw['answer']);
					
					if( !$no_cache && MlrwsBoSettings::cache_is_activated() ){
						$cache_id = MlrwsCache::build_web_service_cache_id($service['slug'],$identifiers);
						MlrwsCache::delete_web_service_cache($cache_id);
						MlrwsCache::cache_web_service_result($cache_id, $final_answer, $final_answer_raw['timestamp']);
					}
					
					$generated_cache = array('service'=>$service,'timestamp'=>$final_answer_raw['timestamp'],'answer'=>$final_answer_raw['answer'],'answer_json'=>$final_answer);
				}
				
				break;
			}
		}
		
		return $generated_cache;
	}
}