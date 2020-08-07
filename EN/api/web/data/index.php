<?php

require '../../main.php';



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

if($action == "basics") // Things only needed on first call
{

  $guest_emails = [];
  $client_emails = [];

  $sql = "SELECT * FROM all_guest_emails WHERE lcode = '$lcode'";
  $rezultat = mysqli_query($konekcija, $sql);
  $guest_emails = mysqli_fetch_assoc($rezultat);

  $sql = "SELECT * FROM all_client_emails WHERE lcode = '$lcode'";
  $rezultat = mysqli_query($konekcija, $sql);
  $client_emails = mysqli_fetch_assoc($rezultat);

  $ret_val["guest_emails"] = $guest_emails;
  $ret_val["client_emails"] = $client_emails;
}

if($action == "basics" || $action == "rooms")
{
  $rooms = [];

  $sql = "SELECT * FROM rooms_$lcode";
  $rezultat = mysqli_query ($konekcija, $sql);
  while($red = mysqli_fetch_assoc($rezultat)){
    array_push($rooms, fixRoom($red));
  }

  $ret_val["rooms"] = $rooms;
}

if($action == "basics" || $action == "channels")
{
  $channels = [];

  $sql = "SELECT * FROM channels_$lcode ORDER BY name ASC";
  $rezultat = mysqli_query ($konekcija, $sql);
  while($red = mysqli_fetch_assoc($rezultat)){
    array_push($channels, $red);
  }

  $ret_val["channels"] = $channels;
}

if($action == "basics" || $action == "pricingPlans")
{
  $pricing_plans = [];

  $sql = "SELECT * FROM prices_$lcode ORDER BY name ASC";
  $rezultat = mysqli_query ($konekcija, $sql);
  while($red = mysqli_fetch_assoc($rezultat)){
    array_push($pricing_plans, $red);
  }

  $ret_val["pricing_plans"] = $pricing_plans;
}

if($action == "basics" || $action == "restrictionPlans")
{
  $restriction_plans = [];

  $sql = "SELECT * FROM restrictions_$lcode ORDER BY name ASC";
  $rezultat = mysqli_query ($konekcija, $sql);
  while($red = mysqli_fetch_assoc($rezultat)){
    array_push($restriction_plans, fixRestriction($red));
  }

  $ret_val["restriction_plans"] = $restriction_plans;
}

if($action == "basics" || $action == "extras")
{
  $extras = [];

  $sql = "SELECT * FROM extras_$lcode";
  $rezultat = mysqli_query ($konekcija, $sql);
  while($red = mysqli_fetch_assoc($rezultat)){
    array_push($extras, fixExtra($red));
  }

  $ret_val["extras"] = $extras;
}

if($action == "basics" || $action == "policies")
{
  $policies = [];

  $sql = "SELECT * FROM policies_$lcode";
  $rezultat = mysqli_query ($konekcija, $sql);
  while($red = mysqli_fetch_assoc($rezultat)){
    array_push($policies, fixRestriction($red));
  }

  $ret_val["policies"] = $policies;
}

if($action == "promocodes")
{
  $promocodes = [];

  $sql = "SELECT * FROM promocodes_$lcode";
  $rezultat = mysqli_query($konekcija, $sql);
  while($red = mysqli_fetch_assoc($rezultat)){
    array_push($promocodes, $red);
  }

  $ret_val["promocodes"] = $promocodes;
}

if($action == "news" || $action == "events" || $action == "reservations" || $action == "reservation" || $action == "calendar" || $action == "guestReservations" || $action == "occupancyReport" || $action == "housekeepingReport" || $action == "dailyReport" ||
$action == "search"){ // Additional items needed for reservations
  $channel_data = [];
  $sql = "SELECT logo, name, id FROM channels_$lcode";
  $rezultat = mysqli_query($konekcija, $sql);
  while($red = mysqli_fetch_assoc($rezultat)){
    $channel_data[$red['id']] = $red;
  }
}

if($action == "news")
{
  $dfrom = checkPost("dfrom");
  $dto = checkPost("dto");
  $order_type = checkPost("order_type");
  $order_by = checkPost("order_by");
  if($order_by == 'date_received')
    $order_by = "date_received $order_type, time_received";

  $received = [];
  $modified = [];
  $canceled = [];

  // Get received
  $sql = "SELECT * FROM reservations_$lcode WHERE date_received >= '$dfrom' AND date_received <= '$dto' AND status = 1 ORDER BY $order_by $order_type";
  $rezultat = mysqli_query($konekcija, $sql);
  while($red = mysqli_fetch_assoc($rezultat)){
    array_push($received, fixReservation($red, $channel_data, $konekcija, $lcode));
  }

  // Get modified and canceled
  $sql = "SELECT * FROM reservations_$lcode WHERE date_canceled >= '$dfrom' AND date_canceled <= '$dto' AND status = 5 ORDER BY $order_by $order_type";
  $rezultat = mysqli_query($konekcija, $sql);
  while($red = mysqli_fetch_assoc($rezultat)){
    $res = fixReservation($red, $channel_data, $konekcija, $lcode);
    if($res["was_modified"] == 1)
      array_push($modified, $res);
    else
      array_push($canceled, $res);
  }

  $ret_val["received"] = $received;
  $ret_val["modified"] = $modified;
  $ret_val["canceled"] = $canceled;
}

if($action == "events")
{

  $dfrom = checkPost("dfrom");
  $dto = checkPost("dto");

  $arrivals = [];
  $departures = [];
  $stay = [];

  // Get arrivals
  $sql = "SELECT * FROM reservations_$lcode WHERE date_arrival >= '$dfrom' AND date_arrival <= '$dto' AND status = 1 ORDER BY date_arrival ASC, date_departure DESC";
  $rezultat = mysqli_query($konekcija, $sql);
  while($red = mysqli_fetch_assoc($rezultat)){
    array_push($arrivals, fixReservation($red, $channel_data, $konekcija, $lcode));
  }

  // Get departures
  $sql = "SELECT * FROM reservations_$lcode WHERE date_departure >= '$dfrom' AND date_departure <= '$dto' AND status = 1 ORDER BY date_arrival ASC, date_departure DESC";
  $rezultat = mysqli_query($konekcija, $sql);
  while($red = mysqli_fetch_assoc($rezultat)){
    array_push($departures, fixReservation($red, $channel_data, $konekcija, $lcode));
  }

  // Get stay
  $sql = "SELECT * FROM reservations_$lcode WHERE ((date_arrival < '$dfrom' AND date_departure > '$dfrom') OR (date_departure < '$dto' AND date_departure > '$dto')) AND status = 1 ORDER BY date_arrival ASC, date_departure DESC";
  $rezultat = mysqli_query($konekcija, $sql);
  while($red = mysqli_fetch_assoc($rezultat)){
    array_push($stay, fixReservation($red, $channel_data, $konekcija, $lcode));
  }

  $ret_val["arrivals"] = $arrivals;
  $ret_val["departures"] = $departures;
  $ret_val["stay"] = $stay;

}

if($action == "calendar")
{
  $kalendar = [];
    $dfrom = checkPost("dfrom");
    $dto = checkPost("dto");
    // Get calendar
    $sql = "SELECT *
            FROM reservations_$lcode
            WHERE ((date_arrival >= '$dfrom' AND date_arrival <= '$dto') OR (date_departure >= '$dfrom' AND date_departure <= '$dto')) AND status=1
            ORDER BY date_arrival ASC
            ";
    $rezultat = mysqli_query ($konekcija, $sql);
    while($red = mysqli_fetch_assoc($rezultat)){
      $red = fixReservation($red, $channel_data, $konekcija, $lcode);
      array_push($kalendar, $red);
    }

  $ret_val["reservations"] = $kalendar;

  $avail = [];
  $avail = plansAvailValues($lcode, $dfrom, $dto, $konekcija);
  $ret_val["avail"] = $avail;

  $prices = [];
  $id = checkPost("price_id");
  $prices = plansPriceValues($lcode, $dfrom, $dto, $id, $konekcija);
  $ret_val["prices"] = $prices;

  $restrictions = [];
  $id = checkPost("restriction_id");
  $restrictions = plansRestrictionValues($lcode, $dfrom, $dto, $id, $konekcija);
  $ret_val["restrictions"] = $restrictions;

}

