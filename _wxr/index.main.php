<?php
/**
 * This template generates an RSS 2.0 feed for the requested blog's latest posts
 *
 * For a quick explanation of b2evo 2.0 skins, please start here:
 * {@link http://manual.b2evolution.net/Skins_2.0}
 *
 * See {@link http://backend.userland.com/rss092}
 *
 * @todo iTunes podcast tags: http://www.apple.com/itunes/store/podcaststechspecs.html
 * Note: itunes support: .m4a, .mp3, .mov, .mp4, .m4v, and .pdf.
 *
 * @package evoskins
 * @subpackage rss
 */
$debug =1;

if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

$statusmap = array('published'=>'publish');

/**
 * Offset to map attachment/file IDs to wp:post_ID.
 */
define('OFFSET_ATTACHMENT_ID', 2000000000);

// Note: even if we request the same post as $Item earlier, the following will do more restrictions (dates, etc.)
// Init the MainList object:
// init_MainList( 1000000000 );
init_MainList( 100 );

// we need to output utf8
init_charsets('utf-8');

// What level of detail do we want?
//$feed_content = $Blog->get_setting('feed_content');
$feed_content = 'full';
//header_content_type( 'application/xml' ); // Sets charset!

echo '<?xml version="1.0" encoding="UTF-8"?'.'>';
//global $GenericCategoryCache;
if( version_compare( $app_version, '4.0' ) > 0 ) {
  $GenericCategoryCache = & get_ChapterCache();
} else {
  $GenericCategoryCache = & new ChapterCache();
}
$GenericCategoryCache->load_subset($Blog->ID);
$ItemTypeCache = & get_Cache( 'ItemTypeCache' );
//print_r($GenericCategoryCache);

function render_content($content) {
  // Replace "video:youtube" shortcodes: WP handles links on separate lines using oEmbed nicely.
  $content = preg_replace('~<p>\[video:youtube:(\S+)\]</p>~', "\nhttp://youtube.com/watch?v=$1\n", $content); // special case: wrapped in P
  $content = preg_replace('~\[video:youtube:(\S+)\]~', "\nhttp://youtube.com/watch?v=$1\n", $content);

  // Massage codespan blocks
  // <!-- codeblock lang="sh" line="1" --><pre><code> => <pre lang="sh" line="1"><code>
  $content = preg_replace_callback('~<!-- codeblock (.*?)--><pre><code>(.*?)</code></pre><!-- /codeblock -->~s', 'massage_code_content', $content);

  $content = make_rel_links_abs( $content );
  // remove trailing LF characters
  $content = preg_replace('~\r$~m', '', $content);
  return $content;
};

function massage_code_content($m) {
  $attribs = trim($m[1]);
  $code = trim($m[2]);
  $code = str_replace(
    array( '&lt;', '&gt;', '&amp;' ),
    array( '<', '>', '&' ), $code ); // yes, b2evo's code_highlighter_plugin does this!

  $r = '<pre';
  if( strlen($attribs) ) {
    $r .= ' '.$attribs;
  }
  $r .= "'>\n$code\n</pre>";
  return $r;
};

$relevant_user_IDs = array();
?>
<!-- generator="<?php echo $app_name ?>/<?php echo $app_version ?>" -->
<rss version="2.0"
  xmlns:excerpt="http://wordpress.org/export/1.2/excerpt/"
  xmlns:content="http://purl.org/rss/1.0/modules/content/"
  xmlns:wfw="http://wellformedweb.org/CommentAPI/"
  xmlns:dc="http://purl.org/dc/elements/1.1/"
  xmlns:wp="http://wordpress.org/export/1.2/">

