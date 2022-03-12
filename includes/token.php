<?php

function create_token_table() {
    global $wpdb;
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE IF NOT EXISTS `{$wpdb->base_prefix}gh_tokens` (
        token_id bigint(20) NOT NULL AUTO_INCREMENT,
        token varchar(255) NOT NULL,
        user_id bigint(20) UNSIGNED NOT NULL,
        url varchar(512) NOT NULL,
        created_at datetime NOT NULL,
        last_run_at datetime,
        last_run_status varchar(255),
        PRIMARY KEY  (token_id),
        UNIQUE KEY unique_user_url (user_id, url)
      ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}

function insert_token($token, $url) {
    global $wpdb;
    $user_ID = get_current_user_id();
    $token_sanit = sanitize_text_field($token); 
    $url_sanit = sanitize_url($url);

    if ($user_ID == 0) {
        // User is not logged in
        return;
    }

    $sql = $wpdb->prepare(
        "INSERT INTO {$wpdb->base_prefix}gh_tokens (token, user_id, url, created_at) VALUES (%s, %d, %s, CURRENT_TIMESTAMP()) ON DUPLICATE KEY UPDATE token = %s", $token_sanit, $user_ID, $url_sanit, $token_sanit);
    $wpdb->query($sql);
}

function update_token_status($token_id, $response) {
    $status = wp_remote_retrieve_response_code($response);
    if ($status < 200 || $status >= 300) {
        $last_run_status = "Error: " . wp_remote_retrieve_response_message($response);
    } else {
        $last_run_status = "Success";
    }
    global $wpdb;
    $sql = $wpdb->prepare(
        "UPDATE {$wpdb->base_prefix}gh_tokens 
        SET last_run_at = CURRENT_TIMESTAMP(),
            last_run_status = %s
        WHERE token_id = %s AND user_id = %s", 
        $last_run_status, $token_id, get_current_user_id());
    $wpdb->query($sql);
}

function get_token($token_id) {
    global $wpdb;
    $table_name = "{$wpdb->base_prefix}gh_tokens";

    return $wpdb->get_row(
        $wpdb->prepare(
            "
                SELECT token, url
                FROM $table_name
                WHERE user_id = %s AND token_id = %s
            ",
            get_current_user_id(),
            $token_id
        )
    );
}


function display_admin_save_token() {
?>
    <h4>Add new Github token</h4>
    <form action="options-general.php?page=ghposts" method="post">
        <?php wp_nonce_field('ghposts_token_clicked'); ?>
        <label for="ghposts_url">Github API url:</label><br>
        <input type="text" name="ghposts_url" type="url" pattern="https://.*" value="https://api.github.com/repos/username/reponame/git/trees/master" size="60" required><br>
        <label for="ghposts_token">Github API token:</label><br>
        <input type="text" name="ghposts_token" size="60" required>
        <?php submit_button('Save token'); ?>
    </form>
<?php
}
?>
