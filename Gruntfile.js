"use strict";

module.exports = function (grunt) {
    // We need to include the core Moodle grunt file too, otherwise we can't run tasks like "amd".
    require("grunt-load-gruntfile")(grunt);
    grunt.loadGruntfile("../../Gruntfile.js");

    // Load all grunt tasks.
    grunt.loadNpmTasks("grunt-contrib-less");
    grunt.loadNpmTasks("grunt-contrib-watch");
    grunt.loadNpmTasks("grunt-contrib-clean");
    //grunt.loadNpmTasks('grunt-contrib-uglify');

    grunt.initConfig({
        watch: {
            // If any .less file changes in directory "less" then run the "less" task.
            files: "less/*.less",
            tasks: ["less"]
        },
        less: {
            // Production config is also available.
            development: {
                options: {
                    // Specifies directories to scan for @import directives when parsing.
                    // Default value is the directory of the source, which is probably what you want.
                    paths: ["less/"],
                    compress: true
                },
                files: {
                    "style/accesscard.css": "less/accesscard.less",
                    "style/admin.css": "less/admin.less",
                    "style/archive.css": "less/archive.less",
                    "style/dropzone.css": "less/dropzone.less",
                    "style/login_mnet.css": "less/login_mnet.less",
                    "style/main.css": "less/main.less",
                    "style/manage_bunch.css": "less/manage_bunch.less",
                    "style/manage.css": "less/manage.less",
                    "style/preferences.css": "less/preferences.less",
                    "style/theme_boost.css": "less/theme_boost.less",
                    "style/ui.css": "less/ui.less",
                }
            },
        },
        uglify: {
            my_target: {
                files: [{
                    expand: true,
                    cwd: 'amd/src',
                    src: '*.js',
                    dest: 'amd/build',
                    rename: function (dst, src) {
                        // To keep src js files and make new files as *.min.js :
                        return dst + '/' + src.replace('.js', '.min.js');
                        // Or to override to src :
                        //return src;
                    }
                }]
            }
  }
    });
    // The default task (running "grunt" in console).
    grunt.registerTask("default", ["less"]);
};
