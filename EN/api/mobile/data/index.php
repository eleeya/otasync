<?php

require '../../main.php';

function fixRow($row)
{
  if(isset($row["services"]))
  {
    $row["services"] = json_decode($row["services"]);
  }
  if(isset($row["currencies"]))
  {
    $row["currencies"] = json_decode($row["currencies"]);
  }
  if(isset($row["discount"]))
  {
    $row["discount"] = json_decode($row["discount"]);
  }
  if(isset($row["access"]))
  {
    $row["access"] = json_decode($row["access"]);
  }
  if(isset($row["rules"]))
  {
    $row["rules"] = json_decode($row["rules"]);
  }
  if(isset($row["values"]) && is_string($row["values"]))
  {
    $row["values"] = json_decode($row["values"]);
  }
  if(isset($row["old_data"]))
  {
    $row["old_data"] = json_decode($row["old_data"]);
  }
  if(isset($row["new_data"]))
  {
    $row["new_data"] = json_decode($row["new_data"]);
  }
  if(isset($row["dayprices"]))
  {
    $row["dayprices"] = json_decode($row["dayprices"]);
  }
  if(isset($row["boards"]))
  {
    $row["boards"] = json_decode($row["boards"]);
  }
  if(isset($row["planned_earnings"]))
  {
    $row["planned_earnings"] = json_decode($row["planned_earnings"]);
  }
  return $row;
}
function old_fixReservation($row, $channel_logos, $room_names, $room_shortnames)
{
  if($row["status"] == 5)
    $row["status"] = 0;
  $row["services"] = json_decode($row["services"]);
  $row["discount"] = json_decode($row["discount"]);
  $row["dayprices"] = json_decode($row["dayprices"]);
  $row["boards"] = json_decode($row["boards"]);
  $row["additional_info"] = "";
  // Adding room names / shortnames
  $res_rooms = explode(",", $row["rooms"]);
  $row["room_names"] = [];
  $row["room_shortnames"] = [];
  $row["customer_notes"] = $row["note"];
  for($i=0;$i<sizeof($res_rooms);$i++){
    $row["room_names"][$i] = $room_names[$res_rooms[$i]];
    $row["room_shortnames"][$i] = $room_shortnames[$res_rooms[$i]];
  }
  // Adding channel logo
  if(isset($channel_logos[$row["id_woodoo"]]))
    $row["channel_logo"] = $channel_logos[$row["id_woodoo"]];
  else
    $row["channel_logo"] = "https://admin.otasync.me/img/ota/youbook.png";
  $row["amount"] = $row["reservation_price"];
  if($row["id_woodoo"] == "-1" || $row["id_woodoo"] == "-2")
    $row["id_woodoo"] = "";
  return $row;
}


if($_SERVER['REQUEST_METHOD'] == "OPTIONS"){
    http_response_code(200);
    die();
}
else if ($_SERVER['REQUEST_METHOD'] != "POST"){
  fatal_error("Invalid method", 405);
}

$key = checkPost("key");
$lcode = checkPost("lcode");
$account = checkPost("account");
$konekcija = connectToDB();
$action = getAction();
$ret_val = [];
$ret_val["status"] = "ok";
$user = getSession($key, $account, $konekcija);
// Check access here

if($action == "all" || $action == "news" || $action == "events" || $action == "calendar" || $action =="reservations" || $action == "reservationsFilter" || $action == "search" || $action == "calendarGantt") // Fetching channel logos
{
  $channel_logos = [];
  $sql = "SELECT logo, id FROM channels_$lcode";
  $rezultat = mysqli_query($konekcija, $sql);
  while($red = mysqli_fetch_assoc($rezultat)) // Including shortname for rooms
  {
    $channel_logos[$red['id']] = $red['logo'];
  }
  // Get names and shortnames for all rooms
  $room_names = [];
  $room_shortnames = [];
  $sql = "SELECT id, name, shortname FROM rooms_$lcode";
  $rezultat = mysqli_query($konekcija, $sql);
  while($red = mysqli_fetch_assoc($rezultat))
  {
    $room_names[$red['id']] = $red['name'];
    $room_shortnames[$red['id']] = $red['shortname'];
  }
}

