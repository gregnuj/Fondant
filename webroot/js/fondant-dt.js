$(document).ready(function() {
    // Setup - add a text input to each footer cell
    $('.indexTable tfoot th').each( function () {
        var title = $(this).text();
        $(this).html( '<input type="text" placeholder="Search '+title+'" />' );
    } );
 
    // Apply the search (not working)
    /*
    $('.indexTable').columns().every( function () {
        var that = this;
        $( 'input', this.footer() ).on( 'keyup change', function () {
            if (e.keyCode == 9 || e.keyCode == 13) {
                if ( that.search() !== this.value ) {
                    that
                        .search( this.value )
                        .draw();
                }
            }
        } );
    } );
    */
} );