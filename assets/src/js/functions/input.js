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

function input(name) {
    let element;

    // Selezione tramite jQuery
    if (name instanceof jQuery) {
        element = name.last();
    }

    // Selezione tramite JS diretto
    else if (isElement(name)) {
        element = $(name);
    }

    // Selezione per nome
    else {
        element = $("[name='" + name + "']").last();

        // Fix per select multipli
        if (element.length === 0) {
            element = $("[name='" + name + "[]']").last();
        }
    }

    if (!element.data("input-controller")) {
        return new Input(element);
    } else {
        const controller = element.data("input-controller");

        if (!element.data("input-init")) {
            controller.init();
        }

        return controller;
    }
}

/**
 *
 * @constructor
 * @param {jQuery} element
 */
function Input(element) {
    this.element = element;

    this.element.data("input-controller", this);
    this.element.data("required", this.element.attr("required"));

    this.init();
}


Input.prototype.init = function () {
    let initCompleted = false;
    let htmlElement = this.element[0];

    // Operazioni di inizializzazione per input specifici
    // Inizializzazione per date
    if (this.element.hasClass('timestamp-picker')) {
        initCompleted = initTimestampInput(htmlElement);
    } else if (this.element.hasClass('datepicker')) {
        initCompleted = initDateInput(htmlElement);
    } else if (this.element.hasClass('timepicker')) {
        initCompleted = initTimeInput(htmlElement);
    }

    // Inizializzazione per campi numerici
    else if (this.element.hasClass('number-input')) {
        initCompleted = initNumberInput(htmlElement);
    }

    // Inizializzazione per textarea
    else if (this.element.hasClass('editor-input')) {
        initCompleted = initEditorInput(htmlElement);
    }

    // Inizializzazione per textarea
    else if (this.element.hasClass('autosize')) {
        initCompleted = initTextareaInput(htmlElement);
    }

    // Inizializzazione per select
    else if (this.element.hasClass('select-input')) {
        initCompleted = initSelectInput(htmlElement);
    }

    // Inizializzazione alternativa per maschere
    else {
        initCompleted = initMaskInput(htmlElement);
    }

    this.element.data("input-init", initCompleted);
}

Input.prototype.getElement = function () {
    return this.element;
}

Input.prototype.setDisabled = function (value) {
    if (value) {
        return this.disable();
    } else {
        return this.enable();
    }
}

Input.prototype.disable = function () {
    this.element.addClass("disabled")
        .attr("disabled", true)
        .attr("readonly", false)
        .attr("required", false);

    let group = this.element.closest(".form-group");

    // Disabilitazione eventuali pulsanti relativi
    group.find("button")
        .addClass("disabled");

    // Disabilitazione per checkbox
    group.find(".btn-group label")
        .addClass("disabled");
    group.find("input[type=checkbox]")
        .attr("disabled", true)
        .attr("readonly", false)
        .addClass("disabled");

    // Gestione dell'editor
    if (this.element.hasClass("editor-input")) {
        const name = this.element.attr("id");
        CKEDITOR.instances[name].setReadOnly(true);
    }

    return this;
}

Input.prototype.enable = function () {
    this.element.removeClass("disabled")
        .attr("disabled", false)
        .attr("readonly", false)
        .attr("required", this.element.data("required"));

    let group = this.element.closest(".form-group");

    // Abilitazione eventuali pulsanti relativi
    group.find("button")
        .removeClass("disabled");

    // Abilitazione per checkbox
    group.find(".btn-group label")
        .removeClass("disabled");
    group.find("input[type=checkbox]")
        .attr("disabled", false)
        .attr("readonly", false)
        .removeClass("disabled");

    // Gestione dell'editor
    if (this.element.hasClass("editor-input")) {
        const name = this.element.attr("id");
        CKEDITOR.instances[name].setReadOnly(false);
    }

    return this;
}

Input.prototype.getData = function () {
    if (this.element.is('select')) {
        return this.element.selectData();
    }

    return {
        value: this.get()
    };
}

/**
 * Restituisce il valore corrente dell'input.
 *
 * @returns {string|number}
 */
Input.prototype.get = function () {
    let value = this.element.val();

    // Gestione dei valori per l'editor
    if (this.element.hasClass("editor-input")) {
        const name = this.element.attr("id");
        value = typeof CKEDITOR !== 'undefined' ? CKEDITOR.instances[name].getData() : value;
    }

    // Conversione del valore per le checkbox
    let group = this.element.closest(".form-group");
    if (group.find("input[type=checkbox]").length) {
        return parseInt(value) ? 1 : 0;
    }

    // Gestione dei valori numerici
    if (this.element.hasClass("number-input")) {
        const autonumeric = this.element.data("autonumeric");

        if (autonumeric) {
            return parseFloat(autonumeric.rawValue);
        }
        // In attesa dell'inizializzazione per autonumeric, il valore registrato è interpretabile
        else {
            return parseFloat(value);
        }
    }

    return value;
}

/**
 * Imposta il valore per l'input.
 *
 * @param value
 * @returns {Input}
 */
Input.prototype.set = function (value) {
    this.element.val(value).trigger("change");

    return this;
}

Input.prototype.setRequired = function (value) {
    this.element.attr("required", value)
        .data("required", value);

    return this;
}

// Eventi permessi
Input.prototype.change = function (callable) {
    return this.on("change", callable);
}

Input.prototype.on = function (event, callable) {
    return this.element.on(event, callable);
}

Input.prototype.off = function (event) {
    return this.element.off(event);
}

Input.prototype.trigger = function (event, callable) {
    return this.element.trigger(event, callable);
}

Input.prototype.destroy = function () {
    if (this.element.data('select2')) {
        this.element.select2().select2("destroy")
    }

    // Gestione della distruzione per l'editor
    if (this.element.hasClass("editor-input")) {
        const name = this.element.attr("id");
        CKEDITOR.instances[name].destroy();
    }

    this.element.data("input-controller", null);
}

/**
 * Returns true if it is a DOM node.
 *
 * @param o
 * @returns boolean
 *
 * @source https://stackoverflow.com/a/384380
 */
function isNode(o) {
    return (
        typeof Node === "object" ? o instanceof Node :
            o && typeof o === "object" && typeof o.nodeType === "number" && typeof o.nodeName === "string"
    );
}

/**
 * Returns true if it is a DOM element.
 *
 * @param o
 * @returns boolean
 *
 * @source https://stackoverflow.com/a/384380
 */
function isElement(o) {
    return (
        typeof HTMLElement === "object" ? o instanceof HTMLElement : // DOM2
            o && typeof o === "object" && o.nodeType === 1 && typeof o.nodeName === "string"
    );
}
