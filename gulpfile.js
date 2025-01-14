/*
 * OpenSTAManager: il software gestionale open source per l'assistenza tecnica e la fatturazione
 * Copyright (C) DevCode s.r.l.
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
const sass = require('gulp-sass')(require('sass'));
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
const { Readable } = require('stream');

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
        'autocompleter/autocomplete.js',
        'html5sortable/dist/html5sortable.js',
        'popper.js/dist/umd/popper.js',
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
        'fullcalendar-scheduler/index.global.js',
        '@fullcalendar/moment/index.global.js',
        '@fullcalendar/core/locales/it.global.js',
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
        'bootstrap-maxlength/dist/bootstrap-maxlength.js',
        'leaflet/dist/leaflet.js',
        'leaflet-gesture-handling/dist/leaflet-gesture-handling.min.js',
        'leaflet.fullscreen/Control.FullScreen.js',
        'ismobilejs/dist/isMobile.min.js',
        'ua-parser-js/dist/ua-parser.min.js',
        'readmore.js/readmore.js',
    ];

    for (const i in vendor) {
        vendor[i] = config.nodeDirectory + '/' + vendor[i];
    }

    return gulp.src(vendor, {
        allowEmpty: true
    })
        .pipe(babel(config.babelOptions))
        .pipe(concat('app.min.js'))
        .pipe(gulpIf(!config.debug, minifyJS({compress:false})))
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
        'smartwizard/dist/css/smart_wizard.min.css',
        'smartwizard/dist/css/smart_wizard_theme_arrows.min.css',
        'leaflet-gesture-handling/dist/leaflet-gesture-handling.min.css',
        'leaflet/dist/leaflet.css',
        'leaflet.fullscreen/Control.FullScreen.css',
        '@ttskch/select2-bootstrap4-theme/dist/select2-bootstrap4.min.css'
    ];

    for (const i in vendor) {
        vendor[i] = config.nodeDirectory + '/' + vendor[i];
    }

    return gulp.src(vendor, {
        allowEmpty: true
    })
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
            config.nodeDirectory + '/admin-lte/dist/css/adminlte.min.css',
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

function leaflet() {
    gulp.src([
        config.nodeDirectory + '/leaflet.fullscreen/icon-fullscreen.svg',
    ]).pipe(gulp.dest(config.production + '/' + config.paths.images + '/leaflet'));

    gulp.src([
        config.development + '/' + config.paths.images + '/leaflet/*',
    ]).pipe(gulp.dest(config.production + '/' + config.paths.images + '/leaflet'));

    return gulp.src([
        config.nodeDirectory + '/leaflet/dist/images/*.{jpg,png,jpeg}',
    ])
        .pipe(flatten())
        .pipe(gulp.dest(config.production + '/' + config.paths.images + '/leaflet'));
}

function wacom(){
    const vendor = [
        'modules/clipper-lib/clipper.js',
        'modules/js-md5/build/md5.min.js',
        'modules/poly2tri/dist/poly2tri.min.js',
        'modules/protobufjs/dist/protobuf.min.js',
        'modules/jszip/dist/jszip.min.js',
        'modules/gl-matrix/gl-matrix-min.js',
        'modules/rbush/rbush.min.js',
        'modules/js-ext/js-ext-min.js',
        'modules/digital-ink/digital-ink-min.js',
        'common/will/tools.js',	
        'modules/sjcl/sjcl.js',
        'common/libs/signature_sdk.js',
        'common/libs/signature_sdk_helper.js',
        'common/libs/stu-sdk.min.js',
        'modules/node-forge/dist/forge.min.js',
        'sigCaptDialog/sigCaptDialog.js',
        'sigCaptDialog/stuCaptDialog.js'
    ];

    for (const i in vendor) {
        vendor[i] = config.development + '/' + config.paths.js + '/wacom/' + vendor[i];
    }

    gulp.src([
        'assets/src/js/wacom/common/libs/signature_sdk.wasm'
    ])
        .pipe(gulp.dest(config.production + '/' + config.paths.js + '/wacom/'));

    return gulp.src(vendor, {
        allowEmpty: true
    })
        .pipe(babel(config.babelOptions))
        .pipe(concat('wacom.min.js'))
        .pipe(gulpIf(!config.debug, minifyJS()))
        .pipe(gulp.dest(config.production + '/' + config.paths.js));
        
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
        '../assets/src/css/fonts/sourcesanspro-regular-webfont.eot',
        '../assets/src/css/fonts/sourcesanspro-regular-webfont.svg',
        '../assets/src/css/fonts/sourcesanspro-regular-webfont.ttf',
        '../assets/src/css/fonts/sourcesanspro-regular-webfont.woff',
        '../assets/src/css/fonts/sourcesanspro-regular-webfont.woff2',
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
    
    const ckeditor =  gulp.src([
        config.nodeDirectory + '/ckeditor4/{adapters,lang,skins,plugins,core}/**/*.{js,json,css,png,gif,html}',
        config.nodeDirectory + '/ckeditor4/*.{js,css}',
    ])
        .pipe(gulp.dest(config.production + '/' + config.paths.js + '/ckeditor'));

    const plugins = gulp.src([
        config.nodeDirectory + '/ckeditor/plugins/{emoji,autocomplete,textmatch,textwatcher}/**/*.{js,json,css,png,gif,html}',
        config.nodeDirectory + '/ckeditor-image-to-base/*.{js,json,css,png,gif,html}',
    ])
        .pipe(gulp.dest(config.production + '/' + config.paths.js + '/ckeditor/plugins'));

    return merge(ckeditor, plugins);
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
        .pipe(gulpIf(!config.debug, minifyJS()))
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
        config.nodeDirectory + '/chart.js/dist/chart.min.js',
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
        config.nodeDirectory + '/pdfjs-viewer-element/dist/pdfjs-4.0.379-dist/web/**/*',
        '!' + config.nodeDirectory + '/pdfjs-viewer-element/dist/pdfjs-4.0.379-dist/web/cmaps/*',
        '!' + config.nodeDirectory + '/pdfjs-viewer-element/dist/pdfjs-4.0.379-dist/web/*.map',
        '!' + config.nodeDirectory + '/pdfjs-viewer-element/dist/pdfjs-4.0.379-dist/web/*.pdf',
    ])
        .pipe(gulp.dest(config.production + '/pdfjs/web'));

    const build = gulp.src([
        config.nodeDirectory + '/pdfjs-viewer-element/dist/pdfjs-4.0.379-dist/build/*',
        '!' + config.nodeDirectory + '/pdfjs-viewer-element/dist/pdfjs-4.0.379-dist/build/*.map',
    ])
        .pipe(gulp.dest(config.production + '/pdfjs/build'));

    return merge(web, build);
}

