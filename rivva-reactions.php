<?php
/*
Plugin Name: Rivva Reactions
Plugin URI:  http://bueltge.de/rivva-reaction-wordpress-plugin/1029/
Description: Displays Rivva reactions on your WordPress 2.7+ dashboard.
Version:     0.1
Author:      Frank B&uuml;ltge
Author URI:  http://bueltge.de/
Last Change: 26.10.2009 13:12:24
/*

/*
This program is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License
as published by the Free Software Foundation; either version 2
of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
The license is also available at http://www.gnu.org/copyleft/gpl.html
*/

//avoid direct calls to this file, because now WP core and framework has been used
if ( !function_exists('add_action') ) {
	header('Status: 403 Forbidden');
	header('HTTP/1.1 403 Forbidden');
	exit();
} else {

	define( 'FB_RR_BASENAME', plugin_basename(__FILE__) );
	define( 'FB_RR_BASEFOLDER', plugin_basename( dirname( __FILE__ ) ) );
	define( 'FB_RR_TEXTDOMAIN', 'rivvareactions' );
	
	if ( file_exists(ABSPATH . WPINC . '/rss.php') ) {
		@require_once (ABSPATH . WPINC . '/rss.php');
		// It's Wordpress 2.x. since it has been loaded successfully
	} elseif (file_exists(ABSPATH . WPINC . '/rss-functions.php')) {
		@require_once (ABSPATH . WPINC . '/rss-functions.php');
		// In Wordpress < 2.1
	} else {
		die (__('Error in file: ' . __FILE__ . ' on line: ' . __LINE__ . '.<br />The Wordpress file "rss-functions.php" or "rss.php" could not be included.'));
	}
}

