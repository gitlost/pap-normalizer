module.exports = function( grunt ) { //The wrapper function

	require( 'load-grunt-tasks' )( grunt );

	// Project configuration & task configuration
	grunt.initConfig( {
		pkg: grunt.file.readJSON( 'package.json' ),

		wp_readme_to_markdown: {
			convert:{
				files: {
					'README.md': 'readme.txt'
				},
				options: {
					'screenshot_url': 'https://ps.w.org/{plugin}/assets/{screenshot}.png',
					'post_convert': function ( readme ) {
						readme = '[![Build Status](https://travis-ci.org/gitlost/pap-normalizer.png?branch=master)](https://travis-ci.org/gitlost/pap-normalizer)\n' + readme;
						return readme;
					}
				}
			}
		},

		compress: {
			main: {
				options: {
					archive: 'dist/<%= pkg.name %>-<%= pkg.version %>.zip',
					mode: 'zip'
				},
				files: [
					{
						src: [
							'../pap-normalizer/readme.txt',
							'../pap-normalizer/pap-normalizer.php',
							'../pap-normalizer/Symfony/LICENSE',
							'../pap-normalizer/Symfony/Normalizer.php',
							'../pap-normalizer/Symfony/wp_regex_alts.php',
							'../pap-normalizer/Symfony/Resources/unidata/canonicalComposition.php',
							'../pap-normalizer/Symfony/Resources/unidata/canonicalDecomposition.php',
							'../pap-normalizer/Symfony/Resources/unidata/combiningClass.php',
							'../pap-normalizer/Symfony/Resources/unidata/compatibilityDecomposition.php'
						]
					}
				]
			}
		},

		phpunit: {
			classes: {
				dir: 'tests/'
			},
			options: {
				bin: 'WP_TESTS_DIR=/var/www/wordpress-develop/tests/phpunit phpunit',
				configuration: 'phpunit.xml'
			}
		},

	} );

	// Default task(s), executed when you run 'grunt'
	grunt.registerTask( 'default', [ 'wp_readme_to_markdown', 'compress' ] );

	// Creating a custom task
	grunt.registerTask( 'test', [ 'phpunit' ] );
};
