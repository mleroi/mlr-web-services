<?php
require_once(dirname(__FILE__). '/mappings_storage.php');

class MlrwsMappingsBoSettings{

	const menu_item = 'mlrws_mappings_settings_panel';

	public static function hooks(){
		if( is_admin() ){
			add_action('admin_menu',array(__CLASS__,'add_plugin_settings_panels'),12);
			add_action('admin_enqueue_scripts', array(__CLASS__,'admin_enqueue_scripts'));
		}
	}

	public static function admin_enqueue_scripts(){
		global $pagenow, $typenow, $plugin_page;
		if( $pagenow == 'admin.php' && $plugin_page == self::menu_item ){
			wp_enqueue_script('mlrws_mappings_admin_panel_js',plugins_url('js/mappings_admin_panel.js', dirname(dirname(__FILE__))),array(),MlrWebServices::resources_version);
		}
	}

	public static function add_plugin_settings_panels(){
		add_submenu_page(MlrwsBoSettings::bo_settings_parent_menu_item,__('Mappings'), __('Mappings'),'manage_options',self::menu_item,array(__CLASS__,'settings_panel'));
	}

	public static function settings_panel(){
		self::handle_new_mapping_post_submit();
		?>
		<div class="wrap">
			<?php screen_icon('generic') ?>
			<h2><?php _e('Mappings') ?> <a href="#" class="add-new-h2" id="add-new-mapping">Add New</a></h2>
			
			<?php settings_errors('mlrws_new_mapping_setting') ?>
			
			<div id="new-mapping-form" style="display:none">
				<h4><?php _e('New Mapping') ?></h4>
				<?php self::echo_mapping_form() ?>
			</div>
			
			<?php  $mappings = MlrwsMappingsStorage::get_mappings() ?>
			<table class="wp-list-table widefat fixed" style="margin-top:15px">
				<thead>
					<tr>
						<th><?php _e('Name') ?></th>
						<th><?php _e('Slug') ?></th>
						<th><?php _e('Wordpress fields') ?></th>
						<th><?php _e('Mapping fields') ?></th>
					</tr>
				</thead>
				<tbody>
				<?php if( !empty($mappings) ): ?>
					<?php foreach($mappings as $id=>$mapping): ?>
						<?php $alternate_class = $i++%2 ? '' : 'class="alternate"' ?>
						<tr <?php echo $alternate_class ?>>
							<td>
								<?php echo $mapping['name'] ?>
								<div class="row-actions">
									<span class="inline hide-if-no-js"><a class="editinline" href="#" data-edit-id="<?php echo $id ?>">Modifier</a> | </span>
									<span class="trash"><a class="submitdelete" href="<?php echo add_query_arg(array('mlrws_action'=>'delete','mapping_id'=>$id)) ?>" class="delete_mapping">Supprimer</a></span>
								</div>
							</td>
							<td><?php echo $mapping['slug'] ?></td>
							<td>
								<?php foreach($mapping['fields'] as $field): ?>
									<?php echo $field['wp_name'] ?> (<?php echo $field['type'] ?>)<br/>  
								<?php endforeach ?>
							</td>
							<td>
								<?php foreach($mapping['fields'] as $field): ?>
									<?php echo $field['mapping_name'] ?><br/>  
								<?php endforeach ?>
							</td>
						</tr>
						<tr class="edit-mapping-wrapper" id="edit-mapping-wrapper-<?php echo $id ?>" style="display:none" <?php echo $alternate_class ?>>
							<td colspan="5">
								<?php self::echo_mapping_form($mapping) ?>
							</td>
						</tr>
					<?php endforeach ?>
				<?php else: ?>
					<tr><td colspan="5"><?php _e('No mapping yet!') ?></td></tr>
				<?php endif ?>
				</tbody>
			</table>
		</div>
		<?php
	}
	
