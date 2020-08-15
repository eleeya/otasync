function findGetParameter(parameterName) {
    var result = null,
        tmp = [];
    location.search
        .substr(1)
        .split("&")
        .forEach(function (item) {
          tmp = item.split("=");
          if (tmp[0] === parameterName) result = decodeURIComponent(tmp[1]);
        });
    return result;
}

$(document).ready(function(){

  var pkey = findGetParameter("key");
  var id = findGetParameter("id");
  $.ajax({
    url: '../api/web/account/subuserInfo',
    method: 'POST',
    data: {
            key: pkey,
            id: id
          },
    success: function(rezultat){
      console.log(rezultat);
      var sve = JSON.parse(rezultat);
      if(sve.status == "ok")
      {
        $("#login_username").val(sve.email);
      }
      else {
        $("#login_error").text(sve.status);
      }
    },
    error: function(rezultat){
      window.alert("Doslo je do greske.");
    }
  });

  $("#login_confirm").click(function(){


    if ($("#login_password").val() !== $("#login_password_confirm").val())
    {
      $("#login_error").text("Lozinke se ne poklapaju.");
      return;
    }
    if($("#login_password").val().length < 6)
    {
      $("#login_error").text("Lozinka mora imati bar 6 karaktera.");
      return;
    }
    $.ajax({
      url: '../api/web/account/subuserConfirm',
      method: 'POST',
      data: {
              email: $("#login_username").val(),
              password: $("#login_password").val(),
              key: pkey
            },
      success: function(rezultat){
        console.log(rezultat);
        var url = "https://admin.otasync.me";
        $(location).attr('href',url);
      },
      error: function(rezultat){
        window.alert("Doslo je do greske.");
      }
    });
  });
});
