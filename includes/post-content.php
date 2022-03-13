<?php

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

    public function toHtml() {
        return "
            <!-- wp:paragraph -->
            <p>
                <em>Skąd: $this->from</em><br>
                <em>Tytuł: $this->orig_title</em><br>
                <em>Słowa: $this->lyrics_by</em><br>
                <em>Polskie słowa: $this->translate_by</em><br>
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

    public function __construct(string $raw_text, PostMetadata $metadata) {
        $this->content = array();
        $this->lines = explode("\n", $raw_text);
        $this->metadata = $metadata;
        $this->parse();
    }

    function find_character($line) {
        $matches = array();
        $res = preg_match($this->character_rgx, $line, $matches);
        if ($res) {
            return strtoupper($matches[1]);
        }
        return '';
    }

    function parse() {
        $verse = array();

        foreach ($this->lines as $line) {
            $character = $this->find_character($line);
            if ($character) {
                if (!empty($verse)) {
                    array_push($this->content, $verse);
                    $verse = array();
                }
                array_push($verse, $character);
                continue;
            }
            if (strlen(trim($line)) == 0) {
                if (!empty($verse)) {
                    array_push($this->content, $verse);
                    $verse = array();
                }
                continue;
            }
            array_push($verse, $line);
        }
        if (!empty($verse)) {
            array_push($this->content, $verse);
        }
    }

    public function getHtml() {
        $metadataHtml = $this->metadata->toHtml();
        $column = "";
        foreach ($this->content as $el) {
            $column .= "<!-- wp:paragraph --><p>" . implode("<br>", $el) . "</p><!-- /wp:paragraph -->";
        }
        $html = "
            <!-- wp:column -->
                <div class=\"wp-block-column\">
                $column
                </div>
            <!-- /wp:column -->
            <!-- wp:column -->
                <div class=\"wp-block-column\">
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