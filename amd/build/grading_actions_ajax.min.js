define(['jquery', 'core/ajax'], function($, Ajax) {
    return {
        init: function(cmId) {
            console.log('Module loaded with cmId:', cmId);

            $('#id_my_button').on('click', function(e) {
                e.preventDefault();

                $.ajax({
                    url: M.cfg.wwwroot + '/local/assign_submission/ajax.php',
                    method: 'POST',
                    data: {
                        action: 'mycustomaction',
                        cmid: cmId,
                        sesskey: M.cfg.sesskey
                    },
                    success: function(response) {
                        try {
                            alert('Response: ' + response.status);
                        } catch (err) {
                            alert('Error parsing response');
                        }
                    },
                    error: function(err) {
                        alert('AJAX error: ' + err.statusText);
                    }
                });
            });
        }
    };
});
