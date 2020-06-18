// Librerie NPM richieste per l'esecuzione
var gulp = require('gulp');
var del = require('del');
var debug = require('gulp-debug');
var util = require('gulp-util');
var shell = require('shelljs');

var mainBowerFiles = require('main-bower-files');
var gulpIf = require('gulp-if');

// Minificatori
//var minifyJS = require('gulp-uglify');
var minifyJS = require('gulp-babel-minify');
var minifyCSS = require('gulp-clean-css');
var minifyJSON = require('gulp-json-minify');

// Interpretatori CSS
var sass = require('gulp-sass');
var less = require('gulp-less');
var stylus = require('gulp-stylus');
var autoprefixer = require('gulp-autoprefixer');

// Concatenatore
var concat = require('gulp-concat');

// Altro
var flatten = require('gulp-flatten');
var rename = require('gulp-rename');
var inquirer = require('inquirer');

// Configurazione
var config = {
    production: 'assets/dist', // Cartella di destinazione
    development: 'assets/src', // Cartella dei file di personalizzazione
    debug: false,
    main: {
        bowerDirectory: './node_modules',
        bowerJson: './package.json',
    },
    paths: {
        js: 'js',
        css: 'css',
        images: 'img',
        fonts: 'fonts'
    }
};

// Elaborazione e minificazione di JS
gulp.task('JS', function (done) {
    gulp.src(mainBowerFiles('**/*.js', {
        paths: config.main,
        debugging: config.debug,
    }))
        .pipe(concat('app.min.js'))
        .pipe(minifyJS({
            builtIns: false,
            evaluate: false,
            mangle: false,
        })) // Commentare per togliere la minificazione
        .pipe(gulp.dest(config.production + '/' + config.paths.js));

    gulp.task('start', gulp.series('srcJS'));

    done();
});

// Elaborazione e minificazione di JS personalizzati
gulp.task('srcJS', function () {
    gulp.src([
        config.development + '/' + config.paths.js + '/*.js',
    ])
        .pipe(concat('custom.min.js'))
        .pipe(minifyJS({
            builtIns: false,
            evaluate: false,
            mangle: false,
        })) // Commentare per togliere la minificazione
        .pipe(gulp.dest(config.production + '/' + config.paths.js));

    gulp.src([
        config.development + '/' + config.paths.js + '/functions/*.js',
    ])
        .pipe(concat('functions.min.js'))
        .pipe(minifyJS({
            builtIns: false,
            evaluate: false,
            mangle: false,
        })) // Commentare per togliere la minificazione
        .pipe(gulp.dest(config.production + '/' + config.paths.js));
});


// Elaborazione e minificazione di CSS
gulp.task('CSS', function (done) {
    gulp.src(mainBowerFiles('**/*.{css,scss,less,styl}', {
        paths: config.main,
        debugging: config.debug,
    }))
        .pipe(gulpIf('*.scss', sass(), gulpIf('*.less', less(), gulpIf('*.styl', stylus()))))
        .pipe(autoprefixer({
            browsers: 'last 2 version',
        }))
        .pipe(minifyCSS({
            rebase: false,
        }))
        .pipe(concat('app.min.css'))
        .pipe(flatten())
        .pipe(gulp.dest(config.production + '/' + config.paths.css));

    gulp.task('start', gulp.series('srcCSS'));

    done();
});

// Elaborazione e minificazione di CSS personalizzati
gulp.task('srcCSS', function () {
    gulp.src([
        config.development + '/' + config.paths.css + '/*.{css,scss,less,styl}',
    ])
        .pipe(gulpIf('*.scss', sass(), gulpIf('*.less', less(), gulpIf('*.styl', stylus()))))
        .pipe(autoprefixer({
            browsers: 'last 2 version',
        }))
        .pipe(minifyCSS({
            rebase: false,
        }))
        .pipe(concat('style.min.css'))
        .pipe(flatten())
        .pipe(gulp.dest(config.production + '/' + config.paths.css));

    gulp.src([
        config.development + '/' + config.paths.css + '/print/*.{css,scss,less,styl}',
        config.bowerDirectory + '/fullcalendar/fullcalendar.print.css',
    ])
        .pipe(gulpIf('*.scss', sass(), gulpIf('*.less', less(), gulpIf('*.styl', stylus()))))
        .pipe(autoprefixer({
            browsers: 'last 2 version',
        }))
        .pipe(minifyCSS({
            rebase: false,
        }))
        .pipe(concat('print.min.css'))
        .pipe(flatten())
        .pipe(gulp.dest(config.production + '/' + config.paths.css));

    gulp.src([
        config.development + '/' + config.paths.css + '/themes/*.{css,scss,less,styl}',
        config.main.bowerDirectory + '/admin-lte/dist/css/skins/_all-skins.css',
    ])
        .pipe(gulpIf('*.scss', sass(), gulpIf('*.less', less(), gulpIf('*.styl', stylus()))))
        .pipe(autoprefixer({
            browsers: 'last 2 version',
        }))
        .pipe(minifyCSS({
            rebase: false,
        }))
        .pipe(concat('themes.min.css'))
        .pipe(flatten())
        .pipe(gulp.dest(config.production + '/' + config.paths.css));
});


