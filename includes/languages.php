<?php

function register_polylang_strings() {
    if (function_exists("pll_register_string")) {
        pll_register_string("ghposts_from", "From");
        pll_register_string("ghposts_origtitle", "Original title");
        pll_register_string("ghposts_lyricsby", "Lyrics by");
        pll_register_string("ghposts_translateby", "Translated by");
        pll_register_string("ghposts_pl", "pl");
        pll_register_string("ghposts_eng", "en");
        pll_register_string("ghposts_de", "de");
    }
}

function pl__( $string = '' ) {
    if ( function_exists( 'pll__' ) ) {
        return pll__( $string );
    }

    return $string;
}

function pl_set_post_language($post_id, $lang) {
    if (function_exists('pll_set_post_language')) {
        pll_set_post_language($post_id, $lang);
    }
}

function pl_get_post_language($post_id) {
    if (function_exists('pll_get_post_language')) {
        return pll_get_post_language($post_id);
    }
    return null;
}

function pl_translate_string($string, $lang) {
    if (function_exists('pll_translate_string')) {
        return pll_translate_string($string, $lang);
    }
    return $string;
}

?>