function update_property(item, value){
  if(disable_calls)
    return;
  $.ajax({
    type: 'POST',
    url: api_link + 'settings/property',
    data: {
      key: main_key,
      account: account_name,
      lcode: main_lcode,
      item: item,
      value: value,
    },
    success: function(rezultat){
      var sve = check_json(rezultat);
      if(sve.status !== "ok"){
        add_change_error(sve.status);
        return;
      }
      properties_map[main_lcode][item] = value;
      add_change_settings();
    },
    error: function(xhr, textStatus, errorThrown){
      window.alert("An error occured. " + xhr.responseText);
    }
  });
}

function update_account(item, value){
  if(disable_calls)
    return;
  $.ajax({
    type: 'POST',
    url: api_link + 'settings/account',
    data: {
      key: main_key,
      account: account_name,
      item: item,
      value: value,
    },
    success: function(rezultat){
      var sve = check_json(rezultat);
      if(sve.status !== "ok"){
        add_change_error(sve.status);
        return;
      }
      add_change_settings();
    },
    error: function(xhr, textStatus, errorThrown){
      window.alert("An error occured. " + xhr.responseText);
    }
  });
}

function update_property_logo(){
  var file = $("#property_logo_file")[0].files[0];
  var formData = new FormData();
  if (!file)
    formData.append("clear", 1);
  else
    formData.append("logo", file);
  formData.append("key", main_key);
  formData.append("account", account_name);
  formData.append("lcode", main_lcode);
  $.ajax({
    type: 'POST',
    url: api_link + 'settings/logo',
    data: formData,
    processData: false,
    contentType: false,
    success: function(rezultat){
      var sve = check_json(rezultat);
      if(sve.status !== "ok"){
        add_change_error(sve.status);
        return;
      }
      add_change_settings();
    },
    error: function(xhr, textStatus, errorThrown){
      window.alert("An error occured. " + xhr.responseText);
    }
  });
}

$(document).ready(function(){

  $("#property_logo_file").change(update_property_logo);
  $("#property_name").change(function(){
    let val = $(this).val();
    update_property("name", val);
  });
  $("#property_pib").change(function(){
    let val = $(this).val();
    update_property("pib", val);
  });
  $("#property_mb").change(function(){
    let val = $(this).val();
    update_property("mb", val);
  });
  $("#property_address").change(function(){
    let val = $(this).val();
    update_property("address", val);
  });
  $("#property_bank_account").change(function(){
    let val = $(this).val();
    update_property("bank_account", val);
  });
  $("#property_iban").change(function(){
    let val = $(this).val();
    update_property("iban", val);
  });
  $("#property_swift").change(function(){
    let val = $(this).val();
    update_property("swift", val);
  });
  $("#property_pdv_included").click(function(){
    let val = $(this).attr("data-value");
    update_property("pdv_included", val);
  });
  $("#property_rooms_tax_included").click(function(){
    let val = $(this).attr("data-value");
    update_property("rooms_tax_included", val);
    if(val == 1){
      $("#property_rooms_tax_containter").show();
    }
    else {
      $("#property_rooms_tax_containter").hide();
    }
  });
  $("#property_rooms_tax").change(function(){
    let val = $(this).val();
    update_property("rooms_tax", val);
  });
  $("#property_notify_guests").click(function(){
    let val = $(this).attr("data-value");
    update_property("notify_guests", val);
  });

  $("#account_undo_timer").change(function(){
    let val = $(this).val();
    update_account("undo_timer", val);
  });
  $("#account_email").change(function(){
    let val = $(this).val();
    update_account("email", val);
  });
  $("#account_notify_overbooking").click(function(){
    let val = $(this).attr("data-value");
    update_account("notify_overbooking", val);
  });
  $("#account_notify_reservations").click(function(){
    let val = $(this).attr("data-value");
    update_account("notify_new_reservations", val);
  });
  $("#account_invoice_delivery").change(function(){
    let val = $(this).val();
    update_account("invoice_delivery", val);
  });
  $("#account_invoice_due").change(function(){
    let val = $(this).val();
    update_account("invoice_issued", val);
  });
  $("#account_invoice_margin").change(function(){
    let val = $(this).val();
    if(isNaN(val))
      return;
    update_account("invoice_margin", val);
  });
  $("#account_invoice_header").click(function(){
    let val = $(this).attr("data-value");
    update_account("invoice_header", val);
  });

  $("#property_sync").click(function(){
    $(this).addClass("button_loader");
    $.ajax({
      type: 'POST',
      url: api_link + 'settings/sync',
      data: {
        key: main_key,
        account: account_name,
        lcode: main_lcode
      },
      success: function(rezultat){
        $(".button_loader").removeClass("button_loader");
        var sve = check_json(rezultat);
        if(sve.status !== "ok"){
          add_change_error(sve.status);
          return;
        }
        add_change_settings();
      },
      error: function(xhr, textStatus, errorThrown){
        $(".button_loader").removeClass("button_loader");
        window.alert("An error occured. " + xhr.responseText);
      }
    });
  });

});