// Elaborazione delle immagini
gulp.task('images', function (done) {
    gulp.src('**/*.{jpg,png,jpeg,gif}', {
        paths: config.main,
        debugging: config.debug,
        allowEmpty: true
    })
        .pipe(flatten())
        .pipe(gulp.dest(config.production + '/' + config.paths.images));

    gulp.task('start', gulp.series('srcImages'));

    done();
});

// Elaborazione delle immagini personalizzate
gulp.task('srcImages', function () {
    gulp.src([
        config.development + '/' + config.paths.images + '/**/*.{jpg,png,jpeg,gif}',
    ])
        .pipe(gulp.dest(config.production + '/' + config.paths.images));
});


// Elaborazione dei fonts
gulp.task('fonts', function (done) {
    gulp.src(mainBowerFiles('**/*.{otf,eot,svg,ttf,woff,woff2}', {
        paths: config.main,
        debugging: config.debug,
    }))
        .pipe(flatten())
        .pipe(gulp.dest(config.production + '/' + config.paths.fonts));

    gulp.task('start', gulp.series('srcFonts'));

    done();
});

// Elaborazione dei fonts personalizzati
gulp.task('srcFonts', function () {
    gulp.src([
        config.development + '/' + config.paths.fonts + '/**/*.{otf,eot,svg,ttf,woff,woff2}',
    ])
        .pipe(flatten())
        .pipe(gulp.dest(config.production + '/' + config.paths.fonts));
});

gulp.task('ckeditor', function (done) {
    gulp.src([
        config.main.bowerDirectory + '/ckeditor/{adapters,lang,skins,plugins}/**/*.{js,json,css,png}',
    ])
        .pipe(gulp.dest(config.production + '/' + config.paths.js + '/ckeditor'));

    gulp.src([
        config.main.bowerDirectory + '/ckeditor/*.{js,css}',
    ])
        .pipe(gulp.dest(config.production + '/' + config.paths.js + '/ckeditor'));

    done();
});

gulp.task('colorpicker', function (done) {
    gulp.src([
        config.main.bowerDirectory + '/bootstrap-colorpicker/dist/**/*.{jpg,png,jpeg}',
    ])
        .pipe(flatten())
        .pipe(gulp.dest(config.production + '/' + config.paths.images + '/bootstrap-colorpicker'));

    done();
});

gulp.task('password-strength', function (done) {
    gulp.src([
        config.main.bowerDirectory + '/pwstrength-bootstrap/dist/*.js',
    ])
        .pipe(concat('password.min.js'))
        .pipe(minifyJS({
            builtIns: false,
            evaluate: false,
            mangle: false,
        }))
        .pipe(gulp.dest(config.production + '/password-strength'));

    done();
});

gulp.task('hotkeys-js', function (done) {
    gulp.src([
        config.main.bowerDirectory + '/hotkeys-js/dist/hotkeys.min.js',
    ])
        .pipe(flatten())
        .pipe(gulp.dest(config.production + '/' + config.paths.js + '/hotkeys-js'));

    done();
});

gulp.task('chartjs', function (done) {
    gulp.src([
        config.main.bowerDirectory + '/chart.js/dist/Chart.min.js',
    ])
        .pipe(flatten())
        .pipe(gulp.dest(config.production + '/' + config.paths.js + '/chartjs'));

    done();
});

gulp.task('csrf', function (done) {
    gulp.src([
        './vendor/owasp/csrf-protector-php/js/csrfprotector.js',
    ])
        .pipe(flatten())
        .pipe(gulp.dest(config.production + '/' + config.paths.js + '/csrf'));

    done();
});

gulp.task('pdfjs', function (done) {
    gulp.src([
        config.main.bowerDirectory + '/pdf/web/**/*',
        '!' + config.main.bowerDirectory + '/pdf/web/cmaps/*',
        '!' + config.main.bowerDirectory + '/pdf/web/*.map',
        '!' + config.main.bowerDirectory + '/pdf/web/*.pdf',
    ])
        .pipe(gulp.dest(config.production + '/pdfjs/web'));

    gulp.src([
        config.main.bowerDirectory + '/pdf/build/*',
        '!' + config.main.bowerDirectory + '/pdf/build/*.map',
    ])
        .pipe(gulp.dest(config.production + '/pdfjs/build'));

    done();
});

