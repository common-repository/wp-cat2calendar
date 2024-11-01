<?php
/*
 * This is a part of WP-Cat2Calendar plugin
 * Description: WP-Cat2Calendar Widget
 * Author: Andrew Mihaylov
 * $Id$
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

class wpCat2Calendar_Widget extends WP_Widget
{

	protected $defaults;

	protected $simple_date_formats = array('F, Y', 'M Y');

	function __construct()
	{
		$widget_ops = array(
			'classname' => 'widget_cat2calendar',
			'description' => __('WP-Cat2Calendar widget', WP_CAT2CALENDAR_TEXTDOMAIN)
		);

		$this->defaults = array(
			'title' => __('Calendar', WP_CAT2CALENDAR_TEXTDOMAIN),
			'hide_title' => false,
			'show_nav' => false,
			'show_date' => false,
			'allow_change_date' => false,
			'date_format' => __('F, Y', WP_CAT2CALENDAR_TEXTDOMAIN),
			'cell_height' => '',
			'cat_id' => '',
			'author_id' => '',
			'month' => 0,
			'year' => 0
		);

		parent::__construct('cat2calendar', __('Calendar', WP_CAT2CALENDAR_TEXTDOMAIN), $widget_ops);
	}

	function widget($args, $instance)
	{	
		extract($args);

		$instance = wp_parse_args((array)$instance, $this->defaults);

		$title = apply_filters('widget_title', empty($instance['title']) ? __('Calendar', WP_CAT2CALENDAR_TEXTDOMAIN) : $instance['title']);

		echo $before_widget;
		
		if ($title && !$instance['hide_title'])
			echo $before_title . $title . $after_title;
			
		$shortcode = '[WP-Cat2Calendar';

		foreach($instance as $key => $val)
		{
			if($key != 'title' && $key != 'hide_title')
			{
				// skip month & year params, it's current by default
				if(in_array($key, array('title', 'hide_title', 'month', 'year', 'cell_height')) && empty($val))
					continue;

				$val = addslashes($val);
				$shortcode .= ' ' . $key . '="' . $val . '"';
			}
		}

		$shortcode .= ']';

		echo do_shortcode($shortcode);

		echo $after_widget;
	}

	function update($new_instance, $old_instance)
	{
		return $new_instance;
	}

	function form($instance)
	{
		global $wp_locale;
		
		$instance = wp_parse_args((array)$instance, $this->defaults);

		$show_limit = (int)$instance['show_limit'];
		$title = esc_attr($instance['title']);
	?>
		<p><label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Title:', WP_CAT2CALENDAR_TEXTDOMAIN); ?></label> <input class="widefat" name="<?php echo $this->get_field_name('title'); ?>" id="<?php echo $this->get_field_id('title'); ?>" value="<?php echo esc_attr($title); ?>" type="text" /></p>

		<p>
			<input class="checkbox" name="<?php echo $this->get_field_name('hide_title'); ?>" id="<?php echo $this->get_field_id('hide_title'); ?>" value="1" type="checkbox" <?php checked($instance['hide_title'], true)?>/> <label for="<?php echo $this->get_field_id('hide_title'); ?>"><?php _e('Hide title', WP_CAT2CALENDAR_TEXTDOMAIN); ?></label>
		</p>

		<p>
			<label for="<?php echo $this->get_field_id('cat_id'); ?>"><?php _e('Category filter:', WP_CAT2CALENDAR_TEXTDOMAIN); ?></label> <input class="widefat" name="<?php echo $this->get_field_name('cat_id'); ?>" id="<?php echo $this->get_field_id('cat_id'); ?>" value="<?php echo esc_attr($instance['cat_id']); ?>" type="text" /><br/>
			<small><?php _e('Category IDs, separated by commas.', WP_CAT2CALENDAR_TEXTDOMAIN); ?></small>
		</p>
		
		<p>
			<label for="<?php echo $this->get_field_id('author_id'); ?>"><?php _e('Author filter:', WP_CAT2CALENDAR_TEXTDOMAIN); ?></label> <input class="widefat" name="<?php echo $this->get_field_name('author_id'); ?>" id="<?php echo $this->get_field_id('author_id'); ?>" value="<?php echo esc_attr($instance['author_id']); ?>" type="text" /><br/>
			<small><?php _e('Author IDs, separated by commas.', WP_CAT2CALENDAR_TEXTDOMAIN); ?></small>
		</p>

		<p>
			<label for="<?php echo $this->get_field_id('month'); ?>"><?php _e('Date:', WP_CAT2CALENDAR_TEXTDOMAIN); ?></label>

			<select name="<?php echo $this->get_field_name('month'); ?>" id="<?php echo $this->get_field_id('month'); ?>">
				<option value="0"<?php selected($i, $instance['month']); ?>><?php _e('Current', WP_CAT2CALENDAR_TEXTDOMAIN); ?></option>
				<option value="prev"<?php selected('prev', $instance['month']); ?>><?php _e('Previous', WP_CAT2CALENDAR_TEXTDOMAIN); ?></option>
				<option value="next"<?php selected('next', $instance['month']); ?>><?php _e('Next', WP_CAT2CALENDAR_TEXTDOMAIN); ?></option>
				<optgroup style="border-top: 1px dotted #ccc; margin: 5px 0;"></optgroup>
				<?php for($i = 1; $i <= 12; $i++) : ?>
				<option value="<?php echo $i; ?>"<?php selected($i, $instance['month']); ?>><?php echo $wp_locale->get_month($i); ?></option>
				<?php endfor; ?>
			</select>

			<select name="<?php echo $this->get_field_name('year'); ?>" id="<?php echo $this->get_field_id('year'); ?>">
				<option value="0"<?php selected($i, $instance['year']); ?>><?php _e('Current', WP_CAT2CALENDAR_TEXTDOMAIN); ?></option>
				<option value="prev"<?php selected('prev', $instance['year']); ?>><?php _e('Previous', WP_CAT2CALENDAR_TEXTDOMAIN); ?></option>
				<option value="next"<?php selected('next', $instance['year']); ?>><?php _e('Next', WP_CAT2CALENDAR_TEXTDOMAIN); ?></option>
				<optgroup style="border-top: 1px dotted #ccc; margin: 5px 0;"></optgroup>
				<?php for($i = 2000; $i <= date_i18n('Y'); $i++) : ?>
				<option value="<?php echo $i; ?>"<?php selected($i, $instance['year']); ?>><?php echo $i; ?></option>
				<?php endfor; ?>
			</select>
		</p>

		<p>
			<input class="checkbox" name="<?php echo $this->get_field_name('show_nav'); ?>" id="<?php echo $this->get_field_id('show_nav'); ?>" value="1" type="checkbox" <?php checked($instance['show_nav'], true)?>/> <label for="<?php echo $this->get_field_id('show_nav'); ?>"><?php _e('Show navigation', WP_CAT2CALENDAR_TEXTDOMAIN); ?></label>
		</p>

		<p>
			<input class="checkbox" name="<?php echo $this->get_field_name('show_date'); ?>" id="<?php echo $this->get_field_id('show_date'); ?>" value="1" type="checkbox" <?php checked($instance['show_date'], true)?>/> <label for="<?php echo $this->get_field_id('show_date'); ?>"><?php _e('Show date', WP_CAT2CALENDAR_TEXTDOMAIN); ?></label>
		</p>

		<p>
			<input class="checkbox" name="<?php echo $this->get_field_name('allow_change_date'); ?>" id="<?php echo $this->get_field_id('allow_change_date'); ?>" value="1" type="checkbox" <?php checked($instance['allow_change_date'], true)?>/> <label for="<?php echo $this->get_field_id('allow_change_date'); ?>"><?php _e('Allow change date', WP_CAT2CALENDAR_TEXTDOMAIN); ?></label><br/>
			<small><?php _e('Useful when navigation is hidden but calendar month/year permalinks still should work. Obsolete when navigation is on.', WP_CAT2CALENDAR_TEXTDOMAIN); ?></small>
		</p>

		<p>
			<span class="alignright hide-if-no-js">
				<small>
				<?php foreach($this->simple_date_formats as $fmt) : ?>
				<a href="javascript:void(document.getElementById('<?php echo $this->get_field_id('date_format'); ?>').value = '<?php echo esc_attr($fmt); ?>');"><?php echo esc_html($fmt); ?></a>
				<?php endforeach; ?>
				</small>
			</span>
			<label for="<?php echo $this->get_field_id('date_format'); ?>"><?php _e('Date format:', WP_CAT2CALENDAR_TEXTDOMAIN); ?></label>
			<input type="text" class="widefat" name="<?php echo $this->get_field_name('date_format'); ?>" id="<?php echo $this->get_field_id('date_format'); ?>" value="<?php echo esc_attr($instance['date_format']); ?>" /><br/>
			<small><?php _e('Navigation dates. <a href="http://php.net/date" target="_blank">PHP date format docs</a>.', WP_CAT2CALENDAR_TEXTDOMAIN); ?></small>
		</p>

		<p>
			<label for="<?php echo $this->get_field_id('cell_height'); ?>"><?php _e('Cell height:', WP_CAT2CALENDAR_TEXTDOMAIN); ?></label>
			<input type="text" class="widefat" name="<?php echo $this->get_field_name('cell_height'); ?>" id="<?php echo $this->get_field_id('cell_height'); ?>" value="<?php echo esc_attr($instance['cell_height']); ?>" /><br/>
			<small><?php _e('Leave empty for default height.', WP_CAT2CALENDAR_TEXTDOMAIN); ?></small>
		</p>


	<?php
	}
}


//
// end of file widget.php
//