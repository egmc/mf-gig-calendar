<?php
/*
Plugin Name: MF Gig Calendar
Description: A simple event calendar created for musicians but useful for anyone. Supports multi-day events, styled text, links, images, and more. Version 1.2 is now compatible with PHP 8.
Version: 1.2.1
Author: Matthew Fries
Plugin URI: https://matthewfries.com/mf-gig-calendar
Author URI: https://matthewfries.com
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

// Stylesheet for display

function mfgigcal_load_stylesheet() {
	$url = plugins_url( '/css/mf_gig_calendar.css', __FILE__ );
	wp_register_style( 'mfgigcal_css', $url );
	wp_enqueue_style( 'mfgigcal_css' );
}

add_action( 'wp_print_styles', 'mfgigcal_load_stylesheet' );


// [mfgigcal] - SHORT CODE functions

function mfgigcal_func( $atts ) {
	return mfgigcal_getrows( $atts );
}

add_shortcode( 'mfgigcal', 'mfgigcal_func' );

function mfgigcal_getrows( $atts ) {
	// get the defaults
	$mfgigcal_settings = get_option( 'mfgigcal_settings' );
	( $mfgigcal_settings['sort_order'] == "DESC" ) ? $sort_order = "DESC" : $sort_order = "ASC";
	( isset( $mfgigcal_settings['event_links'] ) ) ? $event_links = false : $event_links = true; // remove links to individual events?
	( isset( $mfgigcal_settings['event_details'] ) ) ? $event_details = false : $event_details = true; // hide event details?
	// clean the variables
	$ytd 	= $_GET['ytd'] ?? null;
	$ytd	= mfgigcal_Clean( $ytd );

	$event_id	= $_GET['event_id'] ?? null;
	$event_id	= mfgigcal_Clean( $event_id );

	// overrides from shortcode attributes
	extract( shortcode_atts( array(
		'id'      => $event_id,
		'date'    => '',
		'range'   => '',
		'offset'  => '0',
		'limit'   => '18576385629384657',
		'days'    => '',
		'rss'     => '',
		'sort'    => $sort_order,
		'title'   => 'false',
		'link'    => $event_links,
		'details' => $event_details
	), $atts, 'mfgigcal' ) );

	$title   = filter_var( $title, FILTER_VALIDATE_BOOLEAN );
	$link    = filter_var( $link, FILTER_VALIDATE_BOOLEAN );
	$details = filter_var( $details, FILTER_VALIDATE_BOOLEAN );


	$sort = strtoupper( $sort );
	if ( $sort != 'DESC' ) {
		$sort = 'ASC';
	} // we only allow ASC (default) and DESC for the sort order


	global $wpdb;
	$mfgigcal_table = $wpdb->prefix . "mfgigcal";

	date_default_timezone_set( get_option( 'timezone_string' ) );

	// get the dates
	$today = date( "Y-m-d" );

	$sql = "SELECT * FROM $mfgigcal_table ";

	if ( $ytd == date( "Y" ) && empty( $atts ) ) {
		$sql .= "WHERE (end_date >= '" . $ytd . "-01-01' AND end_date < '$today') ";
	} else if ( $ytd && empty( $atts ) ) {
		$sql .= "WHERE (end_date >= '" . $ytd . "-01-01' AND start_date <= '" . $ytd . "-12-31') ";
	}


	// handle the shortcode variables

	// choose which events to display
	else if ( $id ) {
		$sql .= "WHERE id = '" . $id . "' ";
	} else if ( $date ) {
		$sql .= "WHERE (end_date >= '" . $date . "' AND start_date <= '" . $date . "') OR end_date = '" . $date . "' ";
	} else if ( $range ) {
		$start_date = substr( $range, 0, strpos( $range, ':' ) );
		if ( $start_date == 'today' ) {
			$start_date = $today;
		}
		if ( preg_match( "/^[0-9][0-9]-[0-9][0-9]$/", $start_date ) ) {
			$start_date = date( Y ) . "-" . $start_date;
		} // add this year if they didn't include

		$end_date = substr( $range, strpos( $range, ':' ) + 1 );
		if ( $end_date == 'today' ) {
			$end_date = $today;
		}
		if ( preg_match( "/^[0-9][0-9]-[0-9][0-9]$/", $end_date ) ) {
			$end_date = date( Y ) . "-" . $end_date;
		} // add this year if they didn't include

		$sql .= "WHERE (end_date >= '" . $start_date . "' AND start_date <= '" . $end_date . "') ";
	} else if ( $days ) {
		$days     = $days - 1; // days originally includes today. we only want to know how many future days
		$end_date = date( 'Y-m-d', strtotime( "+" . $days . "days" ) );
		$sql      .= "WHERE (end_date >= '" . $today . "' AND start_date <= '" . $end_date . "') ";
	} else { // default view displays upcoming events
		$sql .= "WHERE end_date >= '$today' ";
	}


	$sql .= "ORDER BY start_date " . $sort;
	$sql .= " LIMIT " . $limit . " OFFSET " . $offset;

	$mfgigcal_events = $wpdb->get_results( $sql );

	$mfgigcal_data = "";
	
	$mfgigcal_rss = $mfgigcal_settings['rss'] ?? null;
	if ( ( $mfgigcal_rss && empty( $atts ) ) || ( $mfgigcal_rss && $rss ) ) {
		( get_option( 'permalink_structure' ) ) ? $feed_link = "/feed/mfgigcal" : $feed_link = "/?feed=mfgigcal";
		$mfgigcal_data .= "<a href=\"$feed_link\" class=\"rss-link\">RSS</a>";
	}

	global $post;
	$query_prefix = get_permalink( get_post( $post )->id );
	if ( get_option( 'permalink_structure' ) ) {
		if ( $mfgigcal_settings['calendar_url'] ) {
			$query_prefix = $mfgigcal_settings['calendar_url'];
		}
	}

	if ( empty( $atts ) ) { // don't show the nav if we're working with shortcode display
		$mfgigcal_data .= mfgigcal_CalendarNav();
	}

	if ( empty( $mfgigcal_events ) && $mfgigcal_settings['no-events'] == "text" ) {
		$mfgigcal_data .= "<p>" . $mfgigcal_settings['message'] . "</p>";

		return $mfgigcal_data;
	} else if ( empty( $mfgigcal_events ) && ! empty( $atts ) ) {
		$mfgigcal_data .= "<p>" . $mfgigcal_settings['message'] . "</p>";

		return $mfgigcal_data;
	} else if ( empty( $mfgigcal_events ) ) {
		$this_year = date( "Y" );
		// show the current year
		$sql             = "SELECT * FROM $mfgigcal_table WHERE (end_date >= '$this_year-01-01' AND start_date <= '$this_year-12-31') ORDER BY start_date ASC";
		$mfgigcal_events = $wpdb->get_results( $sql );
		if ( empty( $mfgigcal_events ) ) {
			$mfgigcal_data .= "<p>" . $mfgigcal_settings['message'] . "</p>";

			return $mfgigcal_data;
		}
	}

	( $event_id ) ? $list_type = "mfgigcal_single" : $list_type = "mfgigcal_list";

	$mfgigcal_data .= "\n\n<ul id=\"cal\" class=\"mfgigcal " . $list_type . "\">\n";


	foreach ( $mfgigcal_events as $mfgigcal_event ) {
		$url = add_query_arg( "event_id", $mfgigcal_event->id, $query_prefix );

		$mfgigcal_data .= "\n<li class=\"event\">\n<div class=\"date\">\n\t";
		$mfgigcal_data .= mfgigcal_FormatDate( $mfgigcal_event->start_date, $mfgigcal_event->end_date );
		$mfgigcal_data .= "\n</div>\n";

		$mfgigcal_data .= "<div class=\"info_block\">\n\t<h3>";
		if ( ! $id && $link ) {
			$mfgigcal_data .= "<a href=\"" . esc_attr( $url ) . "\">" . $mfgigcal_event->title . "</a>";
		} else {
			$mfgigcal_data .= $mfgigcal_event->title;
		}
		$mfgigcal_data .= "</h3>\n";
		$mfgigcal_data .= "\t<span class=\"time\">" . $mfgigcal_event->time . "</span>\n";
		$mfgigcal_data .= "\t<span class=\"location\">" . $mfgigcal_event->location . "</span>\n";
		if ( $details || $id ) {
			$mfgigcal_data .= "\t<span class=\"details\">" . apply_filters( 'the_content', $mfgigcal_event->details ) . "</span>\n";
		}
		$mfgigcal_data .= "</div>\n</li>\n";

	}
	$mfgigcal_data .= "</ul>";


	return $mfgigcal_data;

}

$month_array   = "";
$weekday_array = "";

function mfgigcal_FormatDate( $start_date, $end_date ) { // FUNCTION ///////////

	$start_date = strtotime( $start_date );
	$end_date   = strtotime( $end_date );

	if ( $start_date == $end_date ) {
		//print date("M j, Y", $start_date); // one day event
		$mfgigcal_date = "<div class=\"end-date\">";
		$mfgigcal_date .= "<div class=\"weekday\">" . wp_date( "D", $start_date ) . "</div>";
		$mfgigcal_date .= "<div class=\"day\">" . wp_date( "d", $start_date ) . "</div>";
		$mfgigcal_date .= "<div class=\"month\">" . wp_date( "M", $start_date ) . "</div>";
		$mfgigcal_date .= "<div class=\"year\">" . wp_date( "Y", $start_date ) . "</div>";
		$mfgigcal_date .= "</div>";

		return $mfgigcal_date;
	} else {
		//print date("M j, Y", $start_date); // multi-day event
		$mfgigcal_date = "<div class=\"start-date\">";
		$mfgigcal_date .= "<div class=\"weekday\">" . wp_date( "D", $start_date ) . "</div>";
		$mfgigcal_date .= "<div class=\"day\">" . wp_date( "d", $start_date ) . "</div>";
		$mfgigcal_date .= "<div class=\"month\">" . wp_date( "M", $start_date ) . "</div>";
		$mfgigcal_date .= "<div class=\"year\">" . wp_date( "Y", $start_date ) . "</div>";
		$mfgigcal_date .= "</div>";

		$mfgigcal_date .= "<div class=\"end-date\">";
		$mfgigcal_date .= "<div class=\"weekday\">" . wp_date( "D", $end_date ) . "</div>";
		$mfgigcal_date .= "<div class=\"day\">" . wp_date( "d", $end_date ) . "</div>";
		$mfgigcal_date .= "<div class=\"month\">" . wp_date( "M", $end_date ) . "</div>";
		$mfgigcal_date .= "<div class=\"year\">" . wp_date( "Y", $end_date ) . "</div>";
		$mfgigcal_date .= "</div>";

		return $mfgigcal_date;
	}

} // END OF FORMAT DATE FUNCTION!! ///////////////////////////


// INTERNATIONAL ======================================

load_plugin_textdomain( 'mfgigcal', false, basename( dirname( __FILE__ ) ) . '/languages/' );

// ADMIN ==============================================

add_action( 'admin_menu', 'mfgigcal_admin_menu' );
function mfgigcal_admin_menu() {
	$page = add_menu_page( __( 'MF Gig Calendar', 'mfgigcal' ), __( 'MF Gig Calendar', 'mfgigcal' ), 'edit_posts', 'mf_gig_calendar', 'mfgigcal_admin', 'dashicons-calendar-alt' );
	add_action( 'admin_head-' . $page, 'mfgigcal_admin_register_head' );

	$page = add_submenu_page( 'mf_gig_calendar', __( 'MF Gig Calendar Settings', 'mfgigcal' ), __( 'Settings', 'mfgigcal' ), 'edit_posts', 'mf_gig_calendar_settings', 'mfgigcal_settings_page' );
	add_action( 'admin_head-' . $page, 'mfgigcal_admin_register_head' );

	$page = add_submenu_page( 'mf_gig_calendar', __( 'About MF Gig Calendar', 'mfgigcal' ), __( 'Documentation', 'mfgigcal' ), 'edit_posts', 'mf_gig_calendar_about', 'mfgigcal_about_page' );
	add_action( 'admin_head-' . $page, 'mfgigcal_admin_register_head' );

	//call register settings function
	add_action( 'admin_init', 'mfgigcal_register_settings' );

}

// Stylesheet for admin

function mfgigcal_admin_register_head() {
	global $post, $wp_locale;
	date_default_timezone_set( get_option( 'timezone_string' ) );

	// add the jQuery UI elements shipped with WP
	wp_enqueue_script( 'jquery' );
	wp_enqueue_script( 'jquery-ui-datepicker' );

	// add the style
	wp_enqueue_style( 'jquery.ui.theme', plugins_url( '/css/jquery-ui-1.9.2.custom.css', __FILE__ ) );
	wp_enqueue_style( 'mfgigcal-css', plugins_url( '/css/mf_gig_calendar_admin.css', __FILE__ ) );

	// add mfgigcal js
	wp_enqueue_script( 'mfgigcal-admin', '/wp-content/plugins/' . basename( dirname( __FILE__ ) ) . '/mf_gig_calendar_admin.js', array( 'jquery-ui-datepicker' ) );

	// localize our js for datepicker
	$aryArgs = array(
		'closeText'       => __( 'Done', 'mfgigcal' ),
		'currentText'     => __( 'Today', 'mfgigcal' ),
		'monthNames'      => strip_array_indices( $wp_locale->month ),
		'monthNamesShort' => strip_array_indices( $wp_locale->month_abbrev ),
		'monthStatus'     => __( 'Show a different month', 'mfgigcal' ),
		'dayNames'        => strip_array_indices( $wp_locale->weekday ),
		'dayNamesShort'   => strip_array_indices( $wp_locale->weekday_abbrev ),
		'dayNamesMin'     => strip_array_indices( $wp_locale->weekday_initial ),
		// get the start of week from WP general setting
		'firstDay'        => get_option( 'start_of_week' ),
		// is Right to left language? default is false
		'isRTL'           => $wp_locale->is_rtl(),
	);

	// Pass the translation array to the enqueued JS for datepicker
	wp_localize_script( 'mfgigcal-admin', 'objectL10n', $aryArgs );
}


// Settings Page

function mfgigcal_register_settings() {
	//register our settings
	register_setting( 'mfgigcal_settings', 'mfgigcal_settings', 'mfgigcal_settings_validate' );

	add_settings_section( 'mfgigcal_settings_setup', __( 'Basic Usage', 'mfgigcal' ), 'mfgigcal_settings_display_setup', 'mfgigcal' );


	add_settings_section( 'mfgigcal_settings_display', __( 'Oh Make My Events Behave - Optional Settings', 'mfgigcal' ), 'mfgigcal_settings_display_text', 'mfgigcal' );


	add_settings_field( 'upcoming_title', __( 'Calendar Title', 'mfgigcal' ), 'mfgigcal_settings_display_upcoming_field', 'mfgigcal', 'mfgigcal_settings_display' );
	add_settings_field( 'event_title', __( 'Individual Event Title', 'mfgigcal' ), 'mfgigcal_settings_display_eventtitle_field', 'mfgigcal', 'mfgigcal_settings_display' );
	add_settings_field( 'sort_order', __( 'Sort Order', 'mfgigcal' ), 'mfgigcal_settings_display_sort_field', 'mfgigcal', 'mfgigcal_settings_display' );
	add_settings_field( 'event_links', __( 'Individual Events', 'mfgigcal' ), 'mfgigcal_settings_display_link_field', 'mfgigcal', 'mfgigcal_settings_display' );
	add_settings_field( 'event_details', __( 'Event Details Display', 'mfgigcal' ), 'mfgigcal_settings_display_details_field', 'mfgigcal', 'mfgigcal_settings_display' );
	add_settings_field( 'archive_limit', __( 'Archive List Limit', 'mfgigcal' ), 'mfgigcal_settings_display_archive_limit_field', 'mfgigcal', 'mfgigcal_settings_display' );
	add_settings_field( 'display', __( 'Empty Calendar', 'mfgigcal' ), 'mfgigcal_settings_display_display_field', 'mfgigcal', 'mfgigcal_settings_display' );
	add_settings_field( 'calendar_url', __( 'Calendar URL', 'mfgigcal' ), 'mfgigcal_settings_display_url_field', 'mfgigcal', 'mfgigcal_settings_display' );
	add_settings_field( 'rss', __( 'RSS Feed', 'mfgigcal' ), 'mfgigcal_settings_display_rss_field', 'mfgigcal', 'mfgigcal_settings_display' );
}

function mfgigcal_settings_validate( $input ) {
	return $input;
}

function mfgigcal_settings_display_setup() {

	echo "<p>" . __( 'Basic installation is easy. Just place this short code on any Page or Post where you want the event list to appear', 'mfgigcal' ) . ": </p>
	
	<p>[mfgigcal]</p>
	
	<p>" . __( 'Enter some events and you\'ll be up and running', 'mfgigcal' ) . ": <a href=\"admin.php?page=mf_gig_calendar&action=edit\">" . __( 'Create Event', 'mfgigcal' ) . "</a><br>
	" . __( 'Learn more about how it works on the About Page', 'mfgigcal' ) . ": <a href=\"admin.php?page=mf_gig_calendar_about\">" . __( 'About MF Gig Calendar', 'mfgigcal' ) . "</a><br><br></p>";
}


function mfgigcal_settings_display_text() {
	echo "<p>" . __( 'Once you have events displaying on your site you can customize the default MF Gig Calendar with the following optional settings.', 'mfgigcal' ) . "</p>";
}

function mfgigcal_settings_display_rss_field() {
	$options = get_option( 'mfgigcal_settings' );
	$rss = $options['rss'] ?? null;
	$rss_details = $options['rss_details'] ?? null;
	$rss_date_format = $options['rss_date_format'] ?? null;
	?>
    <p><label><input id="rss" name="mfgigcal_settings[rss]" type="checkbox"
                     value="1" <?php checked( '1', $rss ); ?> />
			<?php _e( 'Include an RSS feed link on your event page?', 'mfgigcal' ); ?></label><br>
        <label><input id="rss_details" name="mfgigcal_settings[rss_details]" type="checkbox"
                      value="1" <?php checked( '1', $rss_details ); ?> />
			<?php _e( 'Include full event details in your RSS feed?', 'mfgigcal' ); ?></label><br>
    </p>
    <p><?php _e( 'What is your preferred format to use for dates in your RSS feed?', 'mfgigcal' ); ?>
        <select id="rss_date_format" name="mfgigcal_settings[rss_date_format]">
            <option value="mdy" <?php if ( $rss_date_format == "mdy" ) {
				echo "selected";
			} ?>><?php echo wp_date( 'M j, Y' ); ?>  </option>
            <option value="dmy" <?php if ( $rss_date_format == "dmy" ) {
				echo "selected";
			} ?>><?php echo wp_date( 'j M, Y' ); ?>  </option>
        </select>
        <br>
    </p>

	<?php
}

function mfgigcal_settings_display_archive_limit_field() {
	$siteurl = get_option( 'siteurl' );
	$options = get_option( 'mfgigcal_settings' );
	$archive_limit = $options['archive_limit'] ?? null;
	?>
    <p><?php _e( 'Limit the number of years to display in the archive menu.', 'mfgigcal' ); ?>
        <select id="archive_limit" name="mfgigcal_settings[archive_limit]">
            <option value="0" <?php if ( $archive_limit == "0" ) {
				echo "selected";
			} ?>><?php _e( 'Infininite' ); ?>  </option>
			<?php
			for ( $i = 10; $i > 0; $i -- ) {
				echo '<option value="' . $i . '" ';
				if ( $archive_limit == $i ) {
					echo "selected";
				}
				echo '>' . $i . '  </option>';
			}
			?>
        </select><br>
    </p>

	<?php
}

function mfgigcal_settings_display_display_field() {
	$siteurl = get_option( 'siteurl' );
	$options = get_option( 'mfgigcal_settings' );
	$no_events = $options['no-events'] ?? null;
	$message = $options['message'] ?? null;
	?>
    <p><?php _e( 'It happens. Sometimes there just isn\'t much going on (or there\'s too much going on and you forgot to update your calendar on your website). 
	What do you want your event calendar to do in those rare
	moments when you don\'t have any upcoming events to display in your calendar?', 'mfgigcal' ); ?></p>
    <p><label><input type="radio" name="mfgigcal_settings[no-events]" value="archive"
                     checked> <?php _e( 'Display the events for the current year', 'mfgigcal' ); ?>.
            <i>(<?php _e( 'default', 'mfgigcal' ); ?>)</i></label><br/>
        <label><input type="radio" name="mfgigcal_settings[no-events]"
                      value="text" <?php checked( 'text', $no_events ); ?>> <?php _e( 'Display the following message:', 'mfgigcal' ); ?>
        </label></p>
    <p><textarea id="message" name="mfgigcal_settings[message]"
                 style="width:300px;"><?= $message ?></textarea></p>

	<?php
}

function mfgigcal_settings_display_url_field() {
	$siteurl = get_option( 'siteurl' );
	$options = get_option( 'mfgigcal_settings' );
	$calendar_url = $options['calendar_url'] ?? null;
	?>
    <p><?php _e( 'You can put your event calendar on any Page or Post on your site. Once you have it working you can let MF Gig Calendar know where it is by entering a URL of the Page or Post here. NOTE: MF Gig Calendar will use this URL in the RSS feed and in the widget.', 'mfgigcal' ); ?></p>
    <p><input type="text" id="calendar_url" name="mfgigcal_settings[calendar_url]" style="width:300px;"
              value="<?= $calendar_url ?>"> <i><?php _e( 'Example', 'mfgigcal' ); ?>: <?= $siteurl ?>
            /events</i></p>
	<?php
}

function mfgigcal_settings_display_upcoming_field() {
	$siteurl = get_option( 'siteurl' );
	$options = get_option( 'mfgigcal_settings' );
	( $options['upcoming_title'] == "" ) ? $upcoming_title = __( 'Upcoming Events', 'mfgigcal' ) : $upcoming_title = $options['upcoming_title']
	?>
    <p><?php _e( 'What title do you want to use in your event calendar when it is displaying upcoming events? Feel free to be creative!', 'mfgigcal' ); ?></p>
    <p><input type="text" id="upcoming_title" name="mfgigcal_settings[upcoming_title]" style="width:300px;"
              value="<?= $upcoming_title ?>"></p>

	<?php
}

function mfgigcal_settings_display_details_field() {
	$options = get_option( 'mfgigcal_settings' );
	$event_details = $options['event_details'] ?? null;
	?>
    <p><?php _e( 'If you prefer a tidier list of events, you can hide your event details from the event list.', 'mfgigcal' ); ?></p>
    <p><label><input id="event_details" name="mfgigcal_settings[event_details]" type="checkbox"
                     value="1" <?php checked( '1', $event_details ); ?> />
		<?php _e( 'Hide my event details', 'mfgigcal' ); ?></label><br>
    </p><?php
}

function mfgigcal_settings_display_eventtitle_field() {
	$siteurl = get_option( 'siteurl' );
	$options = get_option( 'mfgigcal_settings' );
	( $options['event_title'] == "" ) ? $event_title = __( 'Event Information', 'mfgigcal' ) : $event_title = $options['event_title']
	?>
    <p><?php _e( 'What title do you want to use in your event calendar when it is displaying an individual event?', 'mfgigcal' ); ?></p>
    <p><input type="text" id="event_title" name="mfgigcal_settings[event_title]" style="width:300px;"
              value="<?= $event_title ?>"></p>

	<?php
}

function mfgigcal_settings_display_sort_field() {
	$options = get_option( 'mfgigcal_settings' );
	( $options['sort_order'] == "" ) ? $sort_order = 'ASC' : $sort_order = $options['sort_order']
	?>
    <p><?php _e( 'How do you like want to sort your list of events? ', 'mfgigcal' ); ?>
    <select id="sort_order" name="mfgigcal_settings[sort_order]">
        <option value="ASC" <?php if ( $options['sort_order'] == "ASC" ) {
			echo "selected";
		} ?>>Ascending
        </option>
        <option value="DESC" <?php if ( $options['sort_order'] == "DESC" ) {
			echo "selected";
		} ?>>Descending
        </option>
    </select>
    <br>
    </p><?php
}

function mfgigcal_settings_display_link_field() {
	$options = get_option( 'mfgigcal_settings' );
	$event_links = $options['event_links'] ?? null;
	?>
    <p><?php _e( 'By default the title of your event will link to a page displaying only that event. It is a handy way to share individual events. But hey! you may not like it. You can disable the link here.', 'mfgigcal' ); ?></p>
    <p><label><input id="event_links" name="mfgigcal_settings[event_links]" type="checkbox"
                     value="1" <?php checked( '1', $event_links ); ?> />
		<?php _e( 'Do not link my event titles to individual event pages', 'mfgigcal' ); ?></label><br>
    </p><?php
}

function mfgigcal_settings_page() {
	if ( ! current_user_can( 'edit_posts' ) ) {
		wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
	}

	if ( isset( $_GET['settings-updated'] ) ) { ?>
        <div id="message" class="updated">
            <p><strong><?php _e( 'Settings saved.', 'mfgigcal' ) ?></strong></p>
        </div>
	<?php } ?>

    <div class="wrap">
        <h2><?php _e( 'MF Gig Calendar Settings', 'mfgigcal' ); ?></h2>

        <form method="post" action="options.php">
			<?php settings_fields( 'mfgigcal_settings' ); ?>
			<?php do_settings_sections( 'mfgigcal' ); ?>

            <input name="Submit" type="submit" value="<?php _e( 'Save Changes', 'mfgigcal' ); ?>"/>
        </form>
    </div>

	<?php
}

function mfgigcal_about_page() {

	$siteurl      = get_option( 'siteurl' );
	$mfgigcal_url = plugins_url( 'mf-gig-calendar' );

	echo '<div class="wrap">';
	echo '<h2>' . __( 'About MF Gig Calendar', 'mfgigcal' ) . '</h2>';

	?>

    <div style="float:right;margin:0px 0px 15px 30px;padding-left:30px;border-left:solid 1px #ccc;width:35%;">
        <h3><?php _e( 'Check Out My Music', 'mfgigcal' ); ?></h3>
        <p><?php _e( 'I have a few albums of jazz piano music. If you are a music fan I hope you will take a moment and listen!', 'mfgigcal' ); ?></p>
        <p><a href="https://matthewfries.com/music">matthewfries.com/music</a></p>
        <p>
            <a href="https://matthewfries.com/music/parallel-states/" target="_blank"
               title="Matthew Fries Trio: Lost Time"><img src="<?= $mfgigcal_url ?>/images/cd_Lost-Time.png"
                                                           alt="Matthew Fries Lost Time" width="75" height="75"
                                                           border="0" style="margin:5px;"/></a>
            <a href="https://matthewfries.com/music/parallel-states/" target="_blank"
               title="Matthew Fries: Parallel States"><img src="<?= $mfgigcal_url ?>/images/cd_Parallel-States.png"
                                                           alt="Matthew Fries Parallel States" width="75" height="67"
                                                           border="0" style="margin:5px;"/></a>
            <a href="https://matthewfries.com/music/staring-into-the-sun/" target="_blank"
               title="TRI-FI: Staring into the Sun **** (4 stars) Downbeat"><img
                        src="<?= $mfgigcal_url ?>/images/cd_Staring-into-the-Sun.png" alt="TRI-FI Staring into the Sun"
                        width="75" height="67" border="0" style="margin:5px;"/></a>
            <a href="https://matthewfries.com/music/tri-fi-3/" target="_blank" title="TRI-FI: 3"><img
                        src="<?= $mfgigcal_url ?>/images/cd_3.png" alt="TRI-FI 3" width="75" height="67" border="0"
                        style="margin:5px;"/></a>
            <a href="https://matthewfries.com/music/a-tri-fi-christmas/" target="_blank" title="A TRI-FI Christmas"><img
                        src="<?= $mfgigcal_url ?>/images/cd_TRI-FI-Christmas.png" alt="A TRI-FI Christmas" width="75"
                        height="67" border="0" style="margin:5px;"/></a>
            <a href="https://matthewfries.com/music/postcards/" target="_blank" title="TRI-FI: Postcards"><img
                        src="<?= $mfgigcal_url ?>/images/cd_Postcards.png" alt="TRI-FI Postcards" width="75" height="67"
                        border="0" style="margin:5px;"/></a>
            <a href="https://matthewfries.com/music/tri-fi/" target="_blank" title="TRI-FI"><img
                        src="<?= $mfgigcal_url ?>/images/cd_TRI-FI.png" alt="TRI-FI" width="75" height="67" border="0"
                        style="margin:5px;"/></a>
            <a href="https://matthewfries.com/music/" target="_blank" title="Matthew Fries: Song for Today"><img
                        src="<?= $mfgigcal_url ?>/images/cd_Song-for-Today.png" alt="Song for Today" width="75"
                        height="67" border="0" style="margin:5px;"/></a>
        </p>
        <h3><?php _e( 'Donate to Support This Project', 'mfgigcal' ); ?></h3>
        <p><?php _e( 'Buy me a beer. Help me pay rent. Whatever. Every little bit helps keep this project going and PayPal makes it easy to send your support.', 'mfgigcal' ); ?></p>
        <p>
        <form action="https://www.paypal.com/cgi-bin/webscr" method="post" target="_top">
            <input type="hidden" name="cmd" value="_s-xclick">
            <input type="hidden" name="hosted_button_id" value="APYLZNJSKAZFN">
            <input type="image" src="https://www.paypalobjects.com/en_US/i/btn/btn_donateCC_LG.gif" border="0"
                   name="submit" alt="PayPal - The safer, easier way to pay online!">
            <img alt="" border="0" src="https://www.paypalobjects.com/en_US/i/scr/pixel.gif" width="1" height="1">
        </form>
        </p>
        <h3><?php _e( '...Or Just Say Something Nice', 'mfgigcal' ); ?></h3>
        <p>
            <a href="http://wordpress.org/support/view/plugin-reviews/mf-gig-calendar"
               target="_blank"><?php _e( 'Submit a Review on Wordpress', 'mfgigcal' ); ?></a><br>
            <a href="http://facebook.com/matthewfriesmusic"
               target="_blank"><?php _e( 'Like Me On Facebook', 'mfgigcal' ); ?></a><br>
            <a href="http://twitter.com/mfjazz"
               target="_blank"><?php _e( 'Follow Me On Twitter', 'mfgigcal' ); ?></a><br>
        </p>
    </div>


    <h3><?php _e( 'Basic Usage', 'mfgigcal' ); ?></h3>
    <p><?php _e( 'Easy! Just place this short code on any Page or Post where you want the event calendar to appear', 'mfgigcal' ); ?>
        : </p>

    <blockquote>[mfgigcal]</blockquote>

    <p><?php _e( 'That\'s it! Well, that and adding some events of course...', 'mfgigcal' ); ?> </p>

    <p><a href="?page=mf_gig_calendar&action=edit"
          class="button-primary"><?php _e( 'Create New Event', 'mfgigcal' ); ?></a></p>

    <p><?php _e( 'The plugin includes a handy widget you can place in your sidebar to show upcoming events. Just drag it into your sidebar to display a list of a few upcoming events.', 'mfgigcal' ); ?>
		<?php _e( 'Be sure to also check out the Event Calendar Settings to get MF Gig Calendar behaving just the way you want.', 'mfgigcal' ); ?>
        : <a href="admin.php?page=mf_gig_calendar_settings"><?php _e( 'Event Calendar Settings', 'mfgigcal' ); ?></a>
    </p>

    <h3><?php _e( 'Advanced Usage', 'mfgigcal' ); ?></h3>

    <p><?php _e( 'You can display more specific event information on any PAGE or POST by including a variables in your short code.', 'mfgigcal' ); ?></p>

    <blockquote>
        <p>
            <b><?php _e( 'SELECT BY EVENT', 'mfgigcal' ); ?></b><br>
            [mfgigcal id=event_id] - <?php _e( 'display only one specific event', 'mfgigcal' ); ?>
        </p>
        <p>
            <b><?php _e( 'SELECT BY DATE', 'mfgigcal' ); ?></b><br>
            [mfgigcal date=YYYY-MM-DD]
            - <?php _e( '(year-month-day) display events that are happening on a particular date', 'mfgigcal' ); ?>
        </p>
        <p>
            <b><?php _e( 'SELECT BY DATE RANGE', 'mfgigcal' ); ?></b><br>
            [mfgigcal range=START:END]
            - <?php _e( 'display event within a particular date range using these accepted date-range formats', 'mfgigcal' ); ?>
            :
        </p>
        <p>
            YYYY-MM-DD - <?php _e( '(year-month-day) display specific dates', 'mfgigcal' ); ?><br>
            MM-DD - <?php _e( '(month-day) - display specific dates in the current year', 'mfgigcal' ); ?><br>
            today - <?php _e( 'display specific dates in relation to the current date', 'mfgigcal' ); ?><br>
        </p>
        <p><?php _e( 'EXAMPLE: [mfgigcal range=01-01:today] would show a year-to-date list of events', 'mfgigcal' ); ?></p>
        <p>
            <b><?php _e( 'LIMIT AND OFFSET BY DATE', 'mfgigcal' ); ?></b><br>
            [mfgigcal days=#]
            - <?php _e( 'how many days of future events (including today) you want to display', 'mfgigcal' ); ?><br/>
            [mfgigcal offset=#]
            - <?php _e( 'offset your list of upcoming events by a certain number of days', 'mfgigcal' ); ?>
        </p>
        <p>
            <b><?php _e( 'LIMIT AND OFFSET BY NUMBER OF EVENTS', 'mfgigcal' ); ?></b><br>
            [mfgigcal limit=#] - <?php _e( 'limit the number of events to display', 'mfgigcal' ); ?><br/>
        </p>
        <p>
            <b><?php _e( 'OTHER SETTINGS', 'mfgigcal' ); ?></b><br>
            [mfgigcal sort=ASC|DESC]
            - <?php _e( 'set the order in which events are displayed - ascending (ASC) or descending (DESC) - default is ASC', 'mfgigcal' ); ?>
            <br>
            [mfgigcal rss=true|false]
            - <?php _e( 'display the link for the RSS feed - default is false', 'mfgigcal' ); ?><br>
            [mfgigcal link=true|false]
            - <?php _e( 'make the event title link to a display of only the one event – default is based on your Event Calendar Settings', 'mfgigcal' ); ?>
            <br>
            [mfgigcal details=true|false]
            - <?php _e( 'display the details of the events in the event list – default is based on your Event Calendar Settings', 'mfgigcal' ); ?>
            <br>
        </p>
        <p>
            <a href="admin.php?page=mf_gig_calendar_settings"><?php _e( 'Event Calendar Settings', 'mfgigcal' ); ?></a>
        </p>
    </blockquote>

    <br>

    <h3><?php _e( 'Note from the Author', 'mfgigcal' ); ?></h3>

    <p>Thanks for trying my MF Gig Calendar event plugin. I hope it helps you!</p>

    <p>I'm <strong>Matthew Fries</strong> (that's where the MF comes from) and I'm a jazz pianist and educator. I developed this plugin because
        I wanted a flexible and easy to use performance calendar for <a href="https://matthewfries.com">my own music
            website</a>. In the process I tried to create
        something that would work for more than just musicians.
        I've added a few features since it started - beginning and end dates for multiple-day events, a duplicate
        function to make it easier if you
        have a repeating event that you don't want to re-enter over and over, an RSS feed, a widget to put upcoming
        events in the sidebar -
        but as a general rule I've tried to keep this as
        simple as possible. I think anyone who needs to display a list of events on their Wordpress site would find this
        useful.</p>

    <p>
        This plugin is free for you to use, and if you find it useful I hope you'll take a moment and also <a
                href="https://matthewfries.com" target="_blank">check out my music</a>! I love playing piano and
        composing and think you'll probably
        find <i>something</i> to like about what I play (unless you're one of those people who just hate everything -
        which would just be a sad, sad existence...).</p>

    <p><a href="https://matthewfries.com" target="_blank">www.matthewfries.com</a>&nbsp;&nbsp;&nbsp;<-- lots of jazz
        music to hear here...</p>

    <p>Please let me know what you think (of the plugin and the music!) - and if you have any suggestions or find any
        problems. While I do my best and this plugin is working great for me
        and a lot of other people, I'm not really supporting it any more as life has gotten super-busy.</p>

    <p>Thanks for your understanding and have fun!<br/>
        Matthew</p>

    <br>

    <h3>Credits</h3>
    <p>
        Polish translation by: <b>Julian Battelli</b><br>
        Spanish translation by: <b>Andrew Kurtis</b> (<a href="http://www.webhostinghub.com" target="_blank">WebHostingHub</a>)<br>
        Swedish translation by: <b>Marie Brunnberg</b><br>
        French translation by: <b>Tanguy Kerfriden</b><br>
    </p>
	<?php

	echo '</div>';

}

function mfgigcal_admin() {
	if ( ! current_user_can( 'edit_posts' ) ) {
		wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
	}

	// is there POST data to deal with?
	if ( $_POST ) {
		mfgigcal_save_record();
	}
	
	$action = $_GET['action'] ?? null;
	
	switch ( $action ) {

		case "edit" :
			echo '<div class="wrap">';
			echo mfgigcal_edit_event();
			echo '</div>';
			break;

		case "delete" :
			mfgigcal_delete_event();
			echo '<div class="wrap">';
			echo mfgigcal_list_events();
			echo '</div>';
			break;

		case "copy" :
			echo '<div class="wrap">';
			echo mfgigcal_edit_event();
			echo '</div>';
			break;

		default :
			echo '<div class="wrap">';
			echo mfgigcal_list_events();
			echo '</div>';
	}
	//}

}

function mfgigcal_delete_event() {
	global $wpdb;

	$id = $_GET['id'];

	$mfgigcal_table = $wpdb->prefix . "mfgigcal";
	$wpdb->query( $wpdb->prepare(
		"
		DELETE FROM $mfgigcal_table 
		WHERE id = %d
		", $id
	) );
}

function mfgigcal_save_record() {
	global $wpdb;
	$mfgigcal_table = $wpdb->prefix . "mfgigcal";

	remove_wp_magic_quotes();

	if ( empty( $_POST['start_date'] ) || empty( $_POST['title'] ) ) {
		echo '<div class="updated"><p>' . _e( 'Oops! Required information was not provided. Event could not be saved.' ) . '</p></div>';

		return;
	}

	// catch bad submissions that might get lost in the database...
	if ( empty( $_POST['end_date'] ) ) {
		$end_date = $_POST['start_date'];
	} else {
		$end_date = $_POST['end_date'];
	}

	$post_id = $_POST['id'] ?? null;
	$event_title = strip_tags($_POST['title']);
	$event_time = strip_tags($_POST['time']) ?? null;
	if ( $post_id ) {  // update record
		$wpdb->update(
			$mfgigcal_table,
			array(
				'start_date' => $_POST['start_date'],
				'end_date'   => $end_date,
				'pub_date'   => date( "Y-m-d H:i:s" ),
				'time'       => $event_time,
				'title'      => $event_title,
				'location'   => $_POST['location'],
				'details'    => $_POST['details']
			),
			array( 'id' => $_POST['id'] ),
			array(
				'%s',
				'%s',
				'%s',
				'%s',
				'%s',
				'%s'
			)
		);
	} else { // new record
		$wpdb->insert(
			$mfgigcal_table,
			array(
				'start_date' => $_POST['start_date'],
				'end_date'   => $end_date,
				'pub_date'   => date( "Y-m-d H:i:s" ),
				'time'       => $event_time,
				'title'      => $event_title,
				'location'   => $_POST['location'],
				'details'    => $_POST['details']
			),
			array(
				'%s',
				'%s',
				'%s',
				'%s',
				'%s',
				'%s'
			)
		);
	}
}

function mfgigcal_edit_event() {
	global $wpdb;
	$mfgigcal_table = $wpdb->prefix . "mfgigcal";

	$id = null;
	if ( isset ( $_GET['id'] ) ) {
		$id = $_GET['id'];
	}

	$sql = "SELECT *
			FROM $mfgigcal_table
			WHERE id = %d
			LIMIT 1";

	$mfgigcal_event = null;
	$mfgigcal_event = $wpdb->get_row( $wpdb->prepare( $sql, $id ) );

	$ytd = null;
	if ( isset ( $_GET['ytd'] ) ) {
		$ytd = mfgigcal_Clean( $_GET['ytd'] );
	}
	
	$start_date = null; 
	
	if ( $_GET['action'] == "copy" ) {
		$start_date = date( 'Y-m-d' );
		$end_date   = date( 'Y-m-d' );
	} else if ( isset ($mfgigcal_event) ) {
		$start_date = $mfgigcal_event->start_date;
		$end_date   = $mfgigcal_event->end_date;
	}

	if ( $start_date == "" ) {
		$start_date = date( 'Y-m-d' );
		$end_date   = date( 'Y-m-d' );
	}

	echo "<h2>" . __( 'Add/Edit Event', 'mfgigcal' ) . "</h2>";

	echo "<form id=\"edit_event_form\" method=\"POST\" action=\"?page=mf_gig_calendar\">";
	if ( $_GET['action'] == "edit" ) {
		echo '<input type="hidden" name="id" value="' . esc_attr( $id ) . '">';
	}
	echo "
		<input type=\"hidden\" name=\"ytd\" value=\"$ytd\">
		<table class=\"form-table\">
		
		<tr><th><label class=\"required\">" . __( 'Date', 'mfgigcal' ) . " <i>(" . __( 'required', 'mfgigcal' ) . ")</i></label></th>
		<td><div class=\"mfgig-datepicker\"></div>
		
		<div style=\"padding:1em;font-size:.8em;\">" . __( 'FROM', 'mfgigcal' ) . " <input type=\"text\" class=\"text\" name=\"start_date\" id=\"start_date\" value=\"$start_date\" /> " . __( 'TO', 'mfgigcal' ) . " 
		<input type=\"text\" class=\"text\" name=\"end_date\" id=\"end_date\" value=\"$end_date\" /></div>
		
		
		</td></tr>";


	echo "<tr>";
	echo "<th><label class=\"required\">" . __( 'Event Title', 'mfgigcal' ) . " <i>(" . __( 'required', 'mfgigcal' ) . ")</i></label></th>";
	echo "<td><input type=\"text\" class=\"text form-required\" style=\"width:400px;font-size:1.5em;\" name=\"title\" id=\"title\" value=\""; 
	if ( isset ( $mfgigcal_event )) { echo str_replace( '"', "&quot;", $mfgigcal_event->title ); }
	echo "\" /><br>&nbsp;</td>";
	echo "</tr>";

	echo "<tr>";
	echo "<th><label>" . __( 'Event Time', 'mfgigcal' ) . "</label></th>";
	echo "<td><input type=\"text\" class=\"text\" name=\"time\" id=\"time\" value=\"";
	if ( isset ( $mfgigcal_event )) { echo str_replace( '"', "&quot;", $mfgigcal_event->time ); }
	echo "\" /></td>";
	echo "</tr>";

	echo "<tr>";
	echo "<th><label>" . __( 'Event Location', 'mfgigcal' ) . "</label></th>";
	echo "<td>";

	$settings = array(
		'media_buttons' => false,
		'wpautop'       => false,
		'tinymce'       => array(
			'theme_advanced_buttons1' => 'bold,italic,underline,|,undo,redo,|,link,unlink,|,fullscreen',
			'theme_advanced_buttons2' => '',
			'height'                  => '200',
			'forced_root_block'       => 'p'
		),
		'quicktags'     => true
	);
	if ( isset ( $mfgigcal_event ) ) {
		wp_editor( $mfgigcal_event->location, "location", $settings );
	} else {
		wp_editor( "", "location", $settings );
	}
	echo "</td>";
	echo "</tr>";

	echo "<tr>";
	echo "<th><label>" . __( 'Event Details', 'mfgigcal' ) . "</label></th>";
	echo "<td>";

	$settings = array(
		'media_buttons' => true,
		'wpautop'       => false,
		'tinymce'       => array(
			'theme_advanced_buttons1' => 'bold,italic,strikethrough,|,bullist,numlist,blockquote,justifyleft,justifycenter,justifyright,link,unlink,spellchecker,fullscreen',
			'theme_advanced_buttons2' => 'formatselect,underline,justifyfull,forecolor,pastetext,pasteword,removeformat,charmap,outdent,indent,undo,redo,help',
			'height'                  => '400',
			'forced_root_block'       => 'p'
		),
		'quicktags'     => true
	);
	
	if ( isset ( $mfgigcal_event ) ) {
		wp_editor( $mfgigcal_event->details, "details", $settings );
	} else {
		wp_editor( "", "details", $settings );
	}
	echo "</td>";
	echo "</tr>";

	echo "</table>";

	echo '<p class="submit"><input type="submit" class="button-primary" name="save" value="' . __( 'Save Event', 'mfgigcal' ) . '" id="submitbutton"> <a href="?page=mf_gig_calendar&ytd=' . $ytd . '" class="button-secondary">' . __( 'Cancel', 'mfgigcal' ) . '</a>';
	echo '&nbsp;&nbsp;<span id="mfgigcal_error_message">' . __( 'Please fill in all the required information to save your event.', 'mfgigcal' ) . '</span></p></form>';
}

function mfgigcal_list_events() {
	global $wpdb;
	$mfgigcal_table = $wpdb->prefix . "mfgigcal";

	// get the dates
	$today = date( "Y-m-d" );
	
	$ytd = null;
	if ( isset ( $_GET['ytd'] ) ) {
		$ytd = mfgigcal_Clean( $_GET['ytd'] );
	}
	if ( isset ( $_POST['ytd'] ) ) {
		$ytd = mfgigcal_Clean( $_POST['ytd'] );
	}

	$sql = "SELECT * FROM $mfgigcal_table ";

	if ( $ytd == date( "Y" ) ) {
		$sql .= "WHERE (end_date >= '" . $ytd . "-01-01' AND end_date < '$today') ";
	} else if ( $ytd ) {
		$sql .= "WHERE (end_date >= '" . $ytd . "-01-01' AND start_date <= '" . $ytd . "-12-31') ";
	} else {
		$sql .= "WHERE end_date >= '$today' ";
	}
	$sql .= "ORDER BY start_date";

	$mfgigcal_events = $wpdb->get_results( $sql );

	if ( ! empty( $mfgigcal_events ) ) {

		$mfgigcal_data = mfgigcal_CalendarNav();
		$mfgigcal_data .= '<p style="text-align:right"><a href="?page=mf_gig_calendar&action=edit" class="button-primary">' . __( 'Create New Event', 'mfgigcal' ) . '</a></p>';
		$mfgigcal_data .= "<table class=\"widefat\" style=\"margin-top:10px;\">";
		$mfgigcal_data .= "<thead>";
		$mfgigcal_data .= "<tr><th>" . __( 'Event Shortcode', 'mfgigcal' ) . "</th><th class=\"event_date\">" . __( 'Date', 'mfgigcal' ) . "</th><th class=\"event_location\">" . __( 'Event', 'mfgigcal' ) . "</th><th class=\"event_details\" colspan=\"2\">" . __( 'Event Details', 'mfgigcal' ) . "</th></tr>";
		$mfgigcal_data .= "</thead>";

		foreach ( $mfgigcal_events as $mfgigcal_event ) {

			$mfgigcal_data .= "<tr><td style='white-space:nowrap;'>[mfgigcal id=$mfgigcal_event->id]</td>";
			$mfgigcal_data .= "<td class=\"event_date\">";
			$mfgigcal_data .= mfgigcal_admin_FormatDate( $mfgigcal_event->start_date, $mfgigcal_event->end_date ) . "<br />";
			$mfgigcal_data .= $mfgigcal_event->time;
			$mfgigcal_data .= "</td>";
			$mfgigcal_data .= "<td class=\"event_location\"><div class=\"event_title\">" . $mfgigcal_event->title . "</div>" . mfgigcal_admin_PrintTruncated( 80, $mfgigcal_event->location ) . "</td>";
			$mfgigcal_data .= "<td class=\"event_details\">" . mfgigcal_admin_PrintTruncated( 100, $mfgigcal_event->details ) . "</td>";

			$mfgigcal_data .= "<td class=\"buttons\" style=\"white-space:nowrap;\">";
			$mfgigcal_data .= "<a href=\"?page=mf_gig_calendar&id=$mfgigcal_event->id&action=edit&ytd=$ytd\" class=\"button-secondary\" title=\"" . __( 'Edit this event', 'mfgigcal' ) . "\">" . __( 'Edit', 'mfgigcal' ) . "</a> ";
			$mfgigcal_data .= "<a href=\"?page=mf_gig_calendar&id=$mfgigcal_event->id&action=copy\" class=\"button-secondary\" title=\"" . __( 'Create a new event based on this event', 'mfgigcal' ) . "\">" . __( 'Duplicate', 'mfgigcal' ) . "</a> ";
			$mfgigcal_data .= "<a href=\"#\" onClick=\"mfgigcal_DeleteEvent($mfgigcal_event->id);return false;\" class=\"button-secondary\" title=\"" . __( 'Delete this event', 'mfgigcal' ) . "\">" . __( 'Delete', 'mfgigcal' ) . "</a>";
			$mfgigcal_data .= "</td></tr>";

		}
	} else {
		$mfgigcal_data = mfgigcal_CalendarNav();
		$mfgigcal_data .= '<p style="text-align:right"><a href="?page=mf_gig_calendar&action=edit" class="button-primary">' . __( 'Create New Event', 'mfgigcal' ) . '</a></p>';
		$mfgigcal_data .= "<table class=\"widefat\" style=\"margin-top:10px;\">";
		$mfgigcal_data .= "<thead>";
		$mfgigcal_data .= "<tr><th class=\"event_date\">" . __( 'Date', 'mfgigcal' ) . "</th><th class=\"event_location\">" . __( 'Event', 'mfgigcal' ) . "</th><th class=\"event_details\" colspan=\"2\">" . __( 'Event Details', 'mfgigcal' ) . "</th></tr>";
		$mfgigcal_data .= "</thead>";
		$mfgigcal_data .= "<tr>
				<td colspan=\"10\" style=\"text-align:center;\">" . __( 'No events found in this range.', 'mfgigcal' ) . "</td>
			</tr>";
	}

	$mfgigcal_data .= "</table>";

	return $mfgigcal_data;
}

function mfgigcal_admin_FormatDate( $start_date, $end_date ) {

	$startArray = explode( "-", $start_date );
	$start_date = strtotime( $start_date );
	$endArray   = explode( "-", $end_date );
	$end_date   = strtotime( $end_date );

	$mfgigcal_date = "";

	if ( $start_date == $end_date ) {
		if ( $startArray[2] == "00" ) {
			$start_date    = mktime( 0, 0, 0, $startArray[1], 15, $startArray[0] );
			$mfgigcal_date .= '<span style="white-space:nowrap;">' . wp_date( "F, Y", $start_date ) . "</span>";

			return $mfgigcal_date;
		}
		$mfgigcal_date .= '<span style="white-space:nowrap;">' . wp_date( "M j, Y", $start_date ) . "</span>";

		return $mfgigcal_date;
	}

	if ( $startArray[0] == $endArray[0] ) {
		if ( $startArray[1] == $endArray[1] ) {
			$mfgigcal_date .= '<span style="white-space:nowrap;">' . wp_date( "M j", $start_date ) . "-" . wp_date( "j, Y", $end_date ) . "</span>";

			return $mfgigcal_date;
		}
		$mfgigcal_date .= '<span style="white-space:nowrap;">' . wp_date( "M j", $start_date ) . "-" . wp_date( "M j, Y", $end_date ) . "</span>";

		return $mfgigcal_date;

	}

	$mfgigcal_date .= '<span style="white-space:nowrap;">' . wp_date( "M j, Y", $start_date ) . "-" . wp_date( "M j, Y", $end_date ) . "</span>";

	return $mfgigcal_date;
}


// FEED
include_once( 'mf_gig_calendar_feed.php' );


// WIDGET
include_once( 'mf_gig_calendar_widget.php' );

// UTILITIES
function remove_wp_magic_quotes() {
	$_GET     = stripslashes_deep( $_GET );
	$_POST    = stripslashes_deep( $_POST );
	$_COOKIE  = stripslashes_deep( $_COOKIE );
	$_REQUEST = stripslashes_deep( $_REQUEST );
}

function strip_array_indices( $ArrayToStrip ) {
	foreach ( $ArrayToStrip as $objArrayItem ) {
		$NewArray[] = $objArrayItem;
	}

	return ( $NewArray );
}

function date_format_php_to_js( $sFormat ) {
	switch ( $sFormat ) {
		//Predefined WP date formats
		case 'F j, Y':
			return ( 'MM dd, yy' );
			break;
		case 'Y/m/d':
			return ( 'yy/mm/dd' );
			break;
		case 'm/d/Y':
			return ( 'mm/dd/yy' );
			break;
		case 'd/m/Y':
			return ( 'dd/mm/yy' );
			break;
	}
}

function mfgigcal_ExtractDate( $date, $format ) {

	if ( $date == "0000-00-00" || ! $date ) {
		return false;
	}

	$dateArray = explode( "-", $date );
	$date      = mktime( 0, 0, 0, $dateArray[1], $dateArray[2], $dateArray[0] );

	// special for day set to "00"
	if ( $dateArray[2] == "00" ) {
		if ( $format == "d" ) {
			return "00";
		} else {
			$date = mktime( 0, 0, 0, $dateArray[1], 15, $dateArray[0] );
		}
	}

	return wp_date( $format, $date );
}

function mfgigcal_CalendarNav( $show_title = true ) {
	global $wpdb, $post;
	$mfgigcal_table = $wpdb->prefix . "mfgigcal";

	$sql        = "SELECT DISTINCT *
			FROM $mfgigcal_table 
			WHERE start_date != '0000-00-00'
			ORDER BY start_date
			ASC
			LIMIT 1";
	$first_year = $wpdb->get_results( $sql, ARRAY_A );

	( ! empty( $first_year ) ) ? $first_year = mfgigcal_ExtractDate( $first_year[0]['start_date'], 'Y' ) : $first_year = date( "Y" );

	$sql       = "SELECT DISTINCT *
			FROM $mfgigcal_table 
			WHERE end_date != '0000-00-00'
			ORDER BY end_date
			DESC
			LIMIT 1";
	$last_year = $wpdb->get_results( $sql, ARRAY_A );

	( ! empty( $last_year ) ) ? $last_year = mfgigcal_ExtractDate( $last_year[0]['end_date'], 'Y' ) : $last_year = date( "Y" );

	if ( is_admin() ) {
		$query_prefix = "?page=mf_gig_calendar&";
		$reset_link = $query_prefix;
	} else if ( get_option( 'permalink_structure' ) ) {
		global $post;
		$query_prefix = get_permalink( get_post( $post )->id ) . "?";
		$reset_link = get_permalink( get_post( $post )->id );
	} else {
		global $post;
		$query_prefix = get_permalink( get_post( $post )->id ) . "&";
		$reset_link = $query_prefix;
	}
	
	//($query_prefix == get_permalink( get_post( $post )->id ) . "?" ) ? $reset_link = get_permalink( get_post( $post )->id ) : $reset_link = $query_prefix;
	
	$ytd = null;
	
	if ( isset ( $_GET['ytd'] ) ) {
		$ytd = mfgigcal_Clean( $_GET['ytd'] );
	}
	if ( isset ( $_POST['ytd'] ) ) {
		$ytd = mfgigcal_Clean( $_POST['ytd'] );
	}
	if ( isset ($_GET['event_id']) ) {
		$event_id = mfgigcal_Clean( $_GET['event_id'] );
	}
	
	$mfgigcal_settings = get_option( 'mfgigcal_settings' );
	if ( isset ( $mfgigcal_settings['always_use_url'] ) )  {
		if ( $mfgigcal_settings['always_use_url'] && $mfgigcal_settings['calendar_url'] && ! is_admin() ) {
			$link_prefix = $mfgigcal_settings['calendar_url'];
		}
	}

	if ( $ytd ) {
		if ( $show_title ) {
			$mfgigcal_nav = "<h2 id=\"cal_title\">" . $ytd . "</h2>";
		}
		$mfgigcal_nav .= "<div id=\"cal_nav\"><a href=\"" . $reset_link . "\">" . __( 'Upcoming', 'mfgigcal' ) . "</a> | " . __( 'Archive', 'mfgigcal' ) . ": ";
	} else if ( isset ( $event_id ) ) {
		if ( $show_title ) {
			( $mfgigcal_settings['event_title'] == "" ) ? $event_title = __( 'Event Information', 'mfgigcal' ) : $event_title = $mfgigcal_settings['event_title'];
			$mfgigcal_nav = "<h2 id=\"cal_title\">$event_title</h2>";
		}
		$mfgigcal_nav .= "<div id=\"cal_nav\"><a href=\"" . $reset_link . "\">" . __( 'Upcoming', 'mfgigcal' ) . "</a> | " . __( 'Archive', 'mfgigcal' ) . ": ";
	} else {
		if ( $show_title ) {
			( $mfgigcal_settings['upcoming_title'] == "" ) ? $upcoming_title = __( 'Upcoming Events', 'mfgigcal' ) : $upcoming_title = $mfgigcal_settings['upcoming_title'];
			$mfgigcal_nav = "<h2 id=\"cal_title\">$upcoming_title</h2>";
		}
		$mfgigcal_nav .= "<div id=\"cal_nav\"><strong>" . __( 'Upcoming', 'mfgigcal' ) . "</strong> | " . __( 'Archive', 'mfgigcal' ) . ": ";
	}

	$archive_start    = date( 'Y' );
	$years_to_display = $archive_start - $first_year + 1;
	if ( $mfgigcal_settings['archive_limit'] > 0 ) {
		$years_to_display = min( $years_to_display, $mfgigcal_settings['archive_limit'] );
	}
	$y = $archive_start;

	for ( $i = 1; $i <= $years_to_display; $i ++ ) {
		( $y == $ytd ) ? $mfgigcal_nav .= '<strong>' . $y . '</strong> ' : $mfgigcal_nav .= '<a href="' . $query_prefix . 'ytd=' . $y . '">' . $y . '</a> ';
		$y --;
	}
	$mfgigcal_nav .= "</div>";

	return $mfgigcal_nav;

}

// 


// a function I found on StackOverflow to truncate HTML...

function mfgigcal_admin_PrintTruncated( $maxLength, $input_html ) {
	$printedLength = 0;
	$position      = 0;
	$tags          = array();

	$mfgigcal_html = "";
	$input_html    = strip_tags( $input_html, "<img><b><i><br><br />" );

	while ( $printedLength < $maxLength && preg_match( '{</?([a-z]+)[^>]*>|&#?[a-zA-Z0-9]+;}', $input_html, $match, PREG_OFFSET_CAPTURE, $position ) ) {
		list( $tag, $tagPosition ) = $match[0];

		// Print text leading up to the tag.
		$str = substr( $input_html, $position, $tagPosition - $position );
		if ( $printedLength + strlen( $str ) > $maxLength ) {
			$mfgigcal_html .= substr( $str, 0, $maxLength - $printedLength );
			$printedLength = $maxLength;
			break;
		}

		$mfgigcal_html .= $str;
		$printedLength += strlen( $str );

		if ( $tag[0] == '&' ) {
			// Handle the entity.
			$mfgigcal_html .= $tag;
			$printedLength ++;
		} else {
			// Handle the tag.
			$tagName = $match[1][0];
			if ( $tag[1] == '/' ) {
				// This is a closing tag.

				$openingTag = array_pop( $tags );
				//assert($openingTag == $tagName); // check that tags are properly nested.

				$mfgigcal_html .= $tag;
			} else if ( $tag[ strlen( $tag ) - 2 ] == '/' ) {
				// Self-closing tag.
				$mfgigcal_html .= $tag;
			} else {
				// Opening tag.
				$mfgigcal_html .= $tag;
				$tags[]        = $tagName;
			}
		}

		// Continue after the tag.
		$position = $tagPosition + strlen( $tag );
	}

	// Print any remaining text.
	if ( $printedLength < $maxLength && $position < strlen( $input_html ) ) {
		$mfgigcal_html .= substr( $input_html, $position, $maxLength - $printedLength );
	}

	if ( $maxLength < strlen( $input_html ) ) {
		$mfgigcal_html .= "...";
	}

	// Close any open tags.
	while ( ! empty( $tags ) ) {
		$mfgigcal_html .= "</" . array_pop( $tags ) . ">";
	}

	return $mfgigcal_html;
}

function mfgigcal_Clean( $var ) {
	if ( strval( intval( $var ) ) == strval( $var ) ) { // we're only using numbers. Nothing else is allowed.
		return $var;
	} else {
		return false;
	}
}


// ACTIVATION - create the database table to store the information

global $mfgigcal_db_version;
$mfgigcal_db_version = "2.0";

function mfgigcal_install() {
	global $wpdb;
	global $mfgigcal_db_version;

	$table_name = $wpdb->prefix . "mfgigcal";

	$sql = "CREATE TABLE " . $table_name . " (
		id int(11) NOT NULL AUTO_INCREMENT,
		pub_date datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
		start_date date NOT NULL DEFAULT '0000-00-00',
		end_date date DEFAULT NULL,
		time text,
		title text NOT NULL,
		location text,
		details text,
		PRIMARY KEY  (id)
	) CHARACTER SET utf8 COLLATE utf8_general_ci";

	require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
	dbDelta( $sql );

	add_option( "mfgigcal_db_version", $mfgigcal_db_version );

}

register_activation_hook( __FILE__, 'mfgigcal_install' );


// UPDATE DB 
function mfgigcal_update_db_check() {
	global $mfgigcal_db_version;
	if ( get_site_option( 'mfgigcal_db_version' ) < $mfgigcal_db_version ) {
		if ( get_site_option( 'mfgigcal_db_version' ) < 2.0 ) {
			mfgigcal_update_db_charset();
		}
		update_option( "mfgigcal_db_version", $mfgigcal_db_version );
	}
}

add_action( 'plugins_loaded', 'mfgigcal_update_db_check' );


function mfgigcal_update_db_charset() {
	global $wpdb;
	$table = $wpdb->prefix . 'mfgigcal';

	$update_query = "ALTER TABLE `" . $table . "` CHANGE `time` `time` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL ,
CHANGE `title` `title` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL ,
CHANGE `location` `location` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL ,
CHANGE `details` `details` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL ;";

	$wpdb->query( $update_query );
}

?>