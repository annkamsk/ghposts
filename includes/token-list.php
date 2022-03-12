<?php

if (!class_exists('WP_List_Table')){
    require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

class Token_List_Table extends WP_List_Table {

    function get_columns() {
        return array(
            'token_id'=>'ID',
            'url'=>'Url',
            'last_run_at'=>'Last run at',
            'last_run_status'=>'Status',
            'sync'=>'Sync'
        );
    }

    function get_tokens() {
        global $wpdb;
        $table_name = "{$wpdb->base_prefix}gh_tokens";
        $sql = $wpdb->prepare(
            "
                SELECT token_id, url, last_run_at, last_run_status
                FROM $table_name
                WHERE user_id = %s
            ",
            get_current_user_id()
        );
        return $wpdb->get_results($sql);
    }

    function prepare_items() {
        $columns = $this->get_columns();
        $tokens = $this->get_tokens(); 
        $totalItems = count($tokens);

        $this->set_pagination_args( array(
            'total_items' => $totalItems,
            'per_page'    => $totalItems
        ) );

        $this->_column_headers = array($columns, array(), array());
        $this->items = $tokens;
    }

    public function column_default( $item, $column_name ) {
        switch( $column_name ) {
            case 'token_id':
            case 'url':
            case 'last_run_at':
            case 'last_run_status':
                return $item->$column_name;
            case 'sync':
                return $this->sync_button($item->token_id);
            default:
                return '';
        }
    }

    private function sync_button($token_id) {
        echo '<form action="options-general.php?page=ghposts" method="post">';
        wp_nonce_field('ghposts_run_clicked');
        echo '<input type="hidden" name="ghposts_run" value="true">';
        echo "<input type=\"hidden\" name=\"ghposts_token_id\" value=$token_id>";
        submit_button('Run');
        echo '</form>';
    }

}

function display_token_list() {
    $wp_token_table = new Token_List_Table();
    $wp_token_table->prepare_items();
    $wp_token_table->display();
}
?>