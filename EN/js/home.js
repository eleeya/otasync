$(document).ready(function(){

// Navigation of home tabs
$(".news_button").click(function(){
  let id = $(this)[0].id;
  $(".news_button").removeClass("selected");
  $(this).addClass("selected");
  $(".news_list").hide();
  $("#"+id+"_list").show();
});
$(".events_button").click(function(){
  let id = $(this)[0].id;
  $(".events_button").removeClass("selected");
  $(this).addClass("selected");
  $(".events_list").hide();
  $("#"+id+"_list").show();
});

// Requests
$("#news_order_by").change(get_news);
$("#news_dfrom").change(get_news);
$("#news_order_type").click(function(){
  if($(this).hasClass("asc")){
    $(this).removeClass("asc");
    $(this).addClass("desc");
    $(this).attr("data-value", "desc");
  }
  else {
    $(this).removeClass("desc");
    $(this).addClass("asc");
    $(this).attr("data-value", "asc");
  }
  get_news();
});
$("#events_dto").change(get_events);

// Exports
$("#news_export").click(function(){
  let $list = $("#received_list");
  if($("#modified_list").css("display") != "none")
    $list = $("#modified_list");
  if($("#canceled_list").css("display") != "none")
    $list = $("#canceled_list");
  let reservations = [];
  $list.find(".reservation").each(function(){
    let id = $(this)[0].id.split("_");
    id = id[id.length - 1];
    reservations.push(id);
  });
  if(reservations.length){
    reservations.join(",");
    $("#news_export_key").val(main_key);
    $("#news_export_account").val(account_name);
    $("#news_export_lcode").val(main_lcode);
    $("#news_export_ids").val(reservations);
    $("#news_export_form").submit();
  }
});
$("#events_export").click(function(){
  let $list = $("#arrivals_list");
  if($("#departures_list").css("display") != "none")
    $list = $("#departures_list");
  if($("#stay_list").css("display") != "none")
    $list = $("#stay_list");
  let reservations = [];
  $list.find(".reservation").each(function(){
    let id = $(this)[0].id.split("_");
    id = id[id.length - 1];
    reservations.push(id);
  });
  if(reservations.length){
    reservations.join(",");
    $("#events_export_key").val(main_key);
    $("#events_export_account").val(account_name);
    $("#events_export_lcode").val(main_lcode);
    $("#events_export_ids").val(reservations);
    $("#events_export_form").submit();
  }
});

});

