$(document).ready(function(){
  $(".rest_item").hide();
  $('#rest_dfrom').datepicker().data('datepicker').update(
    {
      position: "bottom left",
      minDate: new Date(),
      onHide: function(inst, animationCompleted) {
        if(animationCompleted === false && inst.selectedDates.length){
            $('#rest_dto').datepicker().data('datepicker').show();
        }
      }
    }
  );

  var fix_separate = function(){
    var val = $("#rest_separate").attr("data-value");
    var field = $("#rest_work_on").val();
    if(field == 'avail' || field == 'price'){
      if(val == '1'){
        $("#rest_value_container").hide();
        $(".rest_room_value").show();
      }
      else {
        $("#rest_value_container").show();
        $(".rest_room_value").hide();
      }
    }
    else {
      $(".rest_room_value").hide();
    }
  }

  $("#rest_work_on").change(function(){
    var val = $(this).val();
    if(val == 'max')
      val = 'min';
    $(".rest_item").hide();
    if(val == '--'){
      $("#rest_update").hide();
      $("#rest_details").empty();
      $(".rest_room").remove();
      return;
    }
    $("#rest_update").fadeIn(200);
    $(".rest_" + val).fadeIn(200);

    if(val == 'min' || val == 'closure'){
      $("#rest_restriction_plan").change();
      $('.rest_room_value').hide();
    }
    $("#rest_value").val("");
    $(".rest_room_value").val("");
    fix_separate();
    get_rest_details();
  });

  $("#rest_details_period").change(get_rest_details);

  $("#rest_restriction_plan").change(function(){
    var val = $(this).val();
    if(restriction_plans_map[val].type == 'compact'){
      $("#rest_rooms_container").hide();
      $("#rest_rooms_list").hide();
      $("#rest_period_container").hide();
    }
    else {
      $("#rest_rooms_container").fadeIn(200);
      $("#rest_rooms_list").fadeIn(200);
      $("#rest_period_container").fadeIn(200);
    }
    get_rest_details();
  });
  $("#rest_pricing_plan").change(function(){
    get_rest_details();
  });
  $("#rest_separate").click(function(){
    fix_separate();
  });

  $("#rest_rooms").change(function(){
    var val = $("#rest_rooms").val();
    if(val == "-"){
      return;
    }
    else if(val == "none"){
      $(".rest_room").remove();
    }
    else if(val == "all"){
      $(".rest_room").remove();
      for(var i=0;i<rooms_list.length;i++){
        var room = rooms_map[rooms_list[i]];
        $("#rest_rooms_list").append(`
          <div class='rest_room' id='rest_room_${room.id}'>
            <img src='img/trash_b.png' class='rest_remove' id='rest_remove_${room.id}'>
            <div> ${room.shortname} </div>
            <input type='number' class='number_input rest_room_value' id='rest_room_value_${room.id}'>
          </div>`);
      }
    }
    else if($("#rest_room_" + val).length == 0){
      var room = rooms_map[val];
      $("#rest_rooms_list").append(`
        <div class='rest_room' id='rest_room_${room.id}'>
          <img src='img/trash_b.png' class='rest_remove' id='rest_remove_${room.id}'>
          <div> ${room.shortname} </div>
          <input type='number' class='number_input rest_room_value' id='rest_room_value_${room.id}'>
        </div>`);
    }
    fix_separate();
    $("#rest_rooms").val("-").change();

    // Hide options
    $("#rest_rooms option").removeAttr("disabled");
    $(".rest_room").each(function(){
      let val = $(this)[0].id.split("_");
      val = val[val.length - 1];
      $(`#rest_rooms option[value='${val}']`).attr("disabled", "disabled");
    });
  });

  $("#rest_rooms_list").on("click", ".rest_remove", function(e){
    $(this).closest(".rest_room").remove();
    // Hide options
    $("#rest_rooms option").removeAttr("disabled");
    $(".rest_room").each(function(){
      let val = $(this)[0].id.split("_");
      val = val[val.length - 1];
      $(`#rest_rooms option[value='${val}']`).attr("disabled", "disabled");
    });
  });

  $("#rest_update").click(function(){
    var field = $("#rest_work_on").val();
    var dfrom = date_to_iso($("#rest_dfrom").datepicker().data('datepicker').selectedDates[0]);
    var dto = date_to_iso($("#rest_dto").datepicker().data('datepicker').selectedDates[0]);
    var rooms = [];
    var values = {};
    var value;
    var separate = $("#rest_separate").attr("data-value");
    if(field == 'ota')
    {
      value = $("#rest_value_ota").val();
      for(var i=0;i<rooms_list.length;i++)
      {
        if($("#rest_room_" + rooms_list[i]).length){
          rooms.push(rooms_list[i]);
          values[rooms_list[i]] = {};
          values[rooms_list[i]]["no_ota"] = value;
        }
      }
      if(rooms.length)
        send_rest_restriction(dfrom, dto, 1, values, rooms);
    }
    else if(field == 'avail')
    {
      value = $("#rest_value").val();
      for(var i=0;i<rooms_list.length;i++)
      {
        if($("#rest_room_" + rooms_list[i]).length)
        {
          rooms.push(rooms_list[i]);
          values[rooms_list[i]] = {};
          if(separate == "1")
            values[rooms_list[i]] = $("#rest_room_value_" + rooms_list[i]).val();
          else
            values[rooms_list[i]] = value;
        }
      }
      var variation = $("#rest_avail_variation").val();
      send_rest_avail(dfrom, dto, values, rooms, variation);
    }
    else if(field == 'price')
    {
      value = $("#rest_value").val();
      for(var i=0;i<rooms_list.length;i++)
      {
        if($("#rest_room_" + rooms_list[i]).length)
        {
          rooms.push(rooms_list[i]);
          values[rooms_list[i]] = {};
          if(separate == "1")
            values[rooms_list[i]] = $("#rest_room_value_" + rooms_list[i]).val();
          else
            values[rooms_list[i]] = value;
        }
      }
      var variation = $("#rest_price_variation").val();
      var pid = $("#rest_pricing_plan").val();
      send_rest_price(dfrom, dto, pid, values, rooms, variation);
    }
    else if(field == 'min' || field == 'max' || field == 'closure')
    {
      if(field == "min" || field == "max")
        value = $("#rest_value").val();
      else
        value = $("#rest_value_closure").val();
      var pid = $("#rest_restriction_plan").val();
      if(restriction_plans_map[pid].type == "compact")
      {
        send_rest_restriction_compact(value, field, pid);
      }
      else {
        for(var i=0;i<rooms_list.length;i++)
        {
          if($("#rest_room_" + rooms_list[i]).length)
          {
            values[rooms_list[i]] = {};
            rooms.push(rooms_list[i]);
            var field_id = "";
            if(field == "min")
              field_id = "min_stay";
            if(field == "max")
              field_id = "max_stay";
            if(field == "closure")
              field_id = "closed";
            values[rooms_list[i]][field_id] = value;
          }
        }
        send_rest_restriction(dfrom, dto, pid, values, rooms);
      }
    }
  });


});

