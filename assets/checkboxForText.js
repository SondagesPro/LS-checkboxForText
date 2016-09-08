/**
 * @file checkboxForText javascript system
 * @author Denis Chenu
 * @copyright 2016 Denis Chenu <http://www.sondages.pro>
 * @license magnet:?xt=urn:btih:1f739d935676111cfff4b4693e3816e664797050&dn=gpl-3.0.txt GPL-v3-or-Later
 * @license magnet:?xt=urn:btih:0b31508aeb0634b347b8270c7bee4d411b5d4109&dn=agpl-3.0.txt AGPL v3.0
 * @license magnet:?xt=urn:btih:d3d9a9a6595521f9666a5e94cc830dab83b65699&dn=expat.txt Expat (MIT)
 */
/* Event on click on checkbox */
$(document).on('click',':checkbox[data-checkboxfor]',function(){
  if($(this).is(":checked")){
    $(":checkbox[data-checkboxfor][name='"+$(this).attr("name")+"']").not(this).prop('checked',false);
    $("#"+$(this).attr("data-checkboxfor")).val("").trigger("keyup").prop("readonly",true);
  }else{
    $("#"+$(this).attr("data-checkboxfor")).prop("readonly",false);
  }
});
/* Set readonly after ready */
$(function() {
  $(":checkbox[data-checkboxfor]:checked").each(function(){
    $("#"+$(this).attr("data-checkboxfor")).prop("readonly",true);
  });
});
