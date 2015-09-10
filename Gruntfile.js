module.exports = function( grunt ) {
	grunt.initConfig({
		connect: {
			server: {
				options: {
					base: '.'
				}
			}
		},
		qunit: {
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
						'http://localhost:8000/tests/js/index.html?wp=4.2',
						'http://localhost:8000/tests/js/index.html?wp=4.1'
					]
				}
			}
		}
	});

	grunt.loadNpmTasks( 'grunt-contrib-connect' );
	grunt.loadNpmTasks( 'grunt-contrib-qunit' );

	grunt.task.run( 'connect' );

	grunt.registerTask( 'default', ['qunit:latest'] );
};
