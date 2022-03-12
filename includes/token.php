<?php

function create_token_table() {
    global $wpdb;
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE IF NOT EXISTS `{$wpdb->base_prefix}gh_tokens` (
        token_id bigint(20) NOT NULL AUTO_INCREMENT,
        token varchar(255) NOT NULL,
        user_id bigint(20) UNSIGNED NOT NULL UNIQUE,
        created_at datetime NOT NULL,
        PRIMARY KEY  (token_id)
      ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}

function insert_token($token) {
    global $wpdb;
    $user_ID = get_current_user_id();
    $token_sanit = sanitize_text_field($token); 

    if ($user_ID == 0) {
        // User is not logged in
        return;
    }

    $sql = $wpdb->prepare(
        "INSERT INTO wp_gh_tokens (token, user_id, created_at) VALUES (%s, %d, CURRENT_TIMESTAMP()) ON DUPLICATE KEY UPDATE token = %s", $token_sanit, $user_ID, $token_sanit);
    $wpdb->query($sql);

}

function get_token() {
    global $wpdb;
    $table_name = "{$wpdb->base_prefix}gh_tokens";

    return $wpdb->get_var(
        $wpdb->prepare(
            "
                SELECT token
                FROM $table_name
                WHERE user_id = %s
            ",
            get_current_user_id()
        )
    );
}


function admin_save_token() {
?>
    <h4>Github token</h4>
    <form action="options-general.php?page=ghposts" method="post">
        <?php wp_nonce_field('ghposts_token_clicked'); ?>
        <input type="text" name="ghposts_token">
        <?php submit_button('Save token'); ?>
    </form>

<?php
}
?>
