<?php
require_once(dirname(__FILE__). '/web_services_storage.php');
require_once(dirname(__FILE__). '/../mappings/mappings_storage.php');

class MlrwsWebservicesBoSettings{

	const menu_item = 'mlrws_ws_list_settings_panel';

	public static function hooks(){
		if( is_admin() ){
			add_action('admin_menu',array(__CLASS__,'add_plugin_settings_panels'),11);
			add_action('admin_enqueue_scripts', array(__CLASS__,'admin_enqueue_scripts'));
		}
	}

	public static function admin_enqueue_scripts(){
		global $pagenow, $typenow, $plugin_page;
		if( $pagenow == 'admin.php' && $plugin_page == MlrwsBoSettings::bo_settings_parent_menu_item ){
			wp_enqueue_script('ws_admin_panel_js',plugins_url('js/ws_admin_panel.js', dirname(dirname(__FILE__))),array(),MlrWebServices::resources_version);
		}
	}

	public static function add_plugin_settings_panels(){
		add_submenu_page(MlrwsBoSettings::bo_settings_parent_menu_item,__('Web Services'), __('Web Services'),'manage_options',MlrwsBoSettings::bo_settings_parent_menu_item,array(__CLASS__,'settings_panel'));
	}

	public static function settings_panel(){
		self::handle_new_ws_post_submit();
		?>
		<div class="wrap">
			<?php screen_icon('generic') ?>
			<h2><?php _e('Web services') ?> <a href="#" class="add-new-h2" id="add-new-ws">Add New</a></h2>
			
			<?php settings_errors('ws_new_web_service_setting') ?>
			
			<div id="new-ws-form" style="display:none">
				<h4><?php _e('New web service') ?></h4>
				<?php self::echo_ws_form() ?>
			</div>
			
			<?php $web_services = MlrwsWebServicesStorage::get_web_services() ?>
			<table class="wp-list-table widefat fixed" style="margin-top:15px">
				<thead>
					<tr>
						<th><?php _e('Name') ?></th>
						<th><?php _e('Type') ?></th>
						<th style="width: 240px"><?php _e('Web service urls') ?></th>
						<th><?php _e('Options') ?></th>
						<th style="width: 320px"><?php _e('Fields') ?></th>
						<th><?php _e('CRUD permissions') ?></th>
					</tr>
				</thead>
				<tbody>
				<?php if( !empty($web_services) ): ?>
					<?php foreach($web_services as $id=>$ws): ?>
						<?php
							$ws_mapping_data = self::get_web_service_mapping_data($ws);
						?>
						<?php $alternate_class = $i++%2 ? '' : 'class="alternate"' ?>
						<tr <?php echo $alternate_class ?>>
							<td>
								<?php echo $ws['name'] ?> (<?php echo $ws['slug'] ?>)
								<div class="row-actions">
									<span class="inline hide-if-no-js"><a class="editinline" href="#" data-edit-id="<?php echo $id ?>"><?php _e('Edit') ?></a> | </span>
									<?php if( MlrwsBoSettings::cache_is_activated() ): ?>
										<span class="inline hide-if-no-js"><a class="delete-cache-inline" href="<?php echo wp_nonce_url(add_query_arg(array('mlr_ws_action'=>'delete-cache','ws_id'=>$id)),'delete-cache-'. $id) ?>" data-edit-id="<?php echo $id ?>"><?php _e('Delete cache') ?></a> | </span>
									<?php endif ?>
									<?php do_action('mlrws_bo_web_service_actions_links',$ws,$id) ?>
									<span class="trash"><a class="submitdelete" href="<?php echo wp_nonce_url(add_query_arg(array('mlr_ws_action'=>'delete','ws_id'=>$id)),'delete-ws-'. $id) ?>" class="delete_ws"><?php _e('Delete Web service') ?></a></span>
								</div>
							</td>
							<td><?php echo $ws['type'] ?><?php echo !empty($ws['type_data']) ? ' : '. $ws['type_data'] : '' ?></td>
							<td>
								<?php $urls = self::get_web_service_urls($ws) ?>
								<?php foreach($urls as $url): ?>
									<div style="padding-bottom:5px">
									<strong><?php echo $url['label'] ?> : </strong><br/>
									<?php //$url_slug = substr($url['slug'],0,50); ?>
									<?php //$url_slug .= strlen($url_slug) < strlen($url['slug']) ? '...' : '' ?>
									<?php 
										$url_slug = $url['slug'];
										$token = MlrwsWebServices::create_token($ws);
										if( strlen($token) > 10 ){
											$url_slug = str_replace('/'. $token .'/','/[token]/',$url_slug);
										}
									?>
									<?php if( !empty($url['link'])): ?>
										<a href="<?php echo $url['slug'] ?>"><?php echo $url_slug ?></a>	
									<?php else: ?>
										<?php echo $url_slug ?>
									<?php endif ?>
									</div>
								<?php endforeach ?>
							</td>
							<td>
								<?php _e('Nb items') ?> : <?php echo empty($ws['nb_items']) ? __('WP default') : $ws['nb_items']?>
								<?php if( !empty($ws['token_activated']) ): ?>
									<br/><?php _e('Token') ?> <?php echo $ws['token_type'] == 'url' ? __('inside url') : __('as $_GET parameter') ?> <?php echo $ws['token_type'] == 'get' ? ' : '. $ws['token'] : '' ?> : <?php echo MlrwsWebServices::create_token($ws) ?>
								<?php endif ?>
							</td>
							<td>
								<?php if( !empty($ws_mapping_data['name']) ): ?>
									<strong>Mapping "<?php echo $ws_mapping_data['name'] ?>"</strong> (<?php echo $ws_mapping_data['slug'] ?>)
								<?php endif ?>
								<table class="ws_fields">
								<?php foreach($ws_mapping_data['fields'] as $field): ?>
									<tr><td><?php echo $field['wp_name'] ?> (<?php echo $field['type'] ?>)</td><td>&gt;</td><td><?php echo $field['mapping_name'] ?></td></tr>  
								<?php endforeach ?>
								</table>
							</td>
							<td>
								<?php echo $ws['crud']['create'] ? __('create') .'<br/>' : '' ?>
								<?php echo $ws['crud']['read'] ? __('read') .'<br/>' : '' ?>
								<?php echo $ws['crud']['update'] ? __('update') .'<br/>' : '' ?>
								<?php echo $ws['crud']['delete'] ? __('delete') .'<br/>' : '' ?>
							</td>
						</tr>
						<tr class="edit-ws-wrapper" id="edit-ws-wrapper-<?php echo $id ?>" style="display:none" <?php echo $alternate_class ?>>
							<td colspan="6">
								<?php self::echo_ws_form($ws) ?>
							</td>
						</tr>
					<?php endforeach ?>
				<?php else: ?>
					<tr><td colspan="6"><?php _e('No web services yet!') ?></td></tr>
				<?php endif ?>
				</tbody>
			</table>
		</div>
		<?php //TODO : Put this in a stylesheet... : ?>
		<style>
			table.ws_fields td{ border:none }
		</style>
		<?php 
	}
	