// Elaborazione e minificazione delle informazioni sull'internazionalizzazione
gulp.task('i18n', function (done) {
    gulp.src([
        config.main.bowerDirectory + '/**/{i18n,lang,locale,locales}/*.{js,json}',
        config.development + '/' + config.paths.js + '/i18n/**/*.{js,json}',
        '!' + config.main.bowerDirectory + '/**/{src,plugins}/**',
        '!' + config.main.bowerDirectory + '/ckeditor/**',
        '!' + config.main.bowerDirectory + '/jquery-ui/**',
    ])
        // .pipe(gulpIf('*.js', minifyJS({
        //     builtIns: false,
        //     evaluate: false,
        //     mangle: false,
        // }), gulpIf('*.json', minifyJSON())))
        .pipe(gulpIf('!*.min.*', rename({
            suffix: '.min'
        })))
        .pipe(flatten({
            includeParents: 1
        }))
        .pipe(gulp.dest(config.production + '/' + config.paths.js + '/i18n'));

    done();
});

// PHP DebugBar assets
gulp.task('php-debugbar', function (done) {
    gulp.src([
        './vendor/maximebf/debugbar/src/DebugBar/Resources/**/*',
        '!./vendor/maximebf/debugbar/src/DebugBar/Resources/vendor/**/*',
    ])
        .pipe(gulpIf('*.css', minifyCSS(), gulpIf('*.js', minifyJS({
            builtIns: false,
            evaluate: false,
            mangle: false,
        }))))
        .pipe(gulp.dest(config.production + '/php-debugbar'));

    done();
});

// Operazioni per la release
gulp.task('release', function () {
    var archiver = require('archiver');
    var fs = require('fs');

    // Rimozione file indesiderati
    del([
        './vendor/tecnickcom/tcpdf/fonts/*',
        '!./vendor/tecnickcom/tcpdf/fonts/*helvetica*',
        './vendor/mpdf/mpdf/tmp/*',
        './vendor/mpdf/mpdf/ttfonts/*',
        '!./vendor/mpdf/mpdf/ttfonts/DejaVuinfo.txt',
        '!./vendor/mpdf/mpdf/ttfonts/DejaVu*Condensed*',
        './vendor/maximebf/debugbar/src/DebugBar/Resources/vendor/*',
        './vendor/respect/validation/tests/*',
    ]);

    // Impostazione dello zip
    var output = fs.createWriteStream('./release.zip');
    var archive = archiver('zip');

    output.on('close', function () {
        console.log('ZIP completato!');
    });

    archive.on('error', function (err) {
        throw err;
    });

    archive.pipe(output);

    // Aggiunta dei file
    archive.glob('**/*', {
        dot: true,
        ignore: [
            '.git/**',
            'node_modules/**',
            'backup/**',
            'files/**',
            'logs/**',
            'config.inc.php',
            '**/*.lock',
            '**/*.phar',
            '**/*.log',
            '**/*.zip',
            '**/*.bak',
            '**/*.jar',
            '**/*.txt',
            '**/~*',
        ]
    });

    // Eccezioni
    archive.file('backup/.htaccess');
    archive.file('files/.htaccess');
    archive.file('files/my_impianti/componente.ini');
    archive.file('logs/.htaccess');

    // Aggiunta del commit corrente nel file REVISION
    archive.append(shell.exec('git rev-parse --short HEAD', {
        silent: true
    }).stdout, {
        name: 'REVISION'
    });

    // Opzioni sulla release
    inquirer.prompt([{
        type: 'input',
        name: 'version',
        message: 'Numero di versione:',
    }, {
        type: 'confirm',
        name: 'beta',
        message: 'Versione beta?',
        default: false,
    }]).then(function (result) {
        version = result.version;

        if (result.beta) {
            version += 'beta';
        }

        archive.append(version, {
            name: 'VERSION'
        });

        // Completamento dello zip
        archive.finalize();
    });
});

// Pulizia
gulp.task('clean', function () {
    return del([config.production]);
});

// Operazioni particolari per la generazione degli assets
gulp.task('other', gulp.series(['clean', 'ckeditor', 'colorpicker', 'password-strength', 'hotkeys-js', 'i18n', 'pdfjs', 'chartjs', 'php-debugbar', 'csrf'], function (done) {
    return done();
}));

// Operazioni di default per la generazione degli assets
gulp.task('bower', gulp.series(['clean', 'JS', 'CSS', 'images', 'fonts', 'other'], function (done) {
    return done();
}));

gulp.task('default', gulp.series(['clean', 'bower'], function (done) {
    return done();
}));
