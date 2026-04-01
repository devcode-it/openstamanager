/**
 * Funzioni comuni per la gestione dei tour guidato via AJAX
 * Salvataggio nello stato del database invece di localStorage
 */

/**
 * Salva un tour come completato nel database
 * @param {string} idModule ID del modulo
 * @returns {Promise<boolean>} Promise che restituisce true se successo, false altrimenti
 */
function parseTourAjaxResponse(response) {
    if (typeof response === 'object' && response !== null) {
        return response;
    }

    const text = String(response || '').trim();
    if (!text) {
        throw new Error('Risposta vuota dal server');
    }

    return JSON.parse(text);
}

function getDriverJsFactory() {
    if (typeof window !== 'undefined' && window.driver) {
        if (window.driver.js && typeof window.driver.js.driver === 'function') {
            return window.driver.js.driver;
        }

        if (typeof window.driver.js === 'function') {
            return window.driver.js;
        }

        if (typeof window.driver.driver === 'function') {
            return window.driver.driver;
        }

        if (typeof window.driver === 'function') {
            return window.driver;
        }
    }

    if (typeof driver !== 'undefined' && driver) {
        if (driver.js && typeof driver.js.driver === 'function') {
            return driver.js.driver;
        }

        if (typeof driver.js === 'function') {
            return driver.js;
        }

        if (typeof driver.driver === 'function') {
            return driver.driver;
        }

        if (typeof driver === 'function') {
            return driver;
        }
    }

    return null;
}

function waitForDriverJsFactory(maxAttempts = 20, delay = 250) {
    return new Promise(function(resolve) {
        let currentAttempt = 0;

        function checkDriverFactory() {
            const driverFactory = getDriverJsFactory();

            if (typeof driverFactory === 'function') {
                resolve(driverFactory);
                return;
            }

            currentAttempt += 1;

            if (currentAttempt >= maxAttempts) {
                resolve(null);
                return;
            }

            setTimeout(checkDriverFactory, delay);
        }

        checkDriverFactory();
    });
}

const tourCompletionRequests = {};
const activeTourDrivers = {};
let isTourExitHandlerBound = false;
const TOUR_POPOVER_CLASS = 'driver-popover-osm';
const TOUR_INTRO_POPOVER_CLASS = 'driver-popover-osm-intro';

function addClasses(element, classes) {
    if (!element || !classes) {
        return;
    }

    classes.split(' ').filter(Boolean).forEach(function(className) {
        element.classList.add(className);
    });
}

function decorateTourDescriptionContent(container) {
    if (!container) {
        return;
    }

    container.querySelectorAll('.tour-step').forEach(function(step) {
        addClasses(step, 'small');
    });

    container.querySelectorAll('.tour-step p').forEach(function(paragraph, index, paragraphs) {
        addClasses(paragraph, index === paragraphs.length - 1 ? 'mb-0' : 'mb-2');
    });

    container.querySelectorAll('.tour-step ul').forEach(function(list) {
        addClasses(list, 'mb-0 pl-3');
    });

    container.querySelectorAll('.tour-step li').forEach(function(item, index, items) {
        addClasses(item, index === items.length - 1 ? 'mb-0' : 'mb-2');
    });

    container.querySelectorAll('.tour-step strong').forEach(function(strong) {
        addClasses(strong, 'text-dark');
    });
}

function decorateTourPopover(popover) {
    if (!popover) {
        return;
    }

    addClasses(popover.wrapper, TOUR_POPOVER_CLASS + ' shadow');
    addClasses(popover.title, 'text-primary');
    addClasses(popover.description, 'text-muted');
    addClasses(popover.footer, 'd-flex align-items-center justify-content-between flex-wrap');
    addClasses(popover.progress, 'small text-muted');
    addClasses(popover.footerButtons, 'd-flex align-items-center flex-wrap');
    addClasses(popover.previousButton, 'btn btn-default btn-sm');
    addClasses(popover.nextButton, 'btn btn-primary btn-sm');
    addClasses(popover.closeButton, 'd-flex align-items-center justify-content-center');

    decorateTourDescriptionContent(popover.description);
}

function isIntroTourStep(step, index) {
    if (!step || index !== 0 || typeof document === 'undefined') {
        return false;
    }

    return step.element === document.body || step.element === document.documentElement || step.element === 'body' || step.element === 'html';
}

function getTourModuleKey(idModule) {
    if (idModule !== undefined && idModule !== null && idModule !== '') {
        return String(idModule);
    }

    if (typeof globals !== 'undefined' && globals && globals.id_module !== undefined && globals.id_module !== null && globals.id_module !== '') {
        return String(globals.id_module);
    }

    return 'global';
}

function registerActiveTourDriver(idModule, getTourDriver) {
    if (typeof getTourDriver !== 'function') {
        return;
    }

    activeTourDrivers[getTourModuleKey(idModule)] = getTourDriver;
}

function getActiveTourDriver(idModule) {
    const getter = activeTourDrivers[getTourModuleKey(idModule)];

    if (typeof getter === 'function') {
        return getter();
    }

    return null;
}

function getResolvedTourModuleId(idModule) {
    const key = getTourModuleKey(idModule);

    return key === 'global' ? null : key;
}

