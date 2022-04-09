<?php

/*
Plugin Name: GHPosts
*/
require_once( __DIR__ . '/includes/post-content.php');
require_once( __DIR__ . '/includes/token.php');
require_once( __DIR__ . '/includes/token-list.php');
require_once( __DIR__ . '/includes/front-matter-parser.php');
require_once( __DIR__ . '/includes/languages.php');


function get_request($url, $token_id) {
    $token_obj = get_token($token_id);
    $args = array(
        'headers' => array(
            'Authorization' => 'Bearer ' . $token_obj->token,
        ),
    ); 
    $response = wp_remote_get($url, $args);
    update_token_status($token_id, $response);

    $status = wp_remote_retrieve_response_code($response);
    if ($status < 200 || $status >= 300) {
        return null;
    }
    return json_decode(wp_remote_retrieve_body($response));
}

function insert_or_update($postContent) {
    global $wpdb;
    $posts = $wpdb->get_results( $wpdb->prepare("SELECT ID FROM $wpdb->posts WHERE post_title = %s AND post_status IN ('publish', 'draft')", $postContent->metadata->getTitle()));

    foreach ($posts as $post) {
        if (empty(array_filter(get_the_category($post->{'ID'}), function ($v, $k) {
            return $v->{'name'} == 'managed';
        }, ARRAY_FILTER_USE_BOTH))) {
            $lang = pl_get_post_language($post->{'ID'});
            $postData = array(
                'ID' => $post->{'ID'},
                'post_title'   => $postContent->metadata->getTitle(),
                'post_content' => $postContent->getHtml($lang)
            );
            wp_update_post( $postData );
            echo "Updated " . $postContent->metadata->getTitle();

        }
    }

    if (empty($posts)) {
        $post_id_pl = wp_insert_post(array(
            'post_content' => $postContent->getHtml("pl"),
            'post_title' => $postContent->metadata->getTitle()
        ));
        pl_set_post_language($post_id_pl, 'pl');
        $post_id_en = wp_insert_post(array(
            'post_content' => $postContent->getHtml("en"),
            'post_title' => $postContent->metadata->getTitle()
        ));
        pl_set_post_language($post_id_en, 'en');
        pll_save_post_translations(array('pl' => $post_id_pl, 'en' => $post_id_en));
        echo "Created " . $postContent->metadata->getTitle();

    }
    
}

function get_post_content($url, $token_id) {
    $content = get_request($url, $token_id);
    if (!$content) {
        return;
    }
    $decoded = base64_decode($content -> {'content'});
    list($metadata, $body) = FrontMatterParser::parse($decoded);

    if ($metadata && $body) {
        $postContent = new PostContent($body, $metadata);
    
        insert_or_update($postContent);
    }
}

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