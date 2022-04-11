<?php

require_once( __DIR__ . '/languages.php');
require_once( __DIR__ . '/requests.php');


function insert_or_update($postContent) {
    global $wpdb;
    $posts = $wpdb->get_results( $wpdb->prepare("SELECT ID FROM $wpdb->posts WHERE post_title = %s AND post_status IN ('publish', 'draft')", $postContent->metadata->getTitle()));

    if (empty($posts)) {
        insert_post($postContent);
        return;
    }
    if (count($posts) == 1) {
        update_post($posts[0], $postContent);
        $lang = pl_get_post_language($posts[0]->{'ID'});
        $missing_lang = $lang == "pl" ? "en" : "pl";
        $post_id = insert_language_version($postContent, $missing_lang);
        echo $post_id;
        pll_save_post_translations(array($lang => $posts[0]->{'ID'}, $missing_lang => $post_id));
        return;
    }
    foreach ($posts as $post) {
        update_post($post, $postContent);
    }
}

function insert_post($postContent) {
    $post_id_pl = insert_language_version($postContent, "pl");
    $post_id_en = insert_language_version($postContent, "en");
    
    pll_save_post_translations(array("pl" => $post_id_pl, "en" => $post_id_en));
    echo "Created " . $postContent->metadata->getTitle();
}

function insert_language_version($postContent, $lang) {
    $post_id = wp_insert_post(array(
        'post_content' => $postContent->getHtml($lang),
        'post_title' => $postContent->metadata->getTitle()
    ));
    pl_set_post_language($post_id, $lang);
    wp_set_post_tags($post_id, $postContent->metadata->getTags(), true);
    set_post_categories($post_id, $postContent->metadata->getCategories());
    return $post_id;
}

function update_post($post, $postContent) {
    // if (empty(array_filter(get_the_category($post->{'ID'}), function ($v, $k) {
    //     return $v->{'name'} == 'managed';
    // }, ARRAY_FILTER_USE_BOTH))) {
    $lang = pl_get_post_language($post->{'ID'});
    $postData = array(
        'ID' => $post->{'ID'},
        'post_title'   => $postContent->metadata->getTitle(),
        'post_content' => $postContent->getHtml($lang)
    );
    wp_update_post( $postData );
    wp_set_post_tags($post->{'ID'}, $postContent->metadata->getTags(), true);
    set_post_categories($post->{'ID'}, $postContent->metadata->getCategories());
    echo "Updated " . $postContent->metadata->getTitle();
        // }
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

function set_post_categories($post_id, $categories) {

    $wp_categories = array();
    foreach ($categories as $category) {
        $id = get_cat_ID($category);
        if ($id == 0) {
            continue;
        }
        array_push($wp_categories, $id);
    }
    wp_set_post_categories($post_id, $wp_categories, true);
}
?>