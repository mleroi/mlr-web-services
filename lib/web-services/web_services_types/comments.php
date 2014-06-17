<?php

class MlrwsWebServiceComments extends MlrwsWebServiceType{

	protected $type = 'post_comments';
	
	protected function init(){
		$this->type_label = __('Comments');
	}
	
	public function get_rewrite_tags_and_rules(){
		$tags_and_rules = array('tags' => array(), 'rules' => array());
		
		//Comments for a given post (R)
		//ews_subaction_data is a post ID
		//ews_id is a comment ID
		$tags_and_rules['rules']['/comments/post/([0-9]+)/([0-9]+)/?$'] = 'index.php?ews=1&ews_type='. $this->type .'&ews_id=$matches[2]&ews_action=one&ews_subaction=default&ews_subaction_data=$matches[1]';
		$tags_and_rules['rules']['/comments/post/([0-9]+)/?$'] = 'index.php?ews=1&ews_type='. $this->type .'&ews_action=list&ews_subaction=default&ews_subaction_data=$matches[1]';
		
		//Last Comments (R)
		$tags_and_rules['rules']['/comments/?$'] = 'index.php?ews=1&ews_type=post_comments&ews_action=list&ews_subaction=all';
		
		//Create new comment (C)
		$tags_and_rules['rules']['/comments/new/?$'] = 'index.php?ews=1&ews_type=post_comments&ews_action=list&ews_subaction=new';
		
		return $tags_and_rules;
	}
	
	public function add_per_slug_prefix_rules(){
		add_rewrite_rule('^'. MlrwsBoSettings::get_web_service_per_slug_prefix() . '/(.+?)/(.+?)/post/([0-9]+)/([0-9]+)/?$', 'index.php?ews=2&ews_token=$matches[1]&ews_data=$matches[2]&ews_id=$matches[4]&ews_action=one&ews_subaction=default&ews_subaction_data=$matches[3]', 'top');
		add_rewrite_rule('^'. MlrwsBoSettings::get_web_service_per_slug_prefix() . '/(.+?)/(.+?)/post/([0-9]+)/?$', 'index.php?ews=2&ews_token=$matches[1]&ews_data=$matches[2]&ews_action=list&ews_subaction=default&ews_subaction_data=$matches[3]', 'top');
		add_rewrite_rule('^'. MlrwsBoSettings::get_web_service_per_slug_prefix() . '/(.+?)/post/([0-9]+)/([0-9]+)/?$', 'index.php?ews=2&ews_data=$matches[1]&ews_id=$matches[3]&ews_action=one&ews_subaction=default&ews_subaction_data=$matches[2]', 'top');
		add_rewrite_rule('^'. MlrwsBoSettings::get_web_service_per_slug_prefix() . '/(.+?)/post/([0-9]+)/?$', 'index.php?ews=2&ews_data=$matches[1]&ews_action=list&ews_subaction=default&ews_subaction_data=$matches[2]', 'top');
	}
	
	public function query_vars_service_identification($service){
		global $wp_query;
		
		$subaction = !empty($wp_query->query_vars['ews_subaction']) ? $wp_query->query_vars['ews_subaction'] : '';
		
		switch($subaction){ 
			case 'new':
			case 'all':
				$ok = $wp_query->query_vars['ews_type'] == $this->type //TODO this test should be automatized
						&& !empty($wp_query->query_vars['ews_action'])
						&& $service['type'] == $this->type; //TODO this test should be automatized
				break;
				
			default:
				$ok = $wp_query->query_vars['ews_type'] == $this->type //TODO this test should be automatized
						&& !empty($wp_query->query_vars['ews_action'])
						&& !empty($wp_query->query_vars['ews_subaction_data'])
						&& $service['type'] == $this->type; //TODO this test should be automatized
				break;
		}
		
		return $ok;
	}
	