if($action == "events")
{
  $confirmed = [];
  $canceled = [];
  $noshow = [];
  $all_ids = [];
  $status = checkPost("status");
  if(is_string($status))
    $status = json_decode($status);
  $dates = checkPost("dates");
  if(is_string($dates))
    $dates = json_decode($dates);
  $dates_str = $dates;
  for($i=0;$i<sizeof($dates_str);$i++){
      $dates_str[$i] = "'" . $dates_str[$i] . "'";
  }
  $dates_str = implode(", ", $dates_str);

  // Get arrivals
  if(in_array("arrival", $status) || (in_array("arrivals", $status))){
    $sql = "SELECT * FROM reservations_$lcode WHERE date_arrival IN ($dates_str) ORDER BY date_arrival ASC, date_departure ASC";
    $rezultat = mysqli_query($konekcija, $sql);
    while($red = mysqli_fetch_assoc($rezultat)){
      $res = old_fixReservation($red, $channel_logos, $room_names, $room_shortnames);
      array_push($all_ids, $res["reservation_code"]);
      if($res["status"] == 1)
        array_push($confirmed, $res);
      else
        array_push($canceled, $res);
    }
  }
  // Get departures
  if(in_array("departure", $status) || (in_array("departures", $status))){
    $sql = "SELECT * FROM reservations_$lcode WHERE date_departure IN ($dates_str) ORDER BY date_departure ASC, date_arrival ASC";
    $rezultat = mysqli_query ($konekcija, $sql);
    while($red = mysqli_fetch_assoc($rezultat)){
      $res = old_fixReservation($red, $channel_logos, $room_names, $room_shortnames);
      if(!(in_array($res["reservation_code"], $all_ids))){
        if($res["status"] == 1)
          array_push($confirmed, $res);
        else
          array_push($canceled, $res);
      }
    }
  }
  // Get stay
  if(in_array("stay", $status)){
    $stay_sql = [];
    for($i=0;$i<sizeof($dates);$i++){
      $date = $dates[$i];
      array_push($stay_sql, "(date_arrival < '$date' AND  date_departure > '$date')");
    }
    $stay_sql = implode(" OR ", $stay_sql);
    $sql = "SELECT * FROM reservations_$lcode WHERE $stay_sql ORDER BY date_arrival ASC, date_departure ASC";
    $rezultat = mysqli_query ($konekcija, $sql);
    while($red = mysqli_fetch_assoc($rezultat)){
      $res = old_fixReservation($red, $channel_logos, $room_names, $room_shortnames);
      if(!(in_array($res["reservation_code"], $all_ids))){
        if($res["status"] == 1)
          array_push($confirmed, $res);
        else
          array_push($canceled, $res);
      }
    }
  }
  $ret_val["confirmed"] = $confirmed;
  $ret_val["canceled"] = $canceled;
  $ret_val["noshow"] = $noshow;
}

// Filtering
if($action == "reservationsFilter")
{
  $reservations = [];
  $date_received_from = "1";
  if(checkPostExists("date_received_from")){
    $date_received_from = checkPost("date_received_from");
    $date_received_from = "( date_received >= '$date_received_from' )";
  }

  $date_received_to = "1";
  if(checkPostExists("date_received_to")){
    $date_received_to = checkPost("date_received_to");
    $date_received_to = "( date_received <= '$date_received_to' )";
  }

  $date_arrival_from = "1";
  if(checkPostExists("date_arrival_from")){
    $date_arrival_from = checkPost("date_arrival_from");
    $date_arrival_from = "( date_arrival >= '$date_arrival_from' )";
  }

  $date_arrival_to = "1";
  if(checkPostExists("date_arrival_to")){
    $date_arrival_to = checkPost("date_arrival_to");
    $date_arrival_to = "( date_arrival <= '$date_arrival_to' )";
  }

  $date_departure_from = "1";
  if(checkPostExists("date_departure_from")){
    $date_departure_from = checkPost("date_departure_from");
    $date_departure_from = "( date_departure >= '$date_departure_from' )";
  }

  $date_departure_to = "1";
  if(checkPostExists("date_departure_to")){
    $date_departure_to = checkPost("date_departure_to");
    $date_departure_to = "( date_departure <= '$date_departure_to' )";
  }

  $date_canceled_from = "1";
  if(checkPostExists("date_canceled_from")){
    $date_canceled_from = checkPost("date_canceled_from");
    $date_canceled_from = "( date_canceled >= '$date_canceled_from' )";
  }

  $date_canceled_to = "1";
  if(checkPostExists("date_canceled_to")){
    $date_canceled_to = checkPost("date_canceled_to");
    $date_canceled_to = "( date_canceled <= '$date_canceled_to' )";
  }

  $res_status = "1";
  if(checkPostExists("status")){
    $res_status = checkPost("status");
    $res_status = "( status = $res_status )";
  }

  $res_channel = "1";
  if(checkPostExists("channel")){
    $res_channel = checkPost("channel");
    $res_channel = "( id_woodoo = '$res_channel' )";
  }

  $res_room = "1";
  if(checkPostExists("room")){
    $res_room = checkPost("room");
    $res_room = "( rooms LIKE '%$res_room%')";
  }

  $res_name = "1";
  if(checkPostExists("name")){
    $res_name = strtolower(checkPost("name"));
    $res_name = "(LOWER(customer_name) LIKE '%$res_name%' OR LOWER(customer_surname) LIKE '%$res_name%' OR LOWER(customer_mail) LIKE '%$res_name%')";
  }

  $page = checkPost("page");
  $start_index = ($page - 1) * 20;
  $res_sort = checkPost("order_by");
  $res_order = checkPost("order_type");
  $sort_filter = 1;
   $today = date("Y-m-d");
  if($res_sort == "date_received")
    $res_sort = "date_received $res_order, time_received";
   if($res_sort == "check_in"){
       $sort_filter = "(date_arrival >= '$today' AND status = 1)";
       $res_sort = "date_arrival";
       $res_order = "ASC";
   }

    if($res_sort == "check_out"){
        $sort_filter = "(date_departure >= '$today' AND status = 1)";
        $res_sort = "date_departure";
        $res_order = "ASC";
    }



  // Get reservations
  $sql = "SELECT * FROM reservations_$lcode WHERE $date_received_from AND $date_received_to AND $date_arrival_from AND $date_arrival_to AND $date_departure_from AND $date_departure_to AND $res_status AND $res_channel AND $res_room AND $res_name AND $date_canceled_from AND $date_canceled_to AND $sort_filter ORDER BY $res_sort $res_order LIMIT $start_index, 20";
  $rezultat = mysqli_query ($konekcija, $sql);
  while($red = mysqli_fetch_assoc($rezultat)){
    $res = old_fixReservation($red, $channel_logos, $room_names, $room_shortnames);
    array_push($reservations, $res);
  }
  $sql = "SELECT COUNT(*) AS total FROM reservations_$lcode WHERE $date_received_from AND $date_received_to AND $date_arrival_from AND $date_arrival_to AND $date_departure_from AND $date_departure_to AND $res_status AND $res_channel AND $res_room AND $res_name AND $date_canceled_from AND $date_canceled_to AND $sort_filter ORDER BY $res_sort $res_order";
  $rezultat = mysqli_query ($konekcija, $sql);
  $red = mysqli_fetch_assoc($rezultat);
  $total = $red["total"];
  $total = ceil($total / 20);
  $ret_val["reservations"] = $reservations;
  $ret_val["current_page"] = $page;
  $ret_val["total_pages_number"] = $total;
}

