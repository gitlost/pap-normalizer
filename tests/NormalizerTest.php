<?php
/**
 * Test _Normalizer.
 */

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Based on https://github.com/symfony/polyfill/blob/master/tests/Intl/Normalizer/NormalizerTest.php
 *
 * @author Nicolas Grekas <p@tchwork.com>
 */

//namespace Symfony\Polyfill\Tests\Intl\Normalizer;

//use Symfony\Polyfill\Intl\Normalizer\Normalizer as pn;
//use Normalizer as in;

/**
 * @group normalizer
 */
class Tests_Normalizer extends WP_UnitTestCase {

	static $new_8_0_0 = array( 0x8e3, 0xa69e, /*0xa69f,*/ 0xfe2e, 0xfe2f, 0x111ca, 0x1172b, ); // Combining class additions UCD 8.0.0 over 7.0.0
	static $new_8_0_0_regex = '';
	static $new_9_0_0 = array( // Combining class additions UCD 9.0.0 over 8.0.0
		0x8d4, 0x8d5, 0x8d6, 0x8d7, 0x8d8, 0x8d9, 0x8da, 0x8db, 0x8dc, 0x8dd, 0x8de, 0x8df, 0x8e0, 0x8e1,
		0x1dfb,
		0x11442, 0x11446, 0x11c3f,
		0x1e000, 0x1e001, 0x1e002, 0x1e003, 0x1e004, 0x1e005, 0x1e006,
		0x1e008, 0x1e009, 0x1e00a, 0x1e00b, 0x1e00c, 0x1e00d, 0x1e00e, 0x1e00f, 0x1e010, 0x1e011, 0x1e012, 0x1e013, 0x1e014, 0x1e015, 0x1e016, 0x1e017, 0x1e018,
		0x1e01b, 0x1e01c, 0x1e01d, 0x1e01e, 0x1e01f, 0x1e020, 0x1e021, 0x1e023, 0x1e024, 0x1e026, 0x1e027, 0x1e028, 0x1e029, 0x1e02a,
		0x1e944, 0x1e945, 0x1e946, 0x1e947, 0x1e948, 0x1e949, 0x1e94a,
	);
	static $new_9_0_0_regex = '';
	static $pcre_version = PCRE_VERSION;
	static $true = true;
	static $false = false;

	static function wpSetUpBeforeClass() {

		require dirname( dirname( __FILE__ ) ) . '/Symfony/Normalizer.php';

		$icu_version = self::icu_version();

		if ( version_compare( $icu_version, '56.1', '<' ) ) {
			// Enable if using intl built with icu less than 56.1
			self::$new_8_0_0_regex = '/' . implode( '|', array_map( __CLASS__.'::chr', self::$new_8_0_0 ) ) . '/';
		}
		// Always set for the mo as icu for Unicode 9.0.0 not yet released as of September 2016.
		self::$new_9_0_0_regex = '/' . implode( '|', array_map( __CLASS__.'::chr', self::$new_9_0_0 ) ) . '/';

		self::$pcre_version = substr( PCRE_VERSION, 0, strspn( PCRE_VERSION, '0123456789.' ) );

		// Normalizer::isNormalized() returns an integer on HHVM and a boolean on PHP
		list( self::$true, self::$false ) = defined( 'HHVM_VERSION' ) ? array( 1, 0 ) : array( true, false );
	}

	/**
	 * Get ICU version of loaded "intl" extension.
	 */
	static function icu_version() {
		ob_start();
		phpinfo( INFO_MODULES );
		$lines = explode( "\n", ob_get_clean() );
		foreach ( $lines as $line ) {
			if ( preg_match( '/icu +version +=> +(\d+\.\d+(?:\.\d+)?)/i', $line, $matches ) ) {
				return $matches[1];
			}
		}
		return false;
	}

