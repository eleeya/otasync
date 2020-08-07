<?php

/* INIT VALUES FOR NEW USER */


/* Called on initiation of new property. Call twice, for both years of data, also inserts default restrictions */
function plansInsertWubook($lcode, $account, $dfrom, $dto, $konekcija)
{
  $userToken = makeRequest("acquire_token", array($account, "davincijevkod966", "753fa793e9adb95321b061f05e29a78327645c05e097e376"));
  // Wubook start & end date
  $time = strtotime($dfrom);
  $iso_dfrom = $dfrom;
  $iso_dto = $dto;
  $dfrom = ymdToDmy($dfrom);
  $dto = ymdToDmy($dto);
  // Get rooms
  $real_rooms = [];
  $sql = "SELECT id FROM rooms_$lcode WHERE parent_room = '0'"; // Only get real rooms for avail
  $rezultat = mysqli_query($konekcija, $sql);
  while($red = mysqli_fetch_assoc($rezultat)){
    array_push($real_rooms, $red["id"]);
  }
  $real_rooms_sql = "room_" . implode(", room_", $real_rooms); // Order of rooms to insert
  $rooms = [];
  $sql = "SELECT id FROM rooms_$lcode"; // Get all rooms for default restrictions and prices
  $rezultat = mysqli_query($konekcija, $sql);
  while($red = mysqli_fetch_assoc($rezultat)){
    array_push($rooms, $red["id"]);
  }
  $rooms_sql = "room_" . implode(", room_", $rooms); // Order of rooms to insert
  $restrictions_rooms = [];
  $sql = "SELECT id FROM rooms_$lcode"; // Get all rooms and set fields for restrictions
  $rezultat = mysqli_query($konekcija, $sql);
  while($red = mysqli_fetch_assoc($rezultat)){
    // Order is important
    array_push($restrictions_rooms, "min_stay_" . $red["id"]);
    array_push($restrictions_rooms, "min_stay_arrival_" . $red["id"]);
    array_push($restrictions_rooms, "max_stay_" . $red["id"]);
    array_push($restrictions_rooms, "closed_" . $red["id"]);
    array_push($restrictions_rooms, "closed_departure_" . $red["id"]);
    array_push($restrictions_rooms, "closed_arrival_" . $red["id"]);
    array_push($restrictions_rooms, "no_ota_" . $red["id"]);
  }
  $restrictions_rooms_sql = implode(", ", $restrictions_rooms); // Order of rooms to insert
  // Get avail
  $avail = makeRequest("fetch_rooms_values", array($userToken, $lcode, $dfrom, $dto));
  $date = $iso_dfrom;
  $i = 0;
  do { // For every day of the period
    // Current date
    $date = date("Y-m-d", $time+$i*24*60*60);
    // Insert avail
    $avail_sql = [];
    for($j=0;$j<sizeof($real_rooms);$j++){ // Get all values in order
      array_push($avail_sql, $avail[$real_rooms[$j]][$i]["avail"]); // Avail of j-th room for i-th date
    }
    $avail_sql = implode(", ", $avail_sql);
    $sql = "INSERT INTO avail_values_$lcode (avail_date, $real_rooms_sql) VALUES ('$date', $avail_sql)";
    mysqli_query($konekcija, $sql);
    // Insert default restrictions
    $restriction_sql = [];
    for($j=0;$j<sizeof($rooms);$j++){ // Get all values in order
      // Order is important
      array_push($restriction_sql, $avail[$rooms[$j]][$i]["min_stay"]); // Value of j-th room for i-th date
      array_push($restriction_sql, $avail[$rooms[$j]][$i]["min_stay_arrival"]);
      array_push($restriction_sql, $avail[$rooms[$j]][$i]["max_stay"]);
      array_push($restriction_sql, $avail[$rooms[$j]][$i]["closed"]);
      array_push($restriction_sql, $avail[$rooms[$j]][$i]["closed_departure"]);
      array_push($restriction_sql, $avail[$rooms[$j]][$i]["closed_arrival"]);
      array_push($restriction_sql, $avail[$rooms[$j]][$i]["no_ota"]);
    }
    $restriction_sql = implode(", ", $restriction_sql);
    $sql = "INSERT INTO restrictions_values_$lcode (id, restriction_date, $restrictions_rooms_sql) VALUES ('1', '$date', $restriction_sql)";
    mysqli_query($konekcija, $sql);
    $i += 1;
  } while(cmpDates($date, $iso_dto) < 0);
  // Get pricing plans
  $pricing_plans = [];
  $sql = "SELECT id FROM prices_$lcode WHERE type = 'daily'"; // Virtual plans don't need values
  $rezultat = mysqli_query($konekcija, $sql);
  while($red = mysqli_fetch_assoc($rezultat)){
    array_push($pricing_plans, $red["id"]);
  }
  for($j=0;$j<sizeof($pricing_plans);$j++) // For each pricing plan, using j for consistency
  {
    $pid = $pricing_plans[$j];
    // Get plan prices
    $prices = makeRequest("fetch_plan_prices", array($userToken, $lcode, $pid, $dfrom, $dto));
    $date = $iso_dfrom;
    $i = 0;
    do { // For every day of the period
      // Current date
      $date = date("Y-m-d", $time+$i*24*60*60);
      $price_sql = [];
      for($k=0;$k<sizeof($rooms);$k++){ // Get all values in order
        array_push($price_sql, $prices[$rooms[$k]][$i]); // Value of k-th room for i-th date
      }
      $price_sql = implode(", ", $price_sql);
      $sql = "INSERT INTO prices_values_$lcode (id, price_date, $rooms_sql) VALUES ('$pid', '$date', $price_sql)";
      mysqli_query($konekcija, $sql);
      $i += 1;
    } while(cmpDates($date, $iso_dto) < 0);
  }
  // Get restriction plans
  $restriction_plans = [];
  $sql = "SELECT id FROM restrictions_$lcode WHERE type = 'daily'"; // Get only daily restrictions
  $rezultat = mysqli_query($konekcija, $sql);
  while($red = mysqli_fetch_assoc($rezultat)){
    array_push($restriction_plans, $red["id"]);
  }
  // Insert restrictions
  for($j=0;$j<sizeof($restriction_plans);$j++) // For each restriction plan, using j for consistency
  {
    $pid = $restriction_plans[$j];
    if($pid == 1) // Skip default restrictions
      continue;
    // Get plan values
    $restrictions = makeRequest("rplan_get_rplan_values", array($userToken, $lcode, $dfrom, $dto, $pid));
    $restrictions = $restrictions[$pid];
    $date = $iso_dfrom;
    $i=0;
    do { // For every day of the period
      // Current date
      $date = date("Y-m-d", $time+$i*24*60*60);
      $restriction_sql = [];
      for($k=0;$k<sizeof($rooms);$k++) // Get all values in order
      {
        // Order is imporant
        array_push($restriction_sql, $restrictions[$rooms[$k]][$i]["min_stay"]); // Value of k-th room for i-th date
        array_push($restriction_sql, $restrictions[$rooms[$k]][$i]["min_stay_arrival"]);
        array_push($restriction_sql, $restrictions[$rooms[$k]][$i]["max_stay"]);
        array_push($restriction_sql, $restrictions[$rooms[$k]][$i]["closed"]);
        array_push($restriction_sql, $restrictions[$rooms[$k]][$i]["closed_departure"]);
        array_push($restriction_sql, $restrictions[$rooms[$k]][$i]["closed_arrival"]);
        array_push($restriction_sql, 0); // No ota for real restrictions
      }
      $restriction_sql = implode(", ", $restriction_sql);
      $sql = "INSERT INTO restrictions_values_$lcode (id, restriction_date, $restrictions_rooms_sql) VALUES ('$pid', '$date', $restriction_sql)";
      mysqli_query($konekcija, $sql);
      $i += 1;
    } while(cmpDates($date, $iso_dto) < 0);
  }
  makeReleaseRequest("release_token", array($userToken));
  // Insert default restriction plan
  $sql = "INSERT IGNORE INTO restrictions_$lcode (id, name, type, rules) VALUES ('1', 'Osnovne restrikcije', 'daily', '')";
  mysqli_query($konekcija, $sql);
}
/* Call when new pricing plan is inserted */
function plansInsertWubookPrice($lcode, $account, $dfrom, $dto, $id, $konekcija)
{
  $userToken = makeRequest("acquire_token", array($account, "davincijevkod966", "753fa793e9adb95321b061f05e29a78327645c05e097e376"));
  $time = strtotime($dfrom);
  $iso_dfrom = $dfrom;
  $iso_dto = $dto;
  $dfrom = ymdToDmy($dfrom);
  $dto = ymdToDmy($dto);
  $pid = $id;
  // Get rooms
  $rooms = [];
  $sql = "SELECT id FROM rooms_$lcode"; // Get all rooms for default restrictions and prices
  $rezultat = mysqli_query($konekcija, $sql);
  while($red = mysqli_fetch_assoc($rezultat)){
    array_push($rooms, $red["id"]);
  }
  $rooms_sql = "room_" . implode(", room_", $rooms); // Order of rooms to insert
  // Get plan prices
  $prices = makeRequest("fetch_plan_prices", array($userToken, $lcode, $pid, $dfrom, $dto));
  $date = $iso_dfrom;
  $i = 0;
  do { // For every day of the period
    // Current date
    $date = date("Y-m-d", $time+$i*24*60*60);
    $price_sql = [];
    for($k=0;$k<sizeof($rooms);$k++){ // Get all values in order
      array_push($price_sql, $prices[$rooms[$k]][$i]); // Value of k-th room for i-th date
    }
    $price_sql = implode(", ", $price_sql);
    $sql = "INSERT INTO prices_values_$lcode (id, price_date, $rooms_sql) VALUES ('$pid', '$date', $price_sql)";
    mysqli_query($konekcija, $sql);
    $i += 1;
  } while(cmpDates($date, $iso_dto) < 0);
  makeReleaseRequest("release_token", array($userToken));
}
function plansInsertWubookRestriction($lcode, $account, $dfrom, $dto, $id, $konekcija)
{
  $userToken = makeRequest("acquire_token", array($account, "davincijevkod966", "753fa793e9adb95321b061f05e29a78327645c05e097e376"));
  $time = strtotime($dfrom);
  $iso_dfrom = $dfrom;
  $iso_dto = $dto;
  $dfrom = ymdToDmy($dfrom);
  $dto = ymdToDmy($dto);
  $pid = $id;
  // Get rooms
  $rooms = [];
  $sql = "SELECT id FROM rooms_$lcode"; // Get all rooms for default restrictions and prices
  $rezultat = mysqli_query($konekcija, $sql);
  while($red = mysqli_fetch_assoc($rezultat)){
    array_push($rooms, $red["id"]);
  }
  $restrictions_rooms = [];
  $sql = "SELECT id FROM rooms_$lcode"; // Get all rooms and set fields for restrictions
  $rezultat = mysqli_query($konekcija, $sql);
  while($red = mysqli_fetch_assoc($rezultat)){
    // Order is important
    array_push($restrictions_rooms, "min_stay_" . $red["id"]);
    array_push($restrictions_rooms, "min_stay_arrival_" . $red["id"]);
    array_push($restrictions_rooms, "max_stay_" . $red["id"]);
    array_push($restrictions_rooms, "closed_" . $red["id"]);
    array_push($restrictions_rooms, "closed_departure_" . $red["id"]);
    array_push($restrictions_rooms, "closed_arrival_" . $red["id"]);
    array_push($restrictions_rooms, "no_ota_" . $red["id"]);
  }
  $restrictions_rooms_sql = implode(", ", $restrictions_rooms); // Order of rooms to insert
  // Get plan values
  $restrictions = makeRequest("rplan_get_rplan_values", array($userToken, $lcode, $dfrom, $dto, $pid));
  $restrictions = $restrictions[$pid];
  $date = $iso_dfrom;
  $i=0;
  do { // For every day of the period
    // Current date
    $date = date("Y-m-d", $time+$i*24*60*60);
    $restriction_sql = [];
    for($k=0;$k<sizeof($rooms);$k++) // Get all values in order
    {
      // Order is imporant
      array_push($restriction_sql, $restrictions[$rooms[$k]][$i]["min_stay"]); // Value of k-th room for i-th date
      array_push($restriction_sql, $restrictions[$rooms[$k]][$i]["min_stay_arrival"]);
      array_push($restriction_sql, $restrictions[$rooms[$k]][$i]["max_stay"]);
      array_push($restriction_sql, $restrictions[$rooms[$k]][$i]["closed"]);
      array_push($restriction_sql, $restrictions[$rooms[$k]][$i]["closed_departure"]);
      array_push($restriction_sql, $restrictions[$rooms[$k]][$i]["closed_arrival"]);
      array_push($restriction_sql, 0); // No ota for real restrictions
    }
    $restriction_sql = implode(", ", $restriction_sql);
    $sql = "INSERT INTO restrictions_values_$lcode (id, restriction_date, $restrictions_rooms_sql) VALUES ('$pid', '$date', $restriction_sql)";
    mysqli_query($konekcija, $sql);
    $i += 1;
  } while(cmpDates($date, $iso_dto) < 0);
  makeReleaseRequest("release_token", array($userToken));
}