if($action == "avail")
{
  $avail = [];
    $dfrom = checkPost("dfrom");
    $dto = checkPost("dto");
    $avail = plansAvailValues($lcode, $dfrom, $dto, $konekcija);
  $ret_val["avail"] = $avail;
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

if($action == "reservations")
{
  $filter_by = checkPost("filter_by");
  $dfrom = checkPost("dfrom");
  $dto = checkPost("dto");
  $arrivals = checkPost("arrivals");
  $departures = checkPost("departures");
  $status = checkPost("status");
  $rooms = json_decode(checkPost("rooms"));
  $min_price = checkPost("min_price");
  $max_price = checkPost("max_price");
  $min_nights = checkPost("min_nights");
  $max_nights = checkPost("max_nights");
  $channels = json_decode(checkPost("channels"));
  $countries = json_decode(checkPost("countries"));
  $today = date("Y-m-d");

  $dfrom_sql = $dfrom == "" ? 1 : "($filter_by >= '$dfrom')";
  $dto_sql = $dto == "" ? 1 : "($filter_by <= '$dto')";
  $arrivals_sql = $arrivals == 0 ? 1 : "(date_arrival = '$today')";
  $departures_sql = $departures == 0 ? 1 : "(date_departure = '$today')";
  // If both are selected, only one needs to be true
  if($arrivals == 1 && $departures == 1)
    $today_sql = "($arrivals_sql OR $departures_sql)";
  else
    $today_sql = "($arrivals_sql AND $departures_sql)";
  // Pairing dfrom / dto with today if both are set, only one needs to be true
  if(($arrivals == 1 || $departures == 1) && ($dfrom != "" || $dto != ""))
    $dates_sql = "(($dfrom_sql AND $dto_sql) OR $today_sql)";
  else
    $dates_sql = "(($dfrom_sql AND $dto_sql) AND $today_sql)";

  $status_sql = $status == "" ? 1 : "(status = $status)";
  $min_price_sql = $min_price == "" ? 1 : "(total_price >= $min_price)";
  $max_price_sql = $max_price == "" ? 1 : "(total_price <= $max_price)";
  $min_nights_sql = $min_nights == "" ? 1 : "(nights >= $min_nights)";
  $max_nights_sql = $max_nights == "" ? 1 : "(nights <= $max_nights)";

  $rooms_sql = 1;
  if(sizeof($rooms)){
    $rooms_sql = [];
    for($i=0;$i<sizeof($rooms);$i++){
      $room = $rooms[$i];
      array_push($rooms_sql, "(rooms LIKE '%$room%')");
    }
    $rooms_sql = "(". implode(" OR ", $rooms_sql) .")";
  }
  $channels_sql = 1;
  if(sizeof($channels)){
    $channels = implode(",", $channels);
    $channels_sql = "(id_woodoo IN ($channels))";
  }
  $countries_sql = 1;
  if(sizeof($countries)){
    $countries = implode(",", $countries);
    $countries_sql = "(customer_country IN ($countries))";
  }

  $page = checkPost("page");
  $start_index = ($page - 1) * 20;
  $res_sort = checkPost("order_by");
  $res_order = checkPost("order_type");
  if($res_sort == "date_received")
    $res_sort = "date_received $res_order, time_received";

  // Get reservations
  $reservations = [];
  $sql = "SELECT * FROM reservations_$lcode WHERE $dates_sql AND $status_sql AND $min_price_sql AND $max_price_sql AND $min_nights_sql AND $max_nights_sql AND $channels_sql AND $rooms_sql AND $countries_sql ORDER BY $res_sort $res_order LIMIT $start_index, 20";
  $rezultat = mysqli_query($konekcija, $sql);
  while($red = mysqli_fetch_assoc($rezultat)){
    $res = fixReservation($red, $channel_data, $konekcija, $lcode);
    array_push($reservations, $res);
  }
  // Total pages
  $sql = "SELECT COUNT(*) AS total FROM reservations_$lcode WHERE $dates_sql AND $status_sql AND $min_price_sql AND $max_price_sql AND $min_nights_sql AND $max_nights_sql AND $channels_sql AND $rooms_sql AND $countries_sql";
  $rezultat = mysqli_query($konekcija, $sql);
  $red = mysqli_fetch_assoc($rezultat);
  $total = $red["total"];
  $total = ceil($total / 20);
  // Min / Max price
  $sql = "SELECT MIN(total_price) AS min_price, MAX(total_price) AS max_price FROM reservations_$lcode";
  $rezultat = mysqli_query($konekcija, $sql);
  $red = mysqli_fetch_assoc($rezultat);
  $min_price = $red["min_price"];
  $max_price = $red["max_price"];
  // Min / Max nights
  $sql = "SELECT MIN(nights) AS min_nights, MAX(nights) AS max_nights FROM reservations_$lcode";
  $rezultat = mysqli_query ($konekcija, $sql);
  $red = mysqli_fetch_assoc($rezultat);
  $min_nights = $red["min_nights"];
  $max_nights = $red["max_nights"];

  $ret_val["reservations"] = $reservations;
  $ret_val["min_price"] = $min_price;
  $ret_val["max_price"] = $max_price;
  $ret_val["min_nights"] = $min_nights;
  $ret_val["max_nights"] = $max_nights;
  $ret_val["page"] = $page;
  $ret_val["total_pages_number"] = $total;
}

if($action == "reservation")
{
  $reservation_code = checkPost("reservation_code");
  // Get reservation
  $reservations = [];
  $sql = "SELECT * FROM reservations_$lcode WHERE reservation_code = '$reservation_code'";
  $rezultat = mysqli_query($konekcija, $sql);
  while($red = mysqli_fetch_assoc($rezultat)){
    $reservation = fixReservation($red, $channel_data, $konekcija, $lcode);
  }
  $max_nights = $red["max_nights"];

  $ret_val["reservation"] = $reservation;
}

if($action == "guests")
{
  // Guests basicly get reservations and selected valid customer ids from them
  $dfrom = checkPost("dfrom");
  $dto = checkPost("dto");
  $arrivals = checkPost("arrivals");
  $departures = checkPost("departures");
  $rooms = json_decode(checkPost("rooms"));
  $min_price = checkPost("min_price");
  $max_price = checkPost("max_price");
  $min_nights = checkPost("min_nights");
  $max_nights = checkPost("max_nights");
  $channels = json_decode(checkPost("channels"));
  $countries = json_decode(checkPost("countries"));
  $today = date("Y-m-d");

  $dfrom_sql = $dfrom == "" ? 1 : "(date_arrival >= '$dfrom')";
  $dto_sql = $dto == "" ? 1 : "(date_arrival <= '$dto')";
  $arrivals_sql = $arrivals == 0 ? 1 : "(date_arrival = '$today')";
  $departures_sql = $departures == 0 ? 1 : "(date_departure = '$today')";
  // If both are selected, only one needs to be true
  if($arrivals == 1 && $departures == 1)
    $today_sql = "($arrivals_sql OR $departures_sql)";
  else
    $today_sql = "($arrivals_sql AND $departures_sql)";
  // Pairing dfrom / dto with today if both are set, only one needs to be true
  if(($arrivals == 1 || $departures == 1) && ($dfrom != "" || $dto != ""))
    $dates_sql = "(($dfrom_sql AND $dto_sql) OR $today_sql)";
  else
    $dates_sql = "(($dfrom_sql AND $dto_sql) AND $today_sql)";

  $min_price_sql = $min_price == "" ? 1 : "(total_paid >= $min_price)";
  $max_price_sql = $max_price == "" ? 1 : "(total_paid <= $max_price)";
  $min_nights_sql = $min_nights == "" ? 1 : "(total_nights >= $min_nights)";
  $max_nights_sql = $max_nights == "" ? 1 : "(total_nights <= $max_nights)";

  $rooms_sql = 1;
  if(sizeof($rooms)){
    $rooms_sql = [];
    for($i=0;$i<sizeof($rooms);$i++){
      $room = $rooms[$i];
      array_push($rooms_sql, "(rooms LIKE '%$room%')");
    }
    $rooms_sql = "(". implode(" OR ", $rooms_sql) .")";
  }
  $channels_sql = 1;
  if(sizeof($channels)){
    $channels = implode(",", $channels);
    $channels_sql = "(id_woodoo IN ($channels))";
  }
  $countries_sql = 1;
  if(sizeof($countries)){
    $countries = implode(",", $countries);
    $countries_sql = "(country_of_residence IN ($countries))";
  }

  $page = checkPost("page");
  $start_index = ($page - 1) * 20;
  $sort = checkPost("order_by");
  $order = checkPost("order_type");

  // Get reservations
  $customer_ids = [];
  $sql = "SELECT guest_ids FROM reservations_$lcode WHERE $dates_sql AND $channels_sql AND $rooms_sql";
  $rezultat = mysqli_query($konekcija, $sql);
  while($red = mysqli_fetch_assoc($rezultat)){
    $res_ids = explode(",", $red["guest_ids"]);
    for($i=0;$i<sizeof($res_ids);$i++){
      if($res_ids[$i] !== "")
        array_push($customer_ids, $res_ids[$i]);
    }
  }
  $customer_ids = array_unique($customer_ids);
  $customer_ids = implode(", ", $customer_ids);
  // Getting guests
  $guests = [];
  $sql = "SELECT * FROM guests_$lcode WHERE $min_price_sql AND $max_price_sql AND $min_nights_sql AND $max_nights_sql AND $countries_sql AND id IN ($customer_ids) ORDER BY $sort $order LIMIT $start_index, 20";
  $rezultat = mysqli_query($konekcija, $sql);
  while($red = mysqli_fetch_assoc($rezultat)){
    array_push($guests, fixGuest($red));
  }
  // Total pages
  $sql = "SELECT COUNT(*) AS total FROM guests_$lcode WHERE $min_price_sql AND $max_price_sql AND $min_nights_sql AND $max_nights_sql AND $countries_sql AND id IN ($customer_ids)";
  $rezultat = mysqli_query($konekcija, $sql);
  $red = mysqli_fetch_assoc($rezultat);
  $total = $red["total"];
  $total = ceil($total / 20);
  // Min / Max price
  $sql = "SELECT MIN(total_paid) AS min_price, MAX(total_paid) AS max_price FROM guests_$lcode";
  $rezultat = mysqli_query($konekcija, $sql);
  $red = mysqli_fetch_assoc($rezultat);
  $min_price = $red["min_price"];
  $max_price = $red["max_price"];
  if($min_price < 0){
    $min_price = 0;
  }
  // Min / Max nights
  $sql = "SELECT MIN(total_nights) AS min_nights, MAX(total_nights) AS max_nights FROM guests_$lcode";
  $rezultat = mysqli_query ($konekcija, $sql);
  $red = mysqli_fetch_assoc($rezultat);
  $min_nights = $red["min_nights"];
  $max_nights = $red["max_nights"];
  if($min_nights < 0){
     $min_nights = 0;
  }

  $ret_val["guests"] = $guests;
  $ret_val["min_price"] = $min_price;
  $ret_val["max_price"] = $max_price;
  $ret_val["min_nights"] = $min_nights;
  $ret_val["max_nights"] = $max_nights;
  $ret_val["page"] = $page;
  $ret_val["total_pages_number"] = $total;
}

if($action == "invoices")
{
  $dfrom = checkPost("dfrom");
  $dto = checkPost("dto");
  $type = json_decode(checkPost("type"));
  $status = json_decode(checkPost("status"));

  $dfrom_sql = $dfrom == "" ? 1 : "(created_date >= '$dfrom')";
  $dto_sql = $dto == "" ? 1 : "(created_date <= '$dto')";

  $type_sql = 1;
  if(sizeof($type)){
    $type = implode(",", $type);
    $type_sql = "(type IN ($type))";
  }

  $status_sql = 1;
  if(sizeof($status)){
    $status = implode(",", $status);
    $status_sql = "(status IN ($status))";
  }

  $page = checkPost("page");
  $start_index = ($page - 1) * 20;

  // Get invoices
  $invoices = [];
  $sql = "SELECT * FROM invoices_$lcode WHERE $dfrom_sql AND $dto_sql AND $type_sql AND $status_sql ORDER BY created_date DESC, created_time DESC LIMIT $start_index, 20";
  $rezultat = mysqli_query ($konekcija, $sql);
  while($red = mysqli_fetch_assoc($rezultat)){
    array_push($invoices, fixInvoice($red));
  }
  $sql = "SELECT COUNT(*) AS total FROM invoices_$lcode WHERE $dfrom_sql AND $dto_sql AND $type_sql AND $status_sql";
  $rezultat = mysqli_query ($konekcija, $sql);
  $red = mysqli_fetch_assoc($rezultat);
  $total = $red["total"];
  $total = ceil($total / 20);

  // Get next mark
  $invoice_year = date("Y");
  $sql = "SELECT MAX(invoice_number) AS max_num FROM invoices_$lcode WHERE invoice_year = $invoice_year";
  $rezultat = mysqli_query($konekcija, $sql);
  $red = mysqli_fetch_assoc($rezultat);
  $invoice_number = $red["max_num"];
  if($invoice_number == "") // Probably not needed
    $invoice_number = 0;
  $invoice_number += 1;
  $invoice_mark = "$invoice_number - $invoice_year";

  $ret_val["invoices"] = $invoices;
  $ret_val["page"] = $page;
  $ret_val["total_pages_number"] = $total;
  $ret_val["next_invoice_mark"] = $invoice_mark;
}

if($action == "users")
{
  // Get users
  $users = [];
  $sql = "SELECT id, status, email, username, client_name, properties, reservations, guests, invoices, prices, restrictions, avail, rooms, channels, statistics, changelog, articles, wspay FROM all_users WHERE (status = 2 OR status = 3) AND account = '$account'";
  $rezultat = mysqli_query($konekcija, $sql);
  while($red = mysqli_fetch_assoc($rezultat)){
    $red["properties"] = explode(",", $red["properties"]);
    array_push($users, $red);
  }
  $ret_val["users"] = $users;
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
    array_push($reservations, fixReservation($red, $channel_data, $konekcija, $lcode));
  }

  $sql = "SELECT * FROM guests_$lcode WHERE LOWER(name) LIKE '%$keyword%' OR LOWER(surname) LIKE '%$keyword%' OR CONCAT(LOWER(name), \" \", LOWER(surname)) LIKE '%$keyword%' OR LOWER(email) LIKE '%$keyword%'";
  $rezultat = mysqli_query ($konekcija, $sql);
  while($red = mysqli_fetch_assoc($rezultat)){
    array_push($guests, fixGuest($red));
  }

  $sql = "SELECT * FROM invoices_$lcode WHERE LOWER(name) LIKE '%$keyword%' OR id LIKE '%$keyword%' OR LOWER(email) LIKE '%$keyword%'";
  $rezultat = mysqli_query ($konekcija, $sql);
  while($red = mysqli_fetch_assoc($rezultat)){
    array_push($invoices, fixInvoice($red));
  }

  $ret_val["reservations"] = $reservations;
  $ret_val["guests"] = $guests;
  $ret_val["invoices"] = $invoices;
}

