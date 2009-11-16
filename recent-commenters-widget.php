<?php
/*
Plugin Name: Recent Commenters Widget.
Plugin URI: http://macaulay.cuny.edu/eportfolios/techlearning (but I don't have any real support for this)
Description: This is a very very simple modification of the default recent comments widget.  All this does is to display recent commentERS instead of recent comments.  In other words, it just displays the names, without the "...on name-of-the-post" that is there by default.  You can choose whether or not you want the name to have a link, and what it will link to, if it's there
Author: Joseph Ugoretz
Version: 1.1
Author URI: http://mountebank.org/
*/

/*  This is free and available to anyone who wants to use it.  Don't expect any help or warranty or support, and I take no responsibility.  It's just a first try!
*/

/**
 * Recent_Comments widget class
 *
 * @since 2.8.0
 */
class WP_Widget_Recent_Commenters extends WP_Widget {

/* So this is where the widget itself begins. first the general stuff--like pulling the styles and so forth  */

	function WP_Widget_Recent_Commenters() {
		$widget_ops = array('classname' => 'widget_recent_commenters', 'description' => __( 'The most recent commenters--just a list of their names' ) );
		$this->WP_Widget('recent-commenters', __('Recent Commenters'), $widget_ops);
		$this->alt_option_name = 'widget_recent_commenters';

		if ( is_active_widget(false, false, $this->id_base) )
			add_action( 'wp_head', array(&$this, 'recent_commenters_style') );

		add_action( 'comment_post', array(&$this, 'flush_widget_cache') );
		add_action( 'wp_set_comment_status', array(&$this, 'flush_widget_cache') );
	}

	function recent_commenters_style() { ?>
	<style type="text/css">.recentcommenters a{display:inline !important;padding:0 !important;margin:0 !important;}</style>
<?php
	}

	function flush_widget_cache() {
		wp_cache_delete('recent_commenters', 'widget');
	}
	
/*Here's where we get the options that were set in the form in the dashboard. Right now I'm pulling the options as checkboxes.  They should really be radio buttons in the future */

	function widget( $args, $instance ) {
		global $wpdb, $comments, $comment;

		extract($args, EXTR_SKIP);
		$title = apply_filters('widget_title', empty($instance['title']) ? __('') : $instance['title']);
		$nameonly = $instance['commenternameonly'] ? '1' : '0';
		$namelink = $instance['commenternamelink'] ? '1' : '0';
		$namelinkcomment = $instance['commenternamelinkcomment'] ? '1' : '0';
		if ( !$number = (int) $instance['number'] )
			$number = 5;
		else if ( $number < 1 )
			$number = 1;
		else if ( $number > 15 )
			$number = 15;
		if ( !$comments = wp_cache_get( 'recent_commenters', 'widget' ) ) {
			$comments = $wpdb->get_results("SELECT * FROM $wpdb->comments WHERE comment_approved = '1' ORDER BY comment_date_gmt DESC LIMIT 15");
			wp_cache_add( 'recent_comments', $comments, 'widget' );
		}

		$comments = array_slice( (array) $comments, 0, $number );
?>
		<?php echo $before_widget; ?>
			<?php if ( $title ) echo $before_title . $title . $after_title; ?>
			<ul id="recentcomments"><?php
			if ( $comments ) : foreach ( (array) $comments as $comment) : 
	if ( $nameonly && !$namelink && !$namelinkcomment ) :
			echo  '<li class="recentcommenters">' . 
		 get_comment_author() . '</li>';
	elseif ( $namelink && !$nameonly && !$namelinkcomment ) :
		 echo '<li class="recentcommenters">' . get_comment_author_link() . '</li>';
	elseif ( $namelinkcomment && !$nameonly && !$namelink ) :
		echo '<li class="recentcommenters">' . '<a href="' . esc_url( get_comment_link($comment->comment_ID) ) . '">' . get_comment_author() . '</a>' . '</li>';
		 endif;	endforeach; endif;?></ul>
		<?php echo $after_widget; ?> 
		
<?php
	}
/* this updates the options for each instance, like if you want multiple widgets, say, in different sidebars */

	function update( $new_instance, $old_instance ) {
		$instance = $old_instance;
		$instance['title'] = strip_tags($new_instance['title']);
		$instance['number'] = (int) $new_instance['number'];
		$instance['commenternameonly'] = $new_instance['commenternameonly'] ? 1 : 0;
		$instance['commenternamelink'] = $new_instance['commenternamelink'] ? 1 : 0;
		$instance['commenternamelinkcomment'] = $new_instance['commenternamelinkcomment'] ? 1 : 0;
		$this->flush_widget_cache();

		$alloptions = wp_cache_get( 'alloptions', 'options' );
		if ( isset($alloptions['widget_recent_commenters']) )
			delete_option('widget_recent_commenters');

		return $instance;
	}
	
/* And here's the form--including the checkboxes that should ultimately be radio buttons */

	function form( $instance ) {
		//Defaults
		$title = isset($instance['title']) ? esc_attr($instance['title']) : 'Recent Commenters';
		$number = isset($instance['number']) ? absint($instance['number']) : 5;
		$commenternameonly = (bool) $instance['commenternameonly'];
		$commenternamelink = (bool) $instance['commenternamelink'];
		$commenternamelinkcomment = (bool) $instance['commenternamelinkcomment'];
?>
		<p><label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Title to appear above the list of commenter\'s names:'); ?></label>
		<input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo $title; ?>" /></p>

		<p><label for="<?php echo $this->get_field_id('number'); ?>"><?php _e('Number of commenters\' names to show:'); ?></label><br>
		<input id="<?php echo $this->get_field_id('number'); ?>" name="<?php echo $this->get_field_name('number'); ?>" type="text" value="<?php echo $number; ?>" size="3" />
		<small><?php _e('(at least 3, at most 15)'); ?></small></p>
		<p>How do you want the names to show and be linked? (You must choose ONE and only one of these, or nothing will appear)</p>
		<ul>
		<li>
		<input type="checkbox" class="checkbox" id="<?php echo $this->get_field_id('commenternameonly'); ?>" name="<?php echo $this->get_field_name('commenternameonly'); ?>"<?php checked( $commenternameonly ); ?> />
		<label for="<?php echo $this->get_field_id('commenternameonly'); ?>"><?php _e( 'Show just the name of the commenter, without any link' ); ?></label></li>
		<li>
		<input type="checkbox" class="checkbox" id="<?php echo $this->get_field_id('commenternamelink'); ?>" name="<?php echo $this->get_field_name('commenternamelink'); ?>"<?php checked( $commenternamelink ); ?> />
		<label for="<?php echo $this->get_field_id('commenternamelink'); ?>"><?php _e( 'Show the commenter\'s name, linked to her website, if she entered one' ); ?></label></li>
		<li>
		<input type="checkbox" class="checkbox" id="<?php echo $this->get_field_id('commenternamelinkcomment'); ?>" name="<?php echo $this->get_field_name('commenternamelinkcomment'); ?>"<?php checked( $commenternamelinkcomment ); ?> />
		<label for="<?php echo $this->get_field_id('commenternamelinkcomment'); ?>"><?php _e( 'Show the commenter\'s name, linked to the comment she made' ); ?></label></li>
		</ul>
<?php
	}
}

function registerRecentCommenters() {
	register_widget('WP_Widget_Recent_Commenters');
}
add_action('widgets_init', 'registerRecentCommenters');
