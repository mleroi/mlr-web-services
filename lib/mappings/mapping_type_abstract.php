<?php

abstract class MlrwsMappingType{

	//Each child class must add itself to this array via MlrwsMappingType::add_mapping_type()  
	protected static $mapping_types = array();
	
	//Abstract properties and methods to define in each mapping :
	protected $label;
	protected $fields_types;

	abstract protected function init_label();
	abstract protected function init_fields_types();
	
	/**
	 * Applies a mapping to the given entities
	 * @param object | array $entities
	 * @param array $mapping
	 * @return object | array of objects according to what is given in $entities
	 */
	abstract public function apply_mapping_on($entities,$mapping,$service=array());
	
	public function __construct(){
		$this->init_label();
		$this->init_fields_types();
	}
	
	public function __get($property){
		if( in_array($property,array('label','fields_types')) ){
			return $this->{$property};
		}
	}
	
	public static function get_mapping_types(){
		return self::$mapping_types;
	}
	
	public static function mapping_type_exists($mapping_type){
		return array_key_exists($mapping_type,self::$mapping_types) ;
	}
	
	public static function get_mapping_type($mapping_type){
		return array_key_exists($mapping_type,self::$mapping_types) ?  self::$mapping_types[$mapping_type] : null;
	}
	
	public static function add_mapping_type($mapping_type,$mapping_type_object){
		self::$mapping_types[$mapping_type] = $mapping_type_object;
	} 
	
	public static function get_mapping_field_types($mapping_type){
		$field_types = array();
		if( array_key_exists($mapping_type,self::$mapping_types) ){
			$field_types = self::$mapping_types[$mapping_type]->fields_types;
		}
		return $field_types;
	}
	
}

//TODO : automatize file inclusion from mapping_types directory files!
require_once(dirname(__FILE__) .'/mapping_types/post_type.php');