<?php

require_once(dirname(__FILE__) .'/mapping_type_abstract.php');
require_once(dirname(__FILE__) .'/mappings_bo_settings.php');

class MlrwsMappings{
	
	/**
	 * Applies a mapping to given entities
	 * @param int $mapping_id
	 * @param object|array of objects $entities 
	 * @param array (Optional) current webservice
	 * @return object|array of objects according to what is given in $entities
	 */
	public static function apply_mapping_on_entities($mapping_id,$entities,$service=array()){
		$mapped_entities = array();
	
		$mapping = MlrwsMappingsStorage::get_mapping($mapping_id);
		if( !empty($mapping) ){
			$mapping_type = MlrwsMappingType::get_mapping_type($mapping['type']);
			if( is_object($mapping_type) && $mapping_type !== null ){
				$mapped_entities = $mapping_type->apply_mapping_on($entities,$mapping,$service);
			}
		}
	
		return $mapped_entities;
	}
	
}