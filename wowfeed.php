<?php
/**
 * Plugin Name: WowFeed Widget
 * Plugin URI: http://www.snomead.ch/blog/wowfeed
 * Description: A widget that parse a World Of Warcraft character's feed and link achievements and items to wowhead.com
 * Version: 0.1.5
 * Author: SnomeaD
 * Author URI: http://www.snomead.ch
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 */

/**
 * Add function to widgets_init that'll load our widget.
 */
add_action( 'widgets_init', 'example_load_widgets' );

/**
 * Register our widget.
 * 'WowFeed' is the widget class used below.
 */
function example_load_widgets() {
	register_widget( 'WowFeed' );
}

// Include the wordpress feed's function
require_once (ABSPATH . WPINC . '/feed.php');

/**
 * FooWidget Class
 */
class WowFeed extends WP_Widget {
    /** constructor */
    function WowFeed() {
        parent::WP_Widget(false, $name = 'WowFeed');	
    }

    /** @see WP_Widget::widget */
    function widget( $args, $instance ) {
		extract( $args );
		/* Before widget (defined by themes). */
		echo $before_widget;
		// Activate the wowhead's javascript to display tooltip on items/achievements
        echo '<script src="http://static.wowhead.com/widgets/power.js"></script>';

	    $title = apply_filters('widget_title', $instance['title']);

		/* Title of widget (before and after defined by themes). */
		if ( $title )
			echo $before_title . $title . $after_title;
		$this->wowfeed_get_feed($instance);
    
		/* After widget (defined by themes). */
		echo $after_widget;
    }

    /** @see WP_Widget::update */
    function update($new_instance, $old_instance) {				
        return $new_instance;
    }

    /** @see WP_Widget::form */
    function form($instance) {				
		  $title = esc_attr($instance['title']);
	        $realm = esc_attr($instance['realm']);
	        $name = esc_attr($instance['name']) ;
	        $limit = esc_attr($instance['limit']) ;
	        $continent = esc_attr($instance['continent']) ;
	        $language = esc_attr($instance['language']) ;

        ?>
            <p><label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Title:'); ?> <input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo $title; ?>" /></label></p>
            <p><label for="<?php echo $this->get_field_id('realm'); ?>"><?php _e('Realm:'); ?> <input class="widefat" id="<?php echo $this->get_field_id('realm'); ?>" name="<?php echo $this->get_field_name('realm'); ?>" type="text" value="<?php echo $realm; ?>" /></label></p>
            <p><label for="<?php echo $this->get_field_id('name'); ?>"><?php _e("Toon's name:"); ?> <input class="widefat" id="<?php echo $this->get_field_id('name'); ?>" name="<?php echo $this->get_field_name('name'); ?>" type="text" value="<?php echo $name; ?>" /></label></p>
            <p><label for="<?php echo $this->get_field_id('limit'); ?>"><?php _e('Limite:'); ?> <input class="widefat" id="<?php echo $this->get_field_id('limit'); ?>" name="<?php echo $this->get_field_name('limit'); ?>" type="text" value="<?php echo $limit; ?>" /></label></p>
            <p><label for="<?php echo $this->get_field_id('continent'); ?>"><?php _e('us or eu'); ?> <input class="widefat" id="<?php echo $this->get_field_id('continent'); ?>" name="<?php echo $this->get_field_name('continent'); ?>" type="text" value="<?php echo $continent; ?>" /></label></p>
            <p>
              <label for="<?php echo $this->get_field_id('language'); ?>"><?php _e("Wowhead language:"); ?></label>
              <select id="<?php echo $this->get_field_id('language'); ?>" name="<?php echo $this->get_field_name( 'language' ); ?>" class="widefat">
                <option <?php if ( 'www' == $language ) echo 'selected="selected"'; ?> value="www">English</option>
                <option <?php if ( 'fr' == $language ) echo 'selected="selected"'; ?> value="fr" >Français</option>
                <option <?php if ( 'es' == $language ) echo 'selected="selected"'; ?> value="es" >Español</option>
                <option <?php if ( 'de' == $language ) echo 'selected="selected"'; ?> value="de" >Deutsch</option>
                <option <?php if ( 'ru' == $language ) echo 'selected="selected"'; ?> value="ru" >Русском</option>
              </select>
</p>
        <?php 
    }

	function wowfeed_get_feed($instance){
		$realm = (!empty($instance['realm'])) ? $instance['realm'] : 'Sargeras';
        $name = (!empty($instance['name'])) ? esc_attr($instance['name']) : 'SnomeaD';
        $limit = (!empty($instance['limit'])) ? esc_attr($instance['limit']) : '10';
        $continent = (!empty($instance['continent'])) ? esc_attr($instance['continent']) : 'eu';
        $language = (!empty($instance['language'])) ? esc_attr($instance['language']) : 'www';

        $locale = ($language=='www') ? 'en' : $language;
		$url = 'http://'.$continent.'.wowarmory.com/character-feed.atom?locale='.$locale.'&r='.$realm.'&cn='.$name.'&filters=ACHIEVEMENT,CRITERIA,LOOT,RESPEC';
		$rss = fetch_feed( $url );
		if (!is_wp_error($rss)){
			$maxitems = $rss->get_item_quantity($limit);
			// Build an array of all the items, starting with element 0 (first element).
			$rss_items = $rss->get_items(0, $maxitems);

	 		if ($maxitems == 0) echo '<ul><li>No items.</li></ul>';
			else{
				echo "<ul>";
			    // Loop through each feed item and display each item with a hyperlink to wowhead.
			    foreach ( $rss_items as $item ){
					$this->parse_title($item->get_title(), $item->get_content(),$language);
				}
				echo "</ul>";
			}
		}else{
			echo '<ul><li>Unable to fetch the feed</li></ul>';
		}
	}
	
	function parse_title($title, $summary, $language){
			// Search the item's name
			$pattern = '~\[(.)*\]~';
			preg_match_all($pattern, $title, $matches);
			$matches = array_unique($matches[0]);
			$name = $matches[0];

			// Search the item's id
			$pattern = '~item/([0-9]*)~';
			preg_match_all($pattern, $summary, $matches);
			$matches = array_unique($matches[1]);
			// If there's a match, it's an item otherwise it's an achievement
			if($matches){
				$id = $matches[0];
				// Create the new title with the link to the item
				$link = '<a href="http://'.$language.'.wowhead.com/?item='.$id.'">'.$name.'</a>';	
			}else{
				// Search the achievement's id
				$pattern = '~:a([0-9]*)~';
				preg_match_all($pattern, $summary, $matches);
				$matches = array_unique($matches[1]);
				$id = $matches[0];
				// Create the new title with the link to the achievement
				$link = '<a href="http://'.$language.'.wowhead.com/?achievement='.$id.'">'.$name.'</a>';	
			}
			echo '<li>'.str_replace($name,$link,$title).'</li>';
	}
} // class WowFeed
?>