if($action == "rooms")
{
  $rooms = [];

  $sql = "SELECT * FROM rooms_$lcode";
  $rezultat = mysqli_query ($konekcija, $sql);
  while($red = mysqli_fetch_assoc($rezultat)){
    array_push($rooms, fixRoom($red));
  }

  $ret_val["rooms"] = $rooms;
}

if($action == "pricingPlans")
{
  $cene = [];
  $sql = "SELECT *
          FROM prices_$lcode
          ";
  if(checkPostExists("daily"))
    $sql = "SELECT * FROM prices_$lcode WHERE type = 'daily'";
  $rezultat = mysqli_query ($konekcija, $sql);
  while($red = mysqli_fetch_assoc($rezultat)){
    $red = fixRow($red);
    array_push($cene, $red);
  }
  $ret_val["prices"] = $cene;
}

if($action == "restrictionPlans")
{
  $restrikcije = [];
  // Get restrictions
  $sql = "SELECT *
          FROM restrictions_$lcode
          ";
  if(checkPostExists("daily"))
    $sql = "SELECT * FROM restrictions_$lcode WHERE type = 'daily'";
  $rezultat = mysqli_query ($konekcija, $sql);
  while($red = mysqli_fetch_assoc($rezultat)){
    $red = fixRow($red);
    array_push($restrikcije, $red);
  }
  $ret_val["restrictions"] = $restrikcije;
}

if($action == "occupancy")
{
  $total_rooms = 0;
  $jedinice = [];
  // Get rooms
  $sql = "SELECT parent_room, availability
          FROM rooms_$lcode
          ";
  $rezultat = mysqli_query ($konekcija, $sql);
  while($red = mysqli_fetch_assoc($rezultat)){
    array_push($jedinice, $red);
  }
  for($i=0;$i<sizeof($jedinice);$i++) // Total number of rooms
  {
    if($jedinice[$i]['parent_room'] == 0)
      $total_rooms += $jedinice[$i]['availability'];
  }
  $date_today = date("Y-m-d");
  $date_yesterday = date("Y-m-d", time() - ( 24 * 60 * 60));
  $date_last_week = date("Y-m-d", time() - (7 * 24 * 60 * 60));
  $occupancy = [];
  $occupancy['today'] = 0;
  $occupancy['yesterday'] = 0;
  $occupancy['last_week'] = 0;
  // Getting data
  $sql = "SELECT rooms
          FROM reservations_$lcode
          WHERE date_arrival <= '$date_today' AND date_departure > '$date_today' AND status=1
          ";
  $rezultat = mysqli_query ($konekcija, $sql);
  while($red = mysqli_fetch_assoc($rezultat)){
    $occupancy['today'] += sizeof(explode(",",$red['rooms']));
  }
  $sql = "SELECT rooms
          FROM reservations_$lcode
          WHERE date_arrival <= '$date_yesterday' AND date_departure > '$date_yesterday' AND status=1
          ";
  $rezultat = mysqli_query ($konekcija, $sql);
  while($red = mysqli_fetch_assoc($rezultat)){
    $occupancy['yesterday'] += sizeof(explode(",",$red['rooms']));
  }
  $sql = "SELECT rooms
          FROM reservations_$lcode
          WHERE date_arrival <= '$date_last_week' AND date_departure > '$date_last_week' AND status=1
          ";
  $rezultat = mysqli_query ($konekcija, $sql);
  while($red = mysqli_fetch_assoc($rezultat)){
    $occupancy['last_week'] += sizeof(explode(",",$red['rooms']));
  }
  // Formating
  $occupancy['today'] = $occupancy['today'] / $total_rooms * 100;
  $occupancy['yesterday'] = $occupancy['yesterday'] / $total_rooms * 100;
  $occupancy['last_week'] = $occupancy['last_week'] / $total_rooms * 100;
  $occupancy['today'] = number_format($occupancy['today'], 2, '.', '');
  $occupancy['yesterday'] = number_format($occupancy['yesterday'], 2, '.', '');
  $occupancy['last_week'] = number_format($occupancy['last_week'], 2, '.', '');
  $occupancy_confirmed = $occupancy;

  // Canceled

  $occupancy_canceled = [];
  $occupancy_canceled['today'] = 0;
  $occupancy_canceled['yesterday'] = 0;
  $occupancy_canceled['last_week'] = 0;
  // Getting data
  $sql = "SELECT rooms
          FROM reservations_$lcode
          WHERE date_arrival <= '$date_today' AND date_departure > '$date_today' AND status=5
          ";
  $rezultat = mysqli_query ($konekcija, $sql);
  while($red = mysqli_fetch_assoc($rezultat)){
    $occupancy_canceled['today'] += sizeof(explode(",",$red['rooms']));
  }
  $sql = "SELECT rooms
          FROM reservations_$lcode
          WHERE date_arrival <= '$date_yesterday' AND date_departure > '$date_yesterday' AND status=5
          ";
  $rezultat = mysqli_query ($konekcija, $sql);
  while($red = mysqli_fetch_assoc($rezultat)){
    $occupancy_canceled['yesterday'] += sizeof(explode(",",$red['rooms']));
  }
  $sql = "SELECT rooms
          FROM reservations_$lcode
          WHERE date_arrival <= '$date_last_week' AND date_departure > '$date_last_week' AND status=5
          ";
  $rezultat = mysqli_query ($konekcija, $sql);
  while($red = mysqli_fetch_assoc($rezultat)){
    $occupancy_canceled['last_week'] += sizeof(explode(",",$red['rooms']));
  }
  // Formating
  $occupancy_canceled['today'] = $occupancy_canceled['today'] / $total_rooms * 100;
  $occupancy_canceled['yesterday'] = $occupancy_canceled['yesterday'] / $total_rooms * 100;
  $occupancy_canceled['last_week'] = $occupancy_canceled['last_week'] / $total_rooms * 100;
  $occupancy_canceled['today'] = number_format($occupancy_canceled['today'], 2, '.', '');
  $occupancy_canceled['yesterday'] = number_format($occupancy_canceled['yesterday'], 2, '.', '');
  $occupancy_canceled['last_week'] = number_format($occupancy_canceled['last_week'], 2, '.', '');

  // No show
  $occupancy_noshow['today'] = "0.00";
  $occupancy_noshow['yesterday'] = "0.00";
  $occupancy_noshow['last_week'] = "0.00";

  $all_occupancy = [];
  $all_occupancy['confirmed'] = $occupancy_confirmed;
  $all_occupancy['canceled'] = $occupancy_canceled;
  $all_occupancy['noshow'] = $occupancy_noshow;
  $ret_val["occupancy"] = $all_occupancy;
}

