jQuery(document).ready(function () {
    if (jQuery("#iframe").length != 0) {
        if (top.location.href.indexOf("#iframe") == -1)
        {
            top.location.href = location.href + '#iframe';
        }
    }

});