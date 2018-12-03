module.exports = function (grunt) {
	grunt.initConfig({
		pkg: grunt.file.readJSON('package.json'),
		sass: {
			options: {
				sourceMap: true
			},
			dist: {
				files: [{
					expand: true, // Recursive
					cwd: "./", // The startup directory
					src: '**/*.scss', // Source files
					dest: "./",
					ext: ".css" // File extension
				}]
			}
		},
		watch: {
			css: {
				files: '**/*.scss',
				tasks: ['sass:dist']
			}
		}
	});
	grunt.loadNpmTasks('grunt-contrib-sass');
	grunt.loadNpmTasks('grunt-contrib-watch');
	grunt.registerTask('default', [
		'sass:dist'
	]);
};