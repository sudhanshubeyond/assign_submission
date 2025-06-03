define([
    "jquery",
    "core/ajax",
    "core/templates",
    "core/notification",
], function ($, ajax, templates, notification) {
    return /** @alias module:block_programs/programs */ {
        /**
         *
         * @method int]it
         */
        init: function () {
            var url = window.location.href;
            // Add a click handler to the button
            var cmid = getURLParameter("id", url);
            var userid = getURLParameter("userid", url);
            
            $(document).on(
                    "click",
                    "#id_fill_ai_grade",
                    delay(function (e) {
                        console.log(cmid);
                        console.log(userid);


                        var programid = $(this).attr("data-programid");
                        var id = $(this).attr("data-id");
                        var cid = $(this).attr("cmid");

                        $.ajax({
                            url: M.cfg.wwwroot + '/local/assign_submission/ajax.php',
                            method: 'POST',
                            data: {
                                action: 'getgrades',
                                cmid: cmid,
                                userid: userid,
                                sesskey: M.cfg.sesskey
                            },
                            success: function (data) {
                                try {
                                    if (data.status == 200) {
                                        $("#id_assignfeedbackcomments_editoreditable").html(data.feedback);
                                        $("#id_grade").val(data.grade);
                                    }
                                } catch (err) {
                                    alert('There is some issue please try again later');
                                }
                            },
                            error: function (err) {
                                alert('AJAX error: ' + err.statusText);
                            }
                        });

                    }, 100)
                    );
        },
    };

    function getURLParameter(name, url) {
        return (
                decodeURIComponent(
                        (new RegExp("[?|&]" + name + "=" + "([^&;]+?)(&|#|;|$)").exec(
                                url
                                ) || [null, ""])[1].replace(/\+/g, "%20")
                        ) || null
                );
    }
    //Function for delay the keyup event
    function delay(callback, ms) {
        var timer = 0;
        return function () {
            var context = this,
                    args = arguments;
            clearTimeout(timer);
            timer = setTimeout(function () {
                callback.apply(context, args);
            }, ms || 0);
        };
    }
});

//
//define(['jquery', 'core/ajax'], function ($, Ajax) {
//    return {
//        init: function (cmId) {
//            console.log('Module loaded with cmId:', cmId);
//
//            $('#id_my_button').on('click', function (e) {
//                e.preventDefault();
//
//                $.ajax({
//                    url: M.cfg.wwwroot + '/local/assign_submission/ajax.php',
//                    method: 'POST',
//                    data: {
//                        action: 'mycustomaction',
//                        cmid: cmId,
//                        sesskey: M.cfg.sesskey
//                    },
//                    success: function (response) {
//                        try {
//                            alert('Response: ' + response.status);
//                        } catch (err) {
//                            alert('Error parsing response');
//                        }
//                    },
//                    error: function (err) {
//                        alert('AJAX error: ' + err.statusText);
//                    }
//                });
//            });
//        }
//    };
//});