	/**
	 * Create new comment based on WP /wp-comments-post.php
	 * $data must contain : 
	 * - comment_post_ID
	 * - author 
	 * - email
	 * - url
	 * - comment 
	 * - comment_parent
	 * (- _wp_unfiltered_html_comment) 
	 */
	protected function _create($service,$data){		
		
		$service_answer = array();
		
		$data = (array)$data;
		
		global $wp_query;
		$subaction = !empty($wp_query->query_vars['ews_subaction']) ? $wp_query->query_vars['ews_subaction'] : '';
		
		if( $subaction != "new" ){
			$service_answer['error'] = __("This web service doesn't allow the 'create' action");
			$this->log('create : comment for post : '. $service_answer['error']);
			return $service_answer;
		}
		
		$comment_post_ID = isset($data['comment_post_ID']) ? (int) $data['comment_post_ID'] : 0;
		
		$post = get_post($comment_post_ID);
		
		if ( empty($post->comment_status) ) {
			do_action('comment_id_not_found', $comment_post_ID);
			$service_answer['error'] = 'comment_id_not_found';
			$this->log('create : comment for post '. $comment_post_ID .' : '. $service_answer['error']);
			return $service_answer;
		}
		
		// get_post_status() will get the parent status for attachments.
		$status = get_post_status($post);
		
		$status_obj = get_post_status_object($status);
		
		if ( !comments_open($comment_post_ID) ) {
			do_action('comment_closed', $comment_post_ID);
			$service_answer['error'] = __('Sorry, comments are closed for this item.');
			$this->log('create : comment for post '. $comment_post_ID .' : '. $service_answer['error']);
			return $service_answer;
		} elseif ( 'trash' == $status ) {
			do_action('comment_on_trash', $comment_post_ID);
			$service_answer['error'] = 'comment_on_trash';
			$this->log('create : comment for post '. $comment_post_ID .' : '. $service_answer['error']);
			return $service_answer;
		} elseif ( !$status_obj->public && !$status_obj->private ) {
			do_action('comment_on_draft', $comment_post_ID);
			$service_answer['error'] = 'comment_on_draft';
			$this->log('create : comment for post '. $comment_post_ID .' : '. $service_answer['error']);
			return $service_answer;
		} elseif ( post_password_required($comment_post_ID) ) {
			do_action('comment_on_password_protected', $comment_post_ID);
			$service_answer['error'] = 'comment_on_password_protected';
			$this->log('create : comment for post '. $comment_post_ID .' : '. $service_answer['error']);
			return $service_answer;
		} else {
			do_action('pre_comment_on_post', $comment_post_ID);
		}
		
		$comment_author       = ( isset($data['author']) )  ? trim(strip_tags($data['author'])) : null;
		$comment_author_email = ( isset($data['email']) )   ? trim($data['email']) : null;
		$comment_author_url   = ( isset($data['url']) )     ? trim($data['url']) : null;
		$comment_content      = ( isset($data['comment']) ) ? trim($data['comment']) : null;
		
		// If the user is logged in
		$user = wp_get_current_user();
		if ( $user->exists() ) {
			if ( empty( $user->display_name ) )
				$user->display_name=$user->user_login;
			$comment_author       = $wpdb->escape($user->display_name);
			$comment_author_email = $wpdb->escape($user->user_email);
			$comment_author_url   = $wpdb->escape($user->user_url);
			if ( current_user_can('unfiltered_html') ) {
				if ( wp_create_nonce('unfiltered-html-comment_' . $comment_post_ID) != $data['_wp_unfiltered_html_comment'] ) {
					kses_remove_filters(); // start with a clean slate
					kses_init_filters(); // set up the filters
				}
			}
		} else {
			if ( get_option('comment_registration') || 'private' == $status ){
				$service_answer['error'] = __('Sorry, you must be logged in to post a comment.');
				$this->log('create : comment for post '. $comment_post_ID .' : '. $service_answer['error']);
				return $service_answer;
			}
		}
		
		$comment_type = '';
		
		if ( get_option('require_name_email') && !$user->exists() ) {
			if ( 6 > strlen($comment_author_email) || '' == $comment_author ){
				$service_answer['error'] = __('<strong>ERROR</strong>: please fill the required fields (name, email).');
				$this->log('create : comment for post '. $comment_post_ID .' : '. $service_answer['error']);
				return $service_answer;
			}
			elseif ( !is_email($comment_author_email)){
				$service_answer['error'] = __('<strong>ERROR</strong>: please enter a valid email address.');
				$this->log('create : comment for post '. $comment_post_ID .' : '. $service_answer['error']);
				return $service_answer;
			}
		}
		
		if ( '' == $comment_content ){
			$service_answer['error'] = __('<strong>ERROR</strong>: please type a comment.');
			$this->log('create : comment for post '. $comment_post_ID .' : '. $service_answer['error']);
			return $service_answer;
		}
		
		$comment_parent = isset($data['comment_parent']) ? absint($data['comment_parent']) : 0;
		
		$commentdata = compact('comment_post_ID', 'comment_author', 'comment_author_email', 'comment_author_url', 'comment_content', 'comment_type', 'comment_parent', 'user_ID');
		
		$comment_id = wp_new_comment( $commentdata );
		
		$comment = get_comment($comment_id);
		do_action('set_comment_cookies', $comment, $user);
		
		/*$time = current_time('mysql');
		
		$data = array(
				'comment_post_ID' => 1,
				'comment_author' => 'admin',
				'comment_author_email' => 'admin@admin.com',
				'comment_author_url' => 'http://',
				'comment_content' => 'content here',
				'comment_type' => '',
				'comment_parent' => 0,
				'user_id' => 1,
				'comment_author_IP' => '127.0.0.1',
				'comment_agent' => 'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.9.0.10) Gecko/2009042316 Firefox/3.0.10 (.NET CLR 3.5.30729)',
				'comment_date' => $time,
				'comment_approved' => 1,
		);
		
		wp_insert_comment($data);*/
		
		$this->log('create : comment for post '. $comment_post_ID .' : OK! : new comment id : '. $comment_id );
		
		return $service_answer;
	}
	
