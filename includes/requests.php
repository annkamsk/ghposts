<?php 

require_once( __DIR__ . '/token.php');

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
?>