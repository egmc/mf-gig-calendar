<?php

/*

Copyright (C) 2012 Matthew Fries

MF Gig Calendar is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License
as published by the Free Software Foundation; either version 2
of the License, or (at your option) any later version.

MF Gig Calendar is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.

*/

class GigCalendarWidget extends WP_Widget {

	// The construct part
	function __construct() {
		parent::__construct(
 
			// Base ID of your widget
			'mfgigcal_ID', 
 
			// Widget name will appear in UI
			__('MF Gig Calendar', 'mfgigcal'), 
 
			// Widget description
			array( 'description' => __( 'MF Gig Calendar Widget displays a list of upcoming events.', 'mfgigcal' ), )
		);
	}
	
	/* function GigCalendarWidget() {
		parent::WP_Widget( false, $name = 'MF Gig Calendar', array( 'description' => __('MF Gig Calendar Widget displays a list of upcoming events.', 'mfgigcal') ) );
	}*/
	 
	public function widget( $args, $instance ) {
		extract( $args );
		$title = apply_filters( 'widget_title', $instance['title'] );
		$events_to_display = apply_filters( 'events_to_display', $instance['events_to_display'] );
		$display_when_empty = apply_filters( 'display_when_empty', $instance['display_when_empty'] );
		$widget_date_format = apply_filters( 'widget_date_format', $instance['widget_date_format'] );
		$calendar_link = apply_filters( 'calendar_link', $instance['calendar_link'] );
		$link_text = apply_filters( 'link_text', $instance['link_text'] );
		if ($widget_date_format == "") $widget_date_format = 'D j M';
			
		global $wpdb;
		$mfgigcal_table = $wpdb->prefix . "mfgigcal";
		
		date_default_timezone_set(get_option('timezone_string'));
	
		// get the dates
		$today = date("Y-m-d");
		$sql = "SELECT * FROM $mfgigcal_table WHERE end_date >= '$today' ORDER BY start_date ASC LIMIT " . $events_to_display;
		$mfgigcal_events = $wpdb->get_results($sql);
		
		// here are the events
		
		if (!empty($mfgigcal_events) || $display_when_empty) {
		
			$mfgigcal_settings = get_option('mfgigcal_settings');
		
			echo $before_widget;
			
			if ($title) {
				echo $before_title . $title . $after_title;
			}
			
			if (!empty($mfgigcal_events)) {
				echo "\n<ul id=\"mfgigcal-widget\">";
				foreach ($mfgigcal_events as $mfgigcal_event) {
					echo "\n<li>";
					
					$start_date = strtotime($mfgigcal_event->start_date);
					$end_date = strtotime($mfgigcal_event->end_date);
					
					if ($start_date == $end_date) { // single date format
						echo "<div class=\"date\">" . wp_date($widget_date_format, $start_date) . " <span class=\"time\">$mfgigcal_event->time</span></div>";
					}
					else { // multi-day format
						echo "<div class=\"date\">" . wp_date($widget_date_format, $start_date) . "&ndash;" . wp_date($widget_date_format, $end_date) . " <span class=\"time\">$mfgigcal_event->time</span></div>";
					}
					
					if ($mfgigcal_settings['calendar_url']) {
						echo "\n<h4><a href=\"" . $mfgigcal_settings['calendar_url'] . "?event_id=" . $mfgigcal_event->id . "\">$mfgigcal_event->title</a></h4>";
					}
					else {
						echo "\n<h4>$mfgigcal_event->title</h4>";
					}
					echo $mfgigcal_event->location;
					echo "</li>";
				}
			
				echo "\n</ul>\n\n";
			}
			else if ($display_when_empty) {
				echo "<p>" . $mfgigcal_settings['message'] . "</p>";			
			}
			if ($calendar_link != "" && $mfgigcal_settings['calendar_url']) {
				echo "<p><a href=\"" . $mfgigcal_settings['calendar_url'] . "\" class=\"calendar_url\">$link_text</a></p>";
			}
			echo $after_widget;
		}
		
	}

	public function update( $new_instance, $old_instance ) {
		return $new_instance;
	}
	 
	public function form( $instance ) {
		$title = esc_attr( $instance['title'] );
		$events_to_display = esc_attr( $instance['events_to_display']);
		$display_when_empty = esc_attr( $instance['display_when_empty']);
		$widget_date_format = esc_attr( $instance['widget_date_format']);
		$calendar_link = esc_attr( $instance['calendar_link']);
		$link_text = esc_attr( $instance['link_text']);
		if ($link_text == "") $link_text = __('View My Event Calendar', 'mfgigcal');
		if ($widget_date_format == "") $widget_date_format = 'D j M';
		?>
		
		<p>
			<label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e('Widget Title', 'mfgigcal'); ?>:
			<input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo $title; ?>" />
			</label>
		</p>
		<p>
			<label><?php _e('How many events to display?', 'mfgigcal'); ?>
			<input style="width:30px;" id="<?php echo $this->get_field_id( 'events_to_display' ); ?>" name="<?php echo $this->get_field_name( 'events_to_display' ); ?>" type="text" value="<?php echo $events_to_display; ?>" />
			</label>
		</p>
		<p>
			<label><input class="link_detail_switch" id="<?php echo $this->get_field_id( 'display_when_empty' ); ?>" name="<?php echo $this->get_field_name( 'display_when_empty' ); ?>" type="checkbox" value="1" <?php checked( '1', $display_when_empty ); ?> />
			<?php _e('Display the widget even when there are no upcoming events.', 'mfgigcal'); ?>
			</label>
		</p>
		<p>
			<label><?php _e('Date Format:', 'mfgigcal'); ?>
			<input style="width:70px;" id="<?php echo $this->get_field_id( 'widget_date_format' ); ?>" name="<?php echo $this->get_field_name( 'widget_date_format' ); ?>" type="text" value="<?php echo $widget_date_format; ?>" />
			</label> <a href="http://codex.wordpress.org/Formatting_Date_and_Time" title="http://codex.wordpress.org/Formatting_Date_and_Time" target="_blank"><?php _e('Help'); ?>?</a>
		</p>
		<p>
			<label><input class="link_detail_switch" id="<?php echo $this->get_field_id( 'calendar_link' ); ?>" name="<?php echo $this->get_field_name( 'calendar_link' ); ?>" type="checkbox" value="1" <?php checked( '1', $calendar_link ); ?> />
			<?php _e('Add a link to the main event calendar (specified in MF Gig Calendar Settings).', 'mfgigcal'); ?>
			</label>
		</p>
		<div class="link_details">
		<p>
			<label><?php _e('Link Text', 'mfgigcal'); ?>:
			<input class="widefat" id="<?php echo $this->get_field_id( 'link_text' ); ?>" name="<?php echo $this->get_field_name( 'link_text' ); ?>" type="text" value="<?php echo $link_text; ?>" />
			</label>
		</p>
		<p>
			<?php _e('Note: If the "View My Calendar" link isn\'t displaying in your widget please make sure you have entered a URL for your calendar in the MF Gig Calendar Settings.', 'mfgigcal'); ?>
		</p>
		</div>
		
		<?php
	
	}
}
 
add_action( 'widgets_init', 'GigCalendarWidgetInit' );
function GigCalendarWidgetInit() {
	register_widget( 'GigCalendarWidget' );
}

?>