if($action == "statistics") // This needs to be checked
{
  $statistics = [];
  $dfrom = checkPost("dfrom");
  $dto = checkPost("dto");
  $filter_by = checkPost("filter_by");
  $dfrom_ar = explode("-", $dfrom);
  $dto_ar = explode("-", $dto);
  $m1 = $dfrom_ar[1];
  $y1 = $dfrom_ar[0];
  $m2 = intval($dto_ar[1]);
  $y2 = intval($dto_ar[0]);

  // Getting channel names
  $sql = "SELECT name, id FROM channels_$lcode";
  $rezultat = mysqli_query($konekcija, $sql);
  $channel_names = [];
  while($red = mysqli_fetch_assoc($rezultat))
  {
    $channel_names[$red['id']] = $red['name'];
  }
  $channel_names["private"] = "Privatne rezervacije";
  $channel_names["total"] = "Ukupno";

  // Monthly data
  $monthly_data = [];
  $months = [];
  $count = 0;
  while(1) // Getting Month periods
  {
    $count += 1;
    $m1 = intval($m1);
    if($m1 < 10)
      $m1 = '0' . $m1;
    array_push($months, "$y1-$m1");
    $m1 = intval($m1);
    if(($m1 > $m2 && $y1 >= $y2) || ($y1 > $y2))
      break;
    $m1 += 1;
    if($m1 > 12)
    {
      $m1 = 1;
      $y1 += 1;
    }
  }
  $size_of = sizeof($months) - 1;
  for($i=0;$i<$size_of;$i++) // Getting monthly data
  {
    $month_data["occupancy"] = 0;
    $month_data["income"] = 0;
    $month_data["nights"] = 0;
    $month_data["max_income"] = 0;
    $month_data["max_income_guest"] = "";
    $month_data["avg_income"] = 0;
    $month_data["max_nights"] = 0;
    $month_data["max_nights_guest"] = "";
    $month_data["avg_nights"] = 0;
    $month_data["count"] = 0;

    // Rooms and Channels data
    $rooms_data = [];
    $channels_data = [];

    $rooms = [];
    $sql = "SELECT id, name, shortname, availability FROM rooms_$lcode";
    $rezultat = mysqli_query($konekcija, $sql);
    while($red = mysqli_fetch_assoc($rezultat)) // Getting rooms
    {
      array_push($rooms, $red);
      $rooms_data[$red["id"]]["count"] = 0;
      $rooms_data[$red["id"]]["income"] = 0;
      $rooms_data[$red["id"]]["nights"] = 0;
      $rooms_data[$red["id"]]["avg_income"] = 0;
      $rooms_data[$red["id"]]["avg_nights"] = 0;
    }
    $rooms_data['total']["count"] = 0;
    $rooms_data['total']["income"] = 0;
    $rooms_data['total']["nights"] = 0;
    $rooms_data['total']["avg_income"] = 0;
    $rooms_data['total']["avg_nights"] = 0;
    // Room data break

    $m_split = explode("-", $months[$i]);
    $month_data["month"] = intval($m_split[1]);
    $month_data["year"] = intval($m_split[0]);
    $from = $months[$i] . "-01";
    $to = $months[$i+1] . "-01";
    $sql = "SELECT total_price, nights, rooms, date_arrival, date_departure, customer_name, customer_surname FROM reservations_$lcode WHERE $filter_by >= '$from' AND $filter_by < '$to' AND status = 1";
    $rezultat = mysqli_query($konekcija, $sql);
    while($red = mysqli_fetch_assoc($rezultat))
    {
      $income = $red["total_price"];
      $nights = $red["nights"];
      if($filter_by == "date_arrival" && cmpDates($red["date_departure"], $to) >= 0)
        $nights = dateDiff($red["date_arrival"], $to);
      $month_data["income"] += $income;
      $month_data["nights"] += $nights;
      if($month_data["max_income"] < $income)
      {
        $month_data["max_income"] = $income;
        $month_data["max_income_guest"] = $red["customer_name"] . " " . $red["customer_surname"];
      }
      if($month_data["max_nights"] < $nights)
      {
        $month_data["max_nights"] = $nights;
        $month_data["max_nights_guest"] = $red["customer_name"] . " " . $red["customer_surname"];
      }
      $month_data["count"] += 1;
    }
    // Fixing monthly data
    $month_nights = dateDiff($from, $to);
    $total_nights = 0;
    for($j=0;$j<sizeof($rooms);$j++)
    {
      $total_nights += $month_nights * $rooms[$j]["availability"];
    }
    if($month_data["nights"] == 0)
      $month_data["occupancy"] = 0;
    else
      $month_data["occupancy"] = round($month_data["nights"] / $total_nights * 100, 2);

    if($month_data["income"] == 0)
      $month_data["avg_income"] = 0;
    else
      $month_data["avg_income"] = round($month_data["income"] / $month_data["count"], 2);

    if($month_data["nights"] == 0)
      $month_data["avg_nights"] = 0;
    else
      $month_data["avg_nights"] = round($month_data["nights"] / $month_data["count"], 2);
    array_push($monthly_data, $month_data);
  } // Monthly data done

  // Room data continue
  $channels = [];
  $sql = "SELECT id, name, commission FROM channels_$lcode"; // Geting channels
  $rezultat = mysqli_query($konekcija, $sql);
  while($red = mysqli_fetch_assoc($rezultat))
  {
    array_push($channels, $red);
    $channels_data[$red["id"]]["count"] = 0;
    $channels_data[$red["id"]]["income"] = 0;
    $channels_data[$red["id"]]["costs"] = 0;
    $channels_data[$red["id"]]["earnings"] = 0;
    $channels_data[$red["id"]]["avg_income"] = 0;
    $channels_data[$red["id"]]["canceled"] = 0;
    $channels_data[$red["id"]]["commission"] = $red["commission"];
  }
  $channels_data["private"]["count"] = 0;
  $channels_data["private"]["income"] = 0;
  $channels_data["private"]["costs"] = 0;
  $channels_data["private"]["earnings"] = 0;
  $channels_data["private"]["avg_income"] = 0;
  $channels_data["private"]["canceled"] = 0;
  $channels_data["private"]["commission"] = 0;
  $channels_data["total"]["count"] = 0;
  $channels_data["total"]["income"] = 0;
  $channels_data["total"]["costs"] = 0;
  $channels_data["total"]["earnings"] = 0;
  $channels_data["total"]["avg_income"] = 0;
  $channels_data["total"]["canceled"] = 0;
  $channels_data["total"]["commission"] = 0;

  $sql = "SELECT rooms, id_woodoo, total_price, nights, status FROM reservations_$lcode WHERE $filter_by >= '$dfrom' AND $filter_by <= '$dto'";
  $rezultat = mysqli_query($konekcija, $sql);
  while($red = mysqli_fetch_assoc($rezultat)) // Rooms and Channels reservations
  {
    // Channels
    $res_channel = $red["id_woodoo"] != "" ? $red["id_woodoo"] : "private";
    if($red["id_woodoo"] == "-1")
        $res_channel = "private";
    if(!isset($channel_names[$red["id_woodoo"]]))
        $res_channel = "private";
    $channels_data[$res_channel]["count"] += 1;
    if($red["status"] == 5)
      $channels_data[$res_channel]["canceled"] += 1;
    if($red["status"] == 1)
      $channels_data[$res_channel]["income"] += $red["total_price"];
    // Rooms
    if($red["status"] == 1)
    {
      $res_rooms = explode(",", $red["rooms"]);
      for($i=0;$i<sizeof($res_rooms);$i++)
      {
        $rooms_data[$res_rooms[$i]]["count"] += 1;
        $rooms_data[$res_rooms[$i]]["nights"] += $red["nights"];
        $rooms_data['total']["count"] += 1;
        $rooms_data['total']["nights"] += $red["nights"];
        if(sizeof($res_rooms) > 1)
        {
          if($red["dayprices"] != "")
          {
            $dayprices = json_decode($red["dayprices"]);
            $rooms_data[$res_rooms[$i]]["income"] += array_sum($dayprices[$res_rooms[$i]]);
            $rooms_data['total']["income"] += array_sum($dayprices[$res_rooms[$i]]);
          }
          else {
            $rooms_data[$res_rooms[$i]]["income"] += $red["total_price"] / sizeof($rez_rooms);
            $rooms_data['total']["income"] += $red["total_price"] / sizeof($rez_rooms);
          }
        }
        else {
          $rooms_data[$res_rooms[$i]]["income"] += $red["total_price"];
          $rooms_data['total']["income"] += $red["total_price"];
        }
      }
    }
  }
  // Fixing room data
  foreach($rooms_data as $key => $value)
  {
    if($value["count"])
    {
      $rooms_data[$key]["avg_income"] = round($value["income"] / $value["count"], 2);
      $rooms_data[$key]["avg_nights"] = round($value["nights"] / $value["count"], 2);
    }
  }
  // Fixing channel data
  foreach($channels_data as $key => $value)
  {
    if($value["count"])
    {
      $channels_data[$key]["costs"] = round($value["commission"] * $value["income"] / 100, 2);
      $channels_data[$key]["earnings"] = round($value["income"] - $channels_data[$key]["costs"], 2);
      $channels_data[$key]["avg_income"] =  round($value["income"] / $value["count"], 2);
      $channels_data[$key]["canceled"] = round($value["canceled"] / $value["count"] * 100, 2);

      $channels_data["total"]["count"] += $channels_data[$key]["count"];
      $channels_data["total"]["income"] += $channels_data[$key]["income"];
      $channels_data["total"]["costs"] += $channels_data[$key]["costs"];
      $channels_data["total"]["earnings"] += $channels_data[$key]["earnings"];
      $channels_data["total"]["canceled"] += $channels_data[$key]["canceled"];
    }
  }
  $channels_data["total"]["avg_income"] = round($channels_data["total"]["income"] / $channels_data["total"]["count"], 2);
  $channels_data["total"]["canceled"] = round($channels_data["total"]["canceled"] / $channels_data["total"]["count"] * 100, 2);


  // Bookwindow data
  $bookwindow_data = [];
  for($i=0;$i<8;$i++)
  {
    $bookwindow_data[$i]["count"] = 0;
    $bookwindow_data[$i]["confirmed"] = 0;
    $bookwindow_data[$i]["income"] = 0;
    $bookwindow_data[$i]["nights"] = 0;
    $bookwindow_data[$i]["avg_income"] = 0;
    $bookwindow_data[$i]["avg_nights"] = 0;
  }
  $sql = "SELECT status, total_price, date_received, date_arrival, nights FROM reservations_$lcode WHERE $filter_by >= '$dfrom' AND $filter_by <= '$dto'";
  $rezultat = mysqli_query($konekcija, $sql);
  while($red = mysqli_fetch_assoc($rezultat)) // Bookwindow reservations
  {
    $res_window = dateDiff($red["date_received"], $red["date_arrival"]);
    if($res_window < 2)
      $bookwindow = 0;
    else if($res_window < 4)
      $bookwindow = 1;
    else if($res_window < 8)
      $bookwindow = 2;
    else if($res_window < 15)
      $bookwindow = 3;
    else if($res_window < 31)
      $bookwindow = 4;
    else if($res_window < 61)
      $bookwindow = 5;
    else if($res_window < 91)
      $bookwindow = 6;
    else
      $bookwindow = 7;
    $bookwindow_data[$bookwindow]["count"] += 1;
    if($red["status"] == 1)
    {
      $bookwindow_data[$bookwindow]["confirmed"] += 1;
      $bookwindow_data[$bookwindow]["income"] += $red["total_price"];
      $bookwindow_data[$bookwindow]["nights"] += $red["nights"];
    }
  }
  // Fixing bookwindow data
  for($i=0;$i<sizeof($bookwindow_data);$i++)
  {
    $bookwindow_data[$i]["income"] = round($bookwindow_data[$i]["income"], 2);
    $bookwindow_data[$i]["avg_income"] = round($bookwindow_data[$i]["income"] / $bookwindow_data[$i]["confirmed"], 2);
    $bookwindow_data[$i]["avg_nights"] = round($bookwindow_data[$i]["nights"] / $bookwindow_data[$i]["confirmed"], 2);
    $bookwindow_data[$i]["canceled_percentage"] = round(($bookwindow_data[$i]["count"] - $bookwindow_data[$i]["confirmed"]) / $bookwindow_data[$i]["count"] * 100, 2);
  }

  // Pie chart data
  $percent_channels["count"] = 0;
  $percent_countries["count"] = 0;

  $sql = "SELECT customer_country, id_woodoo FROM reservations_$lcode WHERE $filter_by >= '$dfrom' AND $filter_by <= '$dto' AND status = 1";
  $rezultat = mysqli_query($konekcija, $sql);
  while($red = mysqli_fetch_assoc($rezultat)) // Pie chart reservations
  {
    $res_channel = $red["id_woodoo"] != "" ? $red["id_woodoo"] : "private";
    if($red["id_woodoo"] == "-1")
        $res_channel = "private";
    if(!isset($channel_names[$red["id_woodoo"]]))
        $res_channel = "private";
    $res_country = $red["customer_country"] != "" ? $red["customer_country"] : "other";
    $percent_channels["count"] += 1;
    $percent_countries["count"] += 1;
    if(isset($percent_channels[$res_channel]))
      $percent_channels[$res_channel] += 1;
    else
      $percent_channels[$res_channel] = 1;
    if($res_country != "--")
    {
      if(isset($percent_countries[$res_country]))
        $percent_countries[$res_country] += 1;
      else
        $percent_countries[$res_country] = 1;
    }
  }
  // Fixing pie charts
  foreach($percent_channels as $key => $value)
  {
    if($key != "count")
      $percent_channels[$key] = round($value / $percent_channels["count"] * 100, 2);
  }
  foreach($percent_countries as $key => $value)
  {
    if($key != "count")
      $percent_countries[$key] = round($value / $percent_countries["count"] * 100);
  }

  // Formating
  $ret_rooms = [];
  $ret_channels = [];
  $ret_percent_channels = [];
  $ret_percent_countries = [];
  $count = 0;
  $sql = "SELECT shortname, id FROM rooms_$lcode";
  $rezultat = mysqli_query($konekcija, $sql);
  $shortnames = [];
  while($red = mysqli_fetch_assoc($rezultat)) // Including shortname for rooms
  {
    $shortnames[$red['id']] = $red['shortname'];
  }
  foreach($rooms_data as $key => $value) // Making rooms array
  {
    if($key != 'total')
    {
      $ret_rooms[$count] = $value;
      $ret_rooms[$count]["id"] = $key;
      $ret_rooms[$count]["shortname"] = $shortnames[$key];
      $count += 1;
    }
  }
  $ret_rooms[$count] = $rooms_data['total'];
  $ret_rooms[$count]["id"]= "total";

  $count = 0;
  foreach($channels_data as $key => $value) // Making channels array
  {
    if($key != 'total')
    {
      $ret_channels[$count] = $value;
      $ret_channels[$count]["id"] = $key;
      $ret_channels[$count]["name"] = $channel_names[$key];
      $count += 1;
    }
  }
  $ret_channels[$count] = $channels_data["total"];
  $ret_channels[$count]["id"] = "total";
  $ret_channels[$count]["canceled"] = round($ret_channels[$count]["canceled"] / (sizeof($ret_channels) - 1), 2);
  $count = 0;

  foreach($percent_channels as $key => $value) // Making percent channels array
  {
    if($key != "count")
    {
      $ret_percent_channels[$count]["value"] = $value;
      $ret_percent_channels[$count]["id"] = $key;
      if(isset($channel_names[$key])) // Adding channel logo
        $ret_percent_channels[$count]["name"] = $channel_names[$key];
      else
        $ret_percent_channels[$count]["name"] = $key;
      $count += 1;
    }
  }
  $count = 0;
  foreach($percent_countries as $key => $value) // Making percent countries array
  {
    if($key != "count")
    {
      $ret_percent_countries[$count]["value"] = $value;
      $ret_percent_countries[$count]["id"] = $key;
      $ret_percent_countries[$count]["name"] = $iso_countries->$key;
      $count += 1;
    }
  }
  usort($ret_percent_countries, function($a, $b) {return ($a["value"] < $b["value"]);}); // Show only 10 countries
  $ret_percent_countries = array_slice($ret_percent_countries, 0, 9);
  $total = 0;
  for($i=0;$i<sizeof($ret_percent_countries);$i++)
  {
    $total += $ret_percent_countries[$i]["value"];
  }
  $len = sizeof($ret_percent_countries);
  $ret_percent_countries[$len]["id"] = "--";
  $ret_percent_countries[$len]["value"] = 100 - $total;
  $ret_percent_countries[$len]["name"] = "Ostale";

  $statistics["months"] = $monthly_data;
  $statistics["rooms"] = $ret_rooms;
  $statistics["channels"] = $ret_channels;
  $statistics["channels_percentage"] = $ret_percent_channels;
  $statistics["countries_percentage"] = $ret_percent_countries;
  $statistics["bookwindow"] = $bookwindow_data;
  $ret_val["data"] = $statistics;
}