/* FETCH VALUES FROM DATABASE */

/* Returns avail for the selected range, in format values[room_id][yyyy-mm-dd] */
function plansAvailValues($lcode, $dfrom, $dto, $konekcija)
{
  // Init values structure
  $values = [];
  $sql = "SELECT id FROM rooms_$lcode WHERE parent_room = '0'";
  $rezultat = mysqli_query($konekcija, $sql);
  while($red = mysqli_fetch_assoc($rezultat)){
    $values[$red["id"]] = [];
  }
  // Fetch all values
  $sql = "SELECT * FROM avail_values_$lcode WHERE avail_date >= '$dfrom' AND avail_date <= '$dto' ORDER BY avail_date ASC";
  $rezultat = mysqli_query($konekcija, $sql);
  while($red = mysqli_fetch_assoc($rezultat)){
    $date = $red["avail_date"];
    foreach($red as $key => $value){ // Key is column name
      if($key == "avail_date") // Skip first column
        continue;
      $id = explode("_", $key)[1]; // Extract room id from column name
      $values[$id][$date] = (int)$value;
    }
  }
  return $values;
}
/* Returns prices for the selected range and plan, in format values[room_id][yyyy-mm-dd]. Works for both daily and virtual plans */
function plansPriceValues($lcode, $dfrom, $dto, $id, $konekcija)
{
  // Init values structure
  $values = [];
  $sql = "SELECT id FROM rooms_$lcode";
  $rezultat = mysqli_query($konekcija, $sql);
  while($red = mysqli_fetch_assoc($rezultat)){
    $values[$red["id"]] = [];
  }
  // Fetch all values
  $sql = "SELECT type, vpid, variation, variation_type FROM prices_$lcode WHERE id = '$id'";
  $rezultat = mysqli_query($konekcija, $sql);
  $red = mysqli_fetch_assoc($rezultat);
  $type = $red["type"];
  if($type == "virtual"){ // Get parent price and add variation
    $parent_plan = $red["vpid"];
    $variation = $red["variation"];
    $variation_type = $red["variation_type"];
    $sql = "SELECT * FROM prices_values_$lcode WHERE id = '$parent_plan' AND price_date >= '$dfrom' AND price_date <= '$dto' ORDER BY price_date ASC";
    $rezultat = mysqli_query($konekcija, $sql);
    while($red = mysqli_fetch_assoc($rezultat)){
      $date = $red["price_date"];
      foreach($red as $key => $value){ // Key is column name
        if($key == "price_date" || $key == "id") // Skip first columns
          continue;
        $room_id = explode("_", $key)[1]; // Extract room id from column name
        if($variation_type == -2) // - fixed
          $new_value = $value - $variation;
        else if($variation_type == -1) // - %
          $new_value = $value - $value * $variation / 100;
        else if($variation_type == 1) // + %
          $new_value = $value + $value * $variation / 100;
        else if($variation_type == 2) // + fixed
          $new_value = $value + $variation;
        if($new_value < 0)
          $new_value = 0;
        $values[$room_id][$date] = (float)$new_value;
      }
    }
  }
  else {
    $sql = "SELECT * FROM prices_values_$lcode WHERE id = '$id' AND price_date >= '$dfrom' AND price_date <= '$dto' ORDER BY price_date ASC";
    $rezultat = mysqli_query($konekcija, $sql);
    while($red = mysqli_fetch_assoc($rezultat)){
      $date = $red["price_date"];
      foreach($red as $key => $value){ // Key is column name
        if($key == "price_date" || $key == "id") // Skip first columns
          continue;
        $room_id = explode("_", $key)[1]; // Extract room id from column name
        $values[$room_id][$date] = (float)$value;
      }
    }
  }
  return $values;
}
/* Returns restrictions for the selected range and plan, in format values[room_id][yyyy-mm-dd]. Works for both daily and compact plans */
function plansRestrictionValues($lcode, $dfrom, $dto, $id, $konekcija)
{
  // Init values structure
  $values = [];
  $sql = "SELECT id FROM rooms_$lcode";
  $rezultat = mysqli_query($konekcija, $sql);
  while($red = mysqli_fetch_assoc($rezultat)){
    $values[$red["id"]] = [];
  }
  // Check plan type
  $sql = "SELECT type, rules FROM restrictions_$lcode WHERE id = '$id'";
  $rezultat = mysqli_query($konekcija, $sql);
  $red = mysqli_fetch_assoc($rezultat);
  $type = $red["type"];
  if($type == "compact"){ // Compact plans have same values for all dates
    $rules = json_decode($red["rules"]);
    $n = dateDiff($dfrom, $dto) + 1;
    $time = strtotime($dfrom);
    foreach($values as $room_id => $room_values){ // For every room
      for($i=0;$i<$n;$i++){ // For every date
        $date = date("Y-m-d", $time+$i*24*60*60);
        $values[$room_id][$date] = $rules;
      }
    }
  }
  else { // Daily plans have separate values for everything
    // Fetch all values
    $sql = "SELECT * FROM restrictions_values_$lcode WHERE id = '$id' AND restriction_date >= '$dfrom' AND restriction_date <= '$dto' ORDER BY restriction_date ASC";
    $rezultat = mysqli_query($konekcija, $sql);
    while($red = mysqli_fetch_assoc($rezultat)){
      $date = $red["restriction_date"];
      foreach($red as $key => $value){ // Key is column name
        if($key == "restriction_date" || $key == "id") // Skip first columns
          continue;
        // Extract room id and field from column name
        $field = explode("_", $key);
        $room_id = array_pop($field);
        $field = implode("_", $field);
        if(!(isset($values[$room_id][$date]))){ // Init field
          $values[$room_id][$date] = [];
        }
        $values[$room_id][$date][$field] = (int)$value;
      }
    }
  }
  return $values;
}

