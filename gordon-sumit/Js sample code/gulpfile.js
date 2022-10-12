var gulp = require('gulp'),
    watch = require('gulp-watch'),
    autoPrefixer = require('gulp-autoprefixer'),
    cssMin = require('gulp-cssmin'),
    jsMin = require('gulp-jsmin'),
	uglify = require('gulp-uglify'),
 	// jshint = require('gulp-jshint'),
	concat = require('gulp-concat'),
    rigger = require('gulp-rigger'),
    rename = require('gulp-rename'),
    sass = require('gulp-sass'),
    sourceMaps = require('gulp-sourcemaps'),
	imageMin = require('gulp-imagemin'),
	browserSync = require('browser-sync').create();

var devURL = 'http://harbingervavia.test';

var paths = {
    build: {
		theme: {
			js: 'assets/theme/js/',
        	css: 'assets/theme/css/',
			webfont:'assets/theme/webfonts/',
			fonts:'assets/theme/fonts/',
			img: 'assets/theme/img/',
		},
		branding: {
			img: 'assets/branding/img/',
			css: 'assets/branding/css/',
		},
	},
	
    src: {
		theme: {
			js: 'assets-src/theme/js/*.js',
        	css: 'assets-src/theme/scss/main.scss',
			fonts: 'assets-src/theme/fonts/**',
			img: 'assets-src/theme/images/**',
		},
		branding: {
			img: 'assets-src/branding/img/**',
			css: 'assets-src/branding/scss/admin.scss',
		},
    },

    watch: {
        js: 'assets-src/**/*.js',
        css: 'assets-src/**/*.scss'
    }
};

gulp.task('images-branding', function() {
	return gulp.src( paths.src.branding.img )
		.pipe( imageMin([
			imageMin.gifsicle(),
			imageMin.mozjpeg(),
			imageMin.optipng()/// todo use assets images to mini file into branding foldere
		]) )
		.pipe( gulp.dest(paths.build.branding.img) );
});

gulp.task('images-theme', function() {
	return gulp.src( paths.src.theme.img )
		.pipe( imageMin(([
			imageMin.gifsicle(),
			imageMin.mozjpeg(),
			imageMin.optipng()
		])) )
		.pipe( gulp.dest(paths.build.theme.img) );
});

gulp.task('js', function () {
	return gulp.src( paths.src.theme.js )
			.pipe( rigger() )
			.pipe( gulp.dest(paths.build.theme.js) );
});


gulp.task('js-min', function () {
	return gulp.src( paths.src.theme.js )
			.pipe( rigger() )
			.pipe( jsMin() )
			.pipe( gulp.dest(paths.build.theme.js) );
});

gulp.task('vendor', function () {
	return gulp.src([
		'node_modules/jquery/dist/jquery.js',
		'node_modules/bootstrap/dist/js/bootstrap.js',
		'node_modules/swiper/swiper-bundle.js',
		'node_modules/slick-carousel/slick/slick.js',
		'node_modules/@lottiefiles/lottie-player/dist/lottie-player.js',
	])
		.pipe(sourceMaps.init())
		// .pipe(jshint())
		.pipe(uglify())
		.pipe(concat('vendor.js'))
		.pipe(sourceMaps.write('.'))
		.pipe( gulp.dest(paths.build.theme.js) );
});

gulp.task('css', function() {
	return gulp.src( paths.src.theme.css )
		.pipe( sourceMaps.init() )
		.pipe( sass().on('error', sass.logError) )
		.pipe( sourceMaps.write() )
		.pipe( rename('styles.css') )
		.pipe( gulp.dest(paths.build.theme.css) )
		.pipe( browserSync.stream() );
});

gulp.task('css-branding', function() {
	return gulp.src( paths.src.branding.css )
		.pipe( sourceMaps.init() )
		.pipe( sass().on('error', sass.logError) )
		.pipe( sourceMaps.write() )
		.pipe( rename('styles.css') )
		.pipe( gulp.dest(paths.build.branding.css) )
		.pipe( browserSync.stream() );
});

gulp.task('css-min', function () {
	return gulp.src( paths.src.theme.css )
			.pipe( sourceMaps.init() )
			.pipe( sass().on('error', sass.logError) )
			.pipe( sourceMaps.write() )
			.pipe(
				autoPrefixer({
					cascade: false
				})
			)
			.pipe( cssMin() )
			.pipe( rename('styles.css') )
			.pipe( gulp.dest(paths.build.theme.css) );
});

gulp.task('icons', function() {
	return gulp.src('node_modules/@fortawesome/fontawesome-free/webfonts/*')
		.pipe(gulp.dest(paths.build.theme.webfont));
});

gulp.task('fonts', function() {
	return gulp.src(paths.src.theme.fonts)
		.pipe(gulp.dest(paths.build.theme.fonts));
});

gulp.task('browser-sync', function() {
    browserSync.init({
        proxy: devURL
    });
});

gulp.task('dev', gulp.parallel('js-min', 'css-min','images-branding','images-theme','icons','fonts','vendor','css-branding'));

gulp.task('dev-watch', function() {

    watch(paths.watch.js, function(event, cb) {
        gulp.series('js-min')();

		browserSync.reload();
    });

	watch(paths.watch.css, function(event, cb) {
        gulp.series('css-min')();
	});
});

gulp.task('prod', gulp.parallel('js-min', 'css-min'));

gulp.task('default', gulp.parallel('dev', 'browser-sync', 'dev-watch'));