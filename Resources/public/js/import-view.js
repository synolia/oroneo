define(function(require) {
    'use strict';

    var $ = require('jquery');
    var messenger = require('oroui/js/messenger');
    messenger.setup({
        container: '#flash-messages .flash-messages-holder',
        template: _.template($.trim($('#message-item-template').html()))
    });

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

        //Tests the connection
        $('#oro_importexport_import_testConnectionBtn').click(function() {
            $.get(Routing.generate('synolia_oroneo_test_configuration'), function (data) {
                messenger.notificationFlashMessage(data['type'], data['message']);
            });
        });
    };
});
