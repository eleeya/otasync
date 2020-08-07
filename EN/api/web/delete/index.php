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
$id = checkPost("id");

$konekcija = connectToDB();
$action = getAction();

$user = getSession($key, $account, $konekcija);
$user_id = $user["id"];

// Check access here

$old_data = [];
$new_data = [];
$ret_val = [];
$ret_val["status"] = "ok";

if($action == "reservation")
{

  // Remember data
  $sql = "SELECT * FROM reservations_$lcode WHERE reservation_code = '$id'";
  $rezultat = mysqli_query($konekcija, $sql);
  if($rezultat)
    $old_data = mysqli_fetch_assoc($rezultat);
  else
    fatal_error("Invalid ID", 200);

  $today = date("Y-m-d");
  $deleted_advance = dateDiff($today, $old_data["date_arrival"]);
  if($old_data["status"] == 1){
    $userToken = makeRequest("acquire_token", array($account, "davincijevkod966", "753fa793e9adb95321b061f05e29a78327645c05e097e376"));
    makeUncheckedRequest("cancel_reservation", array($userToken, $lcode, $id));
    $res = makeRequest("fetch_booking", array($userToken, $lcode, $id, 1));
    $res = $res[0]; // This is new?
    makeReleaseRequest("release_token", array($userToken));
    insertWubookReservation($lcode, $account, $res);
    /*
    $sql = "UPDATE reservations_$lcode SET status = 5, deleted_advance = $deleted_advance, date_canceled = '$today' WHERE reservation_code = '$id'";
    $rezultat = mysqli_query($konekcija, $sql);
    if($rezultat){
      $userToken = makeRequest("acquire_token", array($account, "davincijevkod966", "753fa793e9adb95321b061f05e29a78327645c05e097e376"));
      makeUncheckedRequest("cancel_reservation", array($userToken, $lcode, $id));
      makeReleaseRequest("release_token", array($userToken));

      // Updating avail in database
      $real_rooms_list = explode(",", $old_data["real_rooms"]);
      $date_arrival = $old_data["date_arrival"];
      $date_departure = $old_data["date_departure"];
      $avail_rooms = [];
      $avail_values = [];
      for($i=0;$i<sizeof($real_rooms_list);$i++){
        $room = $real_rooms_list[$i];
        if(in_array($room, $avail_rooms)){ // Add one to value if room already added
          $avail_values[$room] += 1;
        }
        else { // Push to array of rooms and set to one if room isn't already added
          array_push($avail_rooms, $room);
          $avail_values[$room] = 1;
        }
      }
      $date_obj = date_create($date_departure); // Reduce the departure date by 1
      date_add($date_obj, date_interval_create_from_date_string("-1 day"));
      $avail_date_departure = date_format($date_obj, "Y-m-d");
      plansAvailUpdate($lcode, $date_arrival, $avail_date_departure, $avail_values, $avail_rooms, 1, $konekcija);
    }
    else {
      fatal_error("Database error", 500); // Server failed
    }
    */
  }
  else {
    $sql = "DELETE FROM reservations_$lcode WHERE reservation_code = '$id'";
    $rezultat = mysqli_query($konekcija, $sql);
  }
  // Fixing guests
  $guests_list = explode(",", $old_data["guest_ids"]);
  for($i=0;$i<sizeof($guests_list);$i++){
    repairGuestData($guests_list[$i], $lcode, $konekcija);
  }

}

if($action == "guest")
{
  // Remember data
  $sql = "SELECT * FROM guests_$lcode WHERE id = '$id'";
  $rezultat = mysqli_query($konekcija, $sql);
  if($rezultat)
    $old_data = mysqli_fetch_assoc($rezultat);
  else
    fatal_error("Invalid ID", 200);

  // DB delete
  $sql = "DELETE FROM guests_$lcode WHERE id = '$id'";
  $rezultat = mysqli_query($konekcija, $sql);
  if(!$rezultat)
    fatal_error("Database failed", 500);
}

