<?php
require_once(dirname(__FILE__) .'/../web_service_type_abstract.php');

class MlrwsWebServiceHooks extends MlrwsWebServiceType{

	protected $type = 'hook';
	
	protected function init(){
		$this->type_label = __('Hook');
	}
	
	public function get_rewrite_tags_and_rules(){
		$tags_and_rules = array('tags' => array(), 'rules' => array());
		
		$tags_and_rules['rules']['/hook/([^/]+)/([0-9]+)/?$'] = 'index.php?ews=1&ews_type='. $this->type .'&ews_data=$matches[1]&ews_id=$matches[2]&ews_action=one';
		$tags_and_rules['rules']['/hook/([^/]+)/?$'] = 'index.php?ews=1&ews_type='. $this->type .'&ews_data=$matches[1]&ews_action=list';
		
		return $tags_and_rules;
	}
	
	public function query_vars_service_identification($service){
		global $wp_query;
		return $wp_query->query_vars['ews_type'] == $this->type //TODO this test should be automatized
				&& !empty($wp_query->query_vars['ews_action'])
				&& $service['type_data'] == $wp_query->query_vars['ews_data']
				&& $service['type'] == $this->type; //TODO this test should be automatized
	}
	
	protected function _create($service,$data){
		$service_answer = array();
		$hook = $service['type_data'];
		$service_answer = apply_filters('mrlws_hook_web_service_create_'. $hook,$service_answer,$service,$data);
		return $service_answer;
	}
	
	protected function _read($service,$query_vars){
		$service_answer = array();
		$hook = $service['type_data'];
		$service_answer = apply_filters('mrlws_hook_web_service_read_'. $hook,$service_answer,$service,$query_vars);
		return $service_answer;
	}
	
	protected function _read_one($service,$id){
		$service_answer = array();
		$hook = $service['type_data'];
		$service_answer = apply_filters('mrlws_hook_web_service_read_one_'. $hook,$service_answer,$service,$id);
		return $service_answer;
	}
	
	protected function _update($service,$data){
		$service_answer = array();
		$hook = $service['type_data'];
		$service_answer = apply_filters('mrlws_hook_web_service_update_'. $hook,$service_answer,$service,$data);
		return $service_answer;
	}
	
	protected function _delete($service,$id){
		$service_answer = array();
		$hook = $service['type_data'];
		$service_answer = apply_filters('mrlws_hook_web_service_delete_'. $hook,$service_answer,$service,$id);
		return $service_answer;
	}
	
	protected static function _get_urls($service){
		
		$hook = $service['type_data'];
		
		$urls = array(
				'all' => array('label'=>__('All items'), 'slug'=>'hook/'. $hook .'/', 'link'=>true),
				'one' => array('label'=>__('One item'), 'slug'=>'hook/'. $hook .'/[id]/')
		);
		
		$urls = apply_filters('mrlws_hook_web_service_urls_'. $hook,$urls,$service);
		
		return $urls;
	}

}

MlrwsWebServiceType::add_web_service_type('hook',new MlrwsWebServiceHooks());