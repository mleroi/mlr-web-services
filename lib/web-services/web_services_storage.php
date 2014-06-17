<?php
class MlrwsWebServicesStorage{
	
	const option_id = 'mlrws_web_services_list';
	
	public static function generate_ws_id($current_web_services){
		$id = 1;
		if( !empty($current_web_services) ){
			$id = max(array_keys($current_web_services)) + 1;
		}
		return $id;
	}
	
	public static function delete_web_service($ws_id){
		$deleted_ok = true;
		$web_services = self::get_web_services();
		if( array_key_exists($ws_id,$web_services) ){
			unset($web_services[$ws_id]);
			self::update_web_services($web_services);
		}else{
			$deleted_ok = false;
		}
		return $deleted_ok;
	}
	
	public static function update_web_services($web_services){
		if ( get_option( self::option_id ) != $web_services ) {
			update_option( self::option_id, $web_services );
		} else {
			add_option( self::option_id, $web_services, '', 'no' );
		}
	}
	
	public static function get_web_services(){
		$web_services = get_option( self::option_id );
		return !empty($web_services) ? $web_services : array();
	}
	
	public static function get_web_service($ws_id){
		$web_services = get_option( self::option_id );
		return array_key_exists($ws_id,$web_services) ? $web_services[$ws_id] : null;
	}
	
}