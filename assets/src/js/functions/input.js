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
    return new Input(name);
}

function Input(name) {
    this.element = $("[name='" + name + "']").last();

    // Fix per select multipli
    if (this.element.length === 0) {
        this.element = $("[name='" + name + "[]']").last();
    }

    // Controllo sulla gestione precedente
    if (!this.element.data("input-set")) {
        this.element.data("input-set", 1);
        this.element.data("required", this.element.attr("required"));
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
        value: this.element.val()
    };
}

Input.prototype.get = function () {
    let value = this.element.val();

    // Conversione del valore per le checkbox
    let group = this.element.closest(".form-group");
    if (group.find("input[type=checkbox]").length){
        value = parseInt(value) ? 1 : 0;
    }

    return value;
}

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
