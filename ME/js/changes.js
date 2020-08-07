let settings_timeout;
function add_change_settings(change_text = "Izmjena sačuvana") {
  if($(".undo_item").length > 2){
    $(".undo_item").first().remove();
  }
  $("#undo_item_settings").remove();
  clearTimeout(settings_timeout);
  $("#undo_list").append(`
    <div class='undo_item' id='undo_item_settings'>
      <img class='undo_cancel'>
      <div class='undo_countdown'> </div>
      <div class='undo_text'> ${change_text} </div>
      <div class='undo_undo'> </div>
    </div>`);
  settings_timeout = setTimeout(function(){
    $(`#undo_item_settings`).remove();
  }, 5000);
}

let error_timeout;
function add_change_error(msg) {
  $("#undo_item_settings").remove();
  clearTimeout(error_timeout);
  $("#undo_list").append(`
    <div class='undo_item' id='undo_item_error'>
      <img class='undo_cancel'>
      <div class='undo_countdown'> </div>
      <div class='undo_text'> ${msg} </div>
      <div class='undo_undo'> </div>
    </div>`);
  error_timeout = setTimeout(function(){
    $(`#undo_item_error`).remove();
  }, 30000);
}


let undo_timeout = {};

function add_change(text, id) {

  if($(".undo_item").length > 2){
    $(".undo_item").first().remove();
  }

  let undo_timer = $("#account_undo_timer").val();

  if(undo_timer == 0){
    $("#undo_list").append(`
      <div class='undo_item' id='undo_item_${id}'>
        <img class='undo_cancel'>
        <div class='undo_countdown'> </div>
        <div class='undo_text'> ${text} </div>
        <div class='undo_undo'> </div>
      </div>`);
    settings_timeout = setTimeout(function(){
      $(`#undo_item_${id}`).remove();
    }, 5000);
  }
  else {
    $("#undo_list").append(`
      <div class='undo_item' id='undo_item_${id}'>
        <img class='undo_cancel'>
        <div class='undo_countdown'> ${undo_timer} </div>
        <div class='undo_text'> ${text} </div>
        <div class='undo_undo'> PONIŠTI </div>
      </div>`);
    undo_timeout[id] = setInterval(function(){
      val = $(`#undo_item_${id} .undo_countdown`).text();
      val = parseInt(val) - 1;
      if(val < 0 || isNaN(val)){
        $(`#undo_item_${id}`).remove();
        clearInterval(undo_timeout[id]);
      }
      else {
        $(`#undo_item_${id} .undo_countdown`).text(val);
      }
    }, 1000);
  }

}

$(document).ready(function(){
  $("#undo_list").on("click", ".undo_cancel", function(e){
    let id = $(this).closest(".undo_item")[0].id.split("_")[2];
    $(this).closest(".undo_item").remove();
    if(undo_timeout[id] !== undefined){
      clearInterval(undo_timeout[id]);
    }
  });
  $("#undo_list").on("click", ".undo_undo", function(e){
    let id = $(this).closest(".undo_item")[0].id.split("_")[2];
    if(undo_timeout[id] !== undefined){
      clearInterval(undo_timeout[id]);
    }
    $(this).closest(".undo_item").css("user-select", "none");
    $.ajax({
      url: api_link + 'undo/',
      method: 'POST',
      data: {
              key: main_key,
              account: account_name,
              lcode: main_lcode,
              id: id
            },
      success: function(rezultat){
        $(`#undo_item_${id}`).remove();
        var sve = check_json(rezultat);
        if(sve.status !== "ok") {
          add_change_error(sve.status);
          return;
        }
        add_change_settings("Izmjena poništena");
        hash_change();
      },
      error: function(xhr, textStatus, errorThrown){
        $(`#undo_item_${id}`).remove();
        window.alert("Doslo je do greske. " + xhr.responseText);
      }
    });
  });
});
