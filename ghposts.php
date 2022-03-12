<?php

/*
Plugin Name: GHPosts
*/
require_once( __DIR__ . '/includes/post-content.php');
require_once( __DIR__ . '/includes/token.php');


function get_request($url) {
    $token = get_token();
    $args = array(
        'headers' => array(
            'Authorization' => 'Bearer ' . $token,
        ),
    ); 
    return json_decode(wp_remote_retrieve_body(wp_remote_get($url, $args)));
}

function insert_or_update($postContent) {
    $post = get_page_by_title($postContent->getTitle(), 'OBJECT', 'post');
    
    if ($post) {
        if (empty(array_filter(get_the_category($post->{'ID'}), function ($v, $k) {
            return $v->{'name'} == 'managed';
        }, ARRAY_FILTER_USE_BOTH))) {
            $postData = array(
                'ID' => $post->{'ID'},
                'post_title'   => $postContent->getTitle(),
                'post_content' => $postContent->getHtml(),
            );
            wp_update_post( $postData );
        }
    } else {
        wp_insert_post(array(
            'post_content' => $postContent->getHtml(),
            'post_title' => $postContent->getTitle()
        ));
    }
    
}

function get_post_content($url) {
    $content = get_request($url);
    $decoded = base64_decode($content -> {'content'});
    $postContent = new PostContent($decoded);
    echo "Downloaded " . $postContent->getTitle();
    insert_or_update($postContent);
}

add_action( 'admin_menu', 'ghposts_menu' );

function ghposts_menu() {
	add_options_page( 'GH Posts', 'GH Posts', 'manage_options', 'ghposts', 'ghposts_options' );
}

function ghposts_options() {
    echo '<div class="wrap">';
    echo '<h2>GH Posts</h2>';

    admin_save_token();

    if (isset($_POST['ghposts_token']) && check_admin_referer('ghposts_token_clicked')) {
        create_token_table();
        insert_token($_POST['ghposts_token']);
    }

    if (isset($_POST['ghposts_run']) && check_admin_referer('ghposts_run_clicked')) {
        $body = get_request('https://api.github.com/repos/annkamsk/polishlyrics/git/trees/master'); 
        $tree = $body->{'tree'};
        foreach ($tree as $key => $file) {
            get_post_content($file->{'url'});
        }
    }
?>
    <h4>Sync posts</h4>
    <form action="options-general.php?page=ghposts" method="post">
        <?php wp_nonce_field('ghposts_run_clicked'); ?>
        <input type="hidden" name="ghposts_run" value="true">
        <?php submit_button('Run'); ?>
    </form>
<?php
    echo '</div>';
}