if($action == "invoice")
{
  // Remember data
  $sql = "SELECT * FROM invoices_$lcode WHERE id = '$id'";
  $rezultat = mysqli_query($konekcija, $sql);
  if($rezultat)
    $old_data = mysqli_fetch_assoc($rezultat);
  else
    fatal_error("Invalid ID", 200);

  // DB delete
  $sql = "DELETE FROM invoices_$lcode WHERE id = '$id'";
  $rezultat = mysqli_query($konekcija, $sql);
  if(!$rezultat)
    fatal_error("Database failed", 500);
}

if($action == "promocode")
{
  // Remember data
  $sql = "SELECT * FROM promocodes_$lcode WHERE id = '$id'";
  $rezultat = mysqli_query($konekcija, $sql);
  if($rezultat)
    $old_data = mysqli_fetch_assoc($rezultat);
  else
    fatal_error("Invalid ID", 200);

  // DB delete
  $sql = "DELETE FROM promocodes_$lcode WHERE id = '$id'";
  $rezultat = mysqli_query($konekcija, $sql);
  if(!$rezultat)
    fatal_error("Database failed", 500);
}

if($action == "policy")
{
  // Remember data
  $sql = "SELECT * FROM policies_$lcode WHERE id = '$id'";
  $rezultat = mysqli_query($konekcija, $sql);
  if($rezultat)
    $old_data = mysqli_fetch_assoc($rezultat);
  else
    fatal_error("Invalid ID", 200);

  // DB delete
  $sql = "DELETE FROM policies_$lcode WHERE id = '$id'";
  $rezultat = mysqli_query($konekcija, $sql);
  if(!$rezultat)
    fatal_error("Database failed", 500);
}

if($action == "category")
{
  $sql = "SELECT *
  FROM categories_$lcode
  WHERE id = $id
  LIMIT 1
  ";
  $rezultat = mysqli_query($konekcija, $sql);
  if($rezultat)
    $old_data = mysqli_fetch_assoc($rezultat);
  else
    fatal_error("Invalid ID", 200);

  $sql = "DELETE FROM categories_$lcode WHERE id = $id";
  $rezultat = mysqli_query($konekcija, $sql);
  if(!$rezultat)
    fatal_error("Database error", 500); // Server failed
}

if($action == "article")
{
  $sql = "SELECT *
          FROM articles_$lcode
          WHERE id = $id
          LIMIT 1
          ";
  $rezultat = mysqli_query($konekcija, $sql);
  if($rezultat)
    $old_data = mysqli_fetch_assoc($rezultat);
  else
    fatal_error("Invalid ID", 200);

  $sql = "DELETE FROM articles_$lcode WHERE id = $id";
  $rezultat = mysqli_query($konekcija, $sql);
  if(!$rezultat)
    fatal_error("Database error", 500); // Server failed
}

if($action == "pricingPlan")
{
  // Remember data
  $sql = "SELECT * FROM prices_$lcode WHERE id = '$id'";
  $rezultat = mysqli_query($konekcija, $sql);
  if($rezultat)
    $old_data = mysqli_fetch_assoc($rezultat);
  else
    fatal_error("Invalid ID", 200);

  // Wubook delete
  $userToken = makeRequest("acquire_token", array($account, "davincijevkod966", "753fa793e9adb95321b061f05e29a78327645c05e097e376"));
  makeRequest("del_plan", array($userToken, $lcode, $id));
  makeReleaseRequest("release_token", array($userToken));

  // DB delete
  $sql = "DELETE FROM prices_$lcode WHERE id = '$id'";
  $rezultat = mysqli_query($konekcija, $sql);
  if(!$rezultat)
    fatal_error("Database failed", 500);

  // DB values delete
  $sql = "DELETE FROM prices_values_$lcode WHERE id = '$id'";
  $rezultat = mysqli_query($konekcija, $sql);
}

