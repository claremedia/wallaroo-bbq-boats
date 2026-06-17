/**
 * Wallaroo BBQ Boats — admin.js
 * Handles the logo media uploaders on the Site Settings page.
 * Supports multiple independent uploaders (site logo, footer logo, …),
 * each wrapped in a .wallaroo-logo-uploader container.
 * Loaded only on the wallaroo-settings admin page (see theme-options.php).
 */
( function ( $ ) {
    'use strict';

    $( '.wallaroo-logo-uploader' ).each( function () {
        var $wrap   = $( this );
        var $upload = $wrap.find( '.wallaroo-upload-logo' );
        var $id     = $wrap.find( '.wallaroo-logo-id' );
        var frame;

        // Open media library on upload button click
        $upload.on( 'click', function ( e ) {
            e.preventDefault();

            if ( frame ) {
                frame.open();
                return;
            }

            frame = wp.media( {
                title:    'Select or Upload Logo',
                button:   { text: 'Use this logo' },
                multiple: false,
                library:  { type: [ 'image' ] },
            } );

            frame.on( 'select', function () {
                var attachment = frame.state().get( 'selection' ).first().toJSON();

                $id.val( attachment.id );
                $wrap.find( '.wallaroo-logo-img' ).attr( 'src', attachment.url );
                $wrap.find( '.wallaroo-logo-preview' ).show();
                $upload.text( $upload.data( 'replace-label' ) || 'Replace Logo' );

                // Show remove button if it doesn't exist yet
                if ( ! $wrap.find( '.wallaroo-remove-logo' ).length ) {
                    $upload.after(
                        '<button type="button" class="wallaroo-remove-logo button button-link-delete" style="margin-left:8px;">Remove</button>'
                    );
                }
            } );

            frame.open();
        } );

        // Remove logo
        $wrap.on( 'click', '.wallaroo-remove-logo', function ( e ) {
            e.preventDefault();
            $id.val( '0' );
            $wrap.find( '.wallaroo-logo-preview' ).hide();
            $upload.text( $upload.data( 'upload-label' ) || 'Upload Logo' );
            $wrap.find( '.wallaroo-remove-logo' ).remove();
        } );
    } );

} )( jQuery );