	/**
	 * @ticket normalizer_constants
	 * @requires extension intl
	 */
    function test_constants() {

		if ( class_exists( 'Normalizer' ) ) {
			$rpn = new ReflectionClass( '_Normalizer' );
			$rin = new ReflectionClass( 'Normalizer' );

			$rpn = $rpn->getConstants();
			$rin = $rin->getConstants();

			ksort( $rpn );
			ksort( $rin );

			$this->assertSame( $rin, $rpn );
		} else {
			$this->markTestSkipped( 'Tests_Normalizer::test_constants: no class Normalizer' );
		}
    }

	/**
	 * @ticket normalizer_props
	 */
    function test_props() {

        $rpn = new ReflectionClass( '_Normalizer' );

		$props = $rpn->getStaticProperties();
		$this->assertArrayHasKey( 'ASCII', $props );

		$ascii = array_values( array_unique( str_split( $props['ASCII'] ) ) );
		$this->assertSame( 0x80, count( $ascii ) );
		for ( $i = 0; $i < 0x80; $i++ ) {
			$this->assertSame( true, in_array( chr( $i ), $ascii ) );
		}

		if ( version_compare( PHP_VERSION, '5.3', '>=' ) ) { // For availability of ReflectionClass::setAccessible()
			$prop = $rpn->getProperty( 'D' );
			$prop->setAccessible( true );
			$prop->setValue( null );

			$prop = $rpn->getProperty( 'C' );
			$prop->setAccessible( true );
			$prop->setValue( null );

			$this->assertSame( "\xc3\xbc", _Normalizer::normalize( "u\xcc\x88" ) );
		}
    }

    /**
	 * @ticket normalizer_is_normalized
     */
    function test_is_normalized() {

        $c = 'déjà';
        $d = _Normalizer::normalize( $c, _Normalizer::NFD );

        $this->assertSame( self::$true, _Normalizer::isNormalized( '' ) );
        $this->assertSame( self::$true, _Normalizer::isNormalized( 'abc' ) );
        $this->assertSame( self::$true, _Normalizer::isNormalized( $c ) );
        $this->assertSame( self::$true, _Normalizer::isNormalized( $c, _Normalizer::NFC ) );
        $this->assertSame( self::$false, _Normalizer::isNormalized( $c, _Normalizer::NFD ) );
        $this->assertSame( self::$false, _Normalizer::isNormalized( $d, _Normalizer::NFC ) );
        $this->assertSame( self::$false, _Normalizer::isNormalized( "\xFF" ) );

        $this->assertSame( self::$true, _Normalizer::isNormalized( $d, _Normalizer::NFD ) );
		$this->assertSame( self::$false, _Normalizer::isNormalized( "u\xcc\x88", _Normalizer::NFC ) ); // u umlaut.
		$this->assertSame( self::$false, _Normalizer::isNormalized( "u\xcc\x88\xed\x9e\xa0", _Normalizer::NFC ) ); // u umlaut + Hangul

		if ( class_exists( 'Normalizer' ) ) {
        	$this->assertSame( $d, Normalizer::normalize( $c, Normalizer::NFD ) );

        	$this->assertSame( Normalizer::isNormalized( '' ), _Normalizer::isNormalized( '' ) );
        	$this->assertSame( Normalizer::isNormalized( 'abc' ), _Normalizer::isNormalized( 'abc' ) );
        	$this->assertSame( Normalizer::isNormalized( $c ), _Normalizer::isNormalized( $c ) );
        	$this->assertSame( Normalizer::isNormalized( $c, Normalizer::NFC ), _Normalizer::isNormalized( $c, _Normalizer::NFC ) );
        	$this->assertSame( Normalizer::isNormalized( $c, Normalizer::NFD ), _Normalizer::isNormalized( $c, _Normalizer::NFD ) );
        	$this->assertSame( Normalizer::isNormalized( $d, Normalizer::NFC ), _Normalizer::isNormalized( $d, _Normalizer::NFC ) );
        	$this->assertSame( Normalizer::isNormalized( "\xFF" ), _Normalizer::isNormalized( "\xFF" ) );

        	$this->assertSame( self::$true, Normalizer::isNormalized( $d, Normalizer::NFD ) );
			$this->assertSame( self::$false, Normalizer::isNormalized( "u\xcc\x88", Normalizer::NFC ) ); // u umlaut.
			$this->assertSame( self::$false, Normalizer::isNormalized( "u\xcc\x88\xed\x9e\xa0", Normalizer::NFC ) ); // u umlaut + Hangul
		}
    }

