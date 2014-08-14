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
					"**/*.php",
					"!files/**",
					"!tests/**",
					"!tools/**",
					"!node_modules/**",
					"!libs/**"
				],
			},
		}
	});

	function createPackageChecksum(target_file) {
		/* https://gist.github.com/onelaview/6475037 */
		var fs = require('fs');
		var crypto = require('crypto');
		var md5 = crypto.createHash('md5');
		var file = grunt.template.process(target_file);
		var buffer = fs.readFileSync(file);
		md5.update(buffer);
		var md5Hash = md5.digest('hex');
		grunt.verbose.writeln('file md5: ' + md5Hash);

		var md5FileName = file + '.md5';
		grunt.file.write(md5FileName, md5Hash);
		grunt.verbose.writeln('File "' + md5FileName + '" created.').writeln('...');
	}

	grunt.registerTask('build', '', function(A, B) {
		var _only_export = false;
		var tasks = ['krzip', 'syndication'];

		if(!A) {
			grunt.fail.warn('Undefined build target.');
		} else if(A && !B) {
			_only_export = true;
		}

		if(!_only_export) {
			tasks.push('changed');
			target = A + '...' + B;
			version = B;
		} else {
			target = A;
			version = A;
		}

		var done = this.async();
		var build_dir = 'build';
		var archive_full = build_dir + '/attendance.' + version + '.tar.gz';
		var archive_changed = build_dir + '/attendance.' + version + '.changed.tar.gz';
		var diff, target, version;

		var taskDone = function() {
			tasks.pop();
			grunt.verbose.writeln('remain tasks : '+tasks.length);

			if(tasks.length === 0) {
				grunt.util.spawn({
					cmd: "tar",
					args: ['cfz', 'attendance.'+version+'.tar.gz', 'attendance/'],
					opts: {
						cwd: 'build'
					}
				}, function (error, result, code) {
					grunt.log.ok('Archived(full) : ' + build_dir + '/attendance.'+version+'.tar.gz');
					createPackageChecksum(build_dir + '/attendance.'+version+'.tar.gz');

					grunt.util.spawn({
						cmd: "zip",
						args: ['-r', 'attendance.'+version+'.zip', 'attendance/'],
						opts: {
							cwd: 'build'
						}
					}, function (error, result, code) {
						grunt.log.ok('Archived(full) : ' + build_dir + '/attendance.'+version+'.zip');
						createPackageChecksum(build_dir + '/attendance.'+version+'.zip');

						grunt.file.delete('build/attendance');
						grunt.file.delete('build/temp.full.tar');

						grunt.log.ok('Done!');
					});
				});
			}
		};

		if(grunt.file.isDir(build_dir)) {
			grunt.file.delete(build_dir);
		}
		grunt.file.mkdir(build_dir);
		grunt.file.mkdir(build_dir + '/attendance');

		grunt.log.subhead('Archiving...');
		grunt.log.writeln('Target : ' + target);

		grunt.util.spawn({
			cmd: "git",
			args: ['archive', '--output=build/temp.full.tar', version, '.']
		}, function (error, result, code){
			if(!_only_export) {
				// changed
				grunt.util.spawn({
					cmd: "git",
					args: ['diff', '--name-only', target]
				}, function (error, result, code) {
					diff = result.stdout;

					if(diff) {
						diff = diff.split(grunt.util.linefeed);
					}

					// changed
					if(diff.length) {
						var args_tar = ['archive', '--prefix=attendance/', '-o', 'build/attendance.'+version+'.changed.tar.gz', version];
						var args_zip = ['archive', '--prefix=attendance/', '-o', 'build/attendance.'+version+'.changed.zip', version];
						args_tar = args_tar.concat(diff);
						args_zip = args_zip.concat(diff);

						grunt.util.spawn({
							cmd: "git",
							args: args_tar
						}, function (error, result, code) {
							grunt.log.ok('Archived(changed) : ' + build_dir + '/attendance.'+version+'.changed.tar.gz');
							createPackageChecksum(build_dir + '/attendance.'+version+'.changed.tar.gz');

							grunt.util.spawn({
								cmd: "git",
								args: args_zip
							}, function (error, result, code) {
								grunt.log.ok('Archived(changed) : ' + build_dir + '/attendance.'+version+'.changed.zip');
								createPackageChecksum(build_dir + '/attendance.'+version+'.changed.zip');

								taskDone();
							});
						});
					} else {
						taskDone();
					}
				});
			}
		});
	});

	grunt.loadNpmTasks('grunt-contrib-clean');
	grunt.loadNpmTasks('grunt-contrib-concat');
	grunt.loadNpmTasks('grunt-contrib-jshint');
	grunt.loadNpmTasks('grunt-contrib-uglify');
	grunt.loadNpmTasks('grunt-contrib-csslint');
	grunt.loadNpmTasks('grunt-contrib-cssmin');
	grunt.loadNpmTasks('grunt-phplint');

	grunt.registerTask('default', ['jshint', 'csslint']);
	grunt.registerTask('lint', ['jshint', 'csslint', 'phplint']);
	grunt.registerTask('minify', ['jshint', 'csslint', 'clean', 'concat', 'uglify', 'cssmin']);
};