	private static function echo_mapping_form($mapping=array()){
		
		$edit = !empty($mapping);
		
		if( !$edit ){
			$mapping = array(
				'id' => '',
				'name' => '',
				'slug' => '',
				'type' => 'post_type',
				'fields' => array()
			);
		}
		
		$mapping_types = self::get_mappings_types();

		?>
		<form method="post" action="<?php echo add_query_arg(array()) ?>">
			<table class="form-table">
				<tr valign="top">
					<th scope="row"><?php _e('Mapping name') ?></th>
			        <td><input type="text" name="mapping_name" value="<?php echo $mapping['name'] ?>" /></td>
			    </tr>
			    <tr valign="top">
					<th scope="row"><?php _e('Mapping slug') ?></th>
			        <td><input type="text" name="mapping_slug" value="<?php echo $mapping['slug'] ?>" /></td>
			    </tr>
			    <?php /* For now we only have one mappy type : "Post type" > cf input hidden "mapping_type".
		        <tr valign="top">
		        	<th scope="row"><?php _e('Mapping type') ?></th>
		        	<td>
		        		<select type="text" name="mapping_type" >
		        			<?php foreach($mapping_types as $mapping_type => $type_label): ?>
		        				<?php $selected = $mapping_type == $mapping['type'] ? 'selected="selected"' : '' ?>
		        				<option value="<?php echo $mapping_type ?>" <?php echo $selected ?> ><?php echo $type_label ?></option>
		        			<?php endforeach ?>
		        		</select>
		        	</td>
		        </tr>
		        */ ?>
		        <tr valign="top">
		        	<th scope="row"><?php _e('Fields') ?></th>
		        	<td>
		        		<table id="mapping-fields-table<?php echo $edit ? '-'.$mapping['id'] : '' ?>">
		        			<tr>
		        				<th><?php _e('Field type') ?></th>
		        				<th><?php _e('Mapping field name') ?></th>
		        				<th><?php _e('Wordpress field name') ?></th>
		        				<th></th>
		        			</tr>
		        			<tr id="mapping-field-template<?php echo $edit ? '-'.$mapping['id'] : '' ?>" <?php echo $edit ? 'style="display:none"' : '' ?>>
		        				<?php echo self::echo_field_row($mapping['type'],array(),true) ?>
		        			</tr>
		        			<?php foreach($mapping['fields'] as $field): ?>
		        				<tr>
		        					<?php echo self::echo_field_row($mapping['type'],$field) ?>
		        				</tr>
		        			<?php endforeach ?>
		        		</table>
		        		<a class="add-field" href="#" data-field-template="mapping-field-template<?php echo $edit ? '-'.$mapping['id'] : '' ?>" data-target-table="mapping-fields-table<?php echo $edit ? '-'.$mapping['id'] : '' ?>"><?php _e('Add one field') ?></a>
		        	</td>
		        </tr>
			</table>
			<input type="hidden" name="<?php echo $edit ? 'edit' : 'new' ?>_mapping_submitted" value="<?php echo $edit ? $mapping['id'] : '1'?>"/>
			<input type="hidden" name="mapping_type" value="post_type" />
			<p class="submit">
				<a class="button-secondary alignleft cancel" title="<?php _e('Cancel') ?>" href="#" <?php echo !$edit ? 'id="cancel-new-mapping"' : '' ?>><?php _e('Cancel') ?></a>&nbsp;
				<input type="submit" name="submit" id="submit" class="button button-primary" value="<?php echo $edit ? __('Save Changes') : 'Save new mapping'?>">
			</p>
		</form>
		<?php 
	}
	
