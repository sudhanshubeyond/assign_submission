define([
    "jquery",
    "core/ajax",
    "core/templates",
    "core/notification",
    "core/str",
], function ($, ajax, templates, notification,str) {
    return /** @alias module:block_programs/programs */ {
        /**
         *
         * @method int]it
         */
        init: function () {
            var url = window.location.href;
            var cmid = getURLParameter("id", url);
            var userid = getURLParameter("userid", url);

            setInterval(function () {
                if ($('#id_fill_ai_grade').prop('disabled')) {
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
                                    if (data.grade != ' ' || data.errormessage != null) {
                                        $('#id_fill_ai_grade')
                                                .prop('disabled', false) // Enable the button
                                                .attr('title', '')       // Remove the tooltip
                                                .html('AI Grading');
                                    }
                                }
                            } catch (err) {
                                console.log('There is some issue please try again later');
                            }
                        },
                        error: function (err) {
                            console.log('AJAX error: ' + err.statusText);
                        }
                    });
                }

            }, 5000);

            $(document).on(
                    "click",
                    "#id_fill_ai_grade",
                    delay(function (e) {

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
                                        if (data.errormessage !== null) {
                                            const message = 'There is some issue with following message "' +
                                                    `<strong>${data.errormessage}</strong>` + '" kindly grade this manually';
                                            notification.alert('Grading Issue', message, 'OK');
                                        } else {

                                            const rubricdata = JSON.parse(data.rubricbreakdown);

                                            rubricdata.forEach(item => {
                                                const elementID = `advancedgrading-criteria-${item.criterionid}-levels-${item.selectedlevelid}`;
                                                const feedbckelement = `advancedgrading-criteria-${item.criterionid}-remark`;

                                                const levelElement = document.getElementById(elementID);
                                                const remarkTextarea = document.getElementById(feedbckelement);

                                                if (remarkTextarea) {
                                                    remarkTextarea.value = item.feedback;
                                                    remarkTextarea.style.display = 'block'; // optional: show if hidden
                                                } else {
                                                    console.warn(`Textarea not found for criterion ${item.criterionid}`);
                                                }

                                                if (levelElement) {
                                                    levelElement.click();
                                                } else {
                                                    console.warn(`Element with ID '${elementID}' not found.`);
                                                }
                                            });

                                            const editorDiv = document.getElementById('id_assignfeedbackcomments_editoreditable');
                                            if (editorDiv) {
                                                editorDiv.focus(); // Simulate focus
                                                editorDiv.click(); // Optional
                                                editorDiv.innerHTML = data.feedback;
                                                editorDiv.dispatchEvent(new Event('input', {bubbles: true})); // Notify Atto of change
                                            }
                                            $("#id_grade").val(data.grade);
                                        }
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