<channel>
    <title><?php
      $Blog->disp( 'name', 'xml' );
      // ------------------------- TITLE FOR THE CURRENT REQUEST -------------------------
      request_title( array(
          'title_before'=> ' - ',
          'title_after' => '',
          'title_none'  => '',
          'glue'        => ' - ',
          'title_single_disp' => true,
          'format'      => 'xml',
        ) );
      // ------------------------------ END OF REQUEST TITLE -----------------------------
    ?></title>
    <link><?php $Blog->disp( 'url', 'xml' ) ?></link>
    <description><?php $Blog->disp( 'shortdesc', 'xml' ) ?></description>
    <language><?php $Blog->disp( 'locale', 'xml' ) ?></language>
    <docs>http://blogs.law.harvard.edu/tech/rss</docs>
    <wp:base_site_url><?php $Blog->disp( 'url', 'xml' ) ?></wp:base_site_url>
    <wp:base_blog_url><?php $Blog->disp( 'url', 'xml' ) ?></wp:base_blog_url>
    <wp:wxr_version>1.2</wp:wxr_version>
    <?php
    // List of categories in the blog
    foreach ($GenericCategoryCache->subset_cache[$Blog->ID] as $Chapter)
    {
      echo '<wp:category>';
      echo '<wp:term_id>' . $Chapter->ID . '</wp:term_id>';
      echo '<wp:category_nicename>' . $Chapter->dget( 'urlname' ) .'</wp:category_nicename>';
      if( $Chapter->parent_ID ) {
        $parent_Chapter = $GenericCategoryCache->get_by_ID($Chapter->parent_ID);
        echo '<wp:category_parent><![CDATA[' . $parent_Chapter->name . ']]></wp:category_parent>';
      }
      echo '<wp:cat_name><![CDATA[' . $Chapter->name .']]></wp:cat_name>';
      echo '</wp:category>';
      echo "\n";
    }

    echo '<generator>http://b2evolution.net/?v=' . $app_version . '</generator>';
    echo '<ttl>60</ttl>';
    echo "\n";

    while( $Item = & mainlist_get_item() )
    { // For each blog post, do everything below up to the closing curly brace "}"
      echo "<item>\n";

      echo "<title>".$Item->get_title( array(
          'format' => 'xml',
          'link_type' => 'none',
        ) )."</title>\n";

      echo "<link>" . $Item->get_permanent_url( 'single' ) . "</link>\n";

      $Item->issue_date( array(
          'before'      => '<pubDate>',
          'after'       => '</pubDate>',
          'date_format' => 'r',
          'use_GMT'     => true,
        ) );

      echo '<dc:creator>';
      $Item->get_creator_User();
      echo $Item->creator_User->login;
      if( ! in_array($Item->creator_User->ID, $relevant_user_IDs) ) {
        $relevant_user_IDs[] = $Item->creator_User->ID;
      }
      echo "</dc:creator>\n";

      echo "<wp:post_type>post</wp:post_type>\n"; // b2evo has only posts, no pages.

      echo '<wp:comment_status>' . $Item->comment_status . "</wp:comment_status>\n";
      // TODO: wp:ping_status

      echo '<wp:status>' . $statusmap[$Item->status] . "</wp:status>\n";
      echo '<wp:post_name>' . $Item->urltitle . "</wp:post_name>\n";
      echo '<wp:post_id>' . $Item->ID . "</wp:post_id>\n";

      $Item->issue_date( array(
          'before'      => '<wp:post_date>',
          'after'       => '</wp:post_date>',
          'date_format' => 'Y-m-d H:i:s',
          'use_GMT'     => false,
        ) );
      echo "\n";

      $Item->issue_date( array(
          'before'      => '<wp:post_date_gmt>',
          'after'       => '</wp:post_date_gmt>',
          'date_format' => 'Y-m-d H:i:s',
          'use_GMT'     => true,
        ) );
      echo "\n";

      // Chapters
      foreach( $Item->get_Chapters() as $Chapter )
      {
        echo '<category><![CDATA[' . $Chapter->dget( 'name', 'raw' ) .']]></category>';
        echo '<category domain="category" nicename="' . $Chapter->dget( 'urlname' ) .'"><![CDATA[' . $Chapter->dget( 'name', 'raw' ) .']]></category>';
      }
      echo "\n";

      // Tags
      $tags = $Item->get_tags();
      foreach ($tags as $tag)
      {
        // TODO: wp:tag ?
        echo '<category domain="tag"><![CDATA[' .$tag.']]></category>';
        echo '<category domain="tag" nicename="' . $tag .'"><![CDATA[' . $tag .']]></category>';
      }
      echo "\n";

      if( $Item->ptyp_ID != 1 ) {
        // not a standard type
        if( $Item->ptyp_ID == 2 ) {
          // Link
          echo "<category domain=\"post_format\" nicename=\"post-format-link\">Link</category>\n";
        }
      }

      echo '<guid isPermaLink="false">' . $Item->ID . '@' . $Blog->get('url') . '</guid>';
      echo "\n";

      // PODCAST ------------------------------------------------------------------------
      if( $Item->ptyp_ID == 2000 )
      { // This is a podcast Item !
        echo '<enclosure url="'.$Item->url.'" />';
        // TODO: add length="12216320" type="audio/mpeg"
      }

      // Excerpt
      echo "<excerpt:encoded><![CDATA[";
      $excerpt = make_rel_links_abs($Item->get_excerpt( 'raw' ));
      $excerpt = preg_replace('~\r$~m', '', $excerpt);
      echo $excerpt;
      // Display Item footer text (text can be edited in Blog Settings):
      $Item->footer( array(
          'mode'        => 'xml',
          'block_start' => '<div class="item_footer">',
          'block_end'   => '</div>',
          'format'      => 'raw',
        ) );
      echo "]]></excerpt:encoded>\n";

      // Content
      echo "<content:encoded><![CDATA[";

      // // Display images that are linked to this post:
      // $content = $Item->get_images( array(
      //     'before' =>              '<div>',
      //     'before_image' =>        '<div>',
      //     'before_image_legend' => '<div><i>',
      //     'after_image_legend' =>  '</i></div>',
      //     'after_image' =>         '</div>',
      //     'after' =>               '</div>',
      //     'image_size' =>          'fit-320x320'
      //   ), 'raw' );

      // $content .= $Item->get_prerendered_content('htmlbody');

      $content = render_content($Item->content);

      // Output URL link, if the post has one.
      // Wordpress handles them well by default (via oEmbed).
      // TODO: non-oEmbed-links get not linked.
      if( strlen($Item->url) ) {
        // only output youtube URL if it's not in the post content already.
        $youtube_regexp = 'http://(?:www\.)?youtube.com/watch\?v=%s';
        $links_to_youtube = preg_match(sprintf("~$youtube_regexp~i", '([^#&\s]+)'), $Item->url, $match);
        $content_has_same_youtube_link = $links_to_youtube
          && preg_match('~^'.sprintf($youtube_regexp, $match[1]).'$~m', $content);

        if( ! $content_has_same_youtube_link ) {
          $content = $Item->url . "\n" . $content;
        }
      }
      echo trim($content);

      // Display Item footer text (text can be edited in Blog Settings):
      $Item->footer( array(
          'mode'        => 'xml',
          'block_start' => '<div class="item_footer">',
          'block_end'   => '</div>',
          'format'      => 'raw',
        ) );
      echo ']]></content:encoded>';

      // Comments
      $type_list = array();
      if( version_compare( $app_version, '4.0' ) > 0 )
      {
        $type_list[] = "comment";
        $CommentList = new CommentList2( $Blog, 1000, 'CommentCache', 'c_' );

        // Filter list:
        $CommentList->set_default_filters( array(
          'types' => $type_list,
          'statuses' => array( 'published', 'draft', 'deprecated' ),
          'post_ID' => $Item->ID,
          'order' => $Blog->get_setting( 'comments_orderdir' ),
        ) );

        $CommentList->load_from_Request();

        // Get ready for display (runs the query):
        $CommentList->display_init();
      }
      else
      {
        $type_list[] = "'comment'";
        $CommentList = & new CommentList( NULL, implode(',', $type_list), array( 'published', 'draft', 'deprecated' ), $Item->ID, '', 'ASC' );
      }
      while( $Comment = & $CommentList->get_next() )
      {
        // remove any trailing BRs from "Auto-BR"
        $comment_content = $Comment->content;
        preg_replace('~<br(\s*/)>$~m', '', $comment_content);
        echo '<wp:comment>';
        echo '<wp:comment_id>'. $Comment->ID . '</wp:comment_id>';
        echo '<wp:comment_content><![CDATA[' . $comment_content . ']]></wp:comment_content>';
        echo '<wp:comment_author><![CDATA[' . $Comment->get_author_name() . ']]></wp:comment_author>';
        echo '<wp:comment_author_email>' . $Comment->get_author_email() . '</wp:comment_author_email>';
        echo '<wp:comment_author_url>' . $Comment->get_author_url() . '</wp:comment_author_url>';
        echo '<wp:comment_author_IP>' . $Comment->author_IP .'</wp:comment_author_IP>';
        if( $Comment->author_user_ID ) {
          echo '<wp:comment_user_id>' . $Comment->author_user_ID .'</wp:comment_user_id>';
          if( ! in_array($Comment->author_user_ID, $relevant_user_IDs) ) {
            $relevant_user_IDs[] = $Comment->author_user_ID;
          }
        }
        echo '<wp:comment_date>' . mysql2date( 'Y-m-d H:i:s', $Comment->date, false) .'</wp:comment_date>';
        echo '<wp:comment_date_gmt>' . mysql2date( 'Y-m-d H:i:s', $Comment->date, true) .'</wp:comment_date_gmt>';
        $comment_approved = (int)( $Comment->status == 'published' );
        echo '<wp:comment_approved>'. $comment_approved . '</wp:comment_approved>';
        echo '</wp:comment>';
        echo "\n";
      }

      // Post attachments
      $FileList = $Item->get_attachment_FileList();
      foreach( $Item->get_attachment_FileList() as $File ) {
        if( $File->is_dir() ) {
          continue;
        }
        $File->load_meta();
        $url = $File->get_url();
        // Fix protocol-relative scheme: "//foo/bar" => "http://foo/bar"
        if( substr($url, 0, 2) == '//' ) {
          $url = ( (isset($_SERVER['HTTPS']) && ( $_SERVER['HTTPS'] != 'off' ) ) ?'https://':'http://' ) . substr($url, 2);
        }
        $url = preg_replace('~\?mtime=\d+$~', '', $url); // remove "?mtime=123" suffix from URLs
        $file_ts = $File->get_lastmod_ts();
        $date_gmt = gmdate('r', $file_ts);
        echo "</item>\n";
        echo "<item>\n";
        // TODO: author
        printf( "<title>%s</title>\n", $File->title );
        printf( "<wp:post_id>%s</wp:post_id>\n", $File->ID + OFFSET_ATTACHMENT_ID );
        printf( "<pubDate>%s</pubDate>\n", $date_gmt );
        printf( "<guid isPermaLink=\"false\"></guid>\n" );
        printf( "<content:encoded><![CDATA[%s]]></content:encoded>\n", $File->desc );
        printf( "<excerpt:encoded><![CDATA[%s]]></excerpt:encoded>\n" );
        printf( "<wp:attachment_url>%s</wp:attachment_url>\n", $url );
        printf( "<wp:post_type>%s</wp:post_type>\n", 'attachment' );
        printf( "<wp:post_parent>%s</wp:post_parent>\n", $Item->ID );
        printf( "<wp:post_date>%s</wp:post_date>\n", date('r', $file_ts));
        printf( "<wp:post_date_gmt>%s</wp:post_date_gmt>\n", $date_gmt);

        if( strlen($File->alt) ) {
          printf( "<wp:postmeta><wp:meta_key>_wp_attachment_image_alt</wp:meta_key><wp:meta_value><![CDATA[%s]]></wp:meta_value></wp:postmeta>\n", $File->alt );
        }
      }


      echo "</item>\n\n";
    }


    $UserCache = get_UserCache();
    // $UserCache->load_all();
    // while( $User = $UserCache->get_next() ) {
    foreach( $relevant_user_IDs as $user_ID ) {
      $User = $UserCache->get_by_ID($user_ID);
      printf( "<wp:author>\n" );
      printf( "\t<wp:author_id>%d</wp:author_id>\n", $User->ID );
      printf( "\t<wp:author_login>%s</wp:author_login>\n", $User->login );
      printf( "\t<wp:author_email>%s</wp:author_email>\n", $User->email );
      printf( "\t<wp:author_display_name>%s</wp:author_display_name>\n", $User->get_preferred_name() );
      printf( "\t<wp:author_first_name>%s</wp:author_first_name>\n", $User->firstname );
      printf( "\t<wp:author_last_name>%s</wp:author_last_name>\n", $User->lastname );
      printf( "</wp:author>\n" );
    }
    ?>
  </channel>
</rss>
