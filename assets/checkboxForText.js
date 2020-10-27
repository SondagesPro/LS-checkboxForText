/**
 * @file checkboxForText javascript system
 * @author Denis Chenu
 * @copyright 2016-2017 Denis Chenu <http://www.sondages.pro>
 * @license magnet:?xt=urn:btih:d3d9a9a6595521f9666a5e94cc830dab83b65699&dn=expat.txt Expat (MIT)
 */
/* Event on click on checkbox */
$(document).on('click',':checkbox[data-checkboxfor]',function(){
  if($(this).is(":checked")){
    $(":checkbox[data-checkboxfor][name='"+$(this).attr("name")+"']").not(this).prop('checked',false);
    $("#"+$(this).attr("data-checkboxfor")).val($(this).data('updatevalue')).trigger("keyup").prop("disabled",true);
  }else{
    $("#"+$(this).attr("data-checkboxfor")).val("").trigger("keyup").prop("disabled",false);
  }
  $("#"+$(this).attr("data-checkboxfor")+"_datetimepicker.date-timepicker-group").trigger("dp.change");
});
/* Set readonly after ready */
$(function() {
  $(":checkbox[data-checkboxfor]:checked").each(function(){
    $("#"+$(this).attr("data-checkboxfor")).prop("disabled",true);
  });
});
