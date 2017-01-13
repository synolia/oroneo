define(function(require) {
    'use strict';

    var $ = require('jquery');

    return function(options) {

        // Reset checkbox state.
        $('input[name^="oro_importexport_import[isManualImport]"]').prop('checked', false);

        // Show and hide file upload depending on what the user wants.
        $('input[name^="oro_importexport_import[isManualImport]"]').change(function() {
            if (this.checked) {
                $('.oroneo_file_upload').removeClass('hide');
            } else {
                $('.oroneo_file_upload').addClass('hide');
            }
        });
    };
});