if($action == "avail")
{
  $avail = [];
    $dfrom = checkPost("dfrom");
    $dto = checkPost("dto");
    $avail = plansAvailValues($lcode, $dfrom, $dto, $konekcija);
  $ret_val["avail"] = $avail;
}

if($action == "minAvail")
{
  $avail = [];
  $dfrom = checkPost("dfrom");
  $dto = checkPost("dto");
  $avail = plansAvailValues($lcode, $dfrom, $dto, $konekcija);
  $min_avail = [];
  foreach($avail as $room => $dates){
      $min_avail[$room] = array_values($dates)[0];
      foreach($dates as $date => $value){
          if($value < $min_avail[$room])
              $min_avail[$room] = $value;
      }
  }
  $ret_val["avail"] = $min_avail;
}

if($action == "prices")
{
  $prices = [];
    $dfrom = checkPost("dfrom");
    $dto = checkPost("dto");
    $id = checkPost("id");
    $prices = plansPriceValues($lcode, $dfrom, $dto, $id, $konekcija);
  $ret_val["prices"] = $prices;
}

if($action == "restrictions")
{
  $restrictions = [];
    $dfrom = checkPost("dfrom");
    $dto = checkPost("dto");
    $id = checkPost("id");
    $restrictions = plansRestrictionValues($lcode, $dfrom, $dto, $id, $konekcija);
  $ret_val["restrictions"] = $restrictions;
}