var get_rest_details = function() {
  $("#rest_details").empty();
  $("#rest_details").append("<div class='loader'><div class='loader_icon'></div></div>");
  $("#rest_details_container").show();

  var val = $("#rest_work_on").val();
  var price_id = $("#rest_pricing_plan").val();
  var min_id = $("#rest_restriction_plan").val();
  if(val == 'ota')
    min_id = 1;

  let dfrom = today;
  let dto = relative_date(dfrom, 365);
  if($("#rest_details_period").val() == 2){
    dfrom = relative_date(today, 366);
    dto = relative_date(dfrom, 365);
  }


  let ajax_url = "restrictions";
  let ajax_id = min_id;
  if(val == "price"){
    ajax_url = "prices";
    ajax_id = price_id;
  }
  if(val == "avail"){
    ajax_url = "avail";
    ajax_id = "";
  }
  $.ajax({
    url: api_link + 'data/' + ajax_url,
    method: 'POST',
    data: {
            key: main_key,
            account: account_name,
            lcode: main_lcode,
            dfrom: dfrom,
            dto: dto,
            id: ajax_id
          },
    success: function(rezultat){
      $(".button_loader").removeClass("button_loader");
      var sve = check_json(rezultat);
      if(sve.status !== "ok") {
        add_change_error(sve.status);
        return;
      }
      let data = {};
      if(ajax_url == "avail")
        data = sve.avail;
      if(ajax_url == "prices")
        data = sve.prices;
      if(ajax_url == "restrictions")
        data = sve.restrictions;

        display_rest_details(data, val, dfrom);
    },
    error: function(xhr, textStatus, errorThrown){
      $(".button_loader").removeClass("button_loader");
      window.alert("Doslo je do greske. " + xhr.responseText);
    }
  });

};