/* UPDATE VALUES IN DATABASE */

/* Updates avail in database */
function plansAvailUpdate($lcode, $dfrom, $dto, $values, $rooms, $variation_type, $konekcija)
{
  if($variation_type != 0){ // Value is relative +-
    foreach($values as $room_id => $value){
      $values[$room_id] = $value * $variation_type; // Variation type is +- 1
    }
    $old_values = [];
    $sql = "SELECT * FROM avail_values_$lcode WHERE avail_date >= '$dfrom' AND avail_date <= '$dto'"; // Fetch all old values
    $rezultat = mysqli_query($konekcija, $sql);
    while($red = mysqli_fetch_assoc($rezultat)){ // Group by date first
      $old_values[$red["avail_date"]] = $red;
    }
    foreach($old_values as $date => $date_values){ // For each date
      $rooms_sql = [];
      for($i=0;$i<sizeof($rooms);$i++){ // For each room
        $room = $rooms[$i];
        if(isset($date_values["room_$room"])){ // Check if field exists
          $room_value = $date_values["room_" . $room] + $values[$room];
          if($room_value < 0)
            $room_value = 0;
          array_push($rooms_sql, "room_$room = $room_value"); // SQL update syntax
        }
      }
      $rooms_sql = implode(", ", $rooms_sql); // Generate SQL for the date
      $sql = "UPDATE avail_values_$lcode SET $rooms_sql WHERE avail_date = '$date'";
      $rezultat = mysqli_query($konekcija, $sql);
      if(!$rezultat)
        fatal_error("Database failed", 200);
    }
  }
  else { // No variation, just set avail for all
    $avail_rooms = [];
    $sql = "SHOW COLUMNS FROM avail_values_$lcode";
    $rezultat = mysqli_query($konekcija, $sql);
    while($red = mysqli_fetch_assoc($rezultat)){
      array_push($avail_rooms, $red["Field"]);
    }
    $rooms_sql = [];
    for($i=0;$i<sizeof($rooms);$i++){ // For each room
      $room = $rooms[$i];
      $value = $values[$room];
      if(in_array("room_$room", $avail_rooms))
        array_push($rooms_sql, "room_$room = $value"); // SQL update syntax
    }
    $rooms_sql = implode(", ", $rooms_sql); // Generate SQL for the date
    $sql = "UPDATE avail_values_$lcode SET $rooms_sql WHERE avail_date >= '$dfrom' AND avail_date <= '$dto'";
    $rezultat = mysqli_query($konekcija, $sql);
    if(!$rezultat)
      fatal_error("Database failed", 500);
  }
}
/* Updates pricing plan in database */
function plansPriceUpdate($lcode, $dfrom, $dto, $id, $values, $rooms, $variation_type, $konekcija)
{
  $old_values = plansPriceValues($lcode, $dfrom, $dto, $id, $konekcija);
  $date = $dfrom;
  $date_count = 0;
  $time = strtotime($date);
  do { // While current date is <= $dto
    $date = date("Y-m-d", $time+$date_count*24*60*60); // Current date
    $date_count += 1;
    $rooms_sql = [];
    foreach($old_values as $room_id => $prices){ // For each room
      $price = $prices[$date];
      if(in_array($room_id, $rooms)){ // Calculate variation if that room is being edit
        if($variation_type == 2) // + EUR
          $value = $price + $values[$room_id];
        if($variation_type == 1)
          $value = $price + $price * $values[$room_id] / 100;
        if($variation_type == -1)
          $value = $price - $price * $values[$room_id] / 100;
        if($variation_type == -2)
          $value = $price - $values[$room_id];
        if($variation_type == 0)
          $value = $values[$room_id];
        if($value < 0)
          $value = 0;
        array_push($rooms_sql, "room_$room_id = $value"); // SQL update syntax
      }
    }
    // Update one day
    $rooms_sql = implode(", ", $rooms_sql); // Generate SQL for the date
    $sql = "UPDATE prices_values_$lcode SET $rooms_sql WHERE price_date = '$date' AND id = '$id'";
    $rezultat = mysqli_query($konekcija, $sql);
    if(!$rezultat)
      fatal_error("Database3 failed", 500);
  } while(cmpDates($date, $dto) < 0);
}
/* Updates restriction plan in database */
function plansRestrictionUpdate($lcode, $dfrom, $dto, $id, $values, $rooms, $konekcija)
{
  // Simple all in one update
  $rooms_sql = [];
  for($i=0;$i<sizeof($rooms);$i++){ // For each room
    $room_id = $rooms[$i];
    $room_values = $values[$room_id];
    foreach($room_values as $field => $field_value){ // For each field of room
      array_push($rooms_sql, $field."_".$room_id . " = $field_value"); // SQL update syntax
    }
  }
  $rooms_sql = implode(", ", $rooms_sql); // Generate SQL
  $sql = "UPDATE restrictions_values_$lcode SET $rooms_sql WHERE restriction_date >= '$dfrom' AND restriction_date <= '$dto' AND id = '$id'";
  $rezultat = mysqli_query($konekcija, $sql);
  if(!$rezultat)
    fatal_error("Database failed", 500);
}