function bindTourExitHandler() {
    if (isTourExitHandlerBound || typeof document === 'undefined') {
        return;
    }

    document.addEventListener('click', function(event) {
        const eventTarget = event.target && event.target.nodeType === 3
            ? event.target.parentElement
            : event.target;

        const exitButton = eventTarget && typeof eventTarget.closest === 'function'
            ? eventTarget.closest('.tour-popover-exit-btn')
            : null;

        if (!exitButton) {
            return;
        }

        event.preventDefault();
        event.stopPropagation();

        const idModule = exitButton.getAttribute('data-tour-module-id');
        const resolvedModuleId = getResolvedTourModuleId(idModule);
        const tourDriver = getActiveTourDriver(resolvedModuleId);

        completeTourAndCloseDB(tourDriver, resolvedModuleId);
    }, true);

    isTourExitHandlerBound = true;
}

function saveTourCompletedDB(idModule) {
    const moduleKey = getTourModuleKey(idModule);
    const existingRequest = tourCompletionRequests[moduleKey];

    if (existingRequest) {
        return existingRequest;
    }

    const resolvedModuleId = getResolvedTourModuleId(idModule);
    if (!resolvedModuleId) {
        return Promise.resolve(false);
    }

    const request = new Promise(function(resolve) {
        $.ajax({
            url: globals.rootdir + '/ajax.php?op=save_tour_completed&id_module=' + resolvedModuleId,
            type: 'GET',
            dataType: 'text',
            success: function(response) {
                try {
                    const data = parseTourAjaxResponse(response);
                    if (data && data.success === true) {
                        resolve(true);
                    } else {
                        delete tourCompletionRequests[moduleKey];
                        console.error('Errore durante il salvataggio del tour:', data ? data.message : 'Nessun messaggio');
                        resolve(false);
                    }
                } catch (e) {
                    delete tourCompletionRequests[moduleKey];
                    console.error('Errore nel parsing della risposta:', e, 'Response:', response);
                    resolve(false);
                }
            },
            error: function(_, status, error) {
                delete tourCompletionRequests[moduleKey];
                console.error('Errore AJAX durante il salvataggio del tour:', error, 'Status:', status);
                resolve(false);
            }
        });
    });

    tourCompletionRequests[moduleKey] = request;

    return request;
}

/**
 * Verifica se un tour è stato completato
 * @param {string} idModule ID del modulo
 * @returns {Promise<boolean>} Promise che restituisce true se completato, false altrimenti
 */
function isTourCompletedDB(idModule) {
    return new Promise(function(resolve) {
        $.ajax({
            url: globals.rootdir + '/ajax.php?op=is_tour_completed&id_module=' + idModule,
            type: 'GET',
            dataType: 'text',
            success: function(response) {
                try {
                    const data = parseTourAjaxResponse(response);
                    resolve(data && data.completed === true);
                } catch (e) {
                    console.error('Errore nel parsing della risposta:', e, 'Response:', response);
                    resolve(false);
                }
            },
            error: function(_, status, error) {
                console.error('Errore AJAX durante la verifica del tour:', error, 'Status:', status);
                resolve(false);
            }
        });
    });
}

function injectTourExitButton(popover, getTourDriver, getModuleId) {
    if (!popover || !popover.footerButtons || popover.footerButtons.querySelector('.tour-popover-exit-btn')) {
        return;
    }

    const idModule = typeof getModuleId === 'function' ? getModuleId() : null;
    const resolvedModuleId = getResolvedTourModuleId(idModule);

    registerActiveTourDriver(resolvedModuleId, getTourDriver);
    bindTourExitHandler();

    const exitButton = document.createElement('button');
    exitButton.type = 'button';
    exitButton.className = 'tour-popover-exit-btn btn btn-default btn-sm';
    exitButton.textContent = 'Esci';

    if (resolvedModuleId) {
        exitButton.setAttribute('data-tour-module-id', resolvedModuleId);
    }

    popover.footerButtons.insertBefore(exitButton, popover.footerButtons.firstChild);
}

function addExitButtonsToTourSteps(steps, getTourDriver, getModuleId) {
    if (!Array.isArray(steps)) {
        return steps;
    }

    return steps.map(function(step, index) {
        const popoverConfig = step && step.popover ? step.popover : {};
        const originalOnPopoverRender = popoverConfig.onPopoverRender;
        const popoverClasses = [popoverConfig.popoverClass, TOUR_POPOVER_CLASS];

        if (isIntroTourStep(step, index)) {
            popoverClasses.push(TOUR_INTRO_POPOVER_CLASS);
        }

        const updatedPopover = Object.assign({}, popoverConfig, {
            popoverClass: popoverClasses.filter(Boolean).join(' '),
            onPopoverRender: function(popover, options) {
                if (typeof originalOnPopoverRender === 'function') {
                    originalOnPopoverRender(popover, options);
                }

                injectTourExitButton(popover, getTourDriver, getModuleId);
                decorateTourPopover(popover);
            }
        });

        if (Array.isArray(popoverConfig.showButtons) && !popoverConfig.showButtons.includes('close')) {
            updatedPopover.showButtons = popoverConfig.showButtons.concat('close');
        }

        return Object.assign({}, step, {
            popover: updatedPopover
        });
    });
}

/**
 * Completa il tour e salva lo stato nel database
 * @param {Object} tourDriver Istanza del tour (Driver.js)
 * @param {string} idModule ID del modulo
 * @returns {Promise<void>}
 */
function completeTourAndCloseDB(tourDriver, idModule) {
    if (tourDriver) {
        tourDriver.destroy();
    }

    return saveTourCompletedDB(idModule);
}
