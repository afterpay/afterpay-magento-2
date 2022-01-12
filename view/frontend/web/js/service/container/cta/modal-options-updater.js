define([
    'jquery'
], function ($) {
    'use strict';
    return function (id, modalOptions) {
        const elem = $('#' + id);
        if (elem.length > 0 && elem[0].modalOptions) {
            elem[0].modalOptions.locale = modalOptions.locale;
            elem[0].modalOptions.cbtEnabled = modalOptions.cbtEnabled;
        }
    }
});
