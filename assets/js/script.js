$(document).ready(function() {

    // --- 1. INITIALIZE DATATABLES ---
    $('.data-table, #sessionsTable').DataTable();

    // --- 2. MOBILE SIDEBAR SLIDE TOGGLE ---
    $('#mobile-menu-toggle').on('click', function() {
        $('#sidebar').toggleClass('active');
    });

    // --- 3. DESKTOP SIDEBAR COLLAPSE TOGGLE ---
    $('#desktop-sidebar-toggle').on('click', function() {
        $('#sidebar, #main-content').toggleClass('collapsed');
        // Instantly close any open submenus when collapsing the main sidebar
        if ($('#sidebar').hasClass('collapsed')) {
            $('#sidebar .submenu').hide();
            $('#sidebar .has-submenu').removeClass('open');
        }
    });

    // --- 4. ACCORDION SUBMENU TOGGLE ---
    $('.has-submenu > a.nav-link').on('click', function(e) {
        e.preventDefault();

        // Do not allow opening submenus when the sidebar is collapsed
        if ($('#sidebar').hasClass('collapsed')) {
            return;
        }

        var parentLi = $(this).parent('li');
        var submenu = parentLi.find('.submenu');

        // If the clicked menu is already open, close it
        if (parentLi.hasClass('open')) {
            submenu.slideUp(250);
            parentLi.removeClass('open');
        } else {
            // Otherwise, close all other menus and open the clicked one
            $('.has-submenu.open').find('.submenu').slideUp(250);
            $('.has-submenu.open').removeClass('open');

            submenu.slideDown(250);
            parentLi.addClass('open');
        }
    });

});