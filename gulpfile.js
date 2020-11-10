/*
 * OpenSTAManager: il software gestionale open source per l'assistenza tecnica e la fatturazione
 * Copyright (C) DevCode s.n.c.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

// Librerie NPM richieste per l'esecuzione
const gulp = require('gulp');
const merge = require('merge-stream');
const del = require('del');
const gulpIf = require('gulp-if');
const babel = require('gulp-babel');

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
    nodeDirectory: './node_modules', // Percorso per node_modules
    paths: {
        js: 'js',
        css: 'css',
        images: 'img',
        fonts: 'fonts'
    },
    babelOptions: {
        compact: true,
        presets: [
            ['@babel/env', {
                modules: false
            }],
        ],
    },
    minifiers: {
        css: {
            rebase: false,
        }
    }
};
config.babelOptions.compact = !config.debug;

// Elaborazione e minificazione di JS
const JS = gulp.parallel(() => {
    const vendor = [
        'jquery/dist/jquery.js',
        'autosize/dist/autosize.js',
        'bootstrap-colorpicker/dist/js/bootstrap-colorpicker.js',
        'moment/moment.js',
        'components-jqueryui/jquery-ui.js',
        'datatables.net/js/jquery.dataTables.js',
        'datatables.net-buttons/js/dataTables.buttons.js',
        'datatables.net-buttons/js/buttons.colVis.js',
        'datatables.net-buttons/js/buttons.flash.js',
        'datatables.net-buttons/js/buttons.html5.js',
        'datatables.net-buttons/js/buttons.print.js',
        'datatables.net-scroller/js/dataTables.scroller.js',
        'datatables.net-select/js/dataTables.select.js',
        'dropzone/dist/dropzone.js',
        'autonumeric/dist/autoNumeric.min.js',
        'eonasdan-bootstrap-datetimepicker/build/js/bootstrap-datetimepicker.min.js',
        'fullcalendar/dist/fullcalendar.js',
        'geocomplete/jquery.geocomplete.js',
        'inputmask/dist/min/jquery.inputmask.bundle.min.js',
        'jquery-form/src/jquery.form.js',
        'jquery-ui-touch-punch/jquery.ui.touch-punch.js',
        'jquery.shorten/src/jquery.shorten.js',
        'numeral/numeral.js',
        'parsleyjs/dist/parsley.js',
        'select2/dist/js/select2.min.js',
        'signature_pad/dist/signature_pad.js',
        'sweetalert2/dist/sweetalert2.js',
        'toastr/build/toastr.min.js',
        'tooltipster/dist/js/tooltipster.bundle.js',
        'admin-lte/dist/js/adminlte.js',
        'bootstrap/dist/js/bootstrap.min.js',
        'bootstrap-daterangepicker/daterangepicker.js',
        'datatables.net-bs/js/dataTables.bootstrap.js',
        'datatables.net-buttons-bs/js/buttons.bootstrap.js',
        'smartwizard/dist/js/jquery.smartWizard.min.js',
    ];

    for (const i in vendor) {
        vendor[i] = config.nodeDirectory + '/' + vendor[i];
    }

    return gulp.src(vendor)
        .pipe(babel(config.babelOptions))
        .pipe(concat('app.min.js'))
        .pipe(minifyJS())
        .pipe(gulp.dest(config.production + '/' + config.paths.js));
}, srcJS);

// Elaborazione e minificazione di JS personalizzati
function srcJS() {
    const js = gulp.src([
        config.development + '/' + config.paths.js + '/base/*.js',
    ])
        .pipe(babel(config.babelOptions))
        .pipe(concat('custom.min.js'))
        .pipe(gulpIf(!config.debug, minifyJS()))
        .pipe(gulp.dest(config.production + '/' + config.paths.js));

    const functions = gulp.src([
        config.development + '/' + config.paths.js + '/functions/*.js',
    ])
        .pipe(babel(config.babelOptions))
        .pipe(concat('functions.min.js'))
        .pipe(gulpIf(!config.debug, minifyJS()))
        .pipe(gulp.dest(config.production + '/' + config.paths.js));

    return merge(js, functions);
}

// Elaborazione e minificazione di CSS
const CSS = gulp.parallel(() => {
    const vendor = [
        'bootstrap-colorpicker/dist/css/bootstrap-colorpicker.css',
        'dropzone/dist/dropzone.css',
        'eonasdan-bootstrap-datetimepicker/build/css/bootstrap-datetimepicker.min.css',
        'font-awesome/css/font-awesome.min.css',
        'fullcalendar/dist/fullcalendar.css',
        'parsleyjs/src/parsley.css',
        'select2/dist/css/select2.min.css',
        'sweetalert2/dist/sweetalert2.css',
        'toastr/build/toastr.min.css',
        'tooltipster/dist/css/tooltipster.bundle.css',
        'admin-lte/dist/css/AdminLTE.css',
        'bootstrap/dist/css/bootstrap.min.css',
        'bootstrap-daterangepicker/daterangepicker.css',
        'datatables.net-bs/css/dataTables.bootstrap.css',
        'datatables.net-buttons-bs/css/buttons.bootstrap.css',
        'datatables.net-scroller-bs/css/scroller.bootstrap.css',
        'datatables.net-select-bs/css/select.bootstrap.css',
        'select2-bootstrap-theme/dist/select2-bootstrap.css',
        'smartwizard/dist/css/smart_wizard.min.css',
        'smartwizard/dist/css/smart_wizard_theme_arrows.min.css',
    ];

    for (const i in vendor) {
        vendor[i] = config.nodeDirectory + '/' + vendor[i];
    }

    return gulp.src(vendor)
        .pipe(gulpIf('*.scss', sass(), gulpIf('*.less', less(), gulpIf('*.styl', stylus()))))
        .pipe(autoprefixer())
        .pipe(minifyCSS({
            rebase: false,
        }))
        .pipe(concat('app.min.css'))
        .pipe(gulp.dest(config.production + '/' + config.paths.css));
}, srcCSS);

// Elaborazione e minificazione di CSS personalizzati
function srcCSS() {
    const css = gulp.src([
        config.development + '/' + config.paths.css + '/*.{css,scss,less,styl}',
    ])
        .pipe(gulpIf('*.scss', sass(), gulpIf('*.less', less(), gulpIf('*.styl', stylus()))))
        .pipe(autoprefixer())
        .pipe(gulpIf(!config.debug, minifyCSS(config.minifiers.css)))
        .pipe(concat('style.min.css'))
        .pipe(gulp.dest(config.production + '/' + config.paths.css));

    const print = gulp.src([
        config.development + '/' + config.paths.css + '/print/*.{css,scss,less,styl}',
        config.nodeDirectory + '/fullcalendar/fullcalendar.print.css',
    ], {
        allowEmpty: true
    })
        .pipe(gulpIf('*.scss', sass(), gulpIf('*.less', less(), gulpIf('*.styl', stylus()))))
        .pipe(autoprefixer())
        .pipe(gulpIf(!config.debug, minifyCSS(config.minifiers.css)))
        .pipe(concat('print.min.css'))
        .pipe(gulp.dest(config.production + '/' + config.paths.css));

    const themes = gulp.src([
        config.development + '/' + config.paths.css + '/themes/*.{css,scss,less,styl}',
        config.nodeDirectory + '/admin-lte/dist/css/skins/_all-skins.min.css',
    ])
        .pipe(gulpIf('*.scss', sass(), gulpIf('*.less', less(), gulpIf('*.styl', stylus()))))
        .pipe(autoprefixer())
        .pipe(gulpIf(!config.debug, minifyCSS(config.minifiers.css)))
        .pipe(concat('themes.min.css'))
        .pipe(flatten())
        .pipe(gulp.dest(config.production + '/' + config.paths.css));

    return merge(css, print, themes);
}


// Elaborazione delle immagini
const images = srcImages;

// Elaborazione delle immagini personalizzate
function srcImages() {
    return gulp.src([
        config.development + '/' + config.paths.images + '/**/*.{jpg,png,jpeg,gif}',
    ])
        .pipe(gulp.dest(config.production + '/' + config.paths.images));
}