// Gets data from server
function get_news(){

  // Clear list
  $("#received_list").html("<div class='loader'><div class='loader_icon'></div></div>");
  $("#modified_list").html("<div class='loader'><div class='loader_icon'></div></div>");
  $("#canceled_list").html("<div class='loader'><div class='loader_icon'></div></div>");
  // Parameters
  let order_by = $("#news_order_by").val();
  let order_type = $("#news_order_type").attr("data-value");
  let n = $("#news_dfrom").val();
  let dto = today;
  let dfrom = relative_date(today, n);
  if(n == -1) // For yesterday only
    dto = dfrom;
  // Call
  $.ajax({
    url: api_link + 'data/news',
    method: 'POST',
    data: {
            key: main_key,
            account: account_name,
            lcode: main_lcode,
            order_by: order_by,
            order_type: order_type,
            dfrom: dfrom,
            dto: dto
          },
    success: function(rezultat){
      var sve = check_json(rezultat);
      if(sve.status !== "ok") {
        add_change_error(sve.status);
        return;
      }

      // Save data
      for(let i=0;i<sve.received.length;i++){
        all_reservations[sve.received[i].reservation_code] = sve.received[i];
      }
      for(let i=0;i<sve.modified.length;i++){
        all_reservations[sve.modified[i].reservation_code] = sve.modified[i];
      }
      for(let i=0;i<sve.canceled.length;i++){
        all_reservations[sve.canceled[i].reservation_code] = sve.canceled[i];
      }
      display_news(sve.received, sve.modified, sve.canceled);
    },
    error: function(xhr, textStatus, errorThrown){
      window.alert("An error occured. " + xhr.responseText);
    }
  });
}
function get_events(){

  // Clear list
  $("#arrivals_list").html("<div class='loader'><div class='loader_icon'></div></div>");
  $("#departures_list").html("<div class='loader'><div class='loader_icon'></div></div>");
  $("#stay_list").html("<div class='loader'><div class='loader_icon'></div></div>");
  // Parameters
  let n = $("#events_dto").val();
  let dto = relative_date(today, n);
  let dfrom = dto;
  if(n == 7) // For multiple days dfrom is different
    dfrom = today;
  // Call
  $.ajax({
    url: api_link + 'data/events',
    method: 'POST',
    data: {
            key: main_key,
            account: account_name,
            lcode: main_lcode,
            dfrom: dfrom,
            dto: dto
          },
    success: function(rezultat){
      var sve = check_json(rezultat);
      if(sve.status !== "ok") {
        add_change_error(sve.status);
        return;
      }

      // Save data
      for(let i=0;i<sve.arrivals.length;i++){
        all_reservations[sve.arrivals[i].reservation_code] = sve.arrivals[i];
      }
      for(let i=0;i<sve.departures.length;i++){
        all_reservations[sve.departures[i].reservation_code] = sve.departures[i];
      }
      for(let i=0;i<sve.stay.length;i++){
        all_reservations[sve.stay[i].reservation_code] = sve.stay[i];
      }
      display_events(sve.arrivals, sve.departures, sve.stay);
    },
    error: function(xhr, textStatus, errorThrown){
      window.alert("An error occured. " + xhr.responseText);
    }
  });
}

// Displays fetched data
function display_news(received_list, modified_list, canceled_list){

  $(".news_button .news_number").text("");
  $("#received_list").empty();
  $("#modified_list").empty();
  $("#canceled_list").empty();

  for(var i=0;i<received_list.length;i++){
    $("#received_list").append(compact_res_html("received_list", received_list[i]));
  }
  for(var i=0;i<modified_list.length;i++){
    $("#modified_list").append(compact_res_html("modified_list", modified_list[i]));
  }
  for(var i=0;i<canceled_list.length;i++){
    $("#canceled_list").append(compact_res_html("canceled_list", canceled_list[i]));
  }
  if(received_list.length == 0)
    $("#received_list").append(empty_html("No reservations"));
  else
    $("#received .news_number").text(" - " + received_list.length);
  if(modified_list.length == 0)
    $("#modified_list").append(empty_html("No reservations"));
  else
    $("#modified .news_number").text(" - " + modified_list.length);
  if(canceled_list.length == 0)
    $("#canceled_list").append(empty_html("No reservations"));
  else
    $("#canceled .news_number").text(" - " + canceled_list.length);
}
function display_events(arrivals_list, departures_list, stay_list){
  $(".events_button .news_number").text("");
  $("#arrivals_list").empty();
  $("#departures_list").empty();
  $("#stay_list").empty();

  for(var i=0;i<arrivals_list.length;i++){
    $("#arrivals_list").append(compact_res_html("arrivals_list", arrivals_list[i]));
  }
  for(var i=0;i<departures_list.length;i++){
    $("#departures_list").append(compact_res_html("departures_list", departures_list[i]));
  }
  for(var i=0;i<stay_list.length;i++){
    $("#stay_list").append(compact_res_html("stay_list", stay_list[i]));
  }
  if(arrivals_list.length == 0)
    $("#arrivals_list").append(empty_html("No reservations"));
  else
    $("#arrivals .news_number").text(" - " + arrivals_list.length);
  if(departures_list.length == 0)
    $("#departures_list").append(empty_html("No reservations"));
  else
    $("#departures .news_number").text(" - " + departures_list.length);
  if(stay_list.length == 0)
    $("#stay_list").append(empty_html("No reservations"));
  else
    $("#stay .news_number").text(" - " + stay_list.length);
}
