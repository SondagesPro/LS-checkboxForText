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
    $("#"+$(this).attr("data-checkboxfor")).val($(this).val()).trigger("keyup").prop("readonly",true);
  }else{
    $("#"+$(this).attr("data-checkboxfor")).val("").prop("readonly",false);
  }
});
/* Set readonly after ready */
$(function() {
  $(":checkbox[data-checkboxfor]:checked").each(function(){
    $("#"+$(this).attr("data-checkboxfor")).prop("readonly",true);
  });
});
