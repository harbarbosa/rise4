$(function(){
  // Submit via ajax
  $(document).on('submit', '#os-form', function(e){
    e.preventDefault();
    var $f = $(this);
    $.ajax({
      url: $f.attr('action'),
      type: 'POST',
      data: $f.serialize(),
      dataType: 'json',
      success: function(res){
        if (res && res.success) {
          appAlert.success(AppLanugage.saved);
          $('#ajaxModal').modal('hide');
          if (res.data && res.id) {
            $("#os-table").appTable({newData: res.data, dataId: res.id});
          }
        } else {
          appAlert.error(AppLanugage.somethingWentWrong);
        }
      },
      error: function(){ appAlert.error(AppLanugage.somethingWentWrong); }
    });
    return false;
  });
});
