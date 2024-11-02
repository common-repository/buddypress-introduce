jQuery(document).ready(function () {

    jQuery('li.friend-tab span.p').live('click', function () {
        var name = jQuery(this).parent().attr('id');
        var names = name.split('-');
        var username = names.pop();

        jQuery('#send-to-usernames').removeClass(username);
        jQuery('#send-to-usernames').val(jQuery('#send-to-usernames').attr('class'));

        jQuery(this).parent().remove();
    });

    jQuery("ul.first").autoCompletefb({ urlLookup: ajaxurl });

    jQuery('#invite-event-form').submit(function () {
        jQuery('#send-to-usernames').val(jQuery('#send-to-usernames').attr('class'));
    });
});