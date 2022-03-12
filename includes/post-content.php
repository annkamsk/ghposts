<?php

class PostContent {
    public $content;
    public $lines;
    public $metadata;

    private $character_rgx = '/##### (.+)/';

    private $metadata_rgx = array(
        'title' => '/# (.+)/',
        'from' => '/_From_: _(.+)_/',
        'orig_title' => '/_Title_: _(.+)_/',
        'author' => '/_By_: _(.+)_/',
    );

    public function __construct($raw_text) {
        $this->content = array();
        $this->lines = explode("\n", $raw_text);
        $this->metadata = array();
        $this->parseMetadata();
        $this->parse();
    }

    function getTitle() {
        $post_title = $this->metadata['from'] ? $this->metadata['from'] . ' – ' : '';
        $post_title .= $this->metadata['title'];
        return $this->metadata['orig_title'] ? $post_title . ' (' . $this->metadata['orig_title'] . ')' : $post_title;
    }

    function parseMetadata() {
        $max_row = 0;
        foreach ($this->lines as $key=>$line) {
            foreach($this->metadata_rgx as $name=>$rgx) {
                $matches = array();
                if (preg_match($rgx, $line, $matches)) {
                    $this->metadata[$name] = $matches[1];
                    $max_row = max($key, $max_row);
                    break;
                }
            }
            if ($key > 3) {
                break;
            }
        }
        $this->lines = array_slice($this->lines, $max_row + 1);
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

    function metadataToHtml() {
        $from = $this->metadata["from"];
        $by = $this->metadata["author"];
        $orig_title = $this->metadata["orig_title"];
        return "
            <!-- wp:paragraph -->
            <p>
                <em>Skąd: $from</em><br>
                <em>Tytuł: $orig_title</em><br>
                <em>Słowa: $by</em><br>
                <em>Polskie słowa: Anna Kramarska</em><br>
            </p>
            <!-- /wp:paragraph -->
        ";
    }

    public function getHtml() {
        $metadataHtml = $this->metadataToHtml();
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