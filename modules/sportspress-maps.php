<?php
/*
Plugin Name: SportsPress Maps
Plugin URI: http://themeboy.com/
Description: Integrate OpenStreetMap and GoogleMaps to SportsPress.
Author: ThemeBoy
Author URI: http://themeboy.com/
Version: 2.7
*/

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'SportsPress_Maps' ) ) :

/**
 * Main SportsPress Maps Class
 *
 * @class SportsPress_Maps
 * @version	2.7
 */
 
 class SportsPress_Maps {

	/**
	 * Constructor
	 */
	public function __construct() {
		// Define constants
		$this->define_constants();
		$this->id    = 'general';

		// Actions
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_styles' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_scripts' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'frontend_scripts' ) );
		add_action( 'sp_venue_show_openstreetmap', array( $this, 'show_venue_openstreetmap' ), 10, 5 );
		add_action( 'sp_venue_show_googlemaps', array( $this, 'show_venue_googlemaps' ), 10, 5 );
		add_action( 'sportspress_admin_field_maps', array( $this, 'maps_setting' ) );
		add_action( 'sportspress_settings_save_' . $this->id, array( $this, 'save' ) );

		// Filters
		add_filter( 'sportspress_general_options', array( $this, 'add_general_options' ) );

	}
	
	/**
	 * Define constants.
	*/
	private function define_constants() {
		if ( !defined( 'SP_OPENSTREETMAP_VERSION' ) )
			define( 'SP_OPENSTREETMAP_VERSION', '2.7' );

		if ( !defined( 'SP_OPENSTREETMAP_URL' ) )
			define( 'SP_OPENSTREETMAP_URL', plugin_dir_url( __FILE__ ) );

		if ( !defined( 'SP_OPENSTREETMAP_DIR' ) )
			define( 'SP_OPENSTREETMAP_DIR', plugin_dir_path( __FILE__ ) );
	}
	
	/**
	 * Enqueue admin styles
	 */
	public function admin_styles( $hook ) {
		$screen = get_current_screen();
		if ( in_array( $screen->id, sp_get_screen_ids() ) ) {
			wp_enqueue_style( 'leaflet_stylesheet', SP()->plugin_url() . '/assets/css/leaflet.css', array(), '1.4.0' );
			wp_enqueue_style( 'control-geocoder', SP()->plugin_url() . '/assets/css/Control.Geocoder.css', array() );
		}
	}
	
	/**
	 * Enqueue admin scripts
	 */
	public function admin_scripts( $hook ) {
		$screen = get_current_screen();
		$maps_provider = get_option( 'sportspress_maps_provider', 'openstreetmap' );
		$googlemaps_ip = get_option( 'sportspress_googlemaps_api' );
		if ( in_array( $screen->id, sp_get_screen_ids() ) ) {
			if ( $maps_provider == 'googlemaps' ) {
				wp_register_script( 'google-maps', 'https://maps.googleapis.com/maps/api/js?key='.$googlemaps_ip.'&sensor=false&libraries=places' );
				wp_register_script( 'jquery-locationpicker', SP()->plugin_url() . '/assets/js/locationpicker.jquery.js', array( 'jquery', 'google-maps' ), '0.1.6', true );
				wp_register_script( 'sportspress-admin-locationpicker', SP()->plugin_url() . '/assets/js/admin/locationpicker.js', array( 'jquery', 'google-maps', 'jquery-locationpicker' ), SP_VERSION, true );
			}else{
				wp_register_script( 'leaflet_js', SP()->plugin_url() . '/assets/js/leaflet.js', array(), '1.4.0' );
				wp_register_script( 'control-geocoder', SP()->plugin_url() . '/assets/js/Control.Geocoder.js', array( 'leaflet_js' ) );
				wp_register_script( 'sportspress-admin-geocoder', SP()->plugin_url() . '/assets/js/admin/sp-geocoder.js', array( 'leaflet_js', 'control-geocoder' ), SP_VERSION, true );
			}
		}
		// Edit venue pages
	    if ( in_array( $screen->id, array( 'edit-sp_venue' ) ) ) {
			if ( $maps_provider == 'googlemaps' ) {
				wp_enqueue_script( 'google-maps' );
				wp_enqueue_script( 'jquery-locationpicker' );
				wp_enqueue_script( 'sportspress-admin-locationpicker' );
			}else{
				wp_enqueue_script( 'leaflet_js' );
				wp_enqueue_script( 'control-geocoder' );
			}
		}
	}
	
	/**
	 * Enqueue frontend scripts
	 */
	public function frontend_scripts() {
		if( ( is_single() || is_tax() ) && get_post_type()=='sp_event' ){
			wp_enqueue_style( 'leaflet_stylesheet', SP()->plugin_url() . '/assets/css/leaflet.css', array(), '1.4.0' );
			wp_enqueue_script( 'leaflet_js', SP()->plugin_url() . '/assets/js/leaflet.js', array(), '1.4.0' );
		}
	}
	
	/**
	 * Integrate OpenStreetMap (Show Venue)
	 *
	 * @return mix
	 */
	public function show_venue_openstreetmap( $latitude, $longitude, $address, $zoom, $maptype ) {
		$lat = abs($latitude);
		$lat_deg = floor($lat);
		$lat_sec = ($lat - $lat_deg) * 3600;
		$lat_min = floor($lat_sec / 60);
		$lat_sec = floor($lat_sec - ($lat_min * 60));
		$lat_dir = $latitude > 0 ? 'N' : 'S';

		$lon = abs($longitude);
		$lon_deg = floor($lon);
		$lon_sec = ($lon - $lon_deg) * 3600;
		$lon_min = floor($lon_sec / 60);
		$lon_sec = floor($lon_sec - ($lon_min * 60));
		$lon_dir = $longitude > 0 ? 'E' : 'W';
		?>
		<a href="https://www.google.com.au/maps/place/<?php echo urlencode("{$lat_deg}°{$lat_min}'{$lat_sec}\"{$lat_dir}").'+'.urlencode("{$lon_deg}°{$lon_min}'{$lon_sec}\"{$lon_dir}"); ?>/@<?php echo $latitude; ?>,<?php echo $longitude; ?>,<?php echo $zoom; ?>z" target="_blank"><div id="sp_openstreetmaps_container" style="width: 100%; height: 320px"></div></a>
	<script>
    // position we will use later
    var lat = <?php echo $latitude; ?>;
    var lon = <?php echo $longitude; ?>;
    // initialize map
    map = L.map('sp_openstreetmaps_container', { zoomControl:false }).setView([lat, lon], <?php echo $zoom; ?>);
    // set map tiles source
    <?php if ( 'satellite' === $maptype ) { ?>
		L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', {
		  attribution: 'Tiles &copy; Esri &mdash; Source: Esri, i-cubed, USDA, USGS, AEX, GeoEye, Getmapping, Aerogrid, IGN, IGP, UPR-EGP, and the GIS User Community',
		  maxZoom: 18,
		}).addTo(map);
	<?php }else{ ?>
		L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
		  attribution: 'Map data &copy; <a href="https://www.openstreetmap.org/">OpenStreetMap</a> contributors',
		  maxZoom: 18,
		}).addTo(map);
	<?php } ?>
    // add marker to the map
    marker = L.marker([lat, lon]).addTo(map);
	map.dragging.disable();
	map.touchZoom.disable();
	map.doubleClickZoom.disable();
	map.scrollWheelZoom.disable();
  </script>
		<?php
	}
	
	/**
	 * Integrate GoogleMaps (View Venue)
	 *
	 * @return mix
	 */
	public function show_venue_googlemaps( $latitude, $longitude, $address, $zoom, $maptype ) { ?>
		<div class="sp-google-map-container">
		  <iframe
			class="sp-google-map<?php if ( is_tax( 'sp_venue' ) ): ?> sp-venue-map<?php endif; ?>"
			width="600"
			height="320"
			frameborder="0" style="border:0"
			src="//tboy.co/maps_embed?q=<?php echo $address; ?>&amp;center=<?php echo $latitude; ?>,<?php echo $longitude; ?>&amp;zoom=<?php echo $zoom; ?>&amp;maptype=<?php echo $maptype; ?>" allowfullscreen>
		  </iframe>
		  <a href="https://www.google.com/maps/place/<?php echo $address; ?>/@<?php echo $latitude; ?>,<?php echo $longitude; ?>,<?php echo $zoom; ?>z" target="_blank" class="sp-google-map-link"></a>
		</div>
	<?php
	}
	
	/**
	 * Save settings
	 */
	public function save() {
		if ( isset( $_POST['sportspress_maps_provider'] ) )
	    	update_option( 'sportspress_maps_provider', $_POST['sportspress_maps_provider'] );
		
		if ( isset( $_POST['sportspress_googlemaps_api'] ) )
	    	update_option( 'sportspress_googlemaps_api', $_POST['sportspress_googlemaps_api'] );
	}
	
	/**
	 * Add option to SportsPress General Settings.
	 */
	public function add_general_options( $settings ) {
		$settings[] = array( 'type' => 'maps' );

		return $settings;
	}
	
	/**
	 * Maps settings
	 *
	 * @access public
	 * @return void
	 */
	public function maps_setting() {
		$maps_provider = get_option( 'sportspress_maps_provider', 'openstreetmap' );
		$googlemaps_api = get_option( 'sportspress_googlemaps_api' );

		if ( $maps_provider != 'googlemaps' ) {
			$hide_api = 'style="display:none;"';
			$required = null;
		} else {
			$hide_api = null;
			$required = ' required';
		}
		?>
		<tr valign="top">
			<th scope="row" class="titledesc">
				<label for="sportspress_maps_provider">Maps</label>
			</th>
			<td class="forminp forminp-radio">
				<fieldset>
					<ul>
						<li><label><input name="sportspress_maps_provider" id="openstreetmap" value="openstreetmap" type="radio" <?php checked( $maps_provider, 'openstreetmap' ); ?>> OpenStreetMap</label></li>
						<li><label><input name="sportspress_maps_provider" id="googlemaps" value="googlemaps" type="radio" <?php checked( $maps_provider, 'googlemaps' ); ?>> GoogleMaps</label></li>
					</ul>
				</fieldset>
				<div id="googlemaps_ip_field" <?php echo $hide_api; ?>><input name="sportspress_googlemaps_api" id="sportspress_googlemaps_api" type="text" value="<?php echo $googlemaps_api; ?>"<?php echo $required; ?>> <span class="description">Add your own GoogleMaps API. For more info check <a target="_blank" href="https://developers.google.com/maps/documentation/javascript/get-api-key">HERE</a>.</span></div>
			</td>
		</tr>
		<?php
	}
	
}

endif;

	new SportsPress_Maps();