	private static function echo_field_row($mapping_type,$field=array(),$is_template=false){
	
		if( empty($field) ){
			$field = array(
				'type'=>'',
				'mapping_name'=>'',
				'wp_name'=>''
			);
		}
		
		$field_types = MlrwsMappingType::get_mapping_field_types($mapping_type);
		
		?>
		<td>
        	<select name="field_type[]">
        		<?php foreach($field_types as $field_type => $field_data): ?>
        			<?php $selected = $field['type'] == $field_type ? 'selected="selected"' : '' ?>
        			<option value="<?php echo $field_type ?>" <?php echo $selected ?>><?php echo $field_data['label'] ?></option>
        		<?php endforeach ?>
        	</select>
        </td>
        <td><input type="text" name="mapping_field_name[]" value="<?php echo $field['mapping_name'] ?>" /></td>
        <td><input type="text" name="wp_field_name[]" value="<?php echo $field['wp_name'] ?>" /></td>
        <td>
        	<a href="#" class="remove-field"><?php _e('Remove') ?></a>
        </td>
		<?php 
	}
	
	private static function get_mappings_types(){
	
		foreach(MlrwsMappingType::get_mapping_types() as $entity_type => $mapping_type){
			$mapping_types[$entity_type] = $mapping_type->label;
		}
	
		return $mapping_types;
	}
	
	private static function handle_new_mapping_post_submit(){

		//TODO : add nonce!
		if( $_POST['new_mapping_submitted'] == 1 || !empty($_POST['edit_mapping_submitted']) ){
			
			$edit = !empty($_POST['edit_mapping_submitted']);
			$edit_id = $edit ? $_POST['edit_mapping_submitted'] : 0;

			$mapping_name = trim($_POST['mapping_name']);
			$mapping_slug = trim($_POST['mapping_slug']);
			$mapping_type = $_POST['mapping_type'];
			$fields_types = $_POST['field_type'];
			$mapping_field_names = $_POST['mapping_field_name'];
			$wp_field_name = $_POST['wp_field_name'];
			
			if( empty($mapping_name) ){
				add_settings_error('mlrws_new_mapping_setting','no-mapping-name',__('You must provide a name for the mapping!'));
				return;
			}

			$current_mappings = MlrwsMappingsStorage::get_mappings();
			
			if( !$edit ){
				foreach($current_mappings as $mapping){
					if( $mapping['name'] == $mapping_name ){
						add_settings_error('mlrws_new_mapping_setting','already-exists',sprintf(__('A mapping with the name "%s" already exists!'),$mapping_name));
						return;
					}
				}
			}
			
			$fields = array();
			if( !empty($fields_types) ){
				foreach($fields_types as $k=>$field_type){
					if( !empty($mapping_field_names[$k]) && !empty($wp_field_name[$k]) ){
						$field = array(
							'type'=>$field_type,
							'mapping_name'=>$mapping_field_names[$k],
							'wp_name'=>$wp_field_name[$k]
						);
						$fields[] = $field;
					}
				}
			}

			if( empty($fields) ){
				add_settings_error('mlrws_new_mapping_setting','no-mapping-field',__('Please provide at least one field!'));
				return;
			}
			
			$mapping_id = $edit ? $edit_id : MlrwsMappingsStorage::generate_mapping_id($current_mappings);
			$new_mapping = array(
				'id' => $mapping_id,
				'name' => $mapping_name,
				'slug' => $mapping_slug,
				'type' => $mapping_type,
				'fields' => $fields
			);
			 
			$current_mappings[$mapping_id] = $new_mapping;
			
			MlrwsMappingsStorage::update_mappings($current_mappings);
			
		}elseif( !empty($_GET['mlrws_action']) ){
			$action = $_GET['mlrws_action'];
			switch($action){
				case 'delete':
					$id = $_GET['mapping_id'];
					if(  is_numeric($id) ){
						if( !MlrwsMappingsStorage::delete_mapping($id) ){
							add_settings_error('mlrws_new_mapping_setting','cant-delete',__('Could not delete : mapping not found'));
						}
						wp_redirect(remove_query_arg(array('mlrws_action','mapping_id')));
					}
					break;
			}
		}
	}
	
}

MlrwsMappingsBoSettings::hooks();
