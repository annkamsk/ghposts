<?php

require_once( __DIR__ . '/languages.php');


class PostMetadata {
    public function __construct($data) {
        $this->title = $data["title"] ?? "";
        $this->from = $data["from"] ?? "";
        $this->orig_title = $data["orig_title"] ?? "";
        $this->lyrics_by = $data["lyrics_by"] ?? "";
        $this->translate_by = $data["translate_by"] ?? "";
        $this->source_lang = $data["source_lang"] ?? "";
        $this->target_lang = $data["target_lang"] ?? "";
    }

    public function toHtml($lang = "pl") {
        return "
            <!-- wp:paragraph -->
            <p>
                <em>".pl_translate_string("From", $lang).": $this->from</em><br>
                <em>".pl_translate_string("Original title", $lang).": $this->orig_title</em><br>
                <em>".pl_translate_string("Lyrics by", $lang).": $this->lyrics_by</em><br>
                <em>".pl_translate_string("Translated by", $lang).": $this->translate_by</em><br>
            </p>
            <!-- /wp:paragraph -->
        ";
    }

    public function getTitle() {
        return ($this->from ? $this->from . ' – ' : '') . "$this->title ($this->orig_title)";
    }
}

class PostContent {
    public $content;
    public $lines;
    public $metadata;

    private $character_rgx = '/##### (.+)/';

    public function __construct(array $content, PostMetadata $metadata) {
        $this->metadata = $metadata;
        $raw_text = $content["target"];
        $this->target = $this->parse(explode("\n", $raw_text));

        $this->source = array();

        if (array_key_exists("source", $content)) {
            $this->source = $this->parse(explode("\n", $content["source"]));
        }
    }

    function find_character($line) {
        $matches = array();
        $res = preg_match($this->character_rgx, $line, $matches);
        if ($res) {
            return strtoupper($matches[1]);
        }
        return '';
    }

    function parse(array $lines) {
        $verse = array();
        $content = array();

        foreach ($lines as $line) {
            $character = $this->find_character($line);
            if ($character) {
                if (!empty($verse)) {
                    array_push($content, $verse);
                    $verse = array();
                }
                array_push($verse, $character);
                continue;
            }
            if (strlen(trim($line)) == 0) {
                if (!empty($verse)) {
                    array_push($content, $verse);
                    $verse = array();
                }
                continue;
            }
            array_push($verse, $line);
        }
        if (!empty($verse)) {
            array_push($content, $verse);
        }
        return $content;
    }

    public function getHtml($lang = "pl") {
        $metadataHtml = $this->metadata->toHtml($lang);
        $column1 = "";
        foreach ($this->target as $el) {
            $column1 .= "<!-- wp:paragraph --><p>" . implode("<br>", $el) . "</p><!-- /wp:paragraph -->";
        }
        $column2 = "";
        foreach ($this->source as $el) {
            $column2 .= "<!-- wp:paragraph --><p>" . implode("<br>", $el) . "</p><!-- /wp:paragraph -->";
        }
        $html = "
            <!-- wp:column -->
                <div class=\"wp-block-column\">
                <h4 class=\"lang_header\">" . 
                    pl_translate_string($this->metadata->source_lang, $lang) .
                "</h4>
                $column1
                </div>
            <!-- /wp:column -->
            <!-- wp:column -->
                <div class=\"wp-block-column\">
                <h4 class=\"lang_header\">" .
                pl_translate_string($this->metadata->target_lang, $lang) .
                "</h4>
                $column2
                </div>
            <!-- /wp:column -->
        ";
        return $metadataHtml . "
            <!-- wp:columns -->
                <div class=\"wp-block-columns\">
                $html
                </div>\n
            <!-- /wp:columns -->
        ";
    }

}
?>