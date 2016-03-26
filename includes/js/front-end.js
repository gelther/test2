/*Custom register/login tabs*/
/* global wbnId, ajaxurl, admin */

jQuery(document).on('click', '#custom-tabs li a', function ()
{
    /*Remove active*/
    jQuery('#custom-tabs li').removeClass('active');
    jQuery('.content-wraper .tab-content').addClass('hide');

    /*add active*/
    jQuery(this).parent('li').addClass('active');
    jQuery('.content-wraper .tab-content:eq(' + jQuery(this).parent('li').index() + ')').removeClass('hide');
});

/*
 * 
 * Header bar
 * 
 */
jQuery(document).ready(function ($) {
    var loadedQ = 0;
    $('#wswebinar_private_que').hide().show().hide();
    $('#wswebinar_open_msg_cntr').click(function (e) {
        $(this).toggleClass('message-center-active').removeClass('message-center-newmsg');
        $('#wswebinar_private_que').toggle();
        e.stopPropagation();
    });
    $('#wswebinar_private_que').mousemove(function () {
        $('#wswebinar_open_msg_cntr').removeClass('message-center-newmsg');
    });
    $(document).click(function (e) {
        if (!$(e.target).is('#wswebinar_private_que, #wswebinar_private_que *')) {
            $('#wswebinar_open_msg_cntr').removeClass('message-center-active')
            $("#wswebinar_private_que").hide();
        }
    });

    if (typeof wbnId !== 'undefined')
        setInterval(function () {
            var datas = {action: 'retrieveQuestions', webinar_id: wbnId, last: loadedQ, getAsObject: true, orderByDESC: true};
            jQuery.ajax({type: 'POST', data: datas, url: ajaxurl, dataType: 'json',
                success: function (data) {
                    if (admin && data.text.length > 0) {
                        if (loadedQ === 0)
                            jQuery('#webinar_no_messages').empty();
                        loadedQ = data.id;
                        $.each(data.text.reverse(), function (i, q) {
                            var n = $('#webinar_no_messages .wswebinar-message').length,
                                    seperatorClass = n % 2 === 1 ? 'message-row' : '';

                            $('#webinar_no_messages').prepend('<div class="wswebinar-message ' + seperatorClass + '"><strong>' + q.name + '</strong>: ' + q.question + '</div><br>');
                            $('#wswebinar_open_msg_cntr').addClass('message-center-newmsg');
                        });
                    }
                    $('#wswebinar-live-page-ask-question-form')[data.show_questionbox ? 'slideDown' : 'slideUp']();
                }
            });
        }, 5000);
});

jQuery(document).on('click', '#webinar_show_questionbox', function (event) {
    event.preventDefault();
    jQuery(this).toggleClass('message-center-newmsg');
    var active = jQuery(this).hasClass('message-center-newmsg');
    jQuery.ajax({
        type: 'POST',
        data: {active: active, action: 'toggleLivePageAskQuestionForm', webinar_id: wbnId},
        url: ajaxurl
    });
});