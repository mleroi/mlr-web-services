<?php

class MlrwsBoImportExport{

	const bo_settings_parent_menu_item = 'mlr_ws_bo_import_export';
	
	public static function hooks(){
		if( is_admin() ){
			add_action('admin_menu',array(__CLASS__,'add_panel'),15);
		}
	}
	
	public static function add_panel(){
		add_submenu_page(MlrwsBoSettings::bo_settings_parent_menu_item,__('Import / Export'), __('Import / Export'),'manage_options',self::bo_settings_parent_menu_item,array(__CLASS__,'settings_panel'));
	}
	
	public static function settings_panel(){
		global $wpdb;
		self::handle_import_export();
		?>
		<div class="wrap">
			<?php screen_icon('generic') ?>
			<h2><?php _e('Import / Export') ?></h2>
			<?php settings_errors('ws_import_export') ?>
			
			<h3><?php _e('Import') ?></h3>
			<form method="post" action="<?php echo add_query_arg(array()) ?>">
				<input type="hidden" name="action" value="import" />
				<label><?php _e('Import mappings') ?> : </label>
				<textarea name="import_mappings_code" style="width:100%; height:200px"></textarea><br/><br/>
				<label><?php _e('Import web services') ?> : </label>
				<textarea name="import_web_services_code" style="width:100%; height:200px"></textarea>
				<input type="submit" name="submit" id="submit" class="button button-primary" value="<?php _e('Importer') ?>">
			</form>
			
			<br/><br/>
			<h3><?php _e('Export') ?></h3>
			<label><?php _e('Export mappings') ?> : </label>
			<textarea style="width:100%; height:200px"><?php
					$web_services = $wpdb->get_var("SELECT option_value FROM $wpdb->options WHERE option_name = '". MlrwsMappingsStorage::option_id ."' LIMIT 1");
					echo urlencode($web_services); 
			?></textarea><br/><br/>
			<label><?php _e('Export web services') ?> : </label>
			<textarea style="width:100%; height:200px"><?php
					$web_services = $wpdb->get_var("SELECT option_value FROM $wpdb->options WHERE option_name = '". MlrwsWebServicesStorage::option_id ."' LIMIT 1");
					echo urlencode($web_services); 
			?></textarea>
			
		</div>		
		
		<?php 
	}
	
	private static function handle_import_export(){
		global $wpdb;
		
		//TODO : add nonces!
		//TODO : Merge and not replace when importing !!! (being careful to links between mappings ids and web services!)
		
		if( !empty($_POST['action']) ){
			if( $_POST['action'] == 'import' ){
				if( !empty($_POST['import_mappings_code']) ){
					$import_code = urldecode(trim($_POST['import_mappings_code']));
					$mappings = @unserialize($import_code);
					if( $mappings !== false) {
						MlrwsMappingsStorage::update_mappings($mappings);
					}else{
						add_settings_error('ws_import_export','wrong-import-code',__('The provided code is not a valid serialized data'));
						return;
					}
				}
				if( !empty($_POST['import_web_services_code']) ){
					$import_code = urldecode(trim($_POST['import_web_services_code']));
					$web_services = @unserialize($import_code);
					if( $web_services !== false) {
						MlrwsWebServicesStorage::update_web_services($web_services);
					}else{
						add_settings_error('ws_import_export','wrong-import-code',__('The provided code is not a valid serialized data'));
						return;
					}
				}
			}
		}
	}
	
}

MlrwsBoImportExport::hooks();