module.exports = function (grunt) {
	const sass = require('node-sass');

	grunt.initConfig({
		pkg: grunt.file.readJSON('package.json'),
		sass: {
			theme: {
				options: {
					implementation: sass,
					sourceMap: true
				},
				files: {
					'plugin/example/asset/css/example.css': 'plugin/example/asset/scss/example.scss',
					'plugin/debug/asset/debug.css': 'plugin/debug/asset/debug.scss'
				}
			}
		},
		watch: {
			sass: {
				files: ['**/*.scss'],
				tasks: [
					'sass:theme'
				]
			}
		}
	});
	grunt.loadNpmTasks('grunt-sass');
	grunt.loadNpmTasks('grunt-contrib-watch');
	grunt.registerTask('default', [
		'sass:theme'
	]);
};