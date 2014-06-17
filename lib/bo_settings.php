<?php

class MlrwsBoSettings{
	
	const bo_settings_parent_menu_item = 'mlr_ws_settings_panel';
	
	public static function hooks(){
		if ( is_admin() ){ // admin actions
			add_action('admin_menu',array(__CLASS__,'add_plugin_settings_panels_parent'));
			add_action('admin_menu',array(__CLASS__,'add_plugin_settings_panels'),13);
			add_action('admin_init',array(__CLASS__,'register_settings'));
		}
	}
	
	public static function add_plugin_settings_panels_parent(){
		add_menu_page(__('Web Services'), __('Web Services'), 'manage_options', self::bo_settings_parent_menu_item, array(__CLASS__,'settings_panel'));
	}
	
	public static function add_plugin_settings_panels(){
		add_submenu_page(self::bo_settings_parent_menu_item,__('Main settings'), __('Main settings'),'manage_options','bo_settings_main_menu_item',array(__CLASS__,'settings_panel_main'));
	}
	
	public static function register_settings() { 
		register_setting( 'mlr_ws_options', 'ws_url_prefix', array(__CLASS__,'handle_url_prefix') );
		register_setting( 'mlr_ws_options', 'ws_url_slug_prefix', array(__CLASS__,'handle_url_slug_prefix') );
		register_setting( 'mlr_ws_options', 'ws_cache_activation', array(__CLASS__,'handle_cache_activation') );
		
		add_settings_section('mlr_ws_main_settings_section',__('Main settings'), '', 'mlr_ws_settings_panel');
		add_settings_field('ws_url_prefix_field',__("Url prefix for REST style access"),array(__CLASS__,'url_prefix_field'),'mlr_ws_settings_panel','mlr_ws_main_settings_section');
		add_settings_field('ws_url_slug_prefix_field',__("Url prefix for slug access"),array(__CLASS__,'url_slug_prefix_field'),'mlr_ws_settings_panel','mlr_ws_main_settings_section');
		add_settings_field('ws_cache_activation_field',__("Web services cache"),array(__CLASS__,'cache_activation'),'mlr_ws_settings_panel','mlr_ws_main_settings_section');
	}
	
	public static function settings_panel(){
	}
	
	public static function settings_panel_main(){
		?>
		<div class="wrap">
			<?php screen_icon('generic'); ?>
			<h2><?php _e('Web services main settings') ?></h2>
			<form method="post" action="options.php"> 
				<?php settings_fields('mlr_ws_options') ?>
				<?php do_settings_sections('mlr_ws_settings_panel') ?>
				<?php submit_button() ?>
			</form>
		</div>
		<?php
	}
	
	public static function url_prefix_field(){
		?>
		<input type="text" id="ws_url_prefix" name="ws_url_prefix" value="<?php echo self::get_web_service_prefix() ?>" />
		<?php
	}
	
	public static function url_slug_prefix_field(){
		?>
		<input type="text" id="ws_url_slug_prefix" name="ws_url_slug_prefix" value="<?php echo self::get_web_service_per_slug_prefix() ?>" />
		<?php
	}
	
	public static function cache_activation(){
		$activated = self::cache_is_activated();
		?>
			<select name="ws_cache_activation" >
				<option value="0" <?php echo $activated ? '' : 'selected="selected"' ?>><?php _e('Cache deactivated') ?></option>
				<option value="1" <?php echo $activated ? 'selected="selected"' : '' ?>><?php _e('Cache activated') ?></option>
			</select>
		<?php
	}
	
	public static function handle_url_prefix($input){
		return $input;
	}	
	
	public static function handle_url_slug_prefix($input){
		return $input;
	}
	
	public static function handle_cache_activation($input){
		return $input;
	}
	
	public static function get_web_service_prefix(){
		$prefix = get_option('ws_url_prefix');
		return !empty($prefix) ? $prefix : 'rest';
	}	
	
	public static function get_web_service_per_slug_prefix(){
		$prefix = get_option('ws_url_slug_prefix');
		return !empty($prefix) ? $prefix : 'api';
	}
	
	public static function cache_is_activated(){
		$cache_activation = get_option('ws_cache_activation');
		return !empty($cache_activation) && ((int)$cache_activation) == 1;
	}
	
}

MlrwsBoSettings::hooks();
