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
        return element.data("input-controller");
    }
}

/**
 *
 * @constructor
 * @param {jQuery} element
 */
function Input(element) {
    this.element = element;

    // Controllo sulla gestione precedente
    if (!this.element.data("input-set")) {
        return;
    }

    this.element.data("input-set", 1);
    this.element.data("required", this.element.attr("required"));

    // Operazioni di inizializzazione per input specifici
    // Inizializzazione per date
    if (this.element.hasClass('timestamp-picker')) {
        initTimestampInput(this.element);
    } else if (this.element.hasClass('datepicker')) {
        initDateInput(this.element);
    } else if (this.element.hasClass('timepicker')) {
        initTimeInput(this.element);
    }

    // Inizializzazione per campi numerici
    else if (this.element.hasClass('decimal-number')) {
        initNumberInput(this.element);
    }

    // Inizializzazione per textarea
    else if (this.element.hasClass('autosize')) {
        initTextareaInput(this.element);
    }

    // Inizializzazione per select
    else if (this.element.hasClass('superselect') || this.element.hasClass('superselectajax')) {
        initSelectInput(this.element);
    }

    // Inizializzazione alternativa per maschere
    else {
        initMaskInput(this.element);
    }
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

    // Conversione del valore per le checkbox
    let group = this.element.closest(".form-group");
    if (group.find("input[type=checkbox]").length) {
        return parseInt(value) ? 1 : 0;
    }

    // Gestione dei valori numerici
    if (this.element.hasClass("decimal-number")) {
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
Input.prototype.change = function (event) {
    return this.element.change(event);
}

Input.prototype.on = function (event, action) {
    return this.element.on(event, action(event));
}

Input.prototype.off = function (event) {
    return this.element.off(event);
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
