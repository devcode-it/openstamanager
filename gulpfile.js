// Librerie NPM richieste per l'esecuzione
const gulp = require('gulp');
const merge = require('merge-stream');
const del = require('del');
const debug = require('gulp-debug');

const mainBowerFiles = require('main-bower-files');
const gulpIf = require('gulp-if');

// Minificatori
const minifyJS = require('gulp-uglify');
const minifyCSS = require('gulp-clean-css');
const minifyJSON = require('gulp-json-minify');

// Interpretatori CSS
const sass = require('gulp-sass');
const less = require('gulp-less');
const stylus = require('gulp-stylus');
const autoprefixer = require('gulp-autoprefixer');

// Concatenatore
const concat = require('gulp-concat');

// Altro
const flatten = require('gulp-flatten');
const rename = require('gulp-rename');

// Release
const glob = require('globby');
const md5File = require('md5-file')
const fs = require('fs');
const archiver = require('archiver');
const shell = require('shelljs');
const inquirer = require('inquirer');

// Configurazione
const config = {
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
const JS = gulp.parallel(() => {
    return gulp.src(mainBowerFiles('**/*.js', {
        paths: config.main,
        debugging: config.debug,
    }))
        .pipe(concat('app.min.js'))
        .pipe(minifyJS())
        .pipe(gulp.dest(config.production + '/' + config.paths.js));
}, srcJS);

// Elaborazione e minificazione di JS personalizzati
function srcJS() {
    const js = gulp.src([
        config.development + '/' + config.paths.js + '/*.js',
    ])
        .pipe(concat('custom.min.js'))
        //.pipe(minifyJS())
        .pipe(gulp.dest(config.production + '/' + config.paths.js));

    const indip = gulp.src([
        config.development + '/' + config.paths.js + '/functions/*.js',
    ])
        .pipe(concat('functions.min.js'))
        //.pipe(minifyJS())
        .pipe(gulp.dest(config.production + '/' + config.paths.js));

    return merge(js, indip);
}

// Elaborazione e minificazione di CSS
const CSS = gulp.parallel(() => {
    return gulp.src(mainBowerFiles('**/*.{css,scss,less,styl}', {
        paths: config.main,
        debugging: config.debug,
    }))
        .pipe(gulpIf('*.scss', sass(), gulpIf('*.less', less(), gulpIf('*.styl', stylus()))))
        .pipe(autoprefixer())
        .pipe(minifyCSS({
            rebase: false,
        }))
        .pipe(concat('app.min.css'))
        .pipe(flatten())
        .pipe(gulp.dest(config.production + '/' + config.paths.css));
}, srcCSS);

// Elaborazione e minificazione di CSS personalizzati
function srcCSS() {
    const css = gulp.src([
        config.development + '/' + config.paths.css + '/*.{css,scss,less,styl}',
    ])
        .pipe(gulpIf('*.scss', sass(), gulpIf('*.less', less(), gulpIf('*.styl', stylus()))))
        .pipe(autoprefixer())
        .pipe(minifyCSS({
            rebase: false,
        }))
        .pipe(concat('style.min.css'))
        .pipe(flatten())
        .pipe(gulp.dest(config.production + '/' + config.paths.css));

    const print = gulp.src([
        config.development + '/' + config.paths.css + '/print/*.{css,scss,less,styl}',
        config.bowerDirectory + '/fullcalendar/fullcalendar.print.css',
    ], {
        allowEmpty: true
    })
        .pipe(gulpIf('*.scss', sass(), gulpIf('*.less', less(), gulpIf('*.styl', stylus()))))
        .pipe(autoprefixer())
        .pipe(minifyCSS({
            rebase: false,
        }))
        .pipe(concat('print.min.css'))
        .pipe(flatten())
        .pipe(gulp.dest(config.production + '/' + config.paths.css));

    const themes = gulp.src([
        config.development + '/' + config.paths.css + '/themes/*.{css,scss,less,styl}',
        config.main.bowerDirectory + '/admin-lte/dist/css/skins/_all-skins.min.css',
    ])
        .pipe(gulpIf('*.scss', sass(), gulpIf('*.less', less(), gulpIf('*.styl', stylus()))))
        .pipe(autoprefixer())
        .pipe(minifyCSS({
            rebase: false,
        }))
        .pipe(concat('themes.min.css'))
        .pipe(flatten())
        .pipe(gulp.dest(config.production + '/' + config.paths.css));

    return merge(css, print, themes);
}


// Elaborazione delle immagini
const images = srcImages;
/*
gulp.parallel(() => {*/
//const src = mainBowerFiles('**/*.{jpg,png,jpeg,gif}', {
/*
paths: config.main,
    debugging: config.debug,
});

return gulp.src(src, {
    allowEmpty: true
})
    .pipe(flatten())
    .pipe(gulp.dest(config.production + '/' + config.paths.images));
}, srcImages);
*/

// Elaborazione delle immagini personalizzate
function srcImages() {
    return gulp.src([
        config.development + '/' + config.paths.images + '/**/*.{jpg,png,jpeg,gif}',
    ])
        .pipe(gulp.dest(config.production + '/' + config.paths.images));
}


// Elaborazione dei fonts
const fonts =  gulp.parallel(() => {
    return gulp.src(mainBowerFiles('**/*.{otf,eot,svg,ttf,woff,woff2}', {
        paths: config.main,
        debugging: config.debug,
    }))
        .pipe(flatten())
        .pipe(gulp.dest(config.production + '/' + config.paths.fonts));
}, srcFonts);

// Elaborazione dei fonts personalizzati
function srcFonts() {
    return gulp.src([
        config.development + '/' + config.paths.fonts + '/**/*.{otf,eot,svg,ttf,woff,woff2}',
    ])
        .pipe(flatten())
        .pipe(gulp.dest(config.production + '/' + config.paths.fonts));
}

function ckeditor() {
    return gulp.src([
        config.main.bowerDirectory + '/ckeditor4/{adapters,lang,skins,plugins}/**/*.{js,json,css,png}',
        config.main.bowerDirectory + '/ckeditor4/*.{js,css}',
    ])
        .pipe(gulp.dest(config.production + '/' + config.paths.js + '/ckeditor'));
}

function colorpicker() {
    return gulp.src([
        config.main.bowerDirectory + '/bootstrap-colorpicker/dist/**/*.{jpg,png,jpeg}',
    ])
        .pipe(flatten())
        .pipe(gulp.dest(config.production + '/' + config.paths.images + '/bootstrap-colorpicker'));
}

function password_strength(){
    return gulp.src([
        config.main.bowerDirectory + '/pwstrength-bootstrap/dist/*.js',
    ])
        .pipe(concat('password.min.js'))
        .pipe(minifyJS())
        .pipe(gulp.dest(config.production + '/password-strength'));
}

function hotkeys() {
    return gulp.src([
        config.main.bowerDirectory + '/hotkeys-js/dist/hotkeys.min.js',
    ])
        .pipe(flatten())
        .pipe(gulp.dest(config.production + '/' + config.paths.js + '/hotkeys-js'));
}

function chartjs() {
    return gulp.src([
        config.main.bowerDirectory + '/chart.js/dist/Chart.min.js',
    ])
        .pipe(flatten())
        .pipe(gulp.dest(config.production + '/' + config.paths.js + '/chartjs'));
}

function csrf() {
    return gulp.src([
        './vendor/owasp/csrf-protector-php/js/csrfprotector.js',
    ])
        .pipe(flatten())
        .pipe(gulp.dest(config.production + '/' + config.paths.js + '/csrf'));
}

function pdfjs() {
    const web = gulp.src([
        config.main.bowerDirectory + '/pdf.js/web/**/*',
        '!' + config.main.bowerDirectory + '/pdf.js/web/cmaps/*',
        '!' + config.main.bowerDirectory + '/pdf.js/web/*.map',
        '!' + config.main.bowerDirectory + '/pdf.js/web/*.pdf',
    ])
        .pipe(gulp.dest(config.production + '/pdfjs/web'));

    const build = gulp.src([
        config.main.bowerDirectory + '/pdf.js/build/*',
        '!' + config.main.bowerDirectory + '/pdf.js/build/*.map',
    ])
        .pipe(gulp.dest(config.production + '/pdfjs/build'));

    return merge(web, build);
}

// Elaborazione e minificazione delle informazioni sull'internazionalizzazione
function i18n() {
    return gulp.src([
        config.main.bowerDirectory + '/**/{i18n,lang,locale,locales}/*.{js,json}',
        config.development + '/' + config.paths.js + '/i18n/**/*.{js,json}',
        '!' + config.main.bowerDirectory + '/**/{src,plugins}/**',
        '!' + config.main.bowerDirectory + '/ckeditor4/**',
        '!' + config.main.bowerDirectory + '/summernote/**',
        '!' + config.main.bowerDirectory + '/jquery-ui/**',
    ])
        //.pipe(gulpIf('*.js', minifyJS(), gulpIf('*.json', minifyJSON())))
        .pipe(gulpIf('!*.min.*', rename({
            suffix: '.min'
        })))
        .pipe(flatten({
            includeParents: 1
        }))
        .pipe(gulp.dest(config.production + '/' + config.paths.js + '/i18n'));
}

// PHP DebugBar assets
function phpDebugBar() {
    return gulp.src([
        './vendor/maximebf/debugbar/src/DebugBar/Resources/**/*',
        '!./vendor/maximebf/debugbar/src/DebugBar/Resources/vendor/**/*',
    ])
        .pipe(gulpIf('*.css', minifyCSS(), gulpIf('*.js', minifyJS())))
        .pipe(gulp.dest(config.production + '/php-debugbar'));
}

// Operazioni per la release
function release(done) {
    // Impostazione dello zip
    let output = fs.createWriteStream('./release.zip', {flags: 'w'});
    let archive = archiver('zip');

    output.on('close', function () {
        console.log('ZIP completato!');
    });

    archive.on('error', function (err) {
        throw err;
    });

    archive.pipe(output);

    // Individuazione dei file da aggiungere
    glob([
        '**/*',
        '!checksum.json',
        '!.idea/**',
        '!.git/**',
        '!node_modules/**',
        '!backup/**',
        '!files/**',
        '!logs/**',
        '!config.inc.php',
        '!**/*.(lock|phar|log|zip|bak|jar|txt)',
        '!**/~*',
        '!vendor/tecnickcom/tcpdf/examples/*',
        '!vendor/tecnickcom/tcpdf/fonts/*',
        'vendor/tecnickcom/tcpdf/fonts/*helvetica*',
        '!vendor/mpdf/mpdf/tmp/*',
        '!vendor/mpdf/mpdf/ttfonts/*',
        'vendor/mpdf/mpdf/ttfonts/DejaVuinfo.txt',
        'vendor/mpdf/mpdf/ttfonts/DejaVu*Condensed*',
        '!vendor/maximebf/debugbar/src/DebugBar/Resources/vendor/*',
        '!vendor/respect/validation/tests/*',
    ], {
        dot: true,
    }).then(function (files){
        // Aggiunta dei file con i relativi checksum
        let checksum = {};
        for (const file of files) {
            if (fs.lstatSync(file).isDirectory()) {
                archive.directory(file, file);
            } else {
                archive.file(file);

                if (!file.startsWith('vendor')) {
                    checksum[file] = md5File.sync(file);
                }
            }
        }

        // Eccezioni
        archive.file('backup/.htaccess', {});
        archive.file('files/.htaccess', {});
        archive.file('files/my_impianti/componente.ini', {});
        archive.file('logs/.htaccess', {});

        // Aggiunta del file dei checksum
        let checksumFile = fs.createWriteStream('./checksum.json', {flags: 'w'});
        checksumFile.write(JSON.stringify(checksum));
        checksumFile.close();
        archive.file('checksum.json', {});

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
            let version = result.version;

            if (result.beta) {
                version += 'beta';
            }

            archive.append(version, {
                name: 'VERSION'
            });

            // Completamento dello zip
            archive.finalize();

            done();
        });
    });
}

// Pulizia
function clean() {
    return del([config.production]);
}

// Operazioni di default per la generazione degli assets
const bower = gulp.series(clean, gulp.parallel(JS, CSS, images, fonts, phpDebugBar, ckeditor, colorpicker, i18n, pdfjs, hotkeys, chartjs, password_strength, csrf));

// Debug su CSS e JS
exports.srcJS = srcJS;
exports.srcCSS = srcCSS;

exports.bower = bower;
exports.release = release;
exports.default = bower;
