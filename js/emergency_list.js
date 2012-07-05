/*
 * Provides the autocomplete function with an array of emergency names itself
 * provided by the emergency query php function.
 */
$(function () {

    var myString = $('#emergency_list').html();
    var availableTags = myString.split("*");

    $("#tags").autocomplete({
        source: availableTags
    });

});