/* PLANS CHANGES WHEN EDITING ROOMS */

/* Inserts room and sets default values to avail and pricing/restriction plans tables */
function plansInsertRoom($lcode, $room, $konekcija)
{
  $sql = "SELECT * FROM rooms_$lcode WHERE id = '$room'";
  $rezultat = mysqli_query($konekcija, $sql);
  $room = mysqli_fetch_assoc($rezultat);
  $avail = $room["availability"];
  $price = $room["price"];
  if($room["parent_room"] == 0){
      $sql = "ALTER TABLE avail_values_$lcode ADD room_$room INT DEFAULT $avail";
     mysqli_query($konekcija, $sql);
  }
  $sql = "ALTER TABLE prices_values_$lcode ADD room_$room FLOAT DEFAULT $price";
  mysqli_query($konekcija, $sql);
  $sql = "ALTER TABLE restrictions_values_$lcode ADD min_stay_$room INT DEFAULT 0";
  mysqli_query($konekcija, $sql);
  $sql = "ALTER TABLE restrictions_values_$lcode ADD min_stay_arrival_$room INT DEFAULT 0";
  mysqli_query($konekcija, $sql);
  $sql = "ALTER TABLE restrictions_values_$lcode ADD max_stay_$room INT DEFAULT 0";
  mysqli_query($konekcija, $sql);
  $sql = "ALTER TABLE restrictions_values_$lcode ADD closed_$room INT DEFAULT 0";
  mysqli_query($konekcija, $sql);
  $sql = "ALTER TABLE restrictions_values_$lcode ADD closed_arrival_$room INT DEFAULT 0";
  mysqli_query($konekcija, $sql);
  $sql = "ALTER TABLE restrictions_values_$lcode ADD closed_departure_$room INT DEFAULT 0";
  mysqli_query($konekcija, $sql);
  $sql = "ALTER TABLE restrictions_values_$lcode ADD no_ota_$room INT DEFAULT 0";
  mysqli_query($konekcija, $sql);
}
/* Removes room from avail and pricing/restriction plans tables */
function plansRemoveRoom($lcode, $room, $konekcija)
{
  $sql = "ALTER TABLE avail_values_$lcode DROP COLUMN room_$room";
  mysqli_query($konekcija, $sql);
  $sql = "ALTER TABLE prices_values_$lcode DROP COLUMN room_$room";
  mysqli_query($konekcija, $sql);
  $sql = "ALTER TABLE restrictions_values_$lcode DROP COLUMN min_stay_$room";
  mysqli_query($konekcija, $sql);
  $sql = "ALTER TABLE restrictions_values_$lcode DROP COLUMN min_stay_arrival_$room";
  mysqli_query($konekcija, $sql);
  $sql = "ALTER TABLE restrictions_values_$lcode DROP COLUMN max_stay_$room";
  mysqli_query($konekcija, $sql);
  $sql = "ALTER TABLE restrictions_values_$lcode DROP COLUMN closed_$room";
  mysqli_query($konekcija, $sql);
  $sql = "ALTER TABLE restrictions_values_$lcode DROP COLUMN closed_arrival_$room";
  mysqli_query($konekcija, $sql);
  $sql = "ALTER TABLE restrictions_values_$lcode DROP COLUMN closed_departure_$room";
  mysqli_query($konekcija, $sql);
  $sql = "ALTER TABLE restrictions_values_$lcode DROP COLUMN no_ota_$room";
  mysqli_query($konekcija, $sql);
}