function uaparser() {
    return gulp.src([
        config.nodeDirectory + '/ua-parser-js/dist/icons/mono/**/*',
        '!' + config.nodeDirectory + '/ua-parser-js/dist/icons/mono/LICENSE.md',
    ])
        .pipe(gulp.dest(config.production + '/img/icons/'));
}

// Elaborazione e minificazione delle informazioni sull'internazionalizzazione
function i18n() {
    return gulp.src([
        config.nodeDirectory + '/**/{i18n,lang,locale,locales}/*.{js,json}',
        config.development + '/' + config.paths.js + '/i18n/**/*.{js,json}',
        config.nodeDirectory + '/moment/min/locales.js',
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

    // Individuazione dei file da aggiungere e escludere
    glob([
        '**/*',
        '!checksum.json',
        '!mysql.json',
        '!mysql_8_3.json',
        '!mariadb_10_x.json',
        '!settings.json',
        '!manifest.json',
        '!.idea/**',
        '!.git/**',
        '!.github/**',
        '!.vscode/**',
        '!node_modules/**',
        '!include/custom/**',
        '!backup/**',
        '!files/**',
        'files/temp/.gitkeep',
        '!logs/**',
        '!config.inc.php',
        '!psalm.xml',
        '!update/structure.php',
        '!update/settings.php',
        '!**/*.(lock|phar|log|zip|bak|jar|txt)',
        '!**/~*',
        '!vendor/tecnickcom/tcpdf/examples/**',
        '!vendor/tecnickcom/tcpdf/fonts/*',
        'vendor/tecnickcom/tcpdf/fonts/*helvetica*',
        '!vendor/mpdf/mpdf/tmp/*',
        '!vendor/mpdf/mpdf/ttfonts/*',
        'vendor/mpdf/mpdf/ttfonts/DejaVuinfo.txt',
        'vendor/mpdf/mpdf/ttfonts/DejaVu*Condensed*',
        '!vendor/respect/validation/tests/**',
        '!vendor/willdurand/geocoder/tests/**',
        '!docker/**',
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
        var bufferStream = new Readable();
        
        bufferStream.push(shell.exec('php update/structure.php', {
            silent: true
        }).stdout);
        bufferStream.push(null);
        archive.append(bufferStream, { name: 'mysql.json' });

        // Aggiunta del file per il controllo delle impostazioni
        bufferStream = new Readable();
        bufferStream.push(shell.exec('php update/settings.php', {
            silent: true
        }).stdout);
        bufferStream.push(null);
        archive.append(bufferStream, { name: 'settings.json' });

        // Aggiunta del commit corrente nel file REVISION
        bufferStream = new Readable();
        bufferStream.push(shell.exec('git rev-parse --short HEAD', {
            silent: true
        }).stdout);
        bufferStream.push(null);
        archive.append(bufferStream, { name: 'REVISION' });

        // Opzioni sulla release  
        inquirer.prompt([{
            type: 'input',
            name: 'version',
            message: 'Numero di versione:',
            validate: (input) => input ? true : 'Il numero di versione non può essere vuoto.'
        }, {
            type: 'confirm',
            name: 'beta',
            message: 'Versione beta?',
            default: false,
        }]).then(function (result) {

            let version = result.version;

            // Aggiungi 'beta' solo se l'opzione beta è selezionata  
            if (result.beta) {
                version += 'beta';
            }

            // Creazione di un stream leggibile con la versione  
            const bufferStream = new Readable({
                read() {
                    this.push(version);
                    this.push(null);
                }
            });

            // Aggiunta della versione corrente nel file VERSION  
            archive.append(bufferStream, { name: 'VERSION' });

            // Completamento dello ZIP  
            archive.finalize();

            done();
        }).catch(err => {
            console.error('Si è verificato un errore:', err);
        });

    });
}

// Pulizia
function clean() {
    return del([config.production]);
}

// Operazioni di default per la generazione degli assets
const bower = gulp.series(clean, gulp.parallel(JS, CSS, images, fonts, ckeditor, colorpicker, i18n, pdfjs, uaparser, hotkeys, chartjs, password_strength, csrf, leaflet, wacom));

// Debug su CSS e JS
exports.srcJS = srcJS;
exports.srcCSS = srcCSS;

exports.bower = bower;
exports.release = release;
exports.default = bower;

// Watch task - lanciato con `gulp watch`, resta in attesa e ogni volta che viene modificato un asset in src
// viene aggiornata la dist
gulp.task('watch', function () {
    gulp.watch('assets/src/css/*.css', gulp.series(srcCSS, CSS));
    gulp.watch('assets/src/css/print/*.css', gulp.series(srcCSS, CSS));
    gulp.watch('assets/src/css/themes/*.css', gulp.series(srcCSS, CSS));
    gulp.watch('assets/src/js/base/*.js', gulp.series(srcJS, JS));
    gulp.watch('assets/src/js/functions/*.js', gulp.series(srcJS, JS));
    gulp.watch('assets/src/img/*', gulp.series(images));
});