// Elaborazione dei fonts
const fonts = gulp.parallel(() => {
    const vendor = [
        'font-awesome/fonts/fontawesome-webfont.eot',
        'font-awesome/fonts/fontawesome-webfont.svg',
        'font-awesome/fonts/fontawesome-webfont.ttf',
        'font-awesome/fonts/fontawesome-webfont.woff',
        'font-awesome/fonts/fontawesome-webfont.woff2',
        'font-awesome/fonts/FontAwesome.otf',
        'bootstrap/dist/fonts/glyphicons-halflings-regular.eot',
        'bootstrap/dist/fonts/glyphicons-halflings-regular.svg',
        'bootstrap/dist/fonts/glyphicons-halflings-regular.ttf',
        'bootstrap/dist/fonts/glyphicons-halflings-regular.woff',
        'bootstrap/dist/fonts/glyphicons-halflings-regular.woff2',
    ];

    for (const i in vendor) {
        vendor[i] = config.nodeDirectory + '/' + vendor[i];
    }

    return gulp.src(vendor)
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
        config.nodeDirectory + '/ckeditor4/{adapters,lang,skins,plugins}/**/*.{js,json,css,png}',
        config.nodeDirectory + '/ckeditor4/*.{js,css}',
    ])
        .pipe(gulp.dest(config.production + '/' + config.paths.js + '/ckeditor'));
}