if($action == "freeRooms") // Used for free rooms of single room reservations
{
  $dfrom = checkPost("dfrom");
  $dto = checkPost("dto");
  $date_obj = date_create($dto); // Reduce the departure date by 1
  date_add($date_obj, date_interval_create_from_date_string("-1 day"));
  $dto = date_format($date_obj, "Y-m-d");
  // Add room data to map
  $rooms_map = [];
  $sql = "SELECT id, name FROM rooms_$lcode";
  $rezultat = mysqli_query($konekcija, $sql);
  while($red = mysqli_fetch_assoc($rezultat)){
    $rooms_map[$red["id"]] = $red;
  }
  // Get avail
  $avail = [];
  $avail = plansAvailValues($lcode, $dfrom, $dto, $konekcija);

  // Calc min and add room to array
  $rooms = [];
  foreach($avail as $room => $dates){
    // Gets min value
    $min = array_values($dates)[0];
    foreach($dates as $date => $value){
      if($value < $min){
        $min = $value;
      }
    }
    if($min > 0){
      $rooms_map[$room]["avail"] = $min;
      array_push($rooms, $rooms_map[$room]);
    }
  }
  $ret_val["rooms"] = $rooms;
}

if($action == "resRoomData") // Used for free room numbers and price of single room reservations
{
  $dfrom = checkPost("dfrom");
  $dto = checkPost("dto");
  $date_obj = date_create($dto); // Reduce the departure date by 1
  date_add($date_obj, date_interval_create_from_date_string("-1 day"));
  $dto = date_format($date_obj, "Y-m-d");
  $pid = checkPost("pid");
  if(checkPostExists("room")){ // Getting data for one room
    $room = checkPost("room");
    // Get price
    $prices = [];
    $prices = plansPriceValues($lcode, $dfrom, $dto, $pid, $konekcija);
    $prices = $prices[$room];
    // Calc avg
    $sum = 0;
    $count = 0;
    foreach($prices as $date => $value){
      $count += 1;
      $sum += $value;
    }
    $price = $sum / $count;
    $ret_val["price"] = $price;

    // Get free rooms
    $free_rooms = [];
    $room_names = [];

    $sql = "SELECT id, availability, room_numbers, parent_room FROM rooms_$lcode WHERE id = '$room'";
    $rezultat = mysqli_query($konekcija, $sql);
    $red = mysqli_fetch_assoc($rezultat);
    if($red["parent_room"] != "0"){ // Fetch parent room if original room is virtual
      $room = $red["parent_room"];
      $sql = "SELECT id, availability, room_numbers, parent_room FROM rooms_$lcode WHERE id = '$room'";
      $rezultat = mysqli_query($konekcija, $sql);
      $red = mysqli_fetch_assoc($rezultat);
    }
    $room = $red["id"];
    $room_names = explode(",", $red["room_numbers"]);
    for($i=0;$i<$red["availability"];$i++){
      array_push($free_rooms, 1); // Make all rooms free
    }

    $sql = "SELECT reservation_code, real_rooms, room_numbers FROM reservations_$lcode WHERE date_arrival < '$dto' AND date_departure > '$dfrom' AND status = 1 AND real_rooms LIKE '%$room%'";
    $rezultat = mysqli_query ($konekcija, $sql);
    while($red = mysqli_fetch_assoc($rezultat)){
      $res_rooms = explode(",", $red["real_rooms"]);
      $res_room_numbers = explode(",", $red["room_numbers"]);
      for($i=0;$i<sizeof($res_rooms);$i++)
      {
        if($res_rooms[$i] == $room){
          $free_rooms[$res_room_numbers[$i]] = 0;
        }
      }
    }
    $free_rooms_list = [];
    for($i=0;$i<sizeof($free_rooms);$i++){
      $room_struct = [];
      if($free_rooms[$i] == 1){
        $room_struct["id"] = $i;
        $room_struct["name"] = $room_names[$i];
        array_push($free_rooms_list, $room_struct);
      }
    }
    $ret_val["room_numbers"] = $free_rooms_list;
  }

}

