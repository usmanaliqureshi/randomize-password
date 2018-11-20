/**
 *
 * Gulp Main File for Randomize Password Plugin
 *
 */

var gulp = require('gulp');
var zip = require('gulp-zip');
var notify = require('gulp-notify');
var wpPot = require('gulp-wp-pot');
var sort = require('gulp-sort');

var projectPHPWatchFiles = './**/*.php';
var translatePath = './languages/'
var text_domain = 'rp';
var destFile = 'randomize-password.pot';
var packageName = 'randomize-password';
var bugReport = 'https://github.com/usmanaliqureshi/randomize-password/issues';
var lastTranslator = 'Usman Ali Qureshi <usman@usmanaliqureshi.com>';
var team = 'InspiryThemes <usman@inspirythemes.com>';

gulp.task('translate', function () {
    return gulp.src(projectPHPWatchFiles)
        .pipe(sort())
        .pipe(wpPot({
            domain: text_domain,
            destFile: destFile,
            package: packageName,
            bugReport: bugReport,
            lastTranslator: lastTranslator,
            team: team
        }))
        .pipe(gulp.dest(translatePath + destFile))
        .pipe(notify({message: 'TASK: "translate" Completed!', onLast: true}))

});

gulp.task('zip', gulp.parallel(['translate'], function () {
    return gulp.src([
        // Include
        './**/*',

        // Exclude
        '!./prepros.cfg',
        '!./README.md',
        '!./randomize-password.zip',
        '!./**/.DS_Store',
        '!./sass/**/*.scss',
        '!./sass',
        '!./node_modules/**',
        '!./node_modules',
        '!./package.json',
        '!./gulpfile.js',
        '!./*.sublime-project',
        '!./*.sublime-workspace'
    ])
        .pipe(zip('randomize-password.zip'))
        .pipe(gulp.dest('./'))
        .pipe(notify({
        message: 'TASK: Randomize Password plugin ZIP Package is ready to go.',
        onLast: true
    }));
}));

/**
 * - Running All the TASKS -
 * ZIP task is depending on the translate task so no need to call the translate task as it will automatically run first before ZIP task.
 */

gulp.task('default', gulp.parallel(['zip'], function() {}));