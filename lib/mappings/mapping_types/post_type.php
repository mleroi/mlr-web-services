<?php

require_once(dirname(__FILE__) .'/../mapping_type_abstract.php');

class MlrwsMappingPostType extends MlrwsMappingType{
	
	protected function init_label(){
		$this->label = __('Post type');
	}
	
	protected function init_fields_types(){
		
		$this->fields_types = array(
				'post_field' => array('label'=>__('Default post type field')),
				'post_meta' => array('label'=>__('Meta data')),
				//'taxonomies' => array('label'=>__('Taxonomies')),
				//'comments' => array('label'=>__('Comments')),
				'hook' => array('label'=>__('Hook')),
		);
		
	}
	
	public function apply_mapping_on($posts,$mapping,$service=array()){
		
		$mapped_posts = array();
		
		$single = false;
		if( !is_array($posts) ){
			$posts = array($posts);
			$single = true;
		}
		
		//TODO : we could maybe loop on posts inside the fields loop
		foreach($posts as $key=>$_post){
			
			global $post;
			$post = $_post;
			setup_postdata($post);
			
			$data = array();
			
			foreach($mapping['fields'] as $field){
			
				$name = $field['mapping_name'];
			
				$value = null;
				$wp_name = $name;
			
				if( !empty($field['wp_name']) ){
					$wp_name = $field['wp_name'];
				}
			
				if( !empty($field['type']) ){
					switch($field['type']){
						case 'post_field':
							$value = $post->{$wp_name};
							break;
						case 'post_meta':
							$value = get_post_meta($post->ID,$wp_name,true);
							break;
						case 'taxonomies':
							
							break;
						case 'comments':
							
							break;
						case 'hook':
							$value = apply_filters('mlrws_mapping_post_type_field_hook_'. $mapping['slug'] .'_'. $wp_name,'',$post,$mapping,$service);
							break;
					}
				}else{
					if( isset($post->{$wp_name}) ){
						$value = $post->{$wp_name};
					}
				}
			
				if( $value !== null ){
					$data[$name] = $value;
				}
			
			}
			
			$data = apply_filters('mlrws_mapping_post_type_field_hook_'. $mapping['slug'],$data,$mapping,$service);
			
			$mapped_posts[$key] = $data;
		}
		
		return $single ? array_pop($mapped_posts) : $mapped_posts;
	}
	
}

MlrwsMappingType::add_mapping_type('post_type',new MlrwsMappingPostType());