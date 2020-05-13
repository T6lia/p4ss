/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */


jQuery(document).ready(function ($) {
    var winHeight = window.innerHeight;
//$('.sidebar').slimScroll({
//    position: 'right',
//    height: '150px',
//    railVisible: true,
//    alwaysVisible: true
//});
    function setScroll() {
        $(".sidebar").slimScroll({
            alwaysVisible: true,
            height: (winHeight - 100) + 'px',
            size: '10px',
            position: 'right',
            color: 'rgba(170, 165, 160, 0.6)',
            alwaysVisible: true,
            distance: '0px',
//    start: $('#child_image_element'),
            railVisible: false,
            railColor: '#222',
            railOpacity: 0.3,
            wheelStep: 20,
            allowPageScroll: true,
            disableFadeOut: false
        });
    }

    setScroll();

    $('.txt-code').attr('autocomplete', 'off');
    $('.datepicker').attr('autocomplete', 'off');

    $(window).on("resize", setScroll);

    $('[data-toggle="tooltip"]').tooltip();
    $('.datepicker').datepicker({
        dateFormat: 'dd/mm/yy',
        changeMonth: true,
        changeYear: true,
        altField: "#datepicker",
        closeText: 'Fermer',
        prevText: 'Précédent',
        nextText: 'Suivant',
        currentText: 'Aujourd\'hui',
        monthNames: ['Janvier', 'Février', 'Mars', 'Avril', 'Mai', 'Juin', 'Juillet', 'Août', 'Septembre', 'Octobre', 'Novembre', 'Décembre'],
        monthNamesShort: ['Janv.', 'Févr.', 'Mars', 'Avril', 'Mai', 'Juin', 'Juil.', 'Août', 'Sept.', 'Oct.', 'Nov.', 'Déc.'],
        dayNames: ['Dimanche', 'Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi'],
        dayNamesShort: ['Dim.', 'Lun.', 'Mar.', 'Mer.', 'Jeu.', 'Ven.', 'Sam.'],
        dayNamesMin: ['D', 'L', 'M', 'M', 'J', 'V', 'S'],
        weekHeader: 'Sem.',
        yearRange: "-88:+22",
    });
    $('.form-valida').valida();
    $('.datepicker').change(function () {
        e = jQuery.Event("keydown");
        e.wich = 50;
        $(this).trigger(e);
    });

    $('#chooseFile').bind('change', function () {
        var filename = $('#chooseFile').val();

        if (/^\s*$/.test(filename)) {
            $(".file-upload").removeClass('active');
            $("#noFile").text("No file chosen...");
        } else {
            $(".file-upload").addClass('active');
            $("#noFile").text(filename.replace("C:\\fakepath\\", ""));
        }
    });
    $('#fichier').bind('change', function () {
        var filename = $(this).val();
        if (/^\s*$/.test(filename)) {
            $(".file-upload").removeClass('active');
            $("#noFile").text("No file chosen...");
        } else {
            $(".file-upload").addClass('active');
            $("#noFile").text(filename.replace("C:\\fakepath\\", ""));
        }
    });
});

/**
 * Fonction pour le poup loading
 */
function showLoadingPopup(text) {
    $("#modal-wait-please-text").text(text);
    $('#modal-wait-please').modal('show');
}
function hideLoadingPopup() {
    $('#modal-wait-please').modal('hide');
}



/**
 * Fonction pour la mise à jour des input file
 */
$(function () {
    $('input#type_file').change(function () {
        var nom = $(this).val();
        console.log(nom);
        $('div#noFile').html(nom);
        if (/^s*$/.test(nom)) {
            $('.file-upload').removeClass('active');
            $('#noFile').text('No file chosen...');
        } else {
            $('.file-upload').addClass('active');
            $('#noFile').text(nom.replace('C:\\fakepath\\', ''));
        }
    });

    var $th = $('.tableFixHead').find('thead th')
    $('.tableFixHead').on('scroll', function () {
        $th.css('transform', 'translateY(' + this.scrollTop + 'px)');
    });
    setDecimal();    
});

function partitionerList(items, size) {
    var result = _.groupBy(items, function (item, i) {
        return Math.floor(i / size);
    });
    return _.values(result);
}

function setDecimal(){
    $(".decimal").keyup(function () {
        $(this).val($(this).val().replace(',', '.'));
    });
}