function display_rest_details(data, field, rest_dfrom){
  let allData = {};
  let all_details_dates = range_of_dates(rest_dfrom, 366);
  let cal_rooms = properties_map[main_lcode].custom_calendar.room_types;
  let rooms = [];
  if(field == "avail")
    rooms = cal_rooms;
  else {
    for(let i=0;i<cal_rooms.length;i++){
      rooms.push(cal_rooms[i]);
      for(let j=0;j<rooms_list.length;j++){ // Push all rooms that are virtual of current room
        if(rooms_list[j].parent_room == cal_rooms[i])
          rooms.push(rooms_list[j].id);
      }
    }
  }


  for(let i=0;i<rooms.length;i++){

    var room_id = rooms[i];
    var room_data = []; // All data for one room
    let segment = {}; // A single segment - with same values
    // Getting start value from data based on field
    let cur_val = 0;
    if(field == "avail" || field == "price")
      cur_val = data[room_id][rest_dfrom];
    if(field == "min")
      cur_val = data[room_id][rest_dfrom]["min_stay"];
    if(field == "max")
      cur_val = data[room_id][rest_dfrom]["max_stay"];
    if(field == "closure")
      cur_val = data[room_id][rest_dfrom]["closed"];
    if(field == "ota")
      cur_val = data[room_id][rest_dfrom]["no_ota"];

    // Init segment
    segment["length"] = 0;
    segment["value"] = cur_val;

    for(let j=0;j<all_details_dates.length - 1;j++){
      // Get current value of correct field
      if(data[room_id][all_details_dates[j]] != undefined){
        if(field == "avail" || field == "price")
          cur_val = data[room_id][all_details_dates[j]];
        if(field == "min")
          cur_val = data[room_id][all_details_dates[j]]["min_stay"];
        if(field == "max")
          cur_val = data[room_id][all_details_dates[j]]["max_stay"];
        if(field == "closure")
          cur_val = data[room_id][all_details_dates[j]]["closed"];
        if(field == "ota")
          cur_val = data[room_id][all_details_dates[j]]["no_ota"];
      }
      else { // Use last known value if something fails
        let x = j;
        while(data[room_id][all_details_dates[x]] == undefined && x >= 0){
          x -= 1;
        }
        if(field == "avail" || field == "price")
          cur_val = data[room_id][all_details_dates[x]];
        if(field == "min")
          cur_val = data[room_id][all_details_dates[x]]["min_stay"];
        if(field == "max")
          cur_val = data[room_id][all_details_dates[x]]["max_stay"];
        if(field == "closure")
          cur_val = data[room_id][all_details_dates[x]]["closed"];
        if(field == "ota")
          cur_val = data[room_id][all_details_dates[x]]["no_ota"];
      }

      if(segment.value == cur_val){
        segment.length = segment.length + 1;
      }
      else {
        room_data.push(JSON.parse(JSON.stringify(segment))); // Push old segment
        segment.length = 1;
        segment.value = cur_val;
        // New segment already started, so length is 1 and value is the new value
      }
    }
    room_data.push(JSON.parse(JSON.stringify(segment))); // Insert last segment

    // Adding additional segment info

    var total_length = all_details_dates.length;
    var cur_length = 0;
    // Getting min, max and delta values
    var segmentMin = room_data[0].value;
    var segmentMax = room_data[0].value;
    for(var j=0;j<room_data.length;j++){
      segmentMin = segmentMin < room_data[j].value ?  segmentMin : room_data[j].value;
      segmentMax = segmentMax > room_data[j].value ?  segmentMax : room_data[j].value;
    }
    var segmentDelta = segmentMax - segmentMin;
    if(segmentDelta == 0){ // All values are same
      segmentMin = 0;
      if(segmentMax == 0){ // All values are 0
        segmentMax = 1;
      }
      segmentDelta = segmentMax;
    }

    // Adding width and dates to each segment
    for(var j=0;j<room_data.length;j++)
    {
      room_data[j]["dfrom"] = all_details_dates[cur_length]; // Start date is current length
      cur_length += room_data[j].length; // Add segment length to current
      room_data[j]["dto"] = all_details_dates[cur_length - 1]; // End date is end of segment
      room_data[j]["width"] = (room_data[j].length * 100) / total_length; // % width
      let scaledHeight = (room_data[j].value - segmentMin) / segmentDelta; // Height scaled to 0 - 1
      scaledHeight = (scaledHeight * 0.8) + 0.2; // Height scaled to 0.2 - 1.0
      room_data[j]["height"] = scaledHeight * 100; // Height in %
    }
    allData[room_id] = room_data;
  }

  // Displaying values
  $("#rest_details").empty();

  for(var i=0;i<rooms.length;i++){
    let graph_html = "";
    for(let j=0;j<allData[rooms[i]].length;j++){
      // Different data for closure and no_ota
      if(field == "closure"){
        graph_html = graph_html + `
        <div class='rest_details_data_item' style='width:${allData[rooms[i]][j].width}%;height:100%;'>
          <div style='height:100%;width:100%;background-color:${allData[rooms[i]][j].value == 1 ? "#f0535a" : "#6cd425"};'></div>
          <div class='rest_tooltip'>
            <div>
              ${iso_to_eur(allData[rooms[i]][j].dfrom)} - ${iso_to_eur(allData[rooms[i]][j].dto)}
            </div>
            <div>
              ${allData[rooms[i]][j].value == 1 ? "Zatvoreno" : "Otvoreno"}
            </div>
          </div>
        </div>`;
      }
      else if(field == "ota"){
        graph_html = graph_html + `
        <div class='rest_details_data_item' style='width:${allData[rooms[i]][j].width}%;height:100%;'>
          <div style='height:100%;width:100%;background-color:${allData[rooms[i]][j].value == 1 ? "#f0535a" : "#6cd425"};'></div>
          <div class='rest_tooltip'>
            <div>
              ${iso_to_eur(allData[rooms[i]][j].dfrom)} - ${iso_to_eur(allData[rooms[i]][j].dto)}
            </div>
            <div>
              ${allData[rooms[i]][j].value == 1 ? "Blokirana prodaja" : "Dozvoljena prodaja"}
            </div>
          </div>
        </div>`;
      }
      else {
        graph_html = graph_html + `
        <div class='rest_details_data_item' style='width:${allData[rooms[i]][j].width}%;height:100%;'>
          <div style='height:${allData[rooms[i]][j].height}%;width:100%;background-color:#6cd425;'></div>
          <div class='rest_tooltip'>
            <div>
              ${iso_to_eur(allData[rooms[i]][j].dfrom)} - ${iso_to_eur(allData[rooms[i]][j].dto)}
            </div>
            <div>
              ${allData[rooms[i]][j].value}
            </div>
          </div>
        </div>`;
      }
    }
    $("#rest_details").append(`
      <div class='rest_details_row'>
        <div class='rest_details_name'>
          ${rooms_map[rooms[i]].shortname}
        </div>
        <div class='rest_details_data'>
        ${graph_html}
        </div>
      </div>`);
  }

};


