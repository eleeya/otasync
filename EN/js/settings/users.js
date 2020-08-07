let users_map = {};

function get_users(){
  $.ajax({
    url: api_link + 'data/users/',
    method: 'POST',
    data: {
      key: main_key,
      account: account_name,
      lcode: main_lcode
    },
    success: function(rezultat){
      var sve = check_json(rezultat);
      if(sve.status !== "ok") {
        add_change_error(sve.status);
        return;
      }
      // Clear data
      users_map = {};
      // Save data
      var users = sve.users;
      for(var i=0;i<users.length;i++){
        users_map[users[i].id] = users[i];
      }
      display_users(users);
    },
    error: function(xhr, textStatus, errorThrown){
      // Loading
      $("#login_confirm").removeClass("button_loader");
      window.alert("An error occured. " + xhr.responseText);
    }
  });
}

function display_users(users){
  // List
  $("#users_list").empty();
  for(let i=0;i<users.length;i++){
    let user = users[i];
    // Data
    let id = user.id;
    let name = user.client_name;
    let email = user.username;
    let status = user.status == 3 ? "Confirmation required" : "Confirmed";
    var user_edit = `<div class='list_action'><img class='list_action_icon edit' title='Edit'> </div>`;
    var user_delete = `<div class='list_action'><img class='list_action_icon delete' title='Delete'> </div>`;
    $("#users_list").append(`
      <div class="list_row user" id='users_list_${id}'>
        <div class='user_name'> ${name} </div>
        <div class='user_email'> ${email} </div>
        <div class='user_status'> ${status} </div>
        <div class='user_actions'> ${user_edit} ${user_delete} </div>
      </div>`);
  }
  if(users.length > 0)
    $("#users_list").prepend(`
    <div class="list_names">
    <div class='user_name'> Name </div>
    <div class='user_email'> Email </div>
    <div class='user_status'> Status </div>
      <div class='user_actions'> Actions </div>
    </div>`);
  else
    $("#users_list").append(empty_html("No users"));
};


$(document).ready(function(){

  $("#users_list").on("click", ".delete", function(e){ // Show dialog and delete
    e.stopPropagation();
    let row_id = $(this).closest(".user")[0].id;
    let id = row_id.split("_");
    id = id[id.length - 1];
    let user = users_map[id];
    if(confirm(`Are you sure you want to delete user ${user.client_name}`)){
      $.ajax({
        type: 'POST',
        url: api_link + 'delete/user',
        data: {
          key: main_key,
          account: account_name,
          lcode: main_lcode,
          id: id
        },
        success: function(rezultat){
          var sve = check_json(rezultat);
          if(sve.status !== "ok"){
            add_change_error(sve.status);
            return;
          }
          add_change(`Deleted user ${user.client_name}`, sve.data.id); // Add changelog
          guests_page = 1;
          get_guests(); // Refresh data
        },
        error: function(xhr, textStatus, errorThrown){
          window.alert("An error occured. " + xhr.responseText);
        }
      });
    }
  });
});
