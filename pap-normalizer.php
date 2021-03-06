<?php
/**
 * Plugin Name: PAP Normalizer
 * Plugin URI: https://github.com/gitlost/pap-normalizer
 * Description: Patch-as-plugin that adds the Normalizer class to WP.
 * Version: 1.0.0
 * Author: gitlost
 * Author URI: https://profiles.wordpress.org/gitlost
 * License: GPLv2
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) exit;

// The following could go in "wp-includes/compat.php".
if ( 'UTF-8' === _canonical_charset( get_option( 'blog_charset' ) ) ) {

	if ( ! function_exists( 'normalizer_is_normalized' ) ) :
		require dirname( __FILE__ ) . '/Symfony/Normalizer.php'; // require ABSPATH . WPINC . '/Symfony/Normalizer.php';

		/**
		 * Compat class to mimic Intl extension class Normalizer.
		 * Maps internal _Normalizer class included above.
		 * Done this way to enable testing.
		 *
		 * @since 4.7
		 */
		class Normalizer {
			const NONE = _Normalizer::NONE;
			const FORM_D = _Normalizer::FORM_D;
			const FORM_KD = _Normalizer::FORM_KD;
			const FORM_C = _Normalizer::FORM_C;
			const FORM_KC = _Normalizer::FORM_KC;
			const NFD = _Normalizer::NFD;
			const NFKD = _Normalizer::NFKD;
			const NFC = _Normalizer::NFC;
			const NFKC = _Normalizer::NFKC;

			static function isNormalized( $s, $form = self::NFC ) {
				return _Normalizer::isNormalized( $s, $form );
			}

			static function normalize( $s, $form = self::NFC ) {
				return _Normalizer::normalize( $s, $form );
			}
		}

		/**
		 * Compat function to mimic normalizer_is_normalized().
		 *
		 * @since 4.7
		 *
		 * @param string $str  The UTF-8 string to check if in specified normalization form.
		 * @param int    $form Optional. The normalization form to check. Default Normalizer::FORM_C.
		 * @return bool True if in the specified normalization form, else false.
		 */
		function normalizer_is_normalized( $s, $form = Normalizer::FORM_C ) {
			return _Normalizer::isNormalized( $s, $form );
		}

		/**
		 * Compat function to mimic normalizer_normalize().
		 *
		 * @since 4.7
		 *
		 * @param string $str  The UTF-8 string to normalize into the specified normalization form.
		 * @param int    $form Optional. The normalization form to normalize to. Default Normalizer::FORM_C.
		 * @return string|bool The normalized string, or false on error (invalid UTF-8).
		 */
		function normalizer_normalize( $s, $form = Normalizer::FORM_C ) {
			return _Normalizer::normalize( $s, $form );
		}
	endif;
}
