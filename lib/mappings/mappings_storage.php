<?php
class MlrwsMappingsStorage{
	
	const option_id = 'mlr_mappings_list';
	
	public static function generate_mapping_id($current_mappings){
		$id = 1;
		if( !empty($current_mappings) ){
			$id = max(array_keys($current_mappings)) + 1;
		}
		return $id;
	}
	
	public static function delete_mapping($mapping_id){
		$deleted_ok = true;
		$mappings = self::get_mappings();
		if( array_key_exists($mapping_id,$mappings) ){
			unset($mappings[$mapping_id]);
			self::update_mappings($mappings);
		}else{
			$deleted_ok = false;
		}
		return $deleted_ok;
	}
	
	public static function update_mappings($mappings){
		if ( get_option( self::option_id ) != $mappings ) {
			update_option( self::option_id, $mappings );
		} else {
			add_option( self::option_id, $mappings, '', 'no' );
		}
	}
	
	public static function get_mappings(){
		$mappings = get_option( self::option_id );
		return !empty($mappings) ? $mappings : array();
	}
	
	public static function get_mapping($mapping_id){
		$mappings = get_option( self::option_id );
		
		$mapping = null;
		if( array_key_exists($mapping_id,$mappings) ){
			$mapping = $mappings[$mapping_id];
		}else{
			foreach($mappings as $id => $_mapping){
				if( $_mapping['slug'] == $mapping_id ){
					$mapping = $mappings[$id];
					break;
				}
			}
		}
		
		return $mapping;
	}
	
}