    /**
	 * @ticket normalizer_normalize
     */
    function test_normalize() {

		if ( class_exists( 'Normalizer' ) ) {
			$c = Normalizer::normalize( 'déjà', _Normalizer::NFC ).Normalizer::normalize( '훈쇼™', _Normalizer::NFD );
			$this->assertSame( $c, _Normalizer::normalize( $c, _Normalizer::NONE ) );
		}
        $c = _Normalizer::normalize( 'déjà', _Normalizer::NFC )._Normalizer::normalize( '훈쇼™', _Normalizer::NFD );
        $this->assertSame( $c, _Normalizer::normalize( $c, _Normalizer::NONE ) );
        if ( class_exists( 'Normalizer' ) ) $this->assertSame( $c, Normalizer::normalize( $c, Normalizer::NONE ) );

        $c = 'déjà 훈쇼™';
        $d = _Normalizer::normalize( $c, _Normalizer::NFD );
        $kc = _Normalizer::normalize( $c, _Normalizer::NFKC );
        $kd = _Normalizer::normalize( $c, _Normalizer::NFKD );

        $this->assertSame( '', _Normalizer::normalize( '' ) );
		if ( class_exists( 'Normalizer' ) ) {
        	$this->assertSame( $c, Normalizer::normalize( $d ) );
        	$this->assertSame( $c, Normalizer::normalize( $d, Normalizer::NFC ) );
        	$this->assertSame( $d, Normalizer::normalize( $c, Normalizer::NFD ) );
        	$this->assertSame( $kc, Normalizer::normalize( $d, Normalizer::NFKC ) );
        	$this->assertSame( $kd, Normalizer::normalize( $c, Normalizer::NFKD ) );
		}

        $this->assertSame( self::$false, _Normalizer::normalize( $c, -1 ) );
        $this->assertFalse( _Normalizer::normalize( "\xFF" ) );
    }

	/**
	 * @ticket normalizer_args_compat
	 * @dataProvider data_args_compat
	 * @requires extension intl
	 */
	function test_args_compat( $string ) {

		if ( class_exists( 'Normalizer' ) ) {
			$forms = array( 0, -1, 6, -2, PHP_INT_MAX, -PHP_INT_MAX, Normalizer::NONE, Normalizer::NFD, Normalizer::NFKD, Normalizer::NFC, Normalizer::NFKD );

			foreach ( $forms as $form ) {
				$is_normalized = Normalizer::isNormalized( $string, $form );
				$normalize = Normalizer::normalize( $string, $form );
				$_is_normalized = _Normalizer::isNormalized( $string, $form );
				$_normalize = _Normalizer::normalize( $string, $form );

				$this->assertSame( $is_normalized, $_is_normalized );
				$this->assertSame( $normalize, $_normalize );
			}
		} else {
			$this->markTestSkipped( 'Tests_Normalizer::test_args_compat: no class Normalizer' );
		}
	}

	function data_args_compat() {
		return array(
			array( '' ),
			array( 'a' ), array( "\x80" ), array( "\xc2" ), array( "\xe0" ), array( "\xf0" ),
			array( "\xc2\x80" ), array( "\xc0\x80" ), array( "\xc2\x7f" ), array( "\xc2\xc0" ), array( "\xdf\xc0" ), array( "\xe0\x80" ), array( "\xf0\x80" ),
			array( "\xe0\x80\x80" ), array( "\xe0\x9f\x80" ), array( "\xed\x80\xbf" ), array( "\xef\xbf\xc0" ), array( "\xf0\x80\x80" ),
			array( "\xf0\x80\x80\x80" ), array( "\xf0\x8f\x80\x80" ), array( "\xf1\xc0\x80\x80" ), array( "\xf2\x8f\xbf\xc2" ), array( "\xf4\x90\xbf\xbf" ),
			array( 0 ), array( 2 ), array( -1 ), array( true ), array( false ), array( 0.0 ), array( '0' ), array( null ),
		);
	}