if($action == "restrictionPlan")
{
  // Remember data
  $sql = "SELECT * FROM restrictions_$lcode WHERE id = '$id'";
  $rezultat = mysqli_query($konekcija, $sql);
  if($rezultat)
    $old_data = mysqli_fetch_assoc($rezultat);
  else
    fatal_error("Invalid ID", 200);

  // Wubook delete
  $userToken = makeRequest("acquire_token", array($account, "davincijevkod966", "753fa793e9adb95321b061f05e29a78327645c05e097e376"));
  makeRequest("rplan_del_rplan", array($userToken, $lcode, $id));
  makeReleaseRequest("release_token", array($userToken));

  // DB delete
  $sql = "DELETE FROM restrictions_$lcode WHERE id = '$id'";
  $rezultat = mysqli_query($konekcija, $sql);
  if(!$rezultat)
    fatal_error("Database failed", 500);

  // DB values delete
  $sql = "DELETE FROM restrictions_values_$lcode WHERE id = '$id'";
  $rezultat = mysqli_query($konekcija, $sql);
}

if($action == "extra")
{
  // Remember data
  $sql = "SELECT * FROM extras_$lcode WHERE id = '$id'";
  $rezultat = mysqli_query($konekcija, $sql);
  if($rezultat)
    $old_data = mysqli_fetch_assoc($rezultat);
  else
    fatal_error("Invalid ID", 200);

  // DB delete
  $sql = "DELETE FROM extras_$lcode WHERE id = '$id'";
  $rezultat = mysqli_query($konekcija, $sql);
  if(!$rezultat)
    fatal_error("Database failed", 500);
}

if($action == "channel")
{
  // Remember data
  $sql = "SELECT * FROM channels_$lcode WHERE id = '$id'";
  $rezultat = mysqli_query($konekcija, $sql);
  if($rezultat)
    $old_data = mysqli_fetch_assoc($rezultat);
  else
    fatal_error("Invalid ID", 200);

  // DB delete
  $sql = "DELETE FROM channels_$lcode WHERE id = '$id'";
  $rezultat = mysqli_query($konekcija, $sql);
  if(!$rezultat)
    fatal_error("Database failed", 500);
}

if($action == "user")
{
  // Remember data
  $sql = "SELECT * FROM all_users WHERE id = '$id'";
  $rezultat = mysqli_query($konekcija, $sql);
  if($rezultat)
    $old_data = mysqli_fetch_assoc($rezultat);
  else
    fatal_error("Invalid ID", 200);

  // DB delete
  $sql = "DELETE FROM all_users WHERE id = '$id'";
  $rezultat = mysqli_query($konekcija, $sql);
  if(!$rezultat)
    fatal_error("Database failed", 500);
}

if($action == "yieldVariations")
{
  // Remember data
  $sql = "SELECT * FROM yield_variations_$lcode WHERE id = '$id'";
  $rezultat = mysqli_query($konekcija, $sql);
  if($rezultat)
    $old_data = mysqli_fetch_assoc($rezultat);
  else
    fatal_error("Invalid ID", 200);

  // DB delete
  $sql = "DELETE FROM yield_variations_$lcode WHERE id = '$id'";
  $rezultat = mysqli_query($konekcija, $sql);
  if(!$rezultat)
    fatal_error("Database failed", 500);
}

// Changelog
$data_type = $action;
$ch_action = "delete";
$user_name = $user["name"];

$old_data = mysqli_real_escape_string($konekcija, json_encode($old_data));
$new_data = mysqli_real_escape_string($konekcija, json_encode($new_data));

$sql = "INSERT INTO changelog_$lcode (data_type, action, old_data, new_data, undone, created_by) VALUES (
  '$data_type',
  '$ch_action',
  '$old_data',
  '$new_data',
  0,
  '$user_name')";
$rezultat = mysqli_query($konekcija, $sql);
if($rezultat){
  $ch_id = mysqli_insert_id($konekcija);
  $sql = "SELECT * FROM changelog_$lcode WHERE id = $ch_id";
  $rezultat = mysqli_query($konekcija, $sql);
  $ch_data = mysqli_fetch_assoc($rezultat);
  $ch_data = fixChange($ch_data);
  $ret_val["data"] = $ch_data;
}
else {
  $ret_val["data"] = [];
  $ret_val["data"]["id"] = -1;
//  fatal_error("Changelog error", 500); // Server failed
}

echo json_encode($ret_val);
$konekcija->close();

?>
