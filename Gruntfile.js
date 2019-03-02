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
				files: [{
					expand: true,
					cwd: '.s',
					src: '**/*.scss',
					dest: '.',
					ext: '.css'
				}]
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