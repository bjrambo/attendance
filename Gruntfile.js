module.exports = function(grunt) {
	"use strict";

	grunt.file.defaultEncoding = 'utf8';

	grunt.initConfig({
		phplint: {
			default : {
				options: {
					phpCmd: "php",
				},
				src: [
					"*.php",
					"!node_modules/**",
				],
			},
		}
	});

};
