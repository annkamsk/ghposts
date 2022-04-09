<?php
require_once( __DIR__ . '/Spyc.php');
require_once( __DIR__ . '/post-content.php');

class FrontMatterParser {
    public static function parse(string $content) {
        $start_pattern = '~^[\s\r\n]?---[\s\r\n]?$~sm';
        $parts = preg_split($start_pattern, trim($content));

        if (!$parts || count($parts) < 3) {
            return null;
        }
        $matter = Spyc::YAMLLoad(trim($parts[1]));
        $body = Spyc::YAMLLoad(trim($parts[2]));
        return [new PostMetadata($matter), $body];
    }
}

?>