function colorpicker() {
    return gulp.src([
        config.nodeDirectory + '/bootstrap-colorpicker/dist/**/*.{jpg,png,jpeg}',
    ])
        .pipe(flatten())
        .pipe(gulp.dest(config.production + '/' + config.paths.images + '/bootstrap-colorpicker'));
}

function password_strength() {
    return gulp.src([
        config.nodeDirectory + '/pwstrength-bootstrap/dist/*.js',
    ])
        .pipe(concat('password.min.js'))
        .pipe(minifyJS())
        .pipe(gulp.dest(config.production + '/password-strength'));
}

function hotkeys() {
    return gulp.src([
        config.nodeDirectory + '/hotkeys-js/dist/hotkeys.min.js',
    ])
        .pipe(flatten())
        .pipe(gulp.dest(config.production + '/' + config.paths.js + '/hotkeys-js'));
}

function chartjs() {
    return gulp.src([
        config.nodeDirectory + '/chart.js/dist/Chart.min.js',
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
        config.nodeDirectory + '/pdf.js/web/**/*',
        '!' + config.nodeDirectory + '/pdf.js/web/cmaps/*',
        '!' + config.nodeDirectory + '/pdf.js/web/*.map',
        '!' + config.nodeDirectory + '/pdf.js/web/*.pdf',
    ])
        .pipe(gulp.dest(config.production + '/pdfjs/web'));

    const build = gulp.src([
        config.nodeDirectory + '/pdf.js/build/*',
        '!' + config.nodeDirectory + '/pdf.js/build/*.map',
    ])
        .pipe(gulp.dest(config.production + '/pdfjs/build'));

    return merge(web, build);
}

// Elaborazione e minificazione delle informazioni sull'internazionalizzazione
function i18n() {
    return gulp.src([
        config.nodeDirectory + '/**/{i18n,lang,locale,locales}/*.{js,json}',
        config.development + '/' + config.paths.js + '/i18n/**/*.{js,json}',
        '!' + config.nodeDirectory + '/**/{src,plugins}/**',
        '!' + config.nodeDirectory + '/ckeditor4/**',
        '!' + config.nodeDirectory + '/summernote/**',
        '!' + config.nodeDirectory + '/jquery-ui/**',
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
        '!database.json',
        '!.idea/**',
        '!.git/**',
        '!node_modules/**',
        '!include/custom/**',
        '!backup/**',
        '!files/**',
        '!logs/**',
        '!config.inc.php',
        '!update/structure.php',
        '!**/*.(lock|phar|log|zip|bak|jar|txt)',
        '!**/~*',
        '!vendor/tecnickcom/tcpdf/examples/**',
        '!vendor/tecnickcom/tcpdf/fonts/*',
        'vendor/tecnickcom/tcpdf/fonts/*helvetica*',
        '!vendor/mpdf/mpdf/tmp/*',
        '!vendor/mpdf/mpdf/ttfonts/*',
        'vendor/mpdf/mpdf/ttfonts/DejaVuinfo.txt',
        'vendor/mpdf/mpdf/ttfonts/DejaVu*Condensed*',
        '!vendor/maximebf/debugbar/src/DebugBar/Resources/vendor/**',
        '!vendor/respect/validation/tests/**',
        '!vendor/willdurand/geocoder/tests/**',
    ], {
        dot: true,
    }).then(function (files) {
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

        // Aggiunta del file per il controllo di integrità del database
        archive.append(shell.exec('php update/structure.php', {
            silent: true
        }).stdout, {
            name: 'database.json'
        });

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
