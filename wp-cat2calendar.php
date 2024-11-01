<?php
/*
 * Plugin Name: WP-Cat2Calendar
 * Author URI: http://codeispoetry.ru/
 * Plugin URI: http://www.codeispoetry.ru/wp-cat2calendar
 * Description: Simple plugin which allows make a calendar from posts in selected category.
 * Author: Andrew Mihaylov
 * Version: 1.0.8
 * $Id: wp-cat2calendar.php 326793 2010-12-28 21:11:36Z andddd $
 * Tags: calendar, post, category, author, future post, organizer, agenda
 */

/*
    This program is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/

if(!function_exists('add_action')) die('Cheatin&#8217; uh?');

global $wpCat2Calendar;

require(dirname(__FILE__) . '/widget.php');

define('WP_CAT2CALENDAR_TEXTDOMAIN', 'wp-cat2calendar');
define('WP_CAT2CALENDAR_URL_DATE', 'calendar');

// limit num posts to display in a month
// -1 = unlimited
if(!defined('WP_CAT2CALENDAR_POST_LIMIT'))
    define('WP_CAT2CALENDAR_POST_LIMIT', 100);

$wpCat2Calendar = new wpCat2Calendar();

class wpCat2Calendar
{

	protected $shortcode_calls = 0;

	function __construct() 
	{
		add_action('init', array($this, 'on_init'));
		add_action('widgets_init', array($this, 'on_widgets_init'));
		add_action('template_redirect', array($this, 'on_template'));
		add_action('admin_menu', array($this, 'on_menu'));
		add_action('permalink_structure_changed', array($this, 'on_permalinks_changed'));

		add_action('admin_post_wp-cat2calendar-settings' . $page, array($this, 'on_settings'));

		//add_filter('the_posts', array($this, 'future_posts'));
		//add_action('pre_get_posts', array($this, 'pre_get_posts'));

		add_filter('query_vars', array($this, 'query_vars'));

		//add_filter('generate_rewrite_rules',array($this, 'url_rewrite'));

		$use_permalinks = (bool)get_option('wp_cat2cal_use_permalinks');
		if($use_permalinks)
		{
			add_filter('post_rewrite_rules',array($this, 'post_rewrite_rules'));
			add_filter('page_rewrite_rules', array($this, 'page_rewrite_rules'));
			add_filter('date_rewrite_rules', array($this, 'date_rewrite_rules'));
			add_filter('root_rewrite_rules', array($this, 'root_rewrite_rules'));
			add_filter('category_rewrite_rules', array($this, 'category_rewrite_rules'));
			add_filter('tag_rewrite_rules', array($this, 'tag_rewrite_rules'));
			add_filter('author_rewrite_rules', array($this, 'author_rewrite_rules'));
			add_filter('search_rewrite_rules', array($this, 'search_rewrite_rules'));
		}

		add_shortcode('WP-Cat2Calendar', array($this, 'on_shortcode'));

		register_activation_hook( __FILE__, array($this, 'on_activate'));
		register_deactivation_hook( __FILE__, array($this, 'on_deactivate'));

		if(function_exists('register_uninstall_hook'))
			register_uninstall_hook(__FILE__, 'uninstall_cat2calendar');

		//for($i = 0; $i < 100; $i++)
		//	wp_insert_comment(array('user_id' => 1, 'comment_post_ID' => 39, 'comment_content' => 'Hello, this is auto-comment #' . $i));
	}

	function on_widgets_init()
    {
        return register_widget('wpCat2Calendar_Widget');
    }

	function create_rewrite_rules($struct, $walk_dirs = true)
	{
		global $wp_rewrite;

		$rules = array();

		$calendar_regex = WP_CAT2CALENDAR_URL_DATE . '-([0-9]{4}-[0-9]{2})/?$';
		$index = $wp_rewrite->index;
		//$match = str_replace($wp_rewrite->rewritecode, $wp_rewrite->rewritereplace, $struct);

		$query = '';
		
		// get everything up to the first rewrite tag
		$front = trim(substr($struct, 0, strpos($struct, '%')), '/') . '/';

		if($front == '/')
			$front = '';

		$num_toks = preg_match_all('/%.+?%/', $struct, $toks);
		$concat_struct = array();

		if($walk_dirs) // walk dirs
		{
			for($i = 0; $i < $num_toks; $i++)
			{
				$token = $toks[0][$i];
				$concat_struct[] = $token;

				$match = str_replace($wp_rewrite->rewritecode, $wp_rewrite->rewritereplace,
										implode('/', $concat_struct));

				if($i > 0)
					$query .= '&';

				$replaced = str_replace($wp_rewrite->rewritecode, $wp_rewrite->queryreplace, $token);
				$query .= $replaced . $wp_rewrite->preg_index($i+1);

				$calendar_match = preg_quote($front, '#') . trailingslashit(trim($match, '/')) . $calendar_regex;
				$calendar_query = $index . '?' . $query . '&' . WP_CAT2CALENDAR_URL_DATE . '=' . $wp_rewrite->preg_index($i+2);

				$rules[$calendar_match] = $calendar_query;
			}
		}
		else // do not walk dirs
		{
			$match = str_replace($wp_rewrite->rewritecode, $wp_rewrite->rewritereplace, $struct);
			for ($i = 0; $i < $num_toks; $i++)
			{
				$token = $toks[0][$i];

				if($i > 0)
					$query .= '&';

				$replaced = str_replace($wp_rewrite->rewritecode, $wp_rewrite->queryreplace, $token);

				$query .= $replaced . $wp_rewrite->preg_index($i+1);
			}

			$calendar_match = preg_quote($front, '#') . trailingslashit(trim($match, '/')) . $calendar_regex;
			$calendar_query = $index . '?' . $query . '&' . WP_CAT2CALENDAR_URL_DATE . '=' . $wp_rewrite->preg_index($num_toks + 1);

			$rules[$calendar_match] = $calendar_query;
		}

		if($num_toks == 0) 
		{
			$calendar_match = preg_quote($front, '#') . $calendar_regex;
			$calendar_query = $index . '?' . '&' . WP_CAT2CALENDAR_URL_DATE . '=' . $wp_rewrite->preg_index(1);

			$rules[$calendar_match] = $calendar_query;
		}

		return $rules;
	}

	function _rewrite_rules($type, $rules)
	{
		global $wp_rewrite;

		$struct = '';
		$walk_dirs = true;

		switch($type) {

			case 'post':
				$struct = $wp_rewrite->permalink_structure;
				break;
			case 'root':
				$struct = $wp_rewrite->root . '/';
				$walk_dirs = false;
				break;
			case 'search':
			case 'page':
			case 'date':
			case 'tag':
			case 'author':
			case 'category':
				$struct = call_user_func(array($wp_rewrite, 'get_' . $type . '_permastruct'));
				break;
		}

		$new_rules = $this->create_rewrite_rules($struct, $walk_dirs);
		$rules = $new_rules + $rules;

		return $rules;
	}

	function post_rewrite_rules($rules)
	{
		return $this->_rewrite_rules('post', $rules);
	}

	function page_rewrite_rules($rules)
	{
		return $this->_rewrite_rules('page', $rules);
	}

	function date_rewrite_rules($rules)
	{
		return $this->_rewrite_rules('date', $rules);
	}

	function root_rewrite_rules($rules) {
		return $this->_rewrite_rules('root', $rules);
	}

	function category_rewrite_rules($rules) {
		return $this->_rewrite_rules('category', $rules);
	}

	function tag_rewrite_rules($rules) {
		return $this->_rewrite_rules('tag', $rules);
	}

	function author_rewrite_rules($rules) {
		return $this->_rewrite_rules('author', $rules);
	}

	function search_rewrite_rules($rules) {
		return $this->_rewrite_rules('search', $rules);
	}

	function query_vars($vars)
	{
		array_push($vars, WP_CAT2CALENDAR_URL_DATE);
		return $vars;
	}

	function on_activate()
	{
		global $wp_rewrite;

		add_option('wp_cat2cal_use_default_css', true);
		add_option('wp_cat2cal_use_permalinks', $wp_rewrite->using_permalinks());

		$wp_rewrite->flush_rules();
	}

	function on_deactivate()
	{
		global $wp_rewrite;

		$wp_rewrite->flush_rules();
	}

	function on_uninstall()
	{
		delete_option('wp_cat2cal_use_default_css');
		delete_option('wp_cat2cal_use_permalinks');
	}

	function load_svn_props($var_base)
	{
		$revId = '';

		if(preg_match('/\d+/', '$Rev: 326793 $', $m))
			$revId = array_pop($m);

		define($var_base . '_REV', $revId);
	}

	function on_init()
	{
		$this->load_svn_props('WP_CAT2CALENDAR');

		load_plugin_textdomain(WP_CAT2CALENDAR_TEXTDOMAIN, PLUGINDIR . '/' . dirname(plugin_basename(__FILE__)) . '/langs/', //2.5 Compatibility
											   dirname(plugin_basename(__FILE__)) . '/langs/'); //2.6+, Works with custom wp-content dirs.
	
		//$this->allow_comment_future_posts();
	}

	function on_template()
	{
		$use_default_css = (bool)get_option('wp_cat2cal_use_default_css');
		
		if($use_default_css != false)
			wp_enqueue_style('wp-cat2calendar-default', WP_PLUGIN_URL . '/' . dirname(plugin_basename(__FILE__)) . '/default.css', array(), WP_CAT2CALENDAR_REV, 'all');

		wp_enqueue_script('wp-cat2calendar', WP_PLUGIN_URL . '/' . dirname(plugin_basename(__FILE__)) . '/wpc2c.js', array('jquery'), WP_CAT2CALENDAR_REV);
	}

	function on_menu()
	{
		$page = add_submenu_page( 'options-general.php', __('WP-Cat2Calendar', WP_CAT2CALENDAR_TEXTDOMAIN), __('WP-Cat2Calendar', WP_CAT2CALENDAR_TEXTDOMAIN), 7,  'wp-cat2calendar', array($this, 'on_manage'));

		add_action('load-' . $page, array($this, 'on_load_manage'));
		
		if(function_exists('add_contextual_help'))
			add_contextual_help($page, __('<p><h5>Usage</h5></p>
	<p>Use <tt>WP-Cat2Calendar</tt> shortcode in your post/page to add a calendar.</p>

	<p>You can add a calendar using the php lines:</p>

	<pre>$options = array(...);
	echo wp_cat2calendar($options);</pre>


	<p><strong>Options:</strong></p>

	<ul>
		<li>cat_id – a comma separated list of category ID&#8217;s. (all categories by default)</li>
		<li>author_id – a comma separated list of author ID&#8217;s. (all authors by default)<br />
		You also can use a special keyword <tt>post_author</tt> which will be replaced with a post author ID where shortcode is placed.<br />
		<em>WordPress bug (still in 2.8.5) at <a href="http://core.trac.wordpress.org/browser/tags/2.8.5/wp-includes/query.php#L1979" target="_blank">wp-includes/query.php</a> line 1979 in exclusion so you can exclude only one author, but you can include multiple.</em></li>
		<li>year – year you want to display in calendar (current year by default)</li>
		<li>month – month you want to display in calendar (current month by default)</li>
		<li>show_nav – show/hide month/year navigation, 0 or 1 (0 by default)</li>
		<li>show_date – show/hide selected month/year title, 0 or 1 (0 by default). <span style="text-decoration: underline;">Have no affect if navigation is shown</span>.</li>
		<li>allow_change_date – allow user to navigate through a calendar even if navigation is hidden and user has direct link. <span style="text-decoration: underline;">Has no affect if navigation is shown</span>.</li>
	</ul>


	<p><strong>Examples:</strong></p>

	<p><tt>[WP-Cat2Calendar cat_id="3,4" show_nav="1" year="2009" month="10"]</tt></p>

	<p>Show a calendar of posts for WordPress categories with ID 3 and 4 with navigation and the start date for a calendar will be October, 2009.</p>

	<p><tt>[WP-Cat2Calendar cat_id="1" show_nav="1"]</tt></p>

	<p>Show a calendar of posts for WordPress category ID 1 with navigation and the start date for a calendar will be current date.</p>

	<p><tt>[WP-Cat2Calendar author_id="1, 2, 3" cat_id="-4,-5"]</tt></p>

	<p>Show a calendar of posts posted by users with ID 1, 2, 3 for all WordPress categories excluding categories with ID 4 and 5.</p>

	<p><tt>[WP-Cat2Calendar author_id="-post_author"]</tt></p>

	<p>Show a calendar of posts posted by any user except a posts which belongs to the author of post where shortcode is placed.</p>

	<p><tt>[WP-Cat2Calendar author_id="post_author"]</tt></p>

	<p>Show a calendar of posts posted by the author of post where shortcode is placed.</p>', WP_CAT2CALENDAR_TEXTDOMAIN));
	}

	function on_settings()
	{
		global $wp_rewrite;

		if ( !current_user_can('manage_options') )
			wp_die( __('Cheatin&#8217; uh?') );

		check_admin_referer('wp-cat2calendar-settings');

		$use_default_css = isset($_POST['use_default_css']);
		$use_permalinks = isset($_POST['use_permalinks']);

		update_option('wp_cat2cal_use_default_css', $use_default_css);
		update_option('wp_cat2cal_use_permalinks', $use_permalinks);

		setcookie('_wp_cat2_calendar_saved', true);

		$wp_rewrite->flush_rules();

		wp_redirect($_POST['_wp_http_referer']);
	}

	function on_load_manage()
	{
		if(isset($_COOKIE['_wp_cat2_calendar_saved']))
			setcookie('_wp_cat2_calendar_saved', 0, time()-2592000);
	}

	function on_manage()
	{
		global $wp_rewrite;

		// build category tree
		$cats = get_categories('hide_empty=0');
		$cat_ids = array();

		$_rows = array();

		foreach ($cats as $node)
		{
			$_rows[$node->term_id] = $node;

			foreach( $_rows as $node ) {
				if( $node->parent != 0 )
				{
					if(!isset($_rows[$node->parent]->children))
						$_rows[$node->parent]->children = array();

					$_rows[$node->parent]->children[$node->term_id] = &$_rows[$node->term_id];
				}
			}
		}

	?>

	<?php if(isset($_COOKIE['_wp_cat2_calendar_saved'])) : ?>
		<div class="updated fade"><p><strong><?php _e('Options updated.', WP_CAT2CALENDAR_TEXTDOMAIN); ?></strong></p></div>
	<?php endif; ?>

		<style type="text/css">
			ul.cat-checklist {
				background-color: #FFFFFF;
				border-color: #DDDDDD;
				margin: 1em 0;
			}
		</style>

		<div class="wrap wp_cat2calendar_wrap">
			<h2><? _e('WP-Cat2Calendar Settings', WP_CAT2CALENDAR_TEXTDOMAIN); ?></h2>

			<form method="post" action="admin-post.php">
				<?php wp_nonce_field('wp-cat2calendar-settings'); ?>
				<input type="hidden" name="action" value="wp-cat2calendar-settings" />

				<table class="form-table">
					<tr valign="top">
						<th scope="row"><?php _e('Styles', WP_CAT2CALENDAR_TEXTDOMAIN); ?></th>
						<td>
							<fieldset>
								<legend class="screen-reader-text"><span><?php _e('Styles', WP_CAT2CALENDAR_TEXTDOMAIN); ?></span></legend>
								<label for="use_default_css">
								<input name="use_default_css" type="checkbox" id="use_default_css" value="1"<?php if((bool)get_option('wp_cat2cal_use_default_css') != false) : ?> checked='checked'<?php endif; ?> />
								<?php _e('Use default (bundled) CSS', WP_CAT2CALENDAR_TEXTDOMAIN); ?></label>
							</fieldset>
						</td>
					</tr>

					<tr valign="top">
						<th scope="row"><?php _e('Pretty URLs support', WP_CAT2CALENDAR_TEXTDOMAIN); ?></th>
						<td>
							<fieldset>
								<legend class="screen-reader-text"><span><?php _e('Pretty URLs support', WP_CAT2CALENDAR_TEXTDOMAIN); ?></span></legend>
								<label for="use_permalinks">
								<input name="use_permalinks" type="checkbox" id="use_permalinks" value="1"<?php if((bool)get_option('wp_cat2cal_use_permalinks') != false && $wp_rewrite->using_permalinks()) : ?> checked='checked'<?php endif; ?><?php if(!$wp_rewrite->using_permalinks()) : ?> disabled<?php endif; ?> />
								</label>
							</fieldset>
						</td>
					</tr>

					<!--tr valign="top">
						<th scope="row"><?php _e('Show Future Posts', WP_CAT2CALENDAR_TEXTDOMAIN); ?></th>
						<td>
							<fieldset>
								<legend class="screen-reader-text"><span><?php _e('Show Future Posts', WP_CAT2CALENDAR_TEXTDOMAIN); ?></span></legend>
								<label for="show_future_posts">
								<input name="show_future_posts" type="checkbox" id="show_future_posts" value="1"<?php checked(true, (bool)get_option('wp_cat2cal_show_future_posts')); ?> />
								</label>

								<div class="categorydiv">
								<ul class="categorychecklist cat-checklist category-checklist">
								<?php
									//$this->_categories_checkbox_renderer($_rows, 'cat_id[]', 0, true, $cat_ids);
									wp_category_checklist();
								?>
								</ul>
								</div>
							</fieldset>
						</td>
					</tr-->


				</table>


				<p class="submit">
					<input name="Submit" class="button-primary" value="<?php _e('Save Changes'); ?>" type="submit" />
				</p>
			</form>


		</div>
	<?php
	}

	protected function _categories_checkbox_renderer(&$cats, $field_name, $level = 0, $is_root = false, $checked_array = array())
    {
        if(!is_array($checked_array))
            $checked_array = array((int)$checked_array);

        if(!empty($field_name))
            $field_name_attr = ' name="' . $field_name . '"';

        foreach($cats as $cat) :
            if($is_root && $cat->parent != 0)
                continue;
        ?>

        <label style="margin-left: <?php echo $level*15; ?>px;"><input type="checkbox" value="<?php echo $cat->term_id; ?>" class="level-<?php echo $level; ?>"<?php checked(in_array($cat->term_id, $checked_array), true); echo $field_name_attr; ?> /> <?php echo esc_html($cat->name); ?></label>

        <?php
        if(!empty($cat->children))
               $this->_categories_checkbox_renderer($cat->children, $field_name, $level+1, false, $checked_array);
        ?>

        <?php endforeach; ?>

    <?php
    }

	function on_permalinks_changed($value) {
		if(empty($value)) {
			update_option('wp_cat2cal_use_permalinks', false);
		}
	}

	function allow_comment_future_posts() 
	{
		// hack comment post script to allow future posts to be commented
		if(basename($_SERVER['SCRIPT_FILENAME']) == 'wp-comments-post.php')
		{
			global $wp_post_statuses;
			$wp_post_statuses['future']->public = true;
		}
	}

	/* Small hack which allows future posts to be shown but only if is_single */
	function future_posts($posts)
	{
		global $wp_query, $wpdb;

		if(is_singular() && $wp_query->post_count == 0)
		{
			$posts = $wpdb->get_results($wp_query->request);
		}

		return $posts;
	}

	function pre_get_posts(&$wp_query)
	{
		$qv =& $wp_query->query_vars;

		if(isset($qv['post_status']))
		{
			$split = preg_split('#[,]#', trim(strtolower($qv['post_status'])), PREG_SPLIT_NO_EMPTY);
			if(in_array('publish', $split))
			{
				$split[] = 'future';
				$qv['post_status'] = implode(',', array_unique($split));
			}
		} else {
			$qv['post_status'] = 'publish,future';
		}
		
	}

	function permalink($year, $month)
	{
		global $wp_rewrite;

		$use_permalinks = (bool)get_option('wp_cat2cal_use_permalinks');
		$date = $year . '-' . $month;

		if(in_the_loop()) {
			$permalink = get_permalink();
		} else {
			$permalink = $_SERVER['REQUEST_URI'];
		}

		if($wp_rewrite->using_permalinks() && $use_permalinks)
		{
			$q = '';
			$query_pos = strrpos($permalink, '?');
			
			if($query_pos !== false) {
				$q = substr($permalink, $query_pos);
				$permalink = substr($permalink, 0, $query_pos);
			}

			$permalink = preg_replace('#(/' . preg_quote(WP_CAT2CALENDAR_URL_DATE, '#') . '-[0-9]{4}-[0-9]{2})#', '', $permalink);

			return trailingslashit($permalink) . trailingslashit(WP_CAT2CALENDAR_URL_DATE . '-' . $date) . $q;
		}

		return add_query_arg(WP_CAT2CALENDAR_URL_DATE, $date, $permalink);
	}

	function filter_excerpt($text, $length, $more)
	{
		$raw_excerpt = $text;

		$text = strip_shortcodes( $text );

		$text = apply_filters('the_content', $text);
		$text = str_replace(']]>', ']]&gt;', $text);
		$text = strip_tags($text);
		$excerpt_length = apply_filters('excerpt_length', $length);
		$excerpt_more = apply_filters('excerpt_more', ' ' . $more);
		$words = explode(' ', $text, $excerpt_length + 1);

		if (count($words) > $excerpt_length) {
			array_pop($words);
			$text = implode(' ', $words);
			$text = $text . $excerpt_more;
		}

		return apply_filters('wp_trim_excerpt', $text, $raw_excerpt);
	}

	function display($atts=array()) {
		return $this->on_shortcode($atts);
	}

	function on_shortcode($atts=array())
	{
		global $wp_locale, $post;

		$old_post = $post; // save current post var

		$query_date = get_query_var(WP_CAT2CALENDAR_URL_DATE);

		$current_date = current_time('timestamp');

		$year = date_i18n('Y', $current_date);
		$month = date_i18n('m', $current_date);
		$time = mktime(0, 0, 0, $month, 1, $year);
		$wp_start_of_week = get_option('start_of_week');
		$calendar_days = array();

		$defaults = array(
			'cat_id' => 0, /* all by default */
			'author_id' => 0, /* all authors by default. possible values: post_author or comma-separated list of user IDs */
			'year' => $year,
			'month' => $month,
			'show_nav' => false,
			'show_date' => false,
			'allow_change_date' => false,
			'cell_height' => 0,
			'date_format' => __('F, Y', WP_CAT2CALENDAR_TEXTDOMAIN)
		);

		$query = new WP_Query();
		$my_posts = array();

		// fill days order array depends on start of week wp option
		for($i = $wp_start_of_week; $i < 7; $i++)
			$calendar_days[] = $i;

		for($i = 0; $i < $wp_start_of_week; $i++)
			$calendar_days[] = $i;

		$settings = shortcode_atts($defaults, $atts);
		extract($settings);

		// validate cell height
		$cell_height = (int)$cell_height;
		if($cell_height < 1) {
			$cell_height = $defaults['cell_height'];
		}

		if($year == 'next' || $year == 'prev') {
			$year = date_i18n('Y') + ($year == 'next' ? 1 : -1);
		}

		if($month == 'next' || $month == 'prev') {
			$month = date_i18n('n') + ($month == 'next' ? 1 : -1);
			if($month > 12){
				$month -= 12;
				$year++;
			}
		}

		if(!empty($query_date) && ($show_nav || $allow_change_date))
		{
			$matches = array();
			if(preg_match('#(\d{4})-(\d{2})#', $query_date, $matches)) {
				$month = array_pop($matches);
				$year = array_pop($matches);
			}
		}

		/* filter cat_id, author_id */
		if(in_the_loop() &&
		   is_object($post) &&
		   isset($post->post_author) &&
		   preg_match('#(post_author)#i', $author_id))
		{
			$author_id = preg_replace('#(post_author)#i', $post->post_author, $author_id);
		} else {
			$author_id = preg_replace('#(post_author)#i', 0, $author_id);
		}

		$cat_id = preg_replace(array('#[^0-9,-]#', '#([,])+#', '#^[,]|[,]$#'), array('', '$1 ', ''), $cat_id);
		$author_id = preg_replace(array('#[^0-9,-]#', '#([,])+#', '#^[,]|[,]$#'), array('', '$1 ', ''), $author_id);

		if((int)$month < 1)
			$month = 1;
		else if((int)$month > 12)
			$month = 12;

		$time = mktime(0, 0, 0, $month, 1, $year);
		$month = date_i18n('m', $time);
		$year = date_i18n('Y', $time);

		$num_days = date_i18n('t', $time);
		$first_day = date_i18n('w', $time);
		$day_n = 1;

		$prev_month = mktime(0, 0, 0, $month-1, 1, $year);
		$next_month = mktime(0, 0, 0, $month+1, 1, $year);


		$tag_id = 'wp_cat2calendar' . (++$this->shortcode_calls);

		$classes = array('wp_cat2calendar');

		$out .= '<script type="text/javascript">jQuery(document).ready(function($){try { $("#' . $tag_id . '").wpCat2Calendar(); } catch(e){};});</script>';

		if($cell_height > 0) {
			$out .= '<style type="text/css">#' . $tag_id . ' tbody td { height: ' . $cell_height . 'px; }</style>';
		}

		$out .= '<div id="' . $tag_id . '" class="' . implode(' ', $classes) . '">';

		if($show_nav)
		{
			$out .= '<div class="nav">
				<div class="left"><a href="' . $this->permalink(date_i18n('Y', $prev_month), date_i18n('m', $prev_month)) . '#' . $tag_id . '">&larr; ' . date_i18n($date_format, $prev_month) . '</a></div>
				<div class="right"><a href="' . $this->permalink(date_i18n('Y', $next_month), date_i18n('m', $next_month)) . '#' . $tag_id . '">' . date_i18n($date_format, $next_month) . ' &rarr;</a></div>
				<div class="center current_date">' . date_i18n($date_format, $time) . '</div>
			</div>
			';
		}
		else if($show_date)
		{
			$out .= '<div class="nav">
				<div class="current_date">' . date_i18n($date_format, $time) . '</div>
			</div>';
		}

		$out .= '<table cellspacing="0">';
		$out .= '<thead>';
		$out .= '<tr>';

		for ($day_index = 0; $day_index <= 6; $day_index++)
		{
			$classes = array();
			$class = '';

			$classes[] = 'day-' . $day_index;

			if($day_index == 0) $classes[] = 'day-first';
			if($day_index == 6) $classes[] = 'day-last';

			if(!empty($classes))
				$class = ' class="' . implode(' ', $classes) . '"';

			$out .= '<th' . $class . '>' . $wp_locale->get_weekday_abbrev($wp_locale->get_weekday($calendar_days[$day_index])) . '</th>';
		}

		$out .= '</tr>';
		$out .= '</thead>';

		$out .= '<tbody>';

		for($i = 0; $i < sizeof($calendar_days); $i++)
			if($calendar_days[$i] == $first_day)
				$day_n -= $i;

		$table_rows = ceil(($num_days - $day_n + 1) / 7);

		// build query string and get posts
		$q_string = 'paged=0&showposts=' . (int)WP_CAT2CALENDAR_POST_LIMIT . '&post_status=future,publish&monthnum='. $month . '&year=' . $year;

		if(!empty($cat_id))
			$q_string .= '&cat=' . $cat_id;

		// wp bug (still in 2.8.5) at http://core.trac.wordpress.org/browser/tags/2.8.5/wp-includes/query.php#L1979 in exclusion (can only exclude one author).
		if(!empty($author_id))
			$q_string .= '&author=' . $author_id;

		$q_posts = $query->query($q_string);

		for($i = 0; $i < sizeof($q_posts); $i++) {
			$day = mysql2date('j', $q_posts[$i]->post_date);

			if(!array_key_exists($day, $my_posts))
				$my_posts[$day] = array();

			$my_posts[$day][] =& $q_posts[$i];
		}

		for($i = 0; $i < $table_rows; $i++) { // walk by weeks
			$out .= '<tr>';

			for($j = 0; $j < 7; $j++) { // walk by days of week

				$css_classes = array();
				$css_classes[] = 'day-' . $j;
				$css_classes[] = 'week-' . $i;

				if($i == 0) $css_classes[] = 'week-first';
				if($i == $table_rows-1) $css_classes[] = 'week-last';

				if($j == 0) $css_classes[] = 'day-first';
				if($j == 6) $css_classes[] = 'day-last';

				if($day_n < 1 || $day_n > $num_days)
				{
					$css_classes[] = 'empty';
					$class = ' class="' . implode(' ', $css_classes) . '"';
					$out .= '<td' . $class . '></td>';
				}
				else
				{
					$class = '';
					$has_posts = array_key_exists($day_n, $my_posts);
					$num_posts = $has_posts ? sizeof($my_posts[$day_n]) : 0;
					$is_more = $current_date > mktime(0, 0, 0, $month, $day_n, $year);
					$is_current = $day_n == date('j', $current_date) &&
								  $month == date('m', $current_date) &&
								  $year == date('Y', $current_date);

					if($has_posts)
					{
						$css_classes[] = 'has-posts';
						$css_classes[] = $is_current ? 'today' : ($is_more ? 'past' : 'future');
					} else {
						$css_classes[] = 'no-posts';

						if($is_current)
							$css_classes[] = 'today';
					}

					if(!empty($css_classes))
						$class = ' class="' . implode(' ', $css_classes) . '"';

					$out .= '<td' . $class . '>';

					if($has_posts){
						$daylink = get_day_link($year, $month, $day_n);
						$dt = sprintf('%1$d-%2$02d-%3$02d 00:00:00', $year, $month, $day_n);
						$text = mysql2date(get_option('date_format'), $dt);
						$out .= '<a href="' . $daylink . '" title="' . esc_attr($text) . '">' . $day_n . '</a>';
					}else
						$out .= $day_n;

					if($has_posts)
					{
						$out .= '<div class="posts"><ul>';
						foreach($my_posts[$day_n] as $my_post) {
							$post = $my_post;
							$title = get_the_title();
							$title_attr = '';
							$excerpt = $my_post->post_excerpt;

							if(empty($excerpt) && !post_password_required($my_post)) {
								$excerpt = $this->filter_excerpt($my_post->post_content, 20, '[...]');
							}

							if(!empty($title)) // to prevent crazy 'return;' from the_title_attribute
								$title_attr = the_title_attribute('echo=0');

							$el = '<li>';
							$el .= '<span class="title"><a href="' . get_permalink($my_post->ID) . '" title="' . $title_attr . '">' . $title . '</a></span>';
							$el .= '<span class="desc">' . $excerpt . '</span>';
							$el .= '</li>';

							$el = apply_filters('wp_c2c_post', $el);
							$out .= $el;
						}
						$out .= '</ul></div>';
					}


					$out .= '</td>';
				}
				$day_n++;
			}
			$out .= '</tr>';
		}

		$out .= '</tbody>';
		
		$out .= '<tfoot></tfoot>';

		$out .= '</table>';
		$out .= '</div>';

		$post = $old_post; // restore post var

		return $out;
	}
	
}

function uninstall_cat2calendar() {
	global $wpCat2Calendar;

	$wpCat2Calendar->on_uninstall();
}

//
// end of file wp-cat2calendar.php
//