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