    /**
	 * @ticket normalizer_conformance_9_0_0
     */
    function test_conformance_9_0_0() {

        $t = file( dirname( __FILE__ ) . '/UCD-9.0.0/NormalizationTest.txt' );
        $c = array();

		// From NormalizationTest.txt header:

		# Format:
		#
		#   Columns (c1, c2,...) are separated by semicolons
		#   They have the following meaning:
		#      source; NFC; NFD; NFKC; NFKD
		#   Comments are indicated with hash marks
		#   Each of the columns may have one or more code points.
		#
		# CONFORMANCE:
		# 1. The following invariants must be true for all conformant implementations
		#
		#    NFC
		#      c2 ==  toNFC(c1) ==  toNFC(c2) ==  toNFC(c3)
		#      c4 ==  toNFC(c4) ==  toNFC(c5)
		#
		#    NFD
		#      c3 ==  toNFD(c1) ==  toNFD(c2) ==  toNFD(c3)
		#      c5 ==  toNFD(c4) ==  toNFD(c5)
		#
		#    NFKC
		#      c4 == toNFKC(c1) == toNFKC(c2) == toNFKC(c3) == toNFKC(c4) == toNFKC(c5)
		#
		#    NFKD
		#      c5 == toNFKD(c1) == toNFKD(c2) == toNFKD(c3) == toNFKD(c4) == toNFKD(c5)
		#
		# 2. For every code point X assigned in this version of Unicode that is not specifically
		#    listed in Part 1, the following invariants must be true for all conformant
		#    implementations:
		#
		#      X == toNFC(X) == toNFD(X) == toNFKC(X) == toNFKD(X)

        foreach ( $t as $line_num => $line ) {
			$line_num++;
            $t = explode( '#', $line );
            $t = explode( ';', $t[0] );

            if ( 6 === count( $t ) ) {
                foreach ( $t as $k => $s ) {
                    $t = explode( ' ', $s );
                    $t = array_map( 'hexdec', $t );
                    $t = array_map( __CLASS__.'::chr', $t );
                    $c[$k] = implode( '', $t );
                }
				array_unshift( $c, '' ); // Make 1-based like in NormalizationTest.txt header.

				$this->assertSame( self::$true, _Normalizer::isNormalized( $c[2], _Normalizer::NFC ), "$line_num: {$line}c[2]=" . bin2hex( $c[2] ) );
				$this->assertSame( $c[2], _Normalizer::normalize( $c[1], _Normalizer::NFC ) );
				$this->assertSame( $c[2], _Normalizer::normalize( $c[2], _Normalizer::NFC ) );
				$this->assertSame( $c[2], _Normalizer::normalize( $c[3], _Normalizer::NFC ) );
				$this->assertSame( $c[4], _Normalizer::normalize( $c[4], _Normalizer::NFC ) );
				$this->assertSame( $c[4], _Normalizer::normalize( $c[5], _Normalizer::NFC ) );

				$this->assertSame( $c[3], _Normalizer::normalize( $c[1], _Normalizer::NFD ), "$line_num: {$line}c[3]=" . bin2hex( $c[3] ) );
				$this->assertSame( $c[3], _Normalizer::normalize( $c[2], _Normalizer::NFD ) );
				$this->assertSame( $c[3], _Normalizer::normalize( $c[3], _Normalizer::NFD ) );
				$this->assertSame( $c[5], _Normalizer::normalize( $c[4], _Normalizer::NFD ) );
				$this->assertSame( $c[5], _Normalizer::normalize( $c[5], _Normalizer::NFD ) );

				$this->assertSame( $c[4], _Normalizer::normalize( $c[1], _Normalizer::NFKC ), "$line_num: {$line}c[4]=" . bin2hex( $c[4] ) );
				$this->assertSame( $c[4], _Normalizer::normalize( $c[2], _Normalizer::NFKC ) );
				$this->assertSame( $c[4], _Normalizer::normalize( $c[3], _Normalizer::NFKC ) );
				$this->assertSame( $c[4], _Normalizer::normalize( $c[4], _Normalizer::NFKC ) );
				$this->assertSame( $c[4], _Normalizer::normalize( $c[5], _Normalizer::NFKC ) );

				$this->assertSame( $c[5], _Normalizer::normalize( $c[1], _Normalizer::NFKD ) );
				$this->assertSame( $c[5], _Normalizer::normalize( $c[2], _Normalizer::NFKD ) );
				$this->assertSame( $c[5], _Normalizer::normalize( $c[3], _Normalizer::NFKD ) );
				$this->assertSame( $c[5], _Normalizer::normalize( $c[4], _Normalizer::NFKD ) );
				$this->assertSame( $c[5], _Normalizer::normalize( $c[5], _Normalizer::NFKD ) );
            }
        }
    }