	protected function _read($service,$query_vars){
		
		$subaction = !empty($query_vars['ews_subaction']) ? $query_vars['ews_subaction'] : 'all';
		
		$service_answer = array();
		if( !in_array($subaction, array("default","all")) ){
			$service_answer['error'] = __("This web service doesn't allow the 'read' action");
			return $service_answer;
		}
		
		$post_id = $query_vars['ews_subaction_data'];
		
		$max = $subaction == 'default' ? '' : 20;
		
		$query_args = array(
			'post_id' => $post_id,
			'status' => 'approve',
			'number' => !empty($service['nb_items']) ? $service['nb_items'] : $max,
		);
		
		$comments = get_comments($query_args);
		$comment_tree = self::get_comments_tree($comments,$service);
		
		foreach($comment_tree as $comment_node){
		
			$data = self::get_comment_web_service_data($service,$comment_node);
		
			if( !empty($data) ){
				$service_answer[] = $data;
			}
		}
		
		$this->log('read : comments for post '. $post_id);
		
		return $service_answer;
	}
	
	protected function _read_one($service,$id){
		//TODO : implement this...
		$service_answer = array();
		return $service_answer;
	}
	
	protected function _update($service,$data){
		//We can't upate a comment for now
		$service_answer = array();
		$service_answer['error'] = __("This web service doesn't allow the 'update' action");
		return $service_answer;
	}
	
	protected function _delete($service,$id){
		//We can't delete a comment for now
		$service_answer = array();
		$service_answer['error'] = __("This web service doesn't allow the 'delete' action");
		return $service_answer;
	}
	
	private static function get_comment_web_service_data($service,$comment_node){
		$data = array();
	
		$id = $comment_node['id'];
		$depth = $comment_node['depth'];
		$comment = $comment_node['comment'];
		
		$data_raw = (array)get_comment($id);
		foreach($data_raw as $k=>$v){
			$k = strtolower(str_replace('comment_','',$k));
			$data[$k] = $v;
		}
		
		$data['depth'] = $depth;
		
		unset($data['karma']);
		unset($data['approved']);
		unset($data['agent']);
		unset($data['type']);
		
		return $data;
	}
	
	protected static function _get_urls($service){
		$urls = array(
				'all' => array('label'=>__('Last comments'), 'slug'=>'comments', 'link'=>true),
				'one-post' => array('label'=>__('Last comments for a post'), 'slug'=>'comments/post/[post_id]'),
				'new' => array('label'=>__('New comment'), 'slug'=>'comments/new'),
		);
		return $urls;
	}
	
	private static function get_comments_tree($comments,$service){
		
		$tree = array();
		
		if( !empty($comments) ){
			
			$comments_by_id = array();
			foreach($comments as $comment){
				$comments_by_id[$comment->comment_ID] = $comment;
			}
			
			ob_start();
			$wp_list_comments_args = array();
			wp_list_comments(apply_filters('mlrws_comments_list_args',$wp_list_comments_args,$service),$comments);
			$comments_list = ob_get_contents();
			ob_end_clean();
			
			if( preg_match_all('/<li class="[^"]*?(depth-(\d+))[^"]*?" id="comment-(\d+)">/',$comments_list,$matches) ){
				foreach($matches[2] as $k=>$depth){
					$comment_id = $matches[3][$k];
					$tree[$comment_id] = array(
							'id'=>$comment_id,
							'depth'=>(int)$depth,
							'comment'=>$comments_by_id[$comment_id]
					);
				}
			}
		}
		
		return $tree;
	}

}

MlrwsWebServiceType::add_web_service_type('post_comments',new MlrwsWebServiceComments());
