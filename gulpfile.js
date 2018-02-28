var gulp = require('gulp');
var phpunit = require('gulp-phpunit');

gulp.task('phpunit', function () {
    return gulp.src('**/*Test.php')
        .pipe(phpunit('.\\vendor\\bin\\phpunit', {
                debug: false,
                configurationFile: './phpunit.xml'
            })
        );
});

gulp.task('coverage', function () {
    return gulp.src('**/*Test.php')
        .pipe(phpunit('.\\vendor\\bin\\phpunit', {
                debug: false,
                configurationFile: './phpunit.xml',
                coverageHtml: './build/coverage'
            }
        ));
});

gulp.task('default', function () {
    // Run phpunit test.
    gulp.watch(['**/*Test.php'], {debounceDelay: 2000}, ['phpunit']);

//find ./ -not \( -path ./.svn -prune \) -not \( -path ./libs -prune \) -not \( -path ./.idea -prune \) -type f -print0 | xargs -0 file -i | grep  "charset=utf-8"
});

gulp.task('build', function(){
    gulp.run('phpunit');
});