if($action == "groupResRooms") // Used for free rooms and prices of group reservations
{
  $dfrom = checkPost("dfrom");
  $dto = checkPost("dto");
  $date_obj = date_create($dto); // Reduce the departure date by 1
  date_add($date_obj, date_interval_create_from_date_string("-1 day"));
  $dto = date_format($date_obj, "Y-m-d");
  $pid = checkPost("pid");
  // Add room data to map
  $rooms_map = [];
  $sql = "SELECT id, name FROM rooms_$lcode";
  $rezultat = mysqli_query($konekcija, $sql);
  while($red = mysqli_fetch_assoc($rezultat)){
    $rooms_map[$red["id"]] = $red;
  }
  // Get avail
  $avail = [];
  $avail = plansAvailValues($lcode, $dfrom, $dto, $konekcija);
  // Calc min and add room to array
  $rooms = [];
  foreach($avail as $room => $dates){
    // Gets min value
    $min = array_values($dates)[0];
    foreach($dates as $date => $value){
      if($value < $min){
        $min = $value;
      }
    }
    if($min > 0){
      $rooms_map[$room]["avail"] = $min;
      array_push($rooms, $rooms_map[$room]);
    }
  }

  $prices = plansPriceValues($lcode, $dfrom, $dto, $pid, $konekcija);
  for($i=0;$i<sizeof($rooms);$i++){
    $room = $rooms[$i]["id"];
    $room_prices = $prices[$room];
    // Calc avg
    $sum = 0;
    $count = 0;
    foreach($room_prices as $date => $value){
      $count += 1;
      $sum += $value;
    }
    $price = $sum / $count;
    $rooms[$i]["price"] = $price;


    // Get free rooms
    $free_rooms = [];
    $room_names = [];

    $sql = "SELECT id, availability, room_numbers, parent_room FROM rooms_$lcode WHERE id = '$room'";
    $rezultat = mysqli_query($konekcija, $sql);
    $red = mysqli_fetch_assoc($rezultat);
    if($red["parent_room"] != "0"){ // Fetch parent room if original room is virtual
      $room = $red["parent_room"];
      $sql = "SELECT id, availability, room_numbers, parent_room FROM rooms_$lcode WHERE id = '$room'";
      $rezultat = mysqli_query($konekcija, $sql);
      $red = mysqli_fetch_assoc($rezultat);
    }
    $room = $red["id"];
    $room_names = explode(",", $red["room_numbers"]);
    for($j=0;$j<$red["availability"];$j++){
      array_push($free_rooms, 1); // Make all rooms free
    }

    $sql = "SELECT real_rooms, room_numbers FROM reservations_$lcode WHERE date_arrival < '$dto' AND date_departure > '$dfrom' AND status = 1 AND real_rooms LIKE '%$room%'";
    $rezultat = mysqli_query ($konekcija, $sql);
    while($red = mysqli_fetch_assoc($rezultat)){
      $res_rooms = explode(",", $red["real_rooms"]);
      $res_room_numbers = explode(",", $red["room_numbers"]);
      for($j=0;$j<sizeof($res_rooms);$j++)
      {
        if($res_rooms[$j] == $room)
          $free_rooms[$res_room_numbers[$j]] = 0;
      }
    }
    $free_rooms_list = [];
    for($j=0;$j<sizeof($free_rooms);$j++){
      $room_struct = [];
      if($free_rooms[$j] == 1){
        $room_struct["id"] = $j;
        $room_struct["name"] = $room_names[$j];
        array_push($free_rooms_list, $room_struct);
      }
    }
    $rooms[$i]["room_numbers"] = $free_rooms_list;
  }

  $ret_val["rooms"] = $rooms;
}

