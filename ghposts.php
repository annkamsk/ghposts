<?php

/*
Plugin Name: GHPosts
*/

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
        $this->lines = explode("\n", $raw_text);;
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

function get_request($url) {
    $token = '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';
    $args = array(
        'headers' => array(
            'Authorization' => 'Bearer ' . $token,
        ),
    ); 
    return json_decode(wp_remote_retrieve_body(wp_remote_get($url, $args)));
}

function insert_or_update($postContent) {
    $post = get_page_by_title($postContent->getTitle(), 'OBJECT', 'post');
    
    if ($post) {
        if (empty(array_filter(get_the_category($post->{'ID'}), function ($v, $k) {
            return $v->{'name'} == 'managed';
        }, ARRAY_FILTER_USE_BOTH))) {
            $postData = array(
                'ID' => $post->{'ID'},
                'post_title'   => $postContent->getTitle(),
                'post_content' => $postContent->getHtml(),
            );
            wp_update_post( $postData );
        }
    } else {
        wp_insert_post(array(
            'post_content' => $postContent->getHtml(),
            'post_title' => $postContent->getTitle()
        ));
    }
    
}

function get_post_content($url) {
    $content = get_request($url);
    $decoded = base64_decode($content -> {'content'});
    $postContent = new PostContent($decoded);
    echo "Downloaded " . $postContent->getTitle();
    insert_or_update($postContent);
}

add_action( 'admin_menu', 'ghposts_menu' );

function ghposts_menu() {
	add_options_page( 'GH Posts', 'GH Posts', 'manage_options', 'ghposts', 'ghposts_options' );
}

function ghposts_options() {
    echo '<div class="wrap">';
    echo '<h2>GH Posts</h2>';

    if (isset($_POST['ghposts_run']) && check_admin_referer('ghposts_run_clicked')) {
        $body = get_request('https://api.github.com/repos/annkamsk/polishlyrics/git/trees/master');
        $tree = $body->{'tree'};
        foreach ($tree as $key => $file) {
            get_post_content($file->{'url'});
        }
    }
?>
    <form action="options-general.php?page=ghposts" method="post">
        <?php wp_nonce_field('ghposts_run_clicked'); ?>
        <input type="hidden" name="ghposts_run" value="true">
        <?php submit_button('Call Function'); ?>
    </form>
<?php
    echo '</div>';
}