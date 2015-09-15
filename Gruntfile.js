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
						'http://localhost:8000/tests/js/index.html?wp=4.3',
						'http://localhost:8000/tests/js/index.html?wp=4.2'
					]
				}
			},
			specific: {
				options: {
					urls: [ 'http://localhost:8000/tests/js/index.html?wp=' + grunt.option( 'wp' ) ]
				}
			}
		}
	});

	grunt.loadNpmTasks( 'grunt-contrib-connect' );
	grunt.loadNpmTasks( 'grunt-contrib-qunit' );

	grunt.task.run( 'connect' );

	grunt.registerTask( 'default', ['qunit:latest'] );
};
