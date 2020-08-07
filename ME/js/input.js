function create_checkbox(id, val, label){

  var class_list = "checkbox_value";
  if(val) {
    class_list = "checkbox_value checked"
  }
  var ret_val = `
  <div class='flex_center'>
    <div class='custom_checkbox dynamic' id='${id}' data-value=${val}>
      <div class='${class_list}'>
      </div>
    </div>
    ${label}
  </div>`;
  return ret_val;
};
function set_checkbox(id, val){
  if(val == "1"){
    $('#'+id).attr('data-value', '1');
    $('#'+id+" .checkbox_value").addClass("checked");
  }
  else {
    $('#'+id).attr('data-value', '0');
    $('#'+id+" .checkbox_value").removeClass("checked");
  }
  return val;
};
// Switch
function create_switch_button(id, val, yes_label, no_label, red) {
  var class_list = "switch_button dynamic";
  var yes_label_class = "switch_label";
  var no_label_class = "switch_label";
  if(red){
    class_list = "switch_button dynamic switch_button_red";
    yes_label_class = "yes_label";
    no_label_class = "no_label";
  }
  var slider_class = "switch_slider";
  if(val == 0){
    slider_class = "switch_slider switch_off";
  }
  var ret_val = `
  <div class='flex_center'>
    <div class='${yes_label_class}'>${yes_label}</div>
    <div class='${class_list}' id='${id}' data-value=${val}><div class='${slider_class}'></div></div>
    <div class='${no_label_class}'>${no_label}</div>
  </div>`;
  return ret_val;
};

function set_switch(id, val){
  if(val == "1"){
    $('#'+id).attr('data-value', '1');
    $('#'+id+" .switch_slider").removeClass("switch_off");
  }
  else {
    $('#'+id).attr('data-value', '0');
    $('#'+id+" .switch_slider").addClass("switch_off");
  }
  return val;
};