	private static function echo_ws_form($ws=array()){
		
		$edit = !empty($ws);
		
		if( !$edit ){
			$new_submitted = !empty($_POST['new_web_service_submitted']);
			$ws = array(
				'id' => '',
				'name' => $new_submitted && !empty($_POST['ws_name']) ? $_POST['ws_name'] : '',
				'slug' => $new_submitted && !empty($_POST['ws_slug']) ? $_POST['ws_slug'] : '',
				'type' => $new_submitted && !empty($_POST['ws_type']) ? $_POST['ws_type'] : '',
				'type_data' => $new_submitted && !empty($_POST['type_data']) ? $_POST['type_data'] : '',
				'mapping_id' => $new_submitted && !empty($_POST['mapping_id']) ? $_POST['mapping_id'] : 0,
				'crud' => array('create'=>false, 'read'=>true, 'update'=>false, 'delete'=>false),
				'nb_items' => $new_submitted && !empty($_POST['ws_nb_items']) ? $_POST['ws_nb_items'] : '',
				'token_activated' => $new_submitted && !empty($_POST['ws_token_activated']),
				'token_type' => $new_submitted && !empty($_POST['token_type']) ? $_POST['token_type'] : '',
				'token' => $new_submitted && !empty($_POST['ws_token']) ? $_POST['ws_token'] : '',
				'fields' => array()
			);
		}
		
		if( empty($ws['token']) ){
			$ws['token'] = 'token';
		}
		
		$ws_types = self::get_web_services_types();
		
		$mappings = MlrwsMappingsStorage::get_mappings();

		?>
		<form method="post" action="<?php echo add_query_arg(array()) ?>">
			<table class="form-table">
				<tr valign="top">
					<th scope="row"><?php _e('Web service name') ?></th>
			        <td><input type="text" name="ws_name" value="<?php echo $ws['name'] ?>" /></td>
			    </tr>
			    <tr valign="top">
					<th scope="row"><?php _e('Web service slug') ?></th>
			        <td><input type="text" name="ws_slug" value="<?php echo $ws['slug'] ?>" /></td>
			    </tr>
		        <tr valign="top">
		        	<th scope="row"><?php _e('Web service type') ?></th>
		        	<td>
		        		<select type="text" name="ws_type" >
		        			<?php foreach($ws_types as $ws_type => $type_label): ?>
		        				<?php $selected = $ws_type == $ws['type'] ? 'selected="selected"' : '' ?>
		        				<option value="<?php echo $ws_type ?>" <?php echo $selected ?> ><?php echo $type_label ?></option>
		        			<?php endforeach ?>
		        		</select>
		        		 : <input type="text" name="ws_type_data" value="<?php echo $ws['type_data'] ?>" />
		        	</td>
		        </tr>
		        <tr valign="top">
		        	<th scope="row"><?php _e('Mapping') ?></th>
		        	<td>
		        		<select type="text" name="ws_mapping_id" >
		        			<?php $selected = $mapping_id == 0 ? 'selected="selected"' : '' ?>
		        			<option value="0" <?php echo $selected ?> ><?php _e('Default fields') ?></option>
		        			<?php foreach($mappings as $mapping_id => $mapping_data): ?>
		        				<?php $selected = $mapping_id == $ws['mapping_id'] ? 'selected="selected"' : '' ?>
		        				<option value="<?php echo $mapping_id ?>" <?php echo $selected ?> ><?php echo $mapping_data['name'] ?> (<?php echo $mapping_data['slug'] ?>)</option>
		        			<?php endforeach ?>
		        		</select>
		        	</td>
		        </tr>
		        <tr>
		        	<th scope="row"><?php _e('CRUD') ?></th>
		        	<td>
		        		<label for="ws_create"><?php _e('Create') ?> </label><input type="checkbox" name="ws_create" <?php echo $ws['crud']['create'] ? 'checked="checked"' : ''?>" id="ws_create" />&nbsp;&nbsp;&nbsp;
		        		<label for="ws_create"><?php _e('Read') ?> </label><input type="checkbox" name="ws_read" <?php echo $ws['crud']['read'] ? 'checked="checked"' : ''?>" id="ws_read" />&nbsp;&nbsp;&nbsp;
		        		<label for="ws_create"><?php _e('Update') ?> </label><input type="checkbox" name="ws_update" <?php echo $ws['crud']['update'] ? 'checked="checked"' : ''?>" id="ws_update" />&nbsp;&nbsp;&nbsp;
		        		<label for="ws_create"><?php _e('Delete') ?> </label><input type="checkbox" name="ws_delete" <?php echo $ws['crud']['delete'] ? 'checked="checked"' : ''?>" id="ws_delete" />
		        	</td>
		        </tr>
		        <tr>
		        	<th scopte="row"><?php _e('Nb items in list')?></th>
		        	<td>
		        		<input type="text" name="ws_nb_items" value="<?php echo $ws['nb_items'] ?>" />       		
		        	</td>
		        </tr>
		        <tr>
		        	<th scopte="row"><?php _e('Token')?></th>
		        	<td>
		        		<input type="checkbox" name="ws_token_activated" id="token_activated_<?php echo $ws['id'] ?>" <?php echo !empty($ws['token_activated']) ? 'checked="checked"' : '' ?> />
		        		<label for="token_activated_<?php echo $ws['id'] ?>"><?php _e('Token activated') ?></label>
		        		&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
		        		<?php $token_type = !empty($ws['token_type']) ? $ws['token_type'] : 'url' ?>
		        		<input type="radio" name="ws_token_type" id="token_type_url_<?php echo $ws['id'] ?>" value="url" <?php echo $token_type == 'url' ? 'checked="checked"' : '' ?>/>
		        		<label for="token_type_url_<?php echo $ws['id'] ?>"><?php _e('Inside url') ?></label>
		        		&nbsp;&nbsp;&nbsp;&nbsp;
		        		<input type="radio" name="ws_token_type" id="token_type_get_<?php echo $ws['id'] ?>" value="get" <?php echo $token_type == 'get' ? 'checked="checked"' : '' ?>/>
		        		<label for="token_type_get_<?php echo $ws['id'] ?>"><?php _e('As $_GET parameter') ?> : </label>
		        		<input type="text" name="ws_token" value="<?php echo $ws['token'] ?>" />       		
		        	</td>
		        </tr>
			</table>
			<input type="hidden" name="<?php echo $edit ? 'edit' : 'new' ?>_web_service_submitted" value="<?php echo $edit ? $ws['id'] : '1'?>"/>
			<p class="submit">
				<a class="button-secondary alignleft cancel" title="<?php _e('Cancel') ?>" href="#" <?php echo !$edit ? 'id="cancel-new-ws"' : '' ?>><?php _e('Cancel') ?></a>&nbsp;
				<input type="submit" name="submit" id="submit" class="button button-primary" value="<?php echo $edit ? __('Save Changes') : 'Save new web service'?>">
			</p>
		</form>
		<?php
	}
	
