function update_guest_email_type(){
  if(disable_calls)
    return;
  $.ajax({
    url: api_link + 'settings/guestEmailsType',
    method: 'POST',
    data: {
            key: main_key,
            account: account_name,
            lcode: main_lcode,
            value: $("#guest_emails_res_type").val()
          },
    success: function(rezultat){
      var sve = check_json(rezultat);
      if(sve.status !== "ok") {
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

function update_guest_email(e){

  // Parameters
  let id = e.target.id;
  var field = id.split("_")[2];
  var active = $(`#guest_emails_${field}_active`).val();
  var subject = $(`#guest_emails_${field}_subject`).val();
  var text = $(`#guest_emails_${field}_edit`).html();
  $.ajax({
    url: api_link + 'settings/guestEmails',
    method: 'POST',
    data: {
            key: main_key,
            account: account_name,
            lcode: main_lcode,
            field: field,
            active: active,
            subject: subject,
            text: text
          },
    success: function(rezultat){
      var sve = check_json(rezultat);
      if(sve.status !== "ok") {
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

function update_client_emails(item, value){
  if(disable_calls)
    return;
  $.ajax({
    type: 'POST',
    url: api_link + 'settings/clientEmails',
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
      add_change_settings();
    },
    error: function(xhr, textStatus, errorThrown){
      window.alert("An error occured. " + xhr.responseText);
    }
  });
}


$(document).ready(function(){

  $("#guest_emails").on("click", ".confirm_button", update_guest_email);
  $("#guest_emails_res_type").change(update_guest_email_type);

  $("#client_emails_active").click(function(){
    let val = $(this).attr("data-value");
    update_client_emails("active", val);
  });
  $("#client_emails_emails").change(function(){
    let val = $(this).val();
    update_client_emails("emails", val);
  });
  $("#client_emails_arrivals").click(function(){
    let val = $(this).attr("data-value");
    update_client_emails("arrivals", val);
  });
  $("#client_emails_departures").click(function(){
    let val = $(this).attr("data-value");
    update_client_emails("departures", val);
  });
  $("#client_emails_stay").click(function(){
    let val = $(this).attr("data-value");
    update_client_emails("stay", val);
  });
  $("#client_emails_tomorrow").click(function(){
    let val = $(this).attr("data-value");
    update_client_emails("tomorrow", val);
  });
  $("#client_emails_time").change(function(){
    let val = $(this).val();
    update_client_emails("hour", val);
  });
});