/* SEND UPDATES FROM DATABASE TO WUBOOK */

/* Sends avail values */
function wubookAvailUpdate($lcode, $account, $dfrom, $dto, $rooms, $konekcija)
{
  $today = date("Y-m-d");
  if(cmpDates($dto, $today) == -1)
    return;
  if(cmpDates($dfrom, $today) == -1)
    $dfrom = $today;

  $userToken = makeRequest("acquire_token", array($account, "davincijevkod966", "753fa793e9adb95321b061f05e29a78327645c05e097e376"));
  $values = plansAvailValues($lcode, $dfrom, $dto, $konekcija);
  $wubook_values = []; // Format of wubook values: [{"id":"288966","days":[{"avail":3}]}, {"id":"288942","days":[{"avail":3}]}];
  foreach($values as $room_id => $date_values){
    if(in_array($room_id, $rooms)){ // Only for sent rooms
      // Generate room object
      $room_object = [];
      $room_object["id"] = $room_id;
      $room_object["days"] = [];
      foreach($date_values as $date => $value){ // Not sure if this guarantees correct order
        // Generate single date object
        $date_object = [];
        $date_object["avail"] = $value;
        array_push($room_object["days"], $date_object);
      }
      array_push($wubook_values, $room_object); // Push room to wubook object
    }
  }
  // Format values for wubook
  $wubook_values = json_decode(json_encode($wubook_values));
  $dfrom = ymdToDmy($dfrom);
  makeRequest("update_avail", array($userToken, $lcode, $dfrom, $wubook_values));
  makeReleaseRequest("release_token", array($userToken));
}
/* Sends daily price values */
function wubookPriceUpdate($lcode, $account, $dfrom, $dto, $id, $rooms, $konekcija)
{
  $today = date("Y-m-d");
  if(cmpDates($dto, $today) == -1)
    return;
  if(cmpDates($dfrom, $today) == -1)
    $dfrom = $today;

  $userToken = makeRequest("acquire_token", array($account, "davincijevkod966", "753fa793e9adb95321b061f05e29a78327645c05e097e376"));
  $values = plansPriceValues($lcode, $dfrom, $dto, $id, $konekcija);
  $wubook_values = []; // Format of wubook values: {"288966": [100, 101, 102], "288942": [200, 201, 202]}
  foreach($values as $room_id => $date_values){
    if(in_array($room_id, $rooms)){ // Only for sent rooms
      // Generate room array
      $room_array = [];
      foreach($date_values as $date => $value){ // Not sure if this guarantees correct order
        array_push($room_array, $value);
      }
      $wubook_values[$room_id] = $room_array; // Add to wubook object
    }
  }
  // Format values for wubook
  $wubook_values = json_decode(json_encode($wubook_values));
  $dfrom = ymdToDmy($dfrom);
  makeRequest("update_plan_prices", array($userToken, $lcode, $id, $dfrom, $wubook_values));
  makeReleaseRequest("release_token", array($userToken));
}
/* Sends restriction values, work for default plan and custom daily plans */
function wubookRestrictionUpdate($lcode, $account, $dfrom, $dto, $id, $rooms, $konekcija)
{
  $today = date("Y-m-d");
  if(cmpDates($dto, $today) == -1)
    return;
  if(cmpDates($dfrom, $today) == -1)
    $dfrom = $today;
    
  $userToken = makeRequest("acquire_token", array($account, "davincijevkod966", "753fa793e9adb95321b061f05e29a78327645c05e097e376"));
  $values = plansRestrictionValues($lcode, $dfrom, $dto, $id, $konekcija);
  if($id == 1){ // Default restrictions
    $wubook_values = []; // Format of wubook values: [{"id":"288966","days":[{"avail":3}]}, {"id":"288942","days":[{"avail":3}]}];
    foreach($values as $room_id => $all_dates_values){
      if(in_array($room_id, $rooms)){ // Only for sent rooms
        // Generate room object
        $room_object = [];
        $room_object["id"] = $room_id;
        $room_object["days"] = [];
        foreach($all_dates_values as $date => $date_values){ // For each date of room. Not sure if this guarantees correct order
          $day_object = [];
          foreach($date_values as $field => $single_value){ // For each field of single day
            $day_object[$field] = $single_value;
          }
          array_push($room_object["days"], $day_object);
        }
        array_push($wubook_values, $room_object); // Push room to wubook object
      }
    }
    // Format values for wubook
    $wubook_values = json_decode(json_encode($wubook_values));
    $dfrom = ymdToDmy($dfrom);
    makeRequest("update_avail", array($userToken, $lcode, $dfrom, $wubook_values));
  }
  else { // Daily restriction plan
    $wubook_values = []; // Format of wubook values: {'288966': [ {'min_stay': 3}, {}, {'max_stay': 4}],'288942': [ {'closed': 1}, {}, {'max_stay': 2}]}
    foreach($values as $room_id => $all_dates_values){
      if(in_array($room_id, $rooms)){ // Only for sent rooms
        // Generate room array
        $room_array = [];
        foreach($all_dates_values as $date => $date_values){ // For each date of room. Not sure if this guarantees correct order
          $day_object = [];
          foreach($date_values as $field => $single_value){ // For each field of single day
            if($field != "no_ota"){ // No ota is only sent for default restrictions
              $day_object[$field] = $single_value;
            }
          }
          array_push($room_array, $day_object);
        }
        $wubook_values[$room_id] = $room_array; // Add to wubook object
      }
    }
    // Format values for wubook
    $wubook_values = json_decode(json_encode($wubook_values));
    $dfrom = ymdToDmy($dfrom);
    makeRequest("rplan_update_rplan_values", array($userToken, $lcode, $id, $dfrom, $wubook_values));
  }
  makeReleaseRequest("release_token", array($userToken));
}

?>
