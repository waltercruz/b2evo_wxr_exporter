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
 *
 * @version $Id: index.main.php,v 1.25 2009/05/19 14:51:47 waltercruz Exp $
 */
$debug =1;

if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

$statusmap = array('published'=>'publish');

// Note: even if we request the same post as $Item earlier, the following will do more restrictions (dates, etc.)
// Init the MainList object:
init_MainList( 1000000000 );

// What level of detail do we want?
//$feed_content = $Blog->get_setting('feed_content');
$feed_content = 'full';
//header_content_type( 'application/xml' );	// Sets charset!

echo '<?xml version="1.0" encoding="UTF-8"?'.'>';
//global $GenericCategoryCache;
if( version_compare( $app_version, '4.0' ) > 0 )
{
	$GenericCategoryCache = & get_ChapterCache();
}
else
{
	$GenericCategoryCache = & new ChapterCache();
}
$GenericCategoryCache->load_subset($Blog->ID);
$ItemTypeCache = & get_Cache( 'ItemTypeCache' );
//print_r($GenericCategoryCache);
?>
<!-- generator="<?php echo $app_name ?>/<?php echo $app_version ?>" -->
<rss version="2.0"
        xmlns:content="http://purl.org/rss/1.0/modules/content/"
        xmlns:wfw="http://wellformedweb.org/CommentAPI/"
        xmlns:dc="http://purl.org/dc/elements/1.1/"
        xmlns:wp="http://wordpress.org/export/1.0/">
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
		<wp:wxr_version>1.0</wp:wxr_version>
		<?php
		foreach ($GenericCategoryCache->subset_cache[$Blog->ID] as $Chapter)
		{
			echo '<wp:category><wp:category_nicename>' . $Chapter->dget( 'urlname' ) .'</wp:category_nicename><wp:category_parent></wp:category_parent><wp:cat_name><![CDATA[' . utf8_encode($Chapter->dget( 'name' , 'raw' ))   .']]></wp:cat_name></wp:category>';
			echo "\n";
		}
		?>
		<admin:generatorAgent rdf:resource="http://b2evolution.net/?v=<?php echo $app_version ?>"/>
		<ttl>60</ttl>
		<?php
		while( $Item = & mainlist_get_item() )
		{	// For each blog post, do everything below up to the closing curly brace "}"
			?>
		<item>
			<title><?php $Item->title( array(
				'format' => 'xml',
				'link_type' => 'none',
			) ); ?></title>
			<link><?php $Item->permanent_url( 'single' ) ?></link>
			<?php
				$Item->issue_date( array(
						'before'      => '<pubDate>',
						'after'       => '</pubDate>',
						'date_format' => 'r',
   					'use_GMT'     => true,
					) );
			?>
			<dc:creator><?php $Item->get_creator_User(); $Item->creator_User->preferred_name('xml') ?></dc:creator>
			<wp:post_type><?php
			$Element = & $ItemTypeCache->get_by_ID( $Item->ptyp_ID, true, false );
			echo strtolower($Element->get('name'));
			?></wp:post_type>
			<wp:comment_status><?php echo $Item->comment_status ?></wp:comment_status>
			<wp:status><?php echo($statusmap[$Item->status]) ?></wp:status>
			<wp:post_name><?php echo $Item->urltitle ?></wp:post_name>
			<wp:post_id><?php echo $Item->ID ?></wp:post_id>
			<?php
				$Item->issue_date( array(
						'before'      => '<wp:post_date>',
						'after'       => '</wp:post_date>',
						'date_format' => 'Y-m-d H:i:s',
   					'use_GMT'     => false,
					) );
			?>
			<?php
				$Item->issue_date( array(
						'before'      => '<wp:post_date_gmt>',
						'after'       => '</wp:post_date_gmt>',
						'date_format' => 'Y-m-d H:i:s',
   					'use_GMT'     => true,
					) );
			?>
			<?php
				foreach( $Item->get_Chapters() as $Chapter )
				{
					echo '<category><![CDATA[' . utf8_encode($Chapter->dget( 'name', 'raw' )) .']]></category>';
					echo '<category domain="category" nicename="' . $Chapter->dget( 'urlname' ) .'"><![CDATA[' . utf8_encode($Chapter->dget( 'name', 'raw' ) ) .']]></category>';
				}
				$tags = $Item->get_tags();
				foreach ($tags as $tag)
				{
					echo '<category domain="tag"><![CDATA[' . utf8_encode($tag) .']]></category>';
					echo '<category domain="tag" nicename="' . utf8_encode($tag ) .'"><![CDATA[' . utf8_encode($tag ) .']]></category>';
				}
			?>
			<guid isPermaLink="false"><?php $Item->ID() ?>@<?php echo $baseurl ?></guid>
			<?php
				// PODCAST ------------------------------------------------------------------------
				if( $Item->ptyp_ID == 2000 )
				{	// This is a podcast Item !
					echo '<enclosure url="'.$Item->url.'" />';
					// TODO: add length="12216320" type="audio/mpeg"
				}

				if( $feed_content == 'excerpt' )
				{	// EXCERPTS ---------------------------------------------------------------------

					?>
			<content:encoded><![CDATA[<?php
				$content = $Item->get_excerpt( 'htmlbody' );

				// fp> this is another one of these "oooooh it's just a tiny little change"
				// and "we only need to make the links absolute in RSS"
				// and then you get half baked code! The URL LINK stays RELATIVE!! :((
				// TODO: clean solution : work in format_to_output! --- we probably need 'htmlfeed' as 'htmlbody+absolute'
				echo make_rel_links_abs( $content );

				// Display Item footer text (text can be edited in Blog Settings):
				$Item->footer( array(
						'mode'        => 'xml',
						'block_start' => '<div class="item_footer">',
						'block_end'   => '</div>',
						'format'      => 'htmlbody',
					) );
			?>]]></content:encoded>
					<?php

				}
				elseif( $feed_content == 'normal'
							|| $feed_content == 'full' )
				{	// POST CONTENTS -----------------------------------------------------------------

					?>
			<content:encoded><![CDATA[<?php
				// URL link, if the post has one:
				$Item->url_link( array(
						'before'        => '<p>',
						'after'         => '</p>',
						'podcast'       => false,
					) );

				// Display images that are linked to this post:
				$content = $Item->get_images( array(
						'before' =>              '<div>',
						'before_image' =>        '<div>',
						'before_image_legend' => '<div><i>',
						'after_image_legend' =>  '</i></div>',
						'after_image' =>         '</div>',
						'after' =>               '</div>',
						'image_size' =>          'fit-320x320'
					), 'htmlbody' );

				$content .= $Item->get_content_teaser( 1, false );

				if( $feed_content == 'normal' )
				{	// Teasers only
					$content .= $Item->get_more_link( array(
							'before'    => '',
							'after'     => '',
							'disppage'  => 1,
						) );
				}
				else
				{	// Full contents
					$Item->split_pages('entityencoded');
//					print_r(array_keys($Item->content_pages));
					for ($i=2;$i<=$Item->pages;$i++)
					{		
//					$content .= $Item->get_content_extension( 1, true, 'entityencoded' );
					$content .= format_to_output( $Item->get_content_page($i,'entityencoded'),'htmlbody');

					}
				}

				// fp> this is another one of these "oooooh it's just a tiny little change"
				// and "we only need to make the links absolute in RSS"
				// and then you get half baked code! The URL LINK stays RELATIVE!! :((
				// TODO: clean solution : work in format_to_output! --- we probably need 'htmlfeed' as 'htmlbody+absolute'
				echo make_rel_links_abs( utf8_encode($content) );

				// Display Item footer text (text can be edited in Blog Settings):
				$Item->footer( array(
						'mode'        => 'xml',
						'block_start' => '<div class="item_footer">',
						'block_end'   => '</div>',
						'format'      => 'htmlbody',
					) );
			?>]]></content:encoded>
					<?php
				}
			?>

		<?php
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
		echo '<wp:comment>';
		echo '<wp:comment_id>'. $Comment->ID . '</wp:comment_id>';
		echo '<wp:comment_content><![CDATA[' . $Comment->get_content('xml') . ']]></wp:comment_content>';
		echo '<wp:comment_author><![CDATA['; $Comment->author(); echo']]></wp:comment_author>';
		echo '<wp:comment_author_email>' . $Comment->get_author_email() . '</wp:comment_author_email>';
		echo '<wp:comment_author_url>' . utf8_encode($Comment->get_author_url()) . '</wp:comment_author_url>';
		echo '<wp:comment_author_IP>' . $Comment->author_IP .'</wp:comment_author_IP>';
		echo '<wp:comment_date>' . mysql2date( 'Y-m-d H:i:s', $Comment->date, false) .'</wp:comment_date>';
		echo '<wp:comment_date_gmt>' . mysql2date( 'Y-m-d H:i:s', $Comment->date, true) .'</wp:comment_date_gmt>';
		
		$comment_approved = 0;
		if ($Comment->status == 'published')
		{
		    $comment_approved = 1;    
		}
		echo '<wp:comment_approved>'. $comment_approved . '</wp:comment_approved>';
		echo '</wp:comment>';
		echo "\n";
		}
		?>
		</item>
		<?php
		}
		?>
	</channel>
</rss>
<?
exit(0);
?>