if ( !class_exists('RivvaReactions') ) {
	class RivvaReactions {
		
		
		/**
		 * constructor
		 */
		function RivvaReactions() {
			add_action( 'init', array(&$this, 'textdomain') );
			add_action( 'init', array(&$this, 'on_init'), 1 );
		}
		
		
		/**
		 * include on WordPress init, only an WP Admin
		 */
		function on_init() {
			
			if ( !is_admin() )
				return;
			
			global $pagenow;
			
			if ( $pagenow == 'index.php' )
				add_action( 'admin_init', array(&$this, 'on_dashboard') );
		}
		
		
		/**
		 * load all methods for index.php/Dashboard
		 */
		function on_dashboard() {
			add_action( 'wp_dashboard_setup', array(&$this, 'RivvaReactionsInit') );
				
			wp_enqueue_script( 'jquery-ui-tabs' );
			wp_enqueue_script( 'rivva-reactions', WP_PLUGIN_URL . '/' . FB_RR_BASEFOLDER . '/js/rivva-reactions.js', array('jquery') );
			wp_enqueue_style( 'do-jquery-ui-tabs-css', WP_PLUGIN_URL . '/' . FB_RR_BASEFOLDER . '/css/ui-tabs.css' );
			wp_enqueue_style( 'rivva-reactions-css', WP_PLUGIN_URL . '/' . FB_RR_BASEFOLDER . '/css/style.css' );
		}
		
		
		/**
		 * load language file for multilanguage support
		 */
		function textdomain() {
			load_plugin_textdomain( FB_RR_TEXTDOMAIN, false, dirname( plugin_basename(__FILE__) ) . '/languages' );
		}
		
		
		/**
		 * Filter function to remove own reactions from RSS
		 * @return $array_element
		 */
		function filter_feed_own_tweet($array_element) {
			$widget_options = $this->RivvaReactionsOptions();
			
			$TwitterUrl = 'http://twitter.com/' . $widget_options['twittername'];
			
			if ( strstr( $array_element['link'], $TwitterUrl ) )
				return;
			else
				return $array_element;
		}
		
		
		/**
		 * Wrapper for fetch_rss: If OPTION set, removes own blog posts in search result
		 * @return $result_rss
		 */
		function filtered_fetch_rss($url) {
			global $filter_feed_own_tweet;
			
			$widget_options = $this->RivvaReactionsOptions();
			
			$result_rss = fetch_rss($url);
			
			if ($widget_options['ownreactions'] == 1) {
				if ( is_array($result_rss->items) ) {
					$result_rss->items = array_filter($result_rss->items, array(&$this, 'filter_feed_own_tweet') );
				} else {
					$result_rss->items = array($result_rss->items);
					$result_rss->items = array_filter($result_rss->items, array(&$this, 'filter_feed_own_tweet') );
				}
			}
			
			return $result_rss;
		}
		
		
		/**
		 * inside the Dashboard on WordPress 2.7+
		 */
		function wp_dashboard_rivva_reactions() {
			?>
			<img id="wp_dashboard_rivva_logo" src="<?php echo WP_PLUGIN_URL . '/' . FB_RR_BASEFOLDER . '/images/rivva-logo.png' ?>" alt="" />
			<div id="rivvareactionstabs" class="inside">
				<ul>
					<li><a href="#reactions"><?php _e( 'Reactions', FB_RR_TEXTDOMAIN ); ?></a></li>
					<li><a href="#news"><?php _e( 'Top Stories', FB_RR_TEXTDOMAIN ); ?></a></li>
					<li><a href="#wpnews"><?php _e( 'WP Stories', FB_RR_TEXTDOMAIN ); ?></a></li>
				</ul>
			</div>
			
			<div id="reactions">
			<?php
			// http://rivva.de/http://bueltge.de/atom.xml
			// http://feeds.feedburner.com/rivva
			// http://rivva.de/wordpress/rss.xml
			$rivva_reactions_feed = 'http://rivva.de/' . trailingslashit( get_option( 'home' ) ) . 'atom.xml';
			//$rivva_reactions_feed = 'http://rivva.de/http://bueltge.de/atom.xml';
			$rivva_news_feed = 'http://feeds.feedburner.com/rivva';
			$rivva_wpnews_feed = 'http://rivva.de/wordpress/rss.xml';
			
			$widget_options = $this->RivvaReactionsOptions();
			
			$rss = @$this->filtered_fetch_rss($rivva_reactions_feed);
			
			if ( !empty($rss->items) ) {
				//var_dump($rss->items);
				echo '<ul id="rivva-reactions-list">';
				$rss->items = array_slice($rss->items, 0, $widget_options['items']);
				
				foreach ($rss->items as $item ) {
					$irlink = '<h4><a class="rsswidget" href="' . wp_filter_kses($item['link']) . '">' . wptexturize(wp_specialchars($item['title'])) . '</a>';
					
					if ($widget_options['showtime']) {
						$time = strtotime($item['published']);
					
						if ( ( abs(time() - $time) ) < 86400 )
							$h_time = sprintf( __('%s ago'), human_time_diff( $time ) );
						else
							$h_time = date_i18n( get_option( 'date_format' ), $time );
						
						$irlink .= sprintf( ' %s', '<abbr title="' . date( 'Y/m/d H:i:s', $time ) . '">' . $h_time . '</abbr></h4>' );
					}
					
					if ($widget_options['showexcerpt'])
						$irlink .=  esc_html( strip_tags($item['atom_content']) ) . '<br />';
					
					echo '<li>' . $irlink . '</li>';
				}
			
			
				echo '</ul>';
			} else {
				echo '<p>' . __( 'This dashboard widget queries <a href="http://rivva.de">Rivva</a> so that when another blog or Twitter links to your site it will show up here. It has found no incoming links... yet. It\'s okay - there is no rush.', FB_RR_TEXTDOMAIN ) . "</p>";
			}
			
			echo '<p class="textright"><a class="button" href="http://rivva.de/' . trailingslashit( get_option( 'home' )).'"> ' . __('View all', FB_RR_TEXTDOMAIN) . '</a></p>';
			?>
			</div>
			
			<div id="news" class="ui-tabs-hide">
			<?php
			$rss = @fetch_rss($rivva_news_feed);
			
			if ( !empty($rss->items) ) {
				//var_dump($rss->items);
				echo '<ul id="rivva-reactions-list">';
				$rss->items = array_slice($rss->items, 0, $widget_options['items']);
				
				foreach ($rss->items as $item ) {
					$irlink = '<h4><a class="rsswidget" href="' . wp_filter_kses($item['link']) . '">' . wptexturize( wp_specialchars($item['title']) ) . '</a>';
					
					if ($widget_options['showtime']) {
						$time = strtotime($item['pubdate']);
					
						if ( ( abs(time() - $time) ) < 86400 )
							$h_time = sprintf( __('%s ago'), human_time_diff( $time ) );
						else
							$h_time = date_i18n( get_option( 'date_format' ), $time );
						
						$irlink .= sprintf( ' %s', '<abbr title="' . date( 'Y/m/d H:i:s', $time ) . '">' . $h_time . '</abbr></h4>' );
					}
					
					if ($widget_options['showexcerpt'])
						$irlink .= esc_html( strip_tags($item['description']) ) . '<br />';
					
					echo '<li>' . $irlink . '</li>';
				}
			
			
				echo '</ul>';
			} else {
				echo '<p>' . __( 'This dashboard widget queries <a href="http://rivva.de">Rivva</a> News.', FB_RR_TEXTDOMAIN ) . "</p>";
			}
			
			echo '<p class="textright"><a class="button" href="http://rivva.de/"> ' . __('View all', FB_RR_TEXTDOMAIN) . '</a>';
			echo ' <a class="button" href="http://rivva.de/newsriver"> ' . __('Newsriver', FB_RR_TEXTDOMAIN) . '</a></p>';
			?>
			</div>
			
			<div id="wpnews" class="ui-tabs-hide">
			<?php
			$rss = @fetch_rss($rivva_wpnews_feed);
			
			if ( !empty($rss->items) ) {
				//var_dump($rss->items);
				echo '<ul id="rivva-reactions-list">';
				$rss->items = array_slice($rss->items, 0, $widget_options['items']);
				
				foreach ($rss->items as $item ) {
					$irlink = '<h4><a class="rsswidget" href="' . wp_filter_kses($item['link']) . '">' . wptexturize(wp_specialchars($item['title'])) . '</a>';
					
					if ($widget_options['showtime']) {
						$time = strtotime($item['pubdate']);
					
						if ( ( abs(time() - $time) ) < 86400 )
							$h_time = sprintf( __('%s ago'), human_time_diff( $time ) );
						else
							$h_time = date_i18n( get_option( 'date_format' ), $time );
						
						$irlink .= sprintf( '	 %s', '<abbr title="' . date( 'Y/m/d H:i:s', $time ) . '">' . $h_time . '</abbr></h4>' );
					}
					
					if ($widget_options['showexcerpt'])
						$irlink .= esc_html( strip_tags($item['description']) ) . '<br />';
					
					echo '<li>' . $irlink . '</li>';
				}
			
			
				echo '</ul>';
			} else {
				echo '<p>' . __( 'This dashboard widget queries <a href="http://rivva.de">Rivva</a> for WordPress.', FB_RR_TEXTDOMAIN ) . "</p>";
			}
			
			echo '<p class="textright"><a class="button" href="http://rivva.de/wordpress"> ' . __('View all', FB_RR_TEXTDOMAIN) . '</a>';
			echo ' <a class="button" href="http://www.rivva.de/wordpress/newsriver"> ' . __('Newsriver', FB_RR_TEXTDOMAIN) . '</a></p>';
			?>
			</div>
			<?php
		}
		
		
		/**
		 * add dashboard widget via hook
		 */
		function RivvaReactionsInit() {
			wp_add_dashboard_widget( 'wp_dashboard_rivva_reactions', __( 'Rivva Reactions' ), array(&$this, 'wp_dashboard_rivva_reactions'), array(&$this, 'RivvaReactionsSetup') );
		}
		
		
		/**
		 * options for dashboard widget
		 * return array()
		 */
		function RivvaReactionsOptions() {
			$defaults = array( 'items' => 5, 'showtime' => 1, 'showexcerpt' => 1, 'ownreactions' => '', 'twittername' => '' );
			
			if ( ( !$options = get_option( 'RivvaReactions' ) ) || !is_array($options) )
				$options = array();
			
			return array_merge( $defaults, $options );
		}
		
		
		/**
		 * options setup
		 */
		function RivvaReactionsSetup() {
		
			$options = $this->RivvaReactionsOptions();
		
			if ( 'post' == strtolower($_SERVER['REQUEST_METHOD']) && isset( $_POST['widget_id'] ) && 'RivvaReactionsDashboard' == $_POST['widget_id'] ) {
				foreach ( array( 'items', 'showtime', 'showurl', 'showexcerpt', 'ownreactions', 'twittername' ) as $key )
					$options[$key] = esc_html( strip_tags( $_POST[$key] ) );
				update_option( 'RivvaReactions', $options );
			}
				
		?>
			<p>
				<label for="items"><?php _e('How many Rivva reactions would you like to display?', FB_RR_TEXTDOMAIN ); ?>
					<select id="items" name="items">
						<?php
						for ( $i = 5; $i <= 20; $i = $i + 1 )
							echo '<option value="' . $i . '"' . ( $options['items'] == $i ? ' selected="selected"' : '' ) . '">' . $i . ' </option>';
						?>
					</select>
				</label>
			</p>
			
			<p>
				<label for="showtime">
					<input id="showtime" name="showtime" type="checkbox" value="1"<?php if ( 1 == $options['showtime'] ) echo ' checked="checked"'; ?> />
					<?php _e('Show reaction date?', FB_RR_TEXTDOMAIN ); ?>
				</label>
			</p>
			
			<p>
				<label for="showexcerpt">
					<input id="showexcerpt" name="showexcerpt" type="checkbox" value="1"<?php if ( 1 == $options['showexcerpt'] ) echo ' checked="checked"'; ?> />
					<?php _e('Show excerpt?', FB_RR_TEXTDOMAIN ); ?>
				</label>
			</p>
			
			<p>
				<label for="ownreactions">
					<input id="ownreactions" name="ownreactions" type="checkbox" value="1"<?php if ( 1 == $options['ownreactions'] ) echo ' checked="checked"'; ?> />
					<?php _e('Filter your own tweets?', FB_RR_TEXTDOMAIN ); ?>
				</label>
			</p>
			
			<p>
				<label for="twittername">
					<input id="twittername" name="twittername" type="text" value="<?php echo $options['twittername']; ?>" />
					<?php _e('Your Twitter username', FB_RR_TEXTDOMAIN ); ?>
				</label>
			</p>
			
			<p class="textright">
				<a href="http://rivva.de/about"><?php _e( 'About Rivva', FB_RR_TEXTDOMAIN ); ?></a>
			</p>
		<?php
		}

	} // end class
	
	$RivvaReactions = new RivvaReactions();
}
?>