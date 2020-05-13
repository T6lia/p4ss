$(function() {

    $('#side-menu').metisMenu();

});


function formatDate(d) {
    var dd = d.getDate()
    if ( dd < 10 ) dd = '0' + dd

    var mm = d.getMonth()+1
    if ( mm < 10 ) mm = '0' + mm

    var yy = d.getFullYear()
    if ( yy < 10 ) yy = '0' + yy

    return yy+'-'+mm+'-'+dd;
}


//Loads the correct sidebar on window load,
//collapses the sidebar on window resize.
// Sets the min-height of #page-wrapper to window size
$(function() {
    $(window).bind("load resize", function() {
        topOffset = 50;
        width = (this.window.innerWidth > 0) ? this.window.innerWidth : this.screen.width;
        if (width < 768) {
            $('div.navbar-collapse').addClass('collapse');
            topOffset = 100; // 2-row-menu
        } else {
            $('div.navbar-collapse').removeClass('collapse');
        }

        height = ((this.window.innerHeight > 0) ? this.window.innerHeight : this.screen.height) - 1;
        height = height - topOffset;
        if (height < 1) height = 1;
        if (height > topOffset) {
            $("#page-wrapper").css("min-height", (height) + "px");
        }
    });

    /**
    var url = window.location;
    console.log(url);
    var element = $('ul.nav a').filter(function() {
        var chemin = this.pathname;
        var split = chemin.split('/');
        var controller = split[split.length - 2];
        console.log('-------');
        console.log(chemin);
        console.log(split);
        console.log(controller);
        console.log(url.pathname);
        console.log(url.pathname.indexOf(controller));
        console.log(url.pathname.indexOf(controller) != -1);
        console.log(url.pathname.search(controller));
        console.log('*******');
        return url.pathname.indexOf(controller) != -1;
    }).parent().addClass('active').parent().addClass('in').parent();
    if (element.is('li')) {
        element.addClass('active');
    }
    // */

    $( "#datepicker" ).datepicker({
        inline: true,
        dateFormat: "yy-mm-dd",
        onSelect: function(){
            var d = new Date($(this).datepicker( "getDate" ));
            $('input[name=race_date]').val(formatDate(d));
        }
    });
});