	private static function get_web_services_types(){
		$ws_types = array();
		
		$web_services_types = MlrwsWebServiceType::get_web_service_types();
		foreach($web_services_types as $ws_type => $ws_type_object){
			$ws_types[$ws_type] = $ws_type_object->get_type_label();
		}
	
		return $ws_types;
	}
	
	private static function get_web_service_mapping_data($web_service){
		$mapping_data = array(
			'id' => '',
			'type' => '',
			'type_label' => '',
			'name' => '',
			'slug' => '',
			'fields' => array()
		);
		$mapping = MlrwsMappingsStorage::get_mapping($web_service['mapping_id']);
		if( !empty($mapping) ){
			if( MlrwsMappingType::mapping_type_exists($mapping['type']) ){ 
				$mapping_data['id'] = $mapping['id'];
				$mapping_data['type'] = $mapping['type'];
				$mapping_data['type_label'] = MlrwsMappingType::get_mapping_type($mapping['type'])->label;
				$mapping_data['name'] = $mapping['name'];
				$mapping_data['slug'] = $mapping['slug'];
				$mapping_data['fields'] = $mapping['fields'];
			}
		}
		return $mapping_data;
	}
	
	private static function handle_new_ws_post_submit(){

		//TODO : add nonce!
		if( $_POST['new_web_service_submitted'] == 1 || !empty($_POST['edit_web_service_submitted']) ){
			
			$edit = !empty($_POST['edit_web_service_submitted']);
			$edit_id = $edit ? $_POST['edit_web_service_submitted'] : 0;

			$ws_name = trim($_POST['ws_name']);
			$ws_slug = trim($_POST['ws_slug']);
			$ws_type = $_POST['ws_type'];
			$ws_type_data = trim($_POST['ws_type_data']);
			$ws_mapping_id = trim($_POST['ws_mapping_id']);
			$ws_nb_items = trim($_POST['ws_nb_items']);
			$ws_token_activated = !empty($_POST['ws_token_activated']);
			$ws_token_type = trim($_POST['ws_token_type']);
			$ws_token = trim($_POST['ws_token']);
			
			$ws_crud_create = !empty($_POST['ws_create']);
			$ws_crud_read = !empty($_POST['ws_read']);
			$ws_crud_update = !empty($_POST['ws_update']);
			$ws_crud_delete = !empty($_POST['ws_delete']);
			
			if( empty($ws_name) ){
				add_settings_error('ws_new_web_service_setting','no-ws-name',__('You must provide a name for the Web Service!'));
				return;
			}
			
			if( empty($ws_slug) ){
				add_settings_error('ws_new_web_service_setting','no-ws-slug',__('You must provide a slug for the Web Service!'));
				return;
			}

			$current_web_services = MlrwsWebServicesStorage::get_web_services();
			
			if( !$edit ){
				foreach($current_web_services as $ws){
					if( $ws['slug'] == $ws_slug ){
						add_settings_error('ws_new_web_service_setting','already-exists',sprintf(__('A web service with the slug "%s" already exists!'),$ws_name));
						return;
					}
				}
			}
			
			//TODO : remove "post_type" case from here!!
			/*if( $ws_type == 'post_type' ){
				if( empty($ws_type_data) ){
					add_settings_error('ws_new_web_service_setting','no-post-type',__('Please provide a post type!'));
					return;
				}
				if( !post_type_exists($ws_type_data) ){
					add_settings_error('ws_new_web_service_setting','post-type-doesnt-exist',sprintf(__('The post type "%s" doesn\'t exist!'),$ws_type_data));
					return;
				}
			}*/
			
			//TODO : Do more tests on $ws_mapping_id to see if the choosen mapping exists... 
			if( !is_numeric($ws_mapping_id) ){
				add_settings_error('ws_new_web_service_setting','mapping-id-not-numeric',__('Wrong mapping id'));
				return;
			}
			
			$ws_id = $edit ? $edit_id : MlrwsWebServicesStorage::generate_ws_id($current_web_services);
			$new_web_service = array(
				'id' => $ws_id,
				'name' => $ws_name,
				'slug' => $ws_slug,
				'type' => $ws_type,
				'type_data' => $ws_type_data,
				'mapping_id' => $ws_mapping_id,
				'nb_items' => $ws_nb_items,
				'token_activated' => $ws_token_activated,
				'token_type' => $ws_token_type,
				'token' => $ws_token,
				'crud' => array('create'=>$ws_crud_create, 'read'=>$ws_crud_read, 'update'=>$ws_crud_update, 'delete'=>$ws_crud_delete)
			);
			 
			$current_web_services[$ws_id] = $new_web_service;
			
			MlrwsWebServicesStorage::update_web_services($current_web_services);
			
		}elseif( !empty($_GET['mlr_ws_action']) ){
			$action = $_GET['mlr_ws_action'];
			switch($action){
				case 'delete':
					$ws_id = $_GET['ws_id'];
					if( check_admin_referer('delete-ws-'. $ws_id) ){
						if( is_numeric($ws_id) ){
							if( !MlrwsWebServicesStorage::delete_web_service($ws_id) ){
								add_settings_error('ws_new_web_service_setting','cant-delete',__('Could not delete : web sevice not found'));
							}
							wp_redirect(remove_query_arg(array('mlr_ws_action','ws_id','_wpnonce')));
						}
					}
					break;
				case 'delete-cache':
					$ws_id = $_GET['ws_id'];
					if( check_admin_referer('delete-cache-'. $ws_id) ){
						if( is_numeric($ws_id) ){
							$ws = MlrwsWebServicesStorage::get_web_service($ws_id);
							if( is_array($ws) && !empty($ws['type']) ){
								MlrwsCache::delete_web_service_cache($ws['slug']);
							}
							wp_redirect(remove_query_arg(array('mlr_ws_action','ws_id','_wpnonce')));
						}
					}
					break;
				case 'regenerate-cache': 
					//This action can be added to actions links via the "mlrws_bo_web_service_actions_links" hook :
					//see self::settings_panel()
					$ws_id = $_GET['ws_id'];
					if( check_admin_referer('regenerate-cache-'. $ws_id) ){
						if( is_numeric($ws_id) ){
							$ws = MlrwsWebServicesStorage::get_web_service($ws_id);
							if( is_array($ws) && !empty($ws['type']) ){
								$answer = MlrwsWebServices::manually_compute_and_cache_web_service_answer($ws['slug'], 'list');
								if( empty($answer) ){
									//TODO Handle error...
								}
							}
							wp_redirect(remove_query_arg(array('mlr_ws_action','ws_id','_wpnonce')));
						}
					}
					break;
			}
		}
	}
	
	public static function get_web_service_urls($ws){
		$web_service_urls = array();
		
		if( is_numeric($ws) ){
			$ws = MlrwsWebServicesStorage::get_web_service($ws_id);
		}
		
		if( is_array($ws) && isset($ws['type']) ){
			$web_service_urls = MlrwsWebServiceType::get_web_service_urls($ws);
		}
		
		return $web_service_urls;
	}
	
}

MlrwsWebservicesBoSettings::hooks();