if($action == "search")
{
  $keyword = checkPost("keyword");
  $keyword = strtolower($keyword);
  $reservations = [];
  $guests = [];
  $invoices = [];

  $sql = "SELECT * FROM reservations_$lcode WHERE LOWER(customer_name) LIKE '%$keyword%' OR LOWER(customer_surname) LIKE '%$keyword%' OR reservation_code LIKE '%$keyword%' OR CONCAT(LOWER(customer_name), \" \", LOWER(customer_surname)) LIKE '%$keyword%' OR LOWER(customer_mail) LIKE '%$keyword%'";
  $rezultat = mysqli_query ($konekcija, $sql);
  while($red = mysqli_fetch_assoc($rezultat)){
    array_push($reservations, old_fixReservation($red, $channel_logos, $room_names, $room_shortnames));
  }


  $ret_val["reservations"] = $reservations;
  $ret_val["guests"] = $guests;
  $ret_val["invoices"] = $invoices;
}

if($action == "calendarList")
{
  $dfrom = checkPost("dfrom");
  $price_id = checkPost("price_id");
  $restriction_id = checkPost("restriction_id");
  $dto = date_create($dfrom);
  date_add($dto, date_interval_create_from_date_string("20 days"));
  $dto = date_format($dto, "Y-m-d");
  $next_date = date_create($dfrom);
  date_add($next_date, date_interval_create_from_date_string("21 days"));
  $next_date = date_format($next_date, "Y-m-d");
  $avail = plansAvailValues($lcode, $dfrom, $dto, $konekcija);
  $prices = plansPriceValues($lcode, $dfrom, $dto, $price_id, $konekcija);
  $restrictions = plansRestrictionValues($lcode, $dfrom, $dto, $restriction_id, $konekcija);
  $rooms = [];
  $sql = "SELECT * FROM rooms_$lcode WHERE parent_room = '0'";
  $rezultat = mysqli_query($konekcija, $sql);
  while($red = mysqli_fetch_assoc($rezultat)){
    array_push($rooms, $red);
  }
  $values = [];
  for($i=0;$i<sizeof($rooms);$i++){
    $values[$i] = [];
    $values[$i]["name"] = $rooms[$i]["name"];
    $values[$i]["shortname"] = $rooms[$i]["shortname"];
    $values[$i]["id"] = $rooms[$i]["id"];
    $values[$i]["values"] = [];
    $room_avails = $avail[$rooms[$i]["id"]];
    foreach($room_avails as $date => $value){
      $struct = $restrictions[$rooms[$i]["id"]][$date];
      $struct["price"] = $prices[$rooms[$i]["id"]][$date];
      $struct["avail"] = $avail[$rooms[$i]["id"]][$date];
      $struct["date"] = $date;
      $struct["occupied"] = intval($rooms[$i]["availability"]) - intval($avail[$rooms[$i]["id"]][$date]);
      if($struct["occupied"] < 0 || is_nan($struct["occupied"]))
        $struct["occupied"] = 0;
      array_push($values[$i]["values"], $struct);
    }
  }
  $ret_val["data"] = $values;
  $ret_val["next_date"] = $next_date;
}

