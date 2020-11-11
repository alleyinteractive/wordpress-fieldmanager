module.exports = function( grunt ) {
	if ( ! grunt.option( 'wp' ) ) {
		grunt.option( 'wp', 'master' );
	}

	grunt.initConfig({
		connect: {
			server: {
				options: {
					base: '.'
				}
			}
		},
		qunit: {
			options: {
				timeout: 7000
			},
			latest: {
				options: {
					urls: ['http://localhost:8000/tests/js/index.html']
				}
			},
			recent: {
				options: {
					urls: [
						'http://localhost:8000/tests/js/index.html',
						'http://localhost:8000/tests/js/index.html?wp=4.9',
						'http://localhost:8000/tests/js/index.html?wp=4.8'
					]
				}
			},
			specific: {
				options: {
					urls: [ 'http://localhost:8000/tests/js/index.html?wp=' + grunt.option( 'wp' ) ]
				}
			}
		},
		makepot: {
			target: {
				options: {
					domainPath: '/languages',
					mainFile: 'fieldmanager.php',
					potFilename: 'fieldmanager.pot',
					potHeaders: {
						poedit: true,
						'x-poedit-keywordslist': true
					},
					type: 'wp-plugin',
					updateTimestamp: true
				}
			}
		},
		phpcs: {
			plugin: {},
			options: {
				bin: "vendor/bin/phpcs",
				showSniffCodes: true,
				standard: "phpcs.ruleset.xml",
				verbose: true,
				warningSeverity: 0,
			}
		},
	});


	grunt.loadNpmTasks( 'grunt-contrib-connect' );
	grunt.loadNpmTasks( 'grunt-contrib-qunit' );
	grunt.loadNpmTasks( 'grunt-phpcs' );
	grunt.loadNpmTasks( 'grunt-wp-i18n' );

	// Run server for QUnit.
	grunt.task.run( 'connect' );
};
