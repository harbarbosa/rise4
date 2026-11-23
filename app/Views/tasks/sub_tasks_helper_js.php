<script type="text/javascript">
(function initializeSubTaskHelper(attempt) {
    if (!window.jQuery) {
        if (attempt < 100) {
            window.setTimeout(function () { initializeSubTaskHelper(attempt + 1); }, 50);
        }
        return;
    }

    var $ = window.jQuery;
    //search sub tasks after clicking on filter sub task icon
    $('body').on('click', '.filter-sub-task-kanban-button', function (e) {
        //stop the default modal anchor action
        e.stopPropagation();
        e.preventDefault();

        var value = $(this).attr('main-task-id');
        if ($(".custom-filter-search").val() === value) { //toggle search value
            value = "";
        }

        $(".custom-filter-search").val(value).focus().select();

        var key = $.Event("keyup", {keyCode: 13});
        $(".custom-filter-search").trigger(key);
    });
})(0);
</script>