if($action == "changelog")
{
  $dfrom = checkPost("dfrom");
  $dto = checkPost("dto");
  $actions = json_decode(checkPost("actions"));
  $data_types = json_decode(checkPost("data_types"));
  $dfrom_sql = "1";
  if($dfrom != "")
    $dfrom_sql = "(created_time >= '$dfrom')";

  $dto_sql = "1";
  if($dto != "")
    $dto_sql = "(created_time <= '$dto')";

  $actions_sql = "1";
  if(sizeof($actions)){
    $actions = implode("','", $actions);
    $actions_sql = "(action IN ('$actions'))";
  }

  $data_types_sql = "1";
  if(sizeof($data_types)){
    $data_types = implode("','", $data_types);
    $data_types_sql = "(data_type IN ('$data_types'))";
  }

  $page = checkPost("page");
  $start_index = ($page - 1) * 20;

  // Get changelog
  $changelog = [];
  $sql = "SELECT * FROM changelog_$lcode WHERE $dfrom_sql AND $dto_sql AND $actions_sql AND $data_types_sql ORDER BY created_time DESC LIMIT $start_index, 20";

  $rezultat = mysqli_query ($konekcija, $sql);
  while($red = mysqli_fetch_assoc($rezultat)){
    array_push($changelog, fixChange($red));
  }
  $sql = "SELECT COUNT(*)AS total FROM changelog_$lcode WHERE $dfrom_sql AND $dto_sql AND $actions_sql AND $data_types_sql";
  $rezultat = mysqli_query ($konekcija, $sql);
  $red = mysqli_fetch_assoc($rezultat);
  $total = $red["total"];
  $total = ceil($total / 20);

  // Saving only differences in old/new data

  for($i=0;$i<sizeof($changelog);$i++){

    // Adding name of change
    $name_data = (array)$changelog[$i]["new_data"];
    if($changelog[$i]["action"] == "delete")
      $name_data = (array)$changelog[$i]["old_data"];
    // Bug fix
    if(sizeof($name_data) == 0)
      continue;
    if($changelog[$i]["data_type"] ==="reservation"){
        $name = $name_data["reservation_code"];
    }
    else if($changelog[$i]["data_type"] === "guest"){
        $name = $name_data["name"] . " " . $name_data["surname"];
    }
    else if($changelog[$i]["data_type"] === "invoice"){
        $name = $name_data["mark"];
    }
    else if($changelog[$i]["data_type"] === "promocode"){
        $name = $name_data["name"];
    }
    else if($changelog[$i]["data_type"] === "policy"){
        $name = $name_data["name"];
    }
    else if($changelog[$i]["data_type"] === "category"){
        $name = $name_data["name"];
    }
    else if($changelog[$i]["data_type"] === "article"){
        $name = $name_data["description"];
    }
    else if($changelog[$i]["data_type"] === "pricingPlan"){
        $name = $name_data["name"];
    }
    else if($changelog[$i]["data_type"] === "restrictionPlan"){
        $name = $name_data["name"];
    }
    else if($changelog[$i]["data_type"] === "room"){
        $name = $name_data["shortname"];
    }
    else if($changelog[$i]["data_type"] === "extra"){
        $name = $name_data["name"];
    }
    else if($changelog[$i]["data_type"] === "channel"){
        $name = $name_data["name"];
    }
    else if($changelog[$i]["data_type"] === "user"){
        $name = $name_data["client_name"];
    }
    else if($changelog[$i]["data_type"] === "avail"){
        $name = "";
    }
    else if($changelog[$i]["data_type"] === "price"){
        $name = $name_data[0]->id;
        $sql = "SELECT name FROM prices_$lcode WHERE id = '$name'";
        $rezultat = mysqli_query($konekcija, $sql);
        $red = mysqli_fetch_assoc($rezultat);
        $name = $red["name"];
    }
    else if($changelog[$i]["data_type"] === "restriction"){
        $name = $name_data[0]->id;
        $sql = "SELECT name FROM restrictions_$lcode WHERE id = '$name'";
        $rezultat = mysqli_query($konekcija, $sql);
        $red = mysqli_fetch_assoc($rezultat);
        $name = $red["name"];
    }
    else { // Yield variations?
      $name = "";
    }
    $changelog[$i]["name"] = $name;

    // Showing only changed data for edits
    if($changelog[$i]["action"] == "edit"){

      if($changelog[$i]["data_type"] == "avail" || $changelog[$i]["data_type"] == "price" || $changelog[$i]["data_type"] == "restriction"){ // These items are arrays
        $len = sizeof($changelog[$i]["old_data"]);
        $old = [];
        $new = [];
        for($j=0;$j<$len;$j++){
          $old_one_date = (array)$changelog[$i]["old_data"][$j];
          $new_one_date = (array)$changelog[$i]["new_data"][$j];
          // Implementing array_diff_assoc myself since all values have to be strings anyway
          foreach($old_one_date as $key => $value){ // $old and $new will always have same keys anyway
            if($key == "id" || $key == "avail_date" || $key == "price_date" || $key == "restriction_date")
              continue;
            $old_value = $value;
            $new_value = $new_one_date[$key];
            if(is_array($old_value) || is_object($old_value)){
              $old_value = json_encode($old_value);
              $new_value = json_encode($new_value);
            }
            // Removing equal values
            if($old_value == $new_value){
              unset($old_one_date[$key]);
              unset($new_one_date[$key]);
            }
          }
          if(sizeof($old_one_date) || sizeof($new_one_date)){
            array_push($old, $old_one_date);
            array_push($new, $new_one_date);
          }
        }
      }
      else { // Normal data
        $old = (array)$changelog[$i]["old_data"];
        $new = (array)$changelog[$i]["new_data"];
        // Implementing array_diff_assoc myself since all values have to be strings anyway
        foreach($old as $key => $value){ // $old and $new will always have same keys anyway
          $old_value = $value;
          $new_value = $new[$key];
          if(is_array($old_value) || is_object($old_value)){
            $old_value = json_encode($old_value);
            $new_value = json_encode($new_value);
          }
          // Removing equal values
          if($old_value == $new_value){
            unset($old[$key]);
            unset($new[$key]);
          }
        }
        // Return objects instead of JSON for values
        foreach($old as $key => $value){
          if(is_string($value)){
            $test_val = json_decode($value);
            if(is_array($test_val) || is_object($test_val))
              $old[$key] = $test_val;
          }
        }
        foreach($new as $key => $value){
          if(is_string($value)){
            $test_val = json_decode($value);
            if(is_array($test_val) || is_object($test_val))
              $new[$key] = $test_val;
          }
        }
      }
      $changelog[$i]["old_data"] = $old;
      $changelog[$i]["new_data"] = $new;
    }
  }


  $ret_val["changelog"] = $changelog;
  $ret_val["page"] = $page;
  $ret_val["total_pages_number"] = $total;

}