	/**
	 * @ticket wp_is_valid_utf8_true
	 * @dataProvider data_is_valid_utf8_true
	 */
	function test_is_valid_utf8_true( $str ) {
		$this->assertTrue( wp_is_valid_utf8( $str ) );
		if ( version_compare( self::$pcre_version, '7.3', '>=' ) && version_compare( self::$pcre_version, '8.32', '!=' ) ) { // RFC 3629 compliant and without 8.32 regression (rejecting non-chars).
			$this->assertTrue( 1 === preg_match( '//u', $str ) );
		}
		if ( version_compare( PHP_VERSION, '5.3.4', '>=' ) ) { // RFC 3629 compliant.
			$this->assertTrue( '' === $str || '' !== htmlspecialchars( $str, ENT_NOQUOTES, 'UTF-8' ) );
		}
		$this->assertTrue( 0 === preg_match( WP_REGEX_IS_INVALID_UTF8_NOVERBS, $str ) );
		if ( version_compare( self::$pcre_version, '7.3', '>=' ) ) { // Verbs available.
			$this->assertTrue( 0 === preg_match( WP_REGEX_IS_INVALID_UTF8, $str ) );
		}
	}

	function data_is_valid_utf8_true() {
		$ret = array(
			array( "\x00" ), array( "a" ), array( "\x7f" ), array( "a\x7f" ), array( "\xc2\x80" ),
			array( "\xdf\xaf" ), array( "a\xdf\xbf" ), array( "\xdf\xbfb" ), array( "a\xde\xbfb" ), array( "\xe0\xa0\x80" ),
			array( "\xef\xbf\xbf" ), array( "a\xe1\x80\x80" ), array( "\xef\xb7\x90b" ), array( "a\xef\xbf\xafb" ), array( "\xf0\x90\x80\x80" ),
			array( "\xf4\x8f\xbf\xbf" ), array( "a\xf1\x80\x80\x80" ), array( "\xf2\x80\x80\x80b" ), array( "a\xf3\xbf\xbf\xbfb" ), array( "" ),
		);

		// From "tests/phpunit/tests/formatting/SeemsUtf8.php", "tests/phpunit/data/formatting/utf-8/utf-8.txt".
		$utf8_strings = array(
			array( "\xe7\xab\xa0\xe5\xad\x90\xe6\x80\xa1" ),
			array( "\x46\x72\x61\x6e\xc3\xa7\x6f\x69\x73\x20\x54\x72\x75\x66\x66\x61\x75\x74" ),
			array( "\xe1\x83\xa1\xe1\x83\x90\xe1\x83\xa5\xe1\x83\x90\xe1\x83\xa0\xe1\x83\x97\xe1\x83\x95\xe1\x83\x94\xe1\x83\x9a\xe1\x83\x9d" ),
			array( "\x42\x6a\xc3\xb6\x72\x6b\x20\x47\x75\xc3\xb0\x6d\x75\x6e\x64\x73\x64\xc3\xb3\x74\x74\x69\x72" ),
			array( "\xe5\xae\xae\xe5\xb4\x8e\xe3\x80\x80\xe9\xa7\xbf" ),
			array( "\xf0\x9f\x91\x8d" ),
		);

		$ret = array_merge( $ret, $utf8_strings );
		return $ret;
	}

