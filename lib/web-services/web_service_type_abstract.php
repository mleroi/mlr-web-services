<?php

abstract class MlrwsWebServiceType{

	//Each child class must add itself to this array via MlrwsWebServiceType::add_web_service_type()
	protected static $web_service_types = array();
	
	//Abstract properties and methods to define in each web service :
	protected $type;
	protected $type_label;
	
	protected $mapping_available = false;

	public function __construct(){
		$this->init();
	}
	
	abstract protected function init();
	abstract protected static function _get_urls($service);
	
	abstract protected function _create($service,$data);
	abstract protected function _read($service,$query_vars);
	abstract protected function _read_one($service,$id);
	abstract protected function _update($service,$data);
	abstract protected function _delete($service,$id);
	
	abstract public function get_rewrite_tags_and_rules();
	abstract public function query_vars_service_identification($service);
	//Plus a "public function add_per_slug_prefix_rules()" method can be defined in child classes to define additionnal "per slug prefix" rules
		
	public function get_type_label(){
		return $this->type_label;
	}
	
	public function get_urls($service){
		$urls = $this->_get_urls($service);
		
		foreach($urls as $k=>$url){
			 $full_url = '/'. MlrwsBoSettings::get_web_service_prefix();
			 if( !empty($service['token_activated']) && $service['token_type'] == 'url' ){
			 	$full_url .= '/'. MlrwsWebServices::create_token($service);
			 }
			 $full_url .= '/'. $urls[$k]['slug'];
			 $urls[$k]['slug'] = $full_url;
		}
		
		$url_by_slug = '/'. MlrwsBoSettings::get_web_service_per_slug_prefix();
		if( !empty($service['token_activated']) && $service['token_type'] == 'url' ){
			$url_by_slug .= '/'. MlrwsWebServices::create_token($service);
		}
		$url_by_slug .= '/'. $service['slug'] .'/';
		
		$urls['by-slug'] = array(
				'slug'=>$url_by_slug,
				'label'=>__('Access by slug'),
				'link'=>true
		);
		
		if( !empty($service['token_activated']) && $service['token_type'] == 'get'){
			foreach($urls as $k=>$url){
				$urls[$k]['slug'] = urldecode(add_query_arg(array($service['token']=>urlencode(MlrwsWebServices::create_token($service))),$url['slug']));
			}
		}
		
		return $urls;
	}
	
	public function create($service,$data){
		
		if( empty($service['crud']['create']) ){
			$service_answer['error'] = __('CREATE not allowed for this web service.');
			return $service_answer;
		}
		
		$service_answer = $this->_create($service,$data);
		$service_answer = apply_filters('mlrws_create_answer_'. $service['slug'],$service_answer,$service,$id);
		$service_answer = apply_filters('mlrws_create_answer',$service_answer,$service,$id);
		
		return $service_answer;
	}
	
	public function read($service,$query_vars){
		
		if( empty($service['crud']['read']) ){
			$service_answer['error'] = __('READ not allowed for this web service.');
			return $service_answer;
		}
		
		$service_answer = $this->_read($service,$query_vars);
		if( !empty($service_answer) ){
			$is_object = is_object($service_answer);
			$service_answer_array = $is_object ? (array)$service_answer : $service_answer;
			foreach($service_answer_array as $k=>$answer_row){
				//Gess id from a field name "id" or "ID". If no such field, the id can be found directly in $answer_row.
				$id = !empty($answer_row['id']) ? $answer_row['id'] : (empty($answer_row['ID']) ? '' : $answer_row['ID']);
				$answer_row = apply_filters('mlrws_read_answer_'. $service['slug'],$answer_row,$service,$id);
				$answer_row = apply_filters('mlrws_read_answer',$answer_row,$service,$id);
				$service_answer_array[$k] = $answer_row;
			}
			$service_answer = $is_object ? (object)$service_answer_array : $service_answer_array;
		}
		
		return $service_answer;
	}
	
	public function read_one($service,$id){
		
		if( empty($service['crud']['read']) ){
			$service_answer['error'] = __('READ not allowed for this web service.');
			return $service_answer;
		}
		
		$service_answer = $this->_read_one($service,$id);
		$service_answer = apply_filters('mlrws_read_answer_'. $service['slug'],$service_answer,$service,$id);
		$service_answer = apply_filters('mlrws_read_answer',$service_answer,$service,$id);
		
		return $service_answer;
	}
	
	public function update($service,$data){
		
		if( empty($service['crud']['update']) ){
			$service_answer['error'] = __('UPDATE not allowed for this web service.');
			return $service_answer;
		}
		
		$service_answer = $this->_update($service,$data);
		$service_answer = apply_filters('mlrws_update_answer_'. $service['slug'],$service_answer,$service,$id);
		$service_answer = apply_filters('mlrws_update_answer',$service_answer,$service,$id);
		
		return $service_answer;
	}
	
	public function delete($service,$id){

		if( empty($service['crud']['delete']) ){
			$service_answer['error'] = __('DELETE not allowed for this web service.');
			return $service_answer;
		}
		
		$service_answer = $this->_delete($service,$id);
		$service_answer = apply_filters('mlrws_delete_answer_'. $service['slug'],$service_answer,$service,$id);
		$service_answer = apply_filters('mlrws_delete_answer',$service_answer,$service,$id);
		
		return $service_answer;
	}
	
	public static function get_web_service_urls($service){
		$urls = '';
		if( self::web_service_type_exists($service['type']) ){
			$urls = self::$web_service_types[$service['type']]->get_urls($service);
		}
		return $urls;
	}
	
	protected static function get_entities_web_service_data($service,$entities){
		$data = array();
	
		if( empty($service['mapping_id']) ){
			if( !is_array($entities) ){
				$data = (array)$entities;
			}else{
				foreach($entities as $entity){
					$data[] = (array)$entity;
				}
			}
		}else{
			$data = MlrwsMappings::apply_mapping_on_entities($service['mapping_id'],$entities,$service['type'],$service);
		}
	
		return $data;
	}
	
	public static function add_web_service_type($web_service_type,$web_service_type_object){
		self::$web_service_types[$web_service_type] = $web_service_type_object;
	}
	
	public static function get_web_service_types(){
		return self::$web_service_types;
	}
	
	public static function web_service_type_exists($web_service_type){
		return array_key_exists($web_service_type,self::$web_service_types) ;
	}
	
	public static function get_web_service_type($web_service_type){
		return array_key_exists($web_service_type,self::$web_service_types) ?  self::$web_service_types[$web_service_type] : null;
	}
	
	protected function log($message){
		//MlrwsWebServices::log('WebServiceType "'. $this->type .'" : '. $message);
	}
}

//TODO : automatize file inclusion from the web_sercices_types directory content!
require_once(dirname(__FILE__) .'/web_services_types/post_type.php');
require_once(dirname(__FILE__) .'/web_services_types/comments.php');
require_once(dirname(__FILE__) .'/web_services_types/menus.php');
require_once(dirname(__FILE__) .'/web_services_types/hooks.php');
