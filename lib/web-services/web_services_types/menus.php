<?php
require_once(dirname(__FILE__) .'/../web_service_type_abstract.php');

class MlrwsWebServiceMenus extends MlrwsWebServiceType{

	protected $type = 'menus';

	protected function init(){
		$this->type_label = __('Menus');
	}
	
	public function get_rewrite_tags_and_rules(){
		$tags_and_rules = array('tags' => array(), 'rules' => array());
		
		$tags_and_rules['rules']['/menus/([0-9]+)/?$'] = 'index.php?ews=1&ews_type='. $this->type .'&ews_id=$matches[1]&ews_action=one';
		$tags_and_rules['rules']['/menus/([^/]+)/?$'] = 'index.php?ews=1&ews_type='. $this->type .'&ews_id=$matches[1]&ews_action=one';
		
		return $tags_and_rules;
	}
	
	public function query_vars_service_identification($service){
		global $wp_query;
		return $wp_query->query_vars['ews_type'] == $this->type //TODO this test should be automatized
				&& !empty($wp_query->query_vars['ews_action'])
				&& $service['type'] == $this->type; //TODO this test should be automatized
	}
	
	protected function _create($service,$data){
		$service_answer = array();
		$service_answer['error'] = __("This web service doesn't allow the 'create' action");
		return $service_answer;
	}
	
	protected function _read($service,$query_vars){
		//TODO : implement this...
		$service_answer = array();
		$service_answer['error'] = __("This web service doesn't allow the 'read' action");
		return $service_answer;
	}
	
	protected function _read_one($service,$id){
		$service_answer = array();
		
		//$id can be integer or slug
		$menu_items = wp_get_nav_menu_items( $id );
		
		foreach($menu_items as $menu_item){
			$service_answer[] = array(
				'id' => $menu_item->ID,
				'title' => !empty($menu_item->post_title) ? $menu_item->post_title : $menu_item->title,
				'url' => $menu_item->url,
				'type' => $menu_item->type,
				'object' => $menu_item->object,
				'object_id' => $menu_item->object_id,
			);
		}
		
		return $service_answer;
	}
	
	protected function _update($service,$data){
		$service_answer = array();
		$service_answer['error'] = __("This web service doesn't allow the 'update' action");
		return $service_answer;
	}
	
	protected function _delete($service,$id){
		$service_answer = array();
		$service_answer['error'] = __("This web service doesn't allow the 'delete' action");
		return $service_answer;
	}
	
	protected static function _get_urls($service){
		$urls = array(
				'one' => array('label'=>'One menu', 'slug'=>'menus/[menu_slug]')
		);
		return $urls;
	}

}

MlrwsWebServiceType::add_web_service_type('menus',new MlrwsWebServiceMenus());