	/**
	 * @ticket wp_is_valid_utf8_false
	 * @dataProvider data_is_valid_utf8_false
	 */
	function test_is_valid_utf8_false( $str ) {
		$this->assertFalse( wp_is_valid_utf8( $str ) );
		if ( version_compare( self::$pcre_version, '7.3', '>=' ) && version_compare( self::$pcre_version, '8.32', '!=' ) ) { // RFC 3629 compliant and without 8.32 regression (rejecting non-chars).
			$this->assertFalse( 1 === preg_match( '//u', $str ) );
		}
		if ( version_compare( PHP_VERSION, '5.3.4', '>=' ) ) { // RFC 3629 compliant.
			$this->assertFalse( '' === $str || '' !== htmlspecialchars( $str, ENT_NOQUOTES, 'UTF-8' ) );
		}
		$this->assertFalse( 0 === preg_match( WP_REGEX_IS_INVALID_UTF8_NOVERBS, $str ) );
		if ( version_compare( self::$pcre_version, '7.3', '>=' ) ) { // Verbs available.
			$this->assertFalse( 0 === preg_match( WP_REGEX_IS_INVALID_UTF8, $str ) );
		}
	}

	function data_is_valid_utf8_false() {
		$ret = array(
			array( "\x80" ), array( "\xff" ), array( "a\x81" ), array( "\x83b" ), array( "a\x81b" ),
			array( "ab\xff"), array( "\xc2\x7f" ), array( "\xc0\xb1" ), array( "\xc1\x81" ), array( "a\xc2\xc0" ),
			array( "a\xd0\x7fb" ), array( "ab\xdf\xc0" ), array( "\xe2\x80" ), array( "a\xe2\x80" ), array( "a\xe2\x80b" ),
			array( "\xf1\x80" ), array( "\xe1\x7f\x80" ), array( "\xe0\x9f\x80" ), array( "\xed\xa0\x80" ), array( "\xef\x7f\x80" ),
			array( "\xef\xbf\xc0" ), array( "\xc2\xa0\x80" ), array( "\xf0\x90\x80" ), array( "\xe2\xa0\x80\x80" ), array( "\xf5\x80\x80\x80" ),
			array( "\xf0\x8f\x80\x80" ), array( "\xf4\x90\x80\x80" ), array( "\xf5\x80\x80\x80\x80" ), array( "a\xf5\x80\x80\x80\x80" ), array( "a\xf5\x80\x80\x80\x80b" ),
			array( "a\xc2\x80\x80b" ), array( "a\xc2\x80\xef\xbf\xbf\x80c" ), array( "a\xc2\x80\xe2\x80\x80\xf3\x80\x80\x80\x80b" ), array( "\xe0\x80\xb1" ), array( "\xf0\x80\x80\xb1" ),
			array( "\xf8\x80\x80\x80\xb1" ), array( "\xfc\x80\x80\x80\x80\xb1" ),
		);

		// From "tests/phpunit/tests/formatting/SeemsUtf8.php", "tests/phpunit/data/formatting/big5.txt".
		$big5_strings = array(
			array( "\xaa\xa9\xa5\xbb" ), array( "\xa4\xc0\xc3\xfe" ), array( "\xc0\xf4\xb9\xd2" ), array( "\xa9\xca\xbd\xe8" ), array( "\xad\xba\xad\xb6" ),
		);

		$ret = array_merge( $ret, $big5_strings );
		return $ret;
	}

    private static function chr($c)
    {
        if (0x80 > $c %= 0x200000) {
            return chr($c);
        }
        if (0x800 > $c) {
            return chr(0xC0 | $c >> 6).chr(0x80 | $c & 0x3F);
        }
        if (0x10000 > $c) {
            return chr(0xE0 | $c >> 12).chr(0x80 | $c >> 6 & 0x3F).chr(0x80 | $c & 0x3F);
        }

        return chr(0xF0 | $c >> 18).chr(0x80 | $c >> 12 & 0x3F).chr(0x80 | $c >> 6 & 0x3F).chr(0x80 | $c & 0x3F);
    }
}