$(document).ready(function(){


  $(".form_container").mousedown(function(e){
    // Wierd way of handling closing form on click outside, since stop propagation on form and just checking for click on the container breaks select2s
    let click_pos = e.pageX;
    let width = $(this).find(".form").width();
    let win_width = $(window).width();
    let max_left = (win_width - width) / 2;
    let min_right = win_width - max_left;
    let max_right = win_width - 20;
    max_right = max_right < min_right ? min_right : max_right;
    if(click_pos < max_left || (click_pos > min_right && click_pos < max_right)){
      $(".form_container").hide();
      $("html, body").css("overflow", "");
    }
  });


  


// Select2
$("#property_select").select2({
  minimumResultsForSearch: 5,
  width: "element"
});
$(".basic_select").select2({
    minimumResultsForSearch: Infinity,
    width: "element"
});
$(".multiple.room_select, .multiple.real_room_select").select2({
    placeholder: "Izaberi jedinice",
    minimumResultsForSearch: Infinity,
    width: "100%",
    allowClear: true,
    templateSelection: function(state){
      if(!state.id || rooms_map[state.id] == undefined){
        return state.text;
      }
      return rooms_map[state.id].shortname;
    }
});
$("#new_extra_rooms").select2({
    placeholder: "Izaberi jedinice",
    minimumResultsForSearch: Infinity,
    width: "element",
    allowClear: true,
    templateSelection: function(state){
      if(!state.id || rooms_map[state.id] == undefined){
        return state.text;
      }
      return rooms_map[state.id].shortname;
    }
});
$("#new_extra_specific_rooms").select2({
    placeholder: "Izaberi jedinice",
    minimumResultsForSearch: Infinity,
    width: "element",
    allowClear: true,
    templateSelection: function(state){
      if(!state.id || rooms_map[state.id] == undefined){
        return state.text;
      }
      return rooms_map[state.id].shortname;
    }
});
$(".multiple.country_select").select2({
    placeholder: "Izaberi države",
    minimumResultsForSearch: Infinity,
    width: "100%",
    allowClear: true,
    templateSelection: function(state){
      if(!state.id){
        return state.text;
      }
      return state.id;
    }
});
$(".multiple.channel_select").select2({
    placeholder: "Izaberi kanale",
    minimumResultsForSearch: Infinity,
    width: "100%",
    allowClear: true,
    templateResult: function formatState(state){
      if(!state.id) {
        return state.text;
      }
      if(state.id == -1){
        var $state = $(`<div class='select2_channel'><img src='img/ota/youbook.png'>${state.text}</div>`);
      }
      else {
        var $state = $(`<div class='select2_channel'><img src='${channels_map[state.id].logo}'>${state.text}</div>`);
      }
      return $state;
    }
});
$("#tab_engine select").select2({
    minimumResultsForSearch: Infinity,
    width: "element"
});
$(".color_select").select2({
    placeholder: "Izaberi boju",
    minimumResultsForSearch: Infinity,
    width: "element",
    templateResult: function formatState(state){
      if(!state.id) {
        return state.text;
      }
      var $state = $(`<div class='select2_color'><div class='select2_color_preview' style='background-color: ${state.id};'> </div>${state.text}</div>`);
      return $state;
    },
    templateSelection: function formatState(state){
      if(!state.id) {
        return state.text;
      }
      var $state = $(`<div class='select2_color'><div class='select2_color_preview' style='background-color: ${state.id};'> </div>${state.text}</div>`);
      return $state;
    }
});
$("#room_settings_amenities").select2({
    placeholder: "Izaberi sadržaje",
    minimumResultsForSearch: 10,
    width: "element",
    allowClear: true
});
$('.select2-search__field').css('width', '100%');


// Air Datepicker
var open_calendar = "";
$(".calendar_input").datepicker({
    language: "en",
    dateFormat: "dd-M-yyyy",
    disableNavWhenOutOfRange: true,
    autoClose: true,
    position: "bottom right",
    onShow: function(inst, animationCompleted) {
      if(animationCompleted){
        open_calendar = inst.el.id;
      }
    },
    onHide: function(inst, animationCompleted) {
      if(animationCompleted === false){
        open_calendar = "";
      }
    }
  });

// Slider
$('.range_slider').jRange({
  from: 0,
  to: Number.MAX_SAFE_INTEGER,
  theme: 'theme-blue',
  showScale: false,
  isRange : true
});
$('.range_slider').jRange('setValue', '0,' + Number.MAX_SAFE_INTEGER);

// Checkbox
$(".custom_checkbox").click(function(){
  let id = $(this)[0].id;
  $('#'+id+" .checkbox_value").toggleClass("checked");
  let val = $('#'+id).attr('data-value');
  if(val == 1){
    val = 0;
    $('#'+id).attr('data-value', '0');
  }
  else {
    val = 1;
    $('#'+id).attr('data-value', '1');
  }
});
$("body").on("click", ".custom_checkbox.dynamic", function(){
  let id = $(this)[0].id;
  $('#'+id+" .checkbox_value").toggleClass("checked");
  let val = $('#'+id).attr('data-value');
  if(val == 1){
    val = 0;
    $('#'+id).attr('data-value', '0');
  }
  else {
    val = 1;
    $('#'+id).attr('data-value', '1');
  }
});

// Radio
$(".custom_radio").click(function(){
  let global_id = $(this)[0].id.split("_");
  let val = global_id.pop();
  global_id = global_id.join("_");
  $(`[id^='${global_id}'] .radio_value`).removeClass("checked");
  $(this).find(".radio_value").addClass("checked");
  $(`#${global_id}`).val(val);
});

$(".switch_button").click(function(){
  let id = $(this)[0].id;
  $('#'+id+" .switch_slider").toggleClass("switch_off");
  var val = $('#'+id).attr('data-value');
  if(val == 1){
    val = 0;
    $('#'+id).attr('data-value', '0');
  }
  else {
    val = 1;
    $('#'+id).attr('data-value', '1');
  }
});
$("body").on("click", ".switch_button.dynamic", function(){
  let id = $(this)[0].id;
  $('#'+id+" .switch_slider").toggleClass("switch_off");
  var val = $('#'+id).attr('data-value');
  if(val == 1){
    val = 0;
    $('#'+id).attr('data-value', '0');
  }
  else {
    val = 1;
    $('#'+id).attr('data-value', '1');
  }
});

// Number
$("body").on("click", ".number_input_minus", function(){
  let id = $(this)[0].id.split("_");
  id.pop();
  id = id.join("_");
  let val = $(`#${id}`).val();
  if(isNaN(val) || val < 1){
    val = 1;
  }
  val -= 1;
  $(`#${id}`).val(val).change();
});
$("body").on("click", ".number_input_plus", function(){
  let id = $(this)[0].id.split("_");
  id.pop();
  id = id.join("_");
  let val = $(`#${id}`).val();
  if(isNaN(val)){
    val = 0;
  }
  val = parseFloat(val);
  val += 1;
  $(`#${id}`).val(val).change();
});


// Image

$("body").on("click", ".image_input", function(){
  let id = $(this)[0].id;
  $(`#${id}_file`).click();
});

$("body").on("change", ".image_input_file", function(){
  let id = $(this)[0].id.split("_");
  id.pop();
  id = id.join("_");
  let obj = $(this)[0];
  if(obj.files && obj.files[0]) {
      let reader = new FileReader();
      reader.onload = function(e) {
          $(`#${id}_container`).show();
          $(`#${id}_container .image_input_image`).attr('src', e.target.result);
          $(`#${id}`).hide();
      }
      reader.readAsDataURL(obj.files[0]);
  }
  else {
    $(`#${id}_container`).hide();
    $(`#${id}`).show();
  }
});

$("body").on("click", ".image_input_cancel", function(){
  let id = $(this).closest(".image_input_container")[0].id.split("_");
  id.pop();
  id = id.join("_");
  $(`#${id}_file`).val('').change();
  $(`#${id}_container`).hide();
  $(`#${id}_container .image_input_image`).attr('src', '');
  $(`#${id}`).show();
});

});