if($action == "guestReservations")
{
  $id = checkPost("id");

  $reservations = [];
  $sql = "SELECT * FROM reservations_$lcode WHERE guest_ids = '$id' OR guest_ids LIKE '%,$id,%' OR guest_ids LIKE '%,$id' OR guest_ids LIKE '$id,%'";
  $rezultat = mysqli_query($konekcija, $sql);
  while($red = mysqli_fetch_assoc($rezultat)){
    array_push($reservations, fixReservation($red, $channel_data, $konekcija, $lcode));
  }
  $ret_val["reservations"] = $reservations;
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

if($action == "categories")
{
  $categories = [];
  $sql = "SELECT * FROM categories_$lcode";
  $rezultat = mysqli_query($konekcija, $sql);
  while($red = mysqli_fetch_assoc($rezultat)){
    $red = $red;
    array_push($categories, $red);
  }
  $ret_val["categories"] = $categories;
}

if($action == "articles")
{
  $articles = [];
  $category_id = checkPost("category_id");
  $sql = "SELECT * FROM articles_$lcode WHERE category_id = $category_id";
  $rezultat = mysqli_query($konekcija, $sql);
  while($red = mysqli_fetch_assoc($rezultat)){
    $red = $red;
    array_push($articles, $red);
  }
  $ret_val["articles"] = $articles;
}

if($action == "allArticles")
{
  $all_articles = [];
  // Get articles
  $sql = "SELECT *
          FROM articles_$lcode
          ";
  $rezultat = mysqli_query ($konekcija, $sql);
  while($red = mysqli_fetch_assoc($rezultat)){
    $red = $red;
    array_push($all_articles, $red);
  }
  $ret_val["all_articles"] = $all_articles;
}

// Reports need to be checked

if($action == "dailyReport")
{
  $number = $_POST['number'];
  $room = $_POST['room'];
  $room_number = $_POST['room_number'];
  $guest = $_POST['guest'];
  $adults = $_POST['adults'];
  $children = $_POST['children'];
  $arrival = $_POST['arrival'];
  $departure = $_POST['departure'];
  $note = $_POST['note'];
  $price_per_night = $_POST['price_per_night'];
  $total_price = $_POST['total_price'];
  $channel = $_POST['channel'];

  $today = date("Y-m-d");


  // Fetch rooms
  $room_names = [];
  $all_room_numbers = [];
  $sql = "SELECT id, name, room_numbers FROM rooms_$lcode";
  $rezultat = mysqli_query($konekcija, $sql);
  while($red = mysqli_fetch_assoc($rezultat))
  {
    $room_names[$red["id"]] = $red["name"];
    $all_room_numbers[$red["id"]] = explode(",", $red["room_numbers"]);
  }

  // Getting channel names
  $sql = "SELECT name, id FROM channels_$lcode";
  $rezultat = mysqli_query($konekcija, $sql);
  $channels = [];
  while($red = mysqli_fetch_assoc($rezultat))
  {
    $channels[$red['id']] = $red['name'];
  }

  // Fetch reservations
  $reservations = [];
  $sql = "SELECT * FROM reservations_$lcode WHERE date_arrival <= '$today' AND date_departure >= '$today' AND status = 1";
  $rezultat = mysqli_query($konekcija, $sql);
  while($red = mysqli_fetch_assoc($rezultat))
  {
    $res_rooms = explode(",", $red["rooms"]);
    array_push($reservations, fixReservation($red, $channel_data, $konekcija, $lcode));
  }

  // Add items to list
  $list = [];

  // Append all rows
    $row = 0; // Starting row
    $num = 1;
    for($i=0;$i<sizeof($reservations);$i++)
    {
      $res = $reservations[$i];
      $rooms = $res['rooms'];
      $room_numbers = $res["room_numbers"];
      $room_count = sizeof($rooms);
      for($j=0;$j<$room_count;$j++){ // For each separate room
        $list_item = [];

        if($number)
        {
          $list_item["number"] = $num;
          $num += 1;
        }
        if($room)
        {
          $list_item["room"] = $room_names[$rooms[$j]];
        }
        if($room_number)
        {
          $list_item["room_number"] = $all_room_numbers[$rooms[$j]][$room_numbers[$j]];
        }
        if($guest)
        {
          $list_item["guest"] = $res["customer_name"] . " " . $res["customer_surname"];
        }
        if($adults)
        {
          $list_item["men"] = $res["men"];
        }
        if($children)
        {
          $list_item["children"] = $res["children"];
        }
        if($arrival)
        {
          $list_item["arrival"] = ymdToDmy($res["date_arrival"]);
        }
        if($departure)
        {
          $list_item["departure"] = ymdToDmy($res["date_departure"]);
        }
        if($note)
        {
          $list_item["note"] = $res["customer_notes"];
        }
        if($price_per_night)
        {
          $cur_room = $rooms[$j];
          $avg_price = array_sum($res["dayprices"]->$cur_room) / $res["nights"];
          $list_item["price_per_night"] = $avg_price . " EUR";
        }
        if($total_price)
        {
          $list_item["total_price"] = $res["total_price"] . " EUR";
        }
        if($channel)
        {
          if($res["id_woodoo"] == "")
            $res_channel = "Direktna rezervacija";
          else
            $res_channel = $channels[$res["id_woodoo"]];
          $list_item["channel"] = $res_channel;
        }
        array_push($list, $list_item);
      }
    }
    $ret_val["data"] = $list;


}

if($action == "occupancyReport")
{
    // Get data
    $date = checkPost('date');
    $room_ids = checkPost('rooms');
    $cmp = checkPost('cmp');

    // Fetch rooms
    $rooms = [];
    $rooms_cmp = [];
    $sql = "SELECT id, name, price, availability FROM rooms_$lcode WHERE id IN($room_ids)";
    $rezultat = mysqli_query($konekcija, $sql);
    while($red = mysqli_fetch_assoc($rezultat)){
        $rooms[$red["id"]] = $red;
        $rooms[$red["id"]]["count"] = 0;
        $rooms[$red["id"]]["total_price"] = 0;
        $rooms[$red["id"]]["avg_price"] = 0;
        $rooms[$red["id"]]["occupancy"] = 0;
        $rooms_cmp[$red["id"]] = $red;
        $rooms_cmp[$red["id"]]["count"] = 0;
        $rooms_cmp[$red["id"]]["total_price"] = 0;
        $rooms_cmp[$red["id"]]["avg_price"] = 0;
        $rooms_cmp[$red["id"]]["occupancy"] = 0;

    }


    // Fetch reservations
    $sql = "SELECT rooms, nights, date_arrival FROM reservations_$lcode WHERE date_arrival <= '$date' AND date_departure > '$date' AND status = 1";
    $rezultat = mysqli_query($konekcija, $sql);
    while($red = mysqli_fetch_assoc($rezultat)){
      $red = fixReservation($red, $channel_data, $konekcija, $lcode);
      $res_rooms = $red["room_data"];
      for($i=0;$i<sizeof($res_rooms);$i++) // Individual rooms
      {
        $res_room = $res_rooms[$i];
        $rooms[$res_room->id]["count"] += $res_room->count;
        $rooms[$res_room->id]["total_price"] += ($res_room->count * $res_room->price);
      }
    }


    // Fetch avails
    if(cmpDates($date, date("Y-m-d")) < 0)
    {
      foreach($rooms as $key => $value)
      {
        $rooms[$key]["avail"] = "-";
      }
    }
    else {
      $avail = plansAvailValues($lcode, $date, $date, $konekcija);
      foreach($rooms as $key => $value)
      {
        $rooms[$key]["avail"] = $avail[$key][$date];
      }
    }

    if($cmp != $date) // Fetch cmp values if needed
    {
      // Fetch reservations
      $sql = "SELECT rooms, nights, date_arrival FROM reservations_$lcode WHERE date_arrival <= '$cmp' AND date_departure > '$cmp' AND status = 1";
      $rezultat = mysqli_query($konekcija, $sql);
      while($red = mysqli_fetch_assoc($rezultat))
      {
        $red = fixReservation($red, $channel_data, $konekcija, $lcode);
        $res_rooms = $red["rooms"];
        for($i=0;$i<sizeof($res_rooms);$i++) // Individual rooms
        {
          $rooms_cmp[$res_rooms[$i]]["count"] += 1;
          if($red["dayprices"] != "") // Get day number and add price
          {
            $dayprices = json_decode($red["dayprices"]);
            $day = dateDiff($red["date_arrival"], $cmp);
            $rooms_cmp[$res_rooms[$i]]["total_price"] += $dayprices[$res_rooms[$day]];
          }
          else { // If prices aren't set add the regular room price
            $rooms_cmp[$res_rooms[$i]]["total_price"] += $rooms_cmp[$res_rooms[$i]]["price"];
          }
        }
      }

      // Fetch avails
      if(cmpDates($cmp, date("Y-m-d")) < 0)
      {
        foreach($rooms_cmp as $key => $value)
        {
          $rooms_cmp[$key]["avail"] = "-";
        }
      }
      else {
        $avail_cmp = plansAvailValues($lcode, $cmp, $cmp, $konekcija);
        foreach($rooms_cmp as $key => $value)
        {
          $rooms_cmp[$key]["avail"] = $avail_cmp[$key][$cmp];
        }
      }
    }

    $list = [];


    $room_ids = explode(",", $room_ids);
    if($date == $cmp) // Table for one day
    {
      // Init and set first rows
      $rooms["total"]["name"] = "Ukupno";
      $rooms["total"]["count"] = 0;
      $rooms["total"]["total_price"] = 0;
      $rooms["total"]["avg_price"] = 0;
      $rooms["total"]["occupancy"] = 0;


      // Append all rows
      foreach ($rooms as $room_id => $room_data)
      {
        if($room_id == "total")
          continue;
        $list_item = [];
        // Fixing Values
        if($room_data["count"] > 0)
        {
          $room_data["avg_price"] = round($room_data["total_price"] / $room_data["count"], 2);
          $room_data["occupancy"] = round($room_data["count"] / $room_data["availability"] * 100, 2);
        }
        else
        {
          $room_data["avg_price"] = 0;
          $room_data["occupancy"] = 0;
        }
        $room_data["total_price"] = round($room_data["total_price"], 2);
        // Add to Total
        $rooms["total"]["total_price"] += $room_data["total_price"];
        $rooms["total"]["count"] += $room_data["count"];
        $rooms["total"]["availability"] += $room_data["availability"];
        $rooms["total"]["avail"] += $room_data["avail"];
        // Write to spreadsheet
        $list_item["name"] = ($room_data["name"]);
        $list_item["availability"] = ($room_data["availability"]);
        $list_item["count"] = ($room_data["count"]);
        $list_item["avail"] = ($room_data["avail"]);
        $list_item["occupancy"] = ($room_data["occupancy"] . "%");
        $list_item["avg_price"] = ($room_data["avg_price"] . " EUR");
        $list_item["total_price"] = ($room_data["total_price"] . " EUR");

        array_push($list, $list_item);
      }


      // Fixing Total Values
      if($rooms["total"]["count"] > 0)
      {
        $rooms["total"]["avg_price"] = round($rooms["total"]["total_price"] / $rooms["total"]["count"], 2);
        $rooms["total"]["occupancy"] = round($rooms["total"]["count"] / $rooms["total"]["availability"] * 100, 2);
      }
      else
      {
        $rooms["total"]["avg_price"] = 0;
        $rooms["total"]["occupancy"] = 0;
      }

      $list_item = [];

      $rooms["total"]["total_price"] = round($rooms["total"]["total_price"], 2);
      if(cmpDates($date, date("Y-m-d")) < 0)
        $rooms["total"]["avail"] = "-";


      // Write to spreadsheet
      $row = sizeof($room_ids) + 3;
      $list_item["name"] = ($rooms["total"]["name"]);
      $list_item["availability"] = ($rooms["total"]["availability"]);
      $list_item["count"] = ($rooms["total"]["count"]);
      $list_item["avail"] = ($rooms["total"]["avail"]);
      $list_item["occupancy"] = ($rooms["total"]["occupancy"] . "%");
      $list_item["avg_price"] = ($rooms["total"]["avg_price"] . " EUR");
      $list_item["total_price"] = ($rooms["total"]["total_price"] . " EUR");
      array_push($list, $list_item);
    }
    else { // Table for 2 days

      $rooms["total"]["name"] = "Ukupno";
      $rooms["total"]["count"] = 0;
      $rooms["total"]["total_price"] = 0;
      $rooms["total"]["avg_price"] = 0;
      $rooms["total"]["occupancy"] = 0;
      $rooms_cmp["total"]["name"] = "Ukupno";
      $rooms_cmp["total"]["count"] = 0;
      $rooms_cmp["total"]["total_price"] = 0;
      $rooms_cmp["total"]["avg_price"] = 0;
      $rooms_cmp["total"]["occupancy"] = 0;

      // Append all rows
      foreach ($rooms as $room_id => $room_data)
      {
        if($room_id == "total")
          continue;
        $list_item = [];
        // Fixing Values
        if($room_data["count"] > 0)
        {
          $room_data["avg_price"] = round($room_data["total_price"] / $room_data["count"], 2);
          $room_data["occupancy"] = round($room_data["count"] / $room_data["availability"] * 100, 2);
        }
        else
        {
          $room_data["avg_price"] = 0;
          $room_data["occupancy"] = 0;
        }
        $room_data["total_price"] = round($room_data["total_price"], 2);
        // Add to Total
        $rooms["total"]["total_price"] += $room_data["total_price"];
        $rooms["total"]["count"] += $room_data["count"];
        $rooms["total"]["availability"] += $room_data["availability"];
        $rooms["total"]["avail"] += $room_data["avail"];
        // Write to spreadsheet
        $row = $i + 4;
        $list_item["name"] = ($room_data["name"]);
        $list_item["availability"] = ($room_data["availability"]);
        $list_item["count"] = ($room_data["count"]);
        $list_item["avail"] = ($room_data["avail"]);
        $list_item["occupancy"] = ($room_data["occupancy"] . "%");
        $list_item["avg_price"] = ($room_data["avg_price"] . " EUR");
        $list_item["total_price"] = ($room_data["total_price"] . " EUR");

        // Cmp values

        // Fixing Values
        if($rooms_cmp[$room_id]["count"] > 0)
        {
          $rooms_cmp[$room_id]["avg_price"] = round($rooms_cmp[$room_id]["total_price"] / $rooms_cmp[$room_id]["count"], 2);
          $rooms_cmp[$room_id]["occupancy"] = round($rooms_cmp[$room_id]["count"] / $rooms_cmp[$room_id]["availability"] * 100, 2);
        }
        else
        {
          $rooms_cmp[$room_id]["avg_price"] = 0;
          $rooms_cmp[$room_id]["occupancy"] = 0;
        }
        $rooms_cmp[$room_id]["total_price"] = round($rooms_cmp[$room_id]["total_price"], 2);
        // Add to Total
        $rooms_cmp["total"]["total_price"] += $rooms_cmp[$room_id]["total_price"];
        $rooms_cmp["total"]["count"] += $rooms_cmp[$room_id]["count"];
        $rooms_cmp["total"]["availability"] += $rooms_cmp[$room_id]["availability"];
        $rooms_cmp["total"]["avail"] += $rooms_cmp[$room_id]["avail"];
        // Write to spreadsheet
        $row = $i + 4;
        $list_item["availability_cmp"] = ($rooms_cmp[$room_id]["availability"]);
        $list_item["count_cmp"] = ($rooms_cmp[$room_id]["count"]);
        $list_item["avail_cmp"] = ($rooms_cmp[$room_id]["avail"]);
        $list_item["occupancy_cmp"] = ($rooms_cmp[$room_id]["occupancy"] . "%");
        $list_item["avg_price_cmp"] = ($rooms_cmp[$room_id]["avg_price"] . " EUR");
        $list_item["total_price_cmp"] = ($rooms_cmp[$room_id]["total_price"] . " EUR");

        array_push($list, $list_item);
      }

      $list_item = [];

      // Fixing Total Values
      if($rooms["total"]["count"] > 0)
      {
        $rooms["total"]["avg_price"] = round($rooms["total"]["total_price"] / $rooms["total"]["count"], 2);
        $rooms["total"]["occupancy"] = round($rooms["total"]["count"] / $rooms["total"]["availability"] * 100, 2);
      }
      else
      {
        $rooms["total"]["avg_price"] = 0;
        $rooms["total"]["occupancy"] = 0;
      }
      $rooms["total"]["total_price"] = round($rooms["total"]["total_price"], 2);
      if(cmpDates($date, date("Y-m-d")) < 0)
        $rooms["total"]["avail"] = "-";

      // Write to spreadsheet
      $row = sizeof($room_ids) + 4;
      $list_item["name"] = ($rooms["total"]["name"]);
      $list_item["availability"] = ($rooms["total"]["availability"]);
      $list_item["count"] = ($rooms["total"]["count"]);
      $list_item["avail"] = ($rooms["total"]["avail"]);
      $list_item["occupancy"] = ($rooms["total"]["occupancy"] . "%");
      $list_item["avg_price"] = ($rooms["total"]["avg_price"] . " EUR");
      $list_item["total_price"] = ($rooms["total"]["total_price"] . " EUR");

      // Cmp
      // Fixing Total Values
      if($rooms_cmp["total"]["count"] > 0)
      {
        $rooms_cmp["total"]["avg_price"] = round($rooms_cmp["total"]["total_price"] / $rooms_cmp["total"]["count"], 2);
        $rooms_cmp["total"]["occupancy"] = round($rooms_cmp["total"]["count"] / $rooms_cmp["total"]["availability"] * 100, 2);
      }
      else
      {
        $rooms_cmp["total"]["avg_price"] = 0;
        $rooms_cmp["total"]["occupancy"] = 0;
      }
      $rooms_cmp["total"]["total_price"] = round($rooms_cmp["total"]["total_price"], 2);
      if(cmpDates($cmp, date("Y-m-d")) < 0)
        $rooms_cmp["total"]["avail"] = "-";

      // Write to spreadsheet
      $row = sizeof($room_ids) + 4;
      $list_item["availability_cmp"] = ($rooms_cmp["total"]["availability"]);
      $list_item["count_cmp"] = ($rooms_cmp["total"]["count"]);
      $list_item["avail_cmp"] = ($rooms_cmp["total"]["avail"]);
      $list_item["occupancy_cmp"] = ($rooms_cmp["total"]["occupancy"] . "%");
      $list_item["avg_price_cmp"] = ($rooms_cmp["total"]["avg_price"] . " EUR");
      $list_item["total_price_cmp"] = ($rooms_cmp["total"]["total_price"] . " EUR");

      array_push($list, $list_item);

    }

    $ret_val["data"] = $list;
}

if($action == "housekeepingReport")
{
  $reservation_status = json_decode(checkPost("reservation_status")); // arrival/departure/stay/free
  $rooms = json_decode(checkPost("rooms")); // List of rooms to search
  $room_status = json_decode(checkPost("room_status")); // clean/inspected/dirty
  $guest_status = json_decode(checkPost("guest_status")); // waiting_arrival/arrived/arrived_and_paid/left
  $today = date("Y-m-d");
  $rooms_map = [];

  // Select all rooms
  $rooms_map = [];
  $sql = "SELECT id, name, shortname, room_numbers, status FROM rooms_$lcode WHERE parent_room = '0'";
  $rezultat = mysqli_query($konekcija, $sql);
  while($red = mysqli_fetch_assoc($rezultat)){
    $rooms_map[$red["id"]] = [];
    $rooms_map[$red["id"]]["room_numbers"] = explode(",", $red["room_numbers"]);
    $rooms_map[$red["id"]]["status"] = explode(",", $red["status"]);
    $rooms_map[$red["id"]]["name"] = $red["name"];
    $rooms_map[$red["id"]]["shortname"] = $red["shortname"];
    $rooms_map[$red["id"]]["reservation_status"] = [];
    $rooms_map[$red["id"]]["next_checkin"] = [];
    $rooms_map[$red["id"]]["next_checkout"] = [];
    $rooms_map[$red["id"]]["guest_status"] = [];
  }


  // Get current reservations
  $sql = "SELECT * FROM reservations_$lcode WHERE date_arrival <= '$today' AND date_departure >= '$today' AND status = 1";
  $rezultat = mysqli_query($konekcija, $sql);
  while($red = mysqli_fetch_assoc($rezultat)){
    $room_numbers = explode(",", $red["room_numbers"]);
    $real_rooms = explode(",", $red["real_rooms"]);
    for($i=0;$i<sizeof($real_rooms);$i++){ // List through rooms
      $room = $real_rooms[$i];
      $room_number = $room_numbers[$i];
      $rooms_map[$room]["next_checkout"][$room_number] = $red["date_departure"];
      if($red["date_arrival"] == $today){
        $rooms_map[$room]["reservation_status"][$room_number] = "arrival";
      }
      else if($red["date_departure"] == $today){
        $rooms_map[$room]["reservation_status"][$room_number] = "departure";
      }
      else {
        $rooms_map[$room]["reservation_status"][$room_number] = "stay";
      }
      $rooms_map[$room]["guest_status"][$room_number] = $red["guest_status"];
    }
  }

  $rooms_list = [];
  foreach($rooms_map as $room_id => $values){
    if(sizeof($rooms) && !(in_array($room_id, $rooms))){
      continue;
    }
    for($i=0;$i<sizeof($values["room_numbers"]);$i++){
      $single_room = [];
      $single_room["name"] = $values["room_numbers"][$i] . " " . "(" . $values["shortname"] . ")";
      $single_room["id"] = $room_id;
      $single_room["status"] = $values["status"][$i];
      if(isset($values["reservation_status"][$i]))
        $single_room["reservation_status"] = $values["reservation_status"][$i];
      else
        $single_room["reservation_status"] = "free";
      if(isset($values["guest_status"][$i]))
        $single_room["guest_status"] = $values["guest_status"][$i];
      else
        $single_room["guest_status"] = "";
      if(isset($values["next_checkout"][$i]))
        $single_room["next_checkout"] = $values["next_checkout"][$i];
      else
        $single_room["next_checkout"] = "";
      if(isset($values["next_checkin"][$i]))
        $single_room["next_checkin"] = $values["next_checkin"][$i];
      else
        $single_room["next_checkin"] = "";
      array_push($rooms_list, $single_room);
    }
  }
  $filtered_rooms_list = [];
  for($i=0;$i<sizeof($rooms_list);$i++){
    if(sizeof($room_status) && !(in_array($rooms_list[$i]["status"], $room_status))){
      continue;
    }
    if(sizeof($reservation_status) && !(in_array($rooms_list[$i]["reservation_status"], $reservation_status))){
      continue;
    }
    if(sizeof($guest_status) && !(in_array($rooms_list[$i]["guest_status"], $guest_status))){
      continue;
    }
    array_push($filtered_rooms_list, $rooms_list[$i]);
  }

  $ret_val["data"] = $filtered_rooms_list;
}

if($action == "yieldVariations")
{
  $variations = [];
  $sql = "SELECT * FROM yield_variations_$lcode";
  $rezultat = mysqli_query($konekcija, $sql);
  while($red = mysqli_fetch_assoc($rezultat)){
    array_push($variations, $red);
  }
  $ret_val["variations"] = $variations;
}

if($action == "yieldPrices")
{
  $prices = [];
    $dfrom = checkPost("dfrom");
    $dto = checkPost("dto");
    $id = checkPost("plan_id");
    $room_id = checkPost("room_id");
    $prices = plansPriceValues($lcode, $dfrom, $dto, $id, $konekcija);

  foreach($prices as $room => $values){
    if($room != $room_id)
      continue;
    $sql = "SELECT price FROM rooms_$lcode WHERE id = '$room'";
    $rezultat = mysqli_query($konekcija, $sql);
    $red = mysqli_fetch_assoc($rezultat);
    $price = $red["price"];
    foreach($values as $date => $value){
      $fixed = $value - $price;
      $percent = (($value * 100) / $price) - 100;
      $val_struct = [];
      $val_struct["price"] = $value;
      $val_struct["fixed_variation"] = $fixed;
      $val_struct["percent_variation"] = round($percent, 2);
      $prices[$room][$date] = $val_struct;
    }
  }
  $ret_val["prices"] = $prices[$room_id];
}

if($action == "occupiedRooms")
{
  $rooms_map = [];
  $room_numbers_map = [];
  $rooms = [];

  $sql = "SELECT * FROM rooms_$lcode WHERE parent_room = '0'";
  $rezultat = mysqli_query($konekcija, $sql);
  while($red = mysqli_fetch_assoc($rezultat)){
    $red["room_numbers"] = explode(",", $red["room_numbers"]);
    $rooms_map[$red["id"]] = $red;
  }
  $today = date("Y-m-d");
  $sql = "SELECT * FROM reservations_$lcode WHERE date_arrival <= '$today' AND date_departure > '$today' AND status = 1 ORDER BY date_arrival ASC, date_departure DESC";
  $rezultat = mysqli_query($konekcija, $sql);
  while($red = mysqli_fetch_assoc($rezultat)){
    $res_rooms = explode(",", $red["real_rooms"]);
    $res_room_numbers = explode(",", $red["room_numbers"]);
    for($i=0;$i<sizeof($res_rooms);$i++){
      $id = $res_rooms[$i] . "_" . $res_room_numbers[$i];
      $rooms_numbers_map[$id] = [];
      $rooms_numbers_map[$id]["room_id"] = $id;
      $rooms_numbers_map[$id]["reservation_id"] = $red["reservation_code"];
      $rooms_numbers_map[$id]["name"] =  $rooms_map[$res_rooms[$i]]["room_numbers"][$res_room_numbers[$i]] . " (" . $rooms_map[$res_rooms[$i]]["shortname"] . ")";
      $rooms_numbers_map[$id]["guest"] = $red["customer_name"] . " " . $red["customer_surname"];
    }
  }
  foreach($rooms_numbers_map as $key => $value){
    array_push($rooms, $value);
  }
  $ret_val["rooms"] = $rooms;
}

// Return
echo json_encode($ret_val);
$konekcija->close();


?>
