<?php
require_once(dirname(__FILE__) .'/../web_service_type_abstract.php');

class MlrwsWebServicePostType extends MlrwsWebServiceType{
	
	protected $type = 'post_type';
	
	protected $mapping_available = true;
	
	protected function init(){
		$this->type_label = __('Post Type');
	}
	
	public function get_rewrite_tags_and_rules(){
		
		$tags_and_rules = array('tags' => array(), 'rules' => array());
		
		//Post types (CRUD)
		$tags_and_rules['rules']['/post-type/(.+?)/map/(.+?)/([0-9]+)/?$'] = 'index.php?ews=1&ews_type='. $this->type .'&ews_data=$matches[1]&ews_id=$matches[3]&ews_action=one&ews_mapping=$matches[2]';
		$tags_and_rules['rules']['/post-type/([^/]*?)/([0-9]+)/?$'] = 'index.php?ews=1&ews_type='. $this->type .'&ews_data=$matches[1]&ews_id=$matches[2]&ews_action=one';
		
		$tags_and_rules['rules']['/post-type/(.+?)/map/(.+)/?$'] = 'index.php?ews=1&ews_type='. $this->type .'&ews_data=$matches[1]&ews_action=list&ews_mapping=$matches[2]';
		$tags_and_rules['rules']['/post-type/(.+?)/?$'] = 'index.php?ews=1&ews_type='. $this->type .'&ews_data=$matches[1]&ews_action=list';

		return $tags_and_rules;
	}
	
	public function query_vars_service_identification($service){
		global $wp_query;
		
		$ok = $wp_query->query_vars['ews_type'] == $this->type //TODO this test should be automatized
				&& !empty($wp_query->query_vars['ews_action'])
				&& !empty($wp_query->query_vars['ews_data'])
				&& post_type_exists($wp_query->query_vars['ews_data'])
				&& $service['type'] == $this->type //TODO this test should be automatized
				&& $service['type_data'] == $wp_query->query_vars['ews_data'];
		
		if( $ok ){
			$mapping_id = !empty($wp_query->query_vars['ews_mapping']) ? $wp_query->query_vars['ews_mapping'] : '';
			if( !empty($mapping_id) ){
				$mapping = MlrwsMappingsStorage::get_mapping($mapping_id);
				if( empty($mapping) || $service['mapping_id'] != $mapping['id'] ){
					$ok = false;
				}
			}else{
				if( !empty($service['mapping_id']) ){
					$ok = false;
				}
			}
		}
		
		return $ok;
	}
	
	protected function _create($service,$data){
		$service_answer = array();
		
		$new_post = array(
				'post_type'=>$service['type_data'],
				'post_status'=>'publish'
		);
			
		$new_post = self::set_post_web_service_data($service,$data,$new_post);
		
		$post_id = wp_insert_post($new_post,true);
		if( is_wp_error($post_id) ){
			$this->log('create : insertion error : '. $post_id->get_error_message());
			$service_answer['error'] = __("Post insertion failed");
		}else{
			self::set_post_meta_web_service_data($service,$post_id,$data);
			$post = get_post($post_id);
			$service_answer = self::get_entities_web_service_data($service,$post);
			$this->log('create : '. $post_id);
		}

		return $service_answer;
	}
	
	protected function _read($service,$query_vars){
		$service_answer = array();
		
		$query_args = array(
				'post_type'=>$service['type_data'],
				'orderby'=>'post_date',
				'order'=>'DESC',
		);
		
		if( !empty($service['nb_items']) ){
			$query_args['numberposts'] = $service['nb_items'];
		}
		
		if( !empty($_GET['taxonomy']) && !empty($_GET['term']) ){
			
			$taxonomy = $_GET['taxonomy'];
			$taxonomy_term = $_GET['term'];

			$wp_term = null;
			if( is_numeric($taxonomy_term) ){
				$wp_term = get_term_by('id',$taxonomy_term,$taxonomy);
			}else{
				$wp_term = get_term_by('slug',$taxonomy_term,$taxonomy);
			}
			
			if( !empty($wp_term) ){
				$query_args['tax_query'] = array(
						array(
							'taxonomy' => $taxonomy,
							'field' => 'slug',
							'terms' => $wp_term->slug
						)
				);
			}  
		}
		
		$posts = get_posts($query_args);
		
		$service_answer = self::get_entities_web_service_data($service,$posts);
		
		$this->log('read : '. $service['type_data']);
		
		return $service_answer;
	}
	