var send_rest_avail = function(dfrom, dto, values, rooms, variation_type)
{
  $("#rest_update").addClass("button_loader");
  $.ajax({
    url: api_link + 'edit/avail',
    method: 'POST',
    data: {
            key: main_key,
            account: account_name,
            lcode: main_lcode,
            dfrom: dfrom,
            dto: dto,
            values: JSON.stringify(values),
            rooms: JSON.stringify(rooms),
            variation_type: variation_type
          },
    success: function(rezultat){
      $(".button_loader").removeClass("button_loader");
      var sve = check_json(rezultat);
      if(sve.status !== "ok") {
        add_change_error(sve.status);
        return;
      }
      add_change("Izmjenjena raspoloživost", sve.data.id);
      get_rest_details();
    },
    error: function(xhr, textStatus, errorThrown){
      $(".button_loader").removeClass("button_loader");
      window.alert("Doslo je do greske. " + xhr.responseText);
    }
  });
};

var send_rest_price = function(dfrom, dto, pid, values, rooms, variation_type)
{
  $("#rest_update").addClass("button_loader");
  $.ajax({
    url: api_link + 'edit/price',
    method: 'POST',
    data: {
            key: main_key,
            account: account_name,
            lcode: main_lcode,
            dfrom: dfrom,
            dto: dto,
            pid: pid,
            values: JSON.stringify(values),
            rooms: JSON.stringify(rooms),
            variation_type: variation_type
          },
    success: function(rezultat){
      $(".button_loader").removeClass("button_loader");
      var sve = check_json(rezultat);
      if(sve.status !== "ok") {
        add_change_error(sve.status);
        return;
      }
      add_change("Izmjenjen plan cijena", sve.data.id);
      get_rest_details();
    },
    error: function(xhr, textStatus, errorThrown){
      $(".button_loader").removeClass("button_loader");
      window.alert("Doslo je do greske. " + xhr.responseText);
    }
  });
};
var send_rest_restriction = function(dfrom, dto, pid, values, rooms)
{
  $("#rest_update").addClass("button_loader");
  $.ajax({
    url: api_link + 'edit/restriction',
    method: 'POST',
    data: {
            key: main_key,
            account: account_name,
            lcode: main_lcode,
            dfrom: dfrom,
            dto: dto,
            pid: pid,
            values: JSON.stringify(values),
            rooms: JSON.stringify(rooms)
          },
    success: function(rezultat){
      $(".button_loader").removeClass("button_loader");
      var sve = check_json(rezultat);
      if(sve.status !== "ok") {
        add_change_error(sve.status);
        return;
      }
      add_change("Izmjenjene restrikcije", sve.data.id);
      get_rest_details();
    },
    error: function(xhr, textStatus, errorThrown){
      $(".button_loader").removeClass("button_loader");
      window.alert("Doslo je do greske. " + xhr.responseText);
    }
  });

}
var send_rest_restriction_compact = function(value, field, pid)
{
  var rules = restriction_plans_map[pid].rules;
  var values =
  {
    closed: rules.closed,
    closed_arrival: rules.closed_arrival,
    closed_departure: rules.closed_departure,
    max_stay: rules.max_stay,
    min_stay: rules.min_stay,
    min_stay_arrival: rules.min_stay_arrival
  };
  if(field == 'min')
    values.min_stay = value;
  if(field == 'max')
    values.max_stay = value;
  if(field == 'closure')
    values.closed = value;
  $("#rest_update").addClass("button_loader");
  $.ajax({
    url: api_link + 'edit/restrictionCompact',
    method: 'POST',
    data: {
            key: main_key,
            account: account_name,
            lcode: main_lcode,
            pid: pid,
            values: JSON.stringify(values)
          },
    success: function(rezultat){
      $(".button_loader").removeClass("button_loader");
      var sve = check_json(rezultat);
      if(sve.status !== "ok") {
        add_change_error(sve.status);
        return;
      }
      add_change("Izmjenjene restrikcije",sve.data.id);
      get_rest_details();
    },
    error: function(xhr, textStatus, errorThrown){
      $(".button_loader").removeClass("button_loader");
      window.alert("Doslo je do greske. " + xhr.responseText);
    }
  });
}