if($action == "calendarGantt")
{
    $month = checkPost("month");
    $year = checkPost("year");
    $month = $month < 10 ? "0$month" : $month;
    $dfrom = "$year-$month-01";
    $dto = "$year-$month-31";
    $parent_ids = [];
    $rooms_map = [];
    $sql = "SELECT * FROM rooms_$lcode";
    $rezultat = mysqli_query($konekcija, $sql);
    while($red = mysqli_fetch_assoc($rezultat)){
        if($red["parent_room"] == 0){
            $parent_ids[$red["id"]] = $red["id"];
            $room_numbers = explode(",", $red["room_numbers"]);
            for($i=0;$i<sizeof($room_numbers);$i++){
                $room_struct = [];
                $room_struct["name"] = $red["name"];
                $room_struct["shortname"] = $red["shortname"];
                $room_struct["room_number"] = $room_numbers[$i];
                $room_struct["id"] = $red["id"];
                $room_struct["reservations"] = [];
                $rooms_map[$red["id"] . "_$i"] = $room_struct;
            }
        }
        else {
            $parent_ids[$red["id"]] = $red["parent_room"];
        }
    }
    $sql = "SELECT * FROM reservations_$lcode WHERE date_departure >= '$dfrom' AND date_arrival <= '$dto' AND status = 1 ORDER BY date_arrival ASC";
    $rezultat = mysqli_query($konekcija, $sql);
    while($red = mysqli_fetch_assoc($rezultat)){
        $res_rooms = explode(",", $red["rooms"]);
        $res_numbers = explode(",", $red["room_numbers"]);
        $res_data = old_fixReservation($red, $channel_logos, $room_names, $room_shortnames);
        for($i=0;$i<sizeof($res_rooms);$i++){
            if(is_array($rooms_map[$res_rooms[$i] . "_" . $res_numbers[$i]]["reservations"]))
                 array_push($rooms_map[$res_rooms[$i] . "_" . $res_numbers[$i]]["reservations"], $res_data);
        }
    }
    $rooms_list = [];
    foreach($rooms_map as $key => $value){
          array_push($rooms_list, $value);
    }
    $ret_val["data"] = $rooms_list;
}

if($action == "showCC")
{
  $rcode = checkPost("rcode");
  $cc_pass = checkPost("cc_pass");
  $userToken = makeRequest("acquire_token", array($account, "davincijevkod966", "753fa793e9adb95321b061f05e29a78327645c05e097e376"));
  $resp = makeRequest("fetch_ccard", array($userToken, $lcode, $rcode, $cc_pass));
  makeReleaseRequest("release_token", array($userToken));
  $ret_val["data"] = $resp;
}

// Return
echo json_encode($ret_val);
$konekcija->close();


?>