	protected function _read_one($service,$id){
		$post = get_post($id);
		$this->log('read_one : '. $id);
		return (object)self::get_entities_web_service_data($service,$post);
	}
	
	protected function _update($service,$data){
		$service_answer = array();
		
		if( $data !== null ){
			$updated_post = array('ID'=>$data->id);
			$updated_post = self::set_post_web_service_data($service,$data,$updated_post);
			wp_update_post($updated_post);
		}
		
		$this->log('update : '. $data->id);
		
		return $service_answer;
	}
	
	protected function _delete($service,$id){
		$service_answer = array();
		
		wp_delete_post( $id, false );
		self::log('delete : '. $id);

		return $service_answer;
	}
	
	//TODO reimplement this with mappings...
	private static function set_post_web_service_data($service,$web_service_object,$post_array_to_complete = array()){
	
		$id = array_key_exists('ID',$post_array_to_complete) ? $post_array_to_complete['ID'] : '';
	
		foreach($service['fields'] as $field){
	
			$name = $field['ws_name'];
	
			$value = null;
			$wp_name = $name;
	
			if( !empty($field['wp_name']) ){
				$wp_name = $field['wp_name'];
			}
	
			if( !empty($field['type']) ){
				if( $field['type'] == 'post_meta' ){
					if( !empty($id) ){ //update
						add_post_meta( $id, $wp_name, $web_service_object->{$name}, true ) || update_post_meta($id, $wp_name, $web_service_object->{$name} );
					}
				}elseif( $field['type'] == 'post_field' ){
					if( isset($web_service_object->{$name}) ){
						$post_array_to_complete[$wp_name] = $web_service_object->{$name};
					}
				}elseif( $field['type'] == 'date' ){
					//TODO : check and format date (if sent as unix timestamp for example)
					/*$date_sent = $web_service_object->{$name};
					 if( !preg_match('//') ){
	
					}*/
				}
			}else{
				if( isset($web_service_object->{$name}) ){
					$post_array_to_complete[$wp_name] = $web_service_object->{$name};
				}
			}
	
		}
	
		return $post_array_to_complete;
	}
	
	private static function set_post_meta_web_service_data($service,$post_id,$web_service_object){
	
		foreach($service['fields'] as $field){
	
			$name = $field['ws_name'];
	
			$value = null;
			$wp_name = $name;
	
			if( !empty($field['wp_name']) ){
				$wp_name = $field['wp_name'];
			}
	
			if( !empty($field['type']) ){
				if( $field['type'] == 'post_meta' ){
					add_post_meta( $post_id, $wp_name, $web_service_object->{$name}, true ) || update_post_meta($post_id, $wp_name, $web_service_object->{$name} );
				}
			}
	
		}
	
	}
	
	protected static function _get_urls($service){
		
		$url = 'post-type/'. $service['type_data'];
		
		if( !empty($service['mapping_id']) ){
			$mapping = MlrwsMappingsStorage::get_mapping($service['mapping_id']);
			$url .= '/map/'. $mapping['slug'];
		}
		
		$url .= '/';
		
		$urls = array(
				'all' => array('label'=>'Last posts', 'slug'=>$url, 'link'=>true),
				'one' => array('label'=>'One post', 'slug'=>$url .'[post_id]/'),
				//'taxonomy' => array('label'=>'Last posts per taxonomy', 'slug'=>$url .'/?taxonomy=[taxonomy]&term=[term_slug_or_id]')
		);
		
		return $urls;
	}
	
}

MlrwsWebServiceType::add_web_service_type('post_type',new MlrwsWebServicePostType());