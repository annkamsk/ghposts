<?php

/*
Plugin Name: GHPosts
*/
require_once( __DIR__ . '/includes/token.php');
require_once( __DIR__ . '/includes/requests.php');
require_once( __DIR__ . '/includes/languages.php');
require_once( __DIR__ . '/includes/post-content.php');
require_once( __DIR__ . '/includes/token-list.php');
require_once( __DIR__ . '/includes/front-matter-parser.php');
require_once( __DIR__ . '/includes/post-methods.php');


add_action( 'admin_menu', 'ghposts_menu' );

add_action('init', function() {
    register_polylang_strings();
});

function ghposts_menu() {
	add_options_page( 'GH Posts', 'GH Posts', 'manage_options', 'ghposts', 'ghposts_options' );
}

function ghposts_options() {
    echo '<div class="wrap">';
    echo '<h2>GH Posts</h2>';

    create_token_table();

    display_admin_save_token();
    display_token_list();

    if (isset($_POST['ghposts_token']) && check_admin_referer('ghposts_token_clicked')) {
        insert_token($_POST['ghposts_token'], $_POST['ghposts_url']);
    }

    if (isset($_POST['ghposts_run']) && check_admin_referer('ghposts_run_clicked')) {
        $token_id = $_POST['ghposts_token_id'];
        $token_obj = get_token($token_id);

        $body = get_request($token_obj->url, $token_id);   
        if ($body) {
            $tree = $body->{'tree'};
            
            foreach ($tree as $key => $file) {
                if (str_starts_with($file->{'path'}, 'yaml/')) {
                    get_post_content($file->{'url'}, $token_id);
                }
            }
            
        }
    }
    echo '</div>';
}