<?php
/**
 * Plugin Name:     Media Locations
 * Plugin URI:      PLUGIN SITE HERE
 * Description:     PLUGIN DESCRIPTION HERE
 * Author:          YOUR NAME HERE
 * Author URI:      YOUR SITE HERE
 * Text Domain:     media-locations
 * Domain Path:     /languages
 * Version:         0.1.0
 *
 * @package         Media_Locations
 */

// Your code starts here.
namespace Media_Locations;

/**
 * Convert the EXIF geographical longitude and latitude from degrees, minutes
 * and seconds to degrees format.
 *
 * @param string $coordinate The coordinate to convert to degrees format.
 * @return float Coordinate in degrees format.
 */
function wp_exif_gps_convert( $coordinate ) {
	@list( $degree, $minute, $second ) = $coordinate;
	$float = wp_exif_frac2dec( $degree ) + ( wp_exif_frac2dec( $minute ) / 60 ) + ( wp_exif_frac2dec( $second ) / 3600 );

	return ( ( is_float( $float ) || ( is_int( $float ) && $degree == $float ) ) && ( abs( $float ) <= 180 ) ) ? $float : 999;
}

/**
 * Process an image's latitude and longitude stored in EXIF data.
 *
 * @param array  $meta            Image meta data.
 * @param string $file            Path to image file.
 * @param int    $sourceImageType Type of image.
 *
 * @return array Image's meta data.
 */
function wp_read_image_metadata( $meta, $file, $sourceImageType ) {
	if ( is_callable( 'exif_read_data' ) && in_array( $sourceImageType, apply_filters( 'wp_read_image_metadata_types', array( IMAGETYPE_JPEG, IMAGETYPE_TIFF_II, IMAGETYPE_TIFF_MM ) ) ) ) {
		$exif = @exif_read_data( $file );

		$meta['location'] = array(
			'longitude' => '',
			'latitude'  => '',
		);

		if ( ! empty( $exif['GPSLongitude'] ) && count( $exif['GPSLongitude'] ) == 3 && ! empty( $exif['GPSLongitudeRef'] ) ) {
			$meta['location']['longitude'] = round( ( $exif['GPSLongitudeRef'] == 'W' ? - 1 : 1 ) * wp_exif_gps_convert( $exif['GPSLongitude'] ), 7 );
		}

		if ( ! empty( $exif['GPSLatitude'] ) && count( $exif['GPSLatitude'] ) == 3 && ! empty( $exif['GPSLatitudeRef'] ) ) {
			$meta['location']['latitude'] = round( ( $exif['GPSLatitudeRef'] == 'S' ? - 1 : 1 ) * wp_exif_gps_convert( $exif['GPSLatitude'] ), 7 );
		}
	}

	return $meta;
}
add_filter( 'wp_read_image_metadata', __NAMESPACE__ . '\wp_read_image_metadata', 10, 3 );

/**
 * Display a map mode in the admin.
 */
function admin_init() {
	if ( ! isset( $_GET['mode'] ) || 'map' !== wp_unslash( $_GET['mode'] ) ) {
		return;
	}

	$title       = __( 'Media Library' );
	$parent_file = 'upload.php';

	require_once( ABSPATH . 'wp-admin/admin-header.php' );
	?>
	<div class="wrap" id="wp-media-locations" data-search="<?php _admin_search_query(); ?>">
		<h1 class="wp-heading-inline"><?php echo esc_html( $title ); ?></h1>

		<hr class="wp-header-end">

		<div class="error hide-if-js">
			<p>
				<?php
				printf(
				/* translators: %s: list view URL */
					__( 'The map view for the Media Library requires JavaScript. <a href="%s">Switch to the list view</a>.' ),
					'upload.php?mode=list'
				);
				?>
			</p>
		</div>
	</div>
	<?php
	include( ABSPATH . 'wp-admin/admin-footer.php' );
	exit;
}
add_action( 'admin_init', __NAMESPACE__ . '\admin_init' );