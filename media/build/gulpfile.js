// const gulp = require('gulp');
// const concat = require('gulp-concat');
// const minify = require('gulp-minify');
// const sass = require('gulp-sass');
// sass.compiler = require('node-sass');

var gulp = require('gulp');
var sass = require('gulp-sass')(require('node-sass'));
var concat = require('gulp-concat');
sass.compiler = require('node-sass');

gulp.task('scss', function () {
  return gulp.src('./scss/custom.scss')
    .pipe(sass({outputStyle: 'compressed'}).on('error', sass.logError))
    .pipe(gulp.dest('../../templates/shaper_helix3/css/'));
});
  
gulp.task('watch', function() {
  gulp.watch('./scss/**/*.scss', ['scss'])  ;
});

gulp.task('default', ['scss','watch']);
