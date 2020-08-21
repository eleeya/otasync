<?php

require "../main.php";

ignore_user_abort(true);
set_time_limit(0);

ob_start();

header('Connection: close');
header('Content-Length: '.ob_get_length());
ob_end_flush();
ob_flush();
flush();

  $account = getAction();
  $konekcija = connectToDB();
  $lcode = $_POST["lcode"];
  $rcode = $_POST["rcode"];
  $userToken = makeRequest("acquire_token", array($account, "davincijevkod966", "753fa793e9adb95321b061f05e29a78327645c05e097e376"));
  $res = makeRequest("fetch_booking", array($userToken, $lcode, $rcode, 1));
  $res = $res[0];
  insertWubookReservation($lcode, $account, $res);
  sendReservationConfirmation($rcode, $lcode, $account, $konekcija);
  sendGuestEmail($rcode, $lcode, $account, $konekcija);


  // Mobile app push notification


function sendNotification($rcode, $lcode, $account, $konekcija)
{
  // Channel data
  $channel_data = [];
  $sql = "SELECT logo, name, id FROM channels_$lcode";
  $rezultat = mysqli_query($konekcija, $sql);
  while($red = mysqli_fetch_assoc($rezultat)){
    $channel_data[$red['id']] = $red;
  }
  // Res data
  $sql = "SELECT * FROM reservations_$lcode WHERE reservation_code = '$rcode'";
  $rezultat = mysqli_query($konekcija, $sql);
  $res = fixReservation(mysqli_fetch_assoc($rezultat), $channel_data, $konekcija, $lcode);
  // Message
  if($res["status"] == 5)
    $message = "Otkazana rezervacija: " . ymdToDmy($res["date_arrival"]) . " - " . ymdToDmy($res["date_departure"]) . " (" . $res["customer_name"] . " " . $res["customer_surname"] . ")";
  else
    $message = "Nova rezervacija: " . ymdToDmy($res["date_arrival"]) . " - " . ymdToDmy($res["date_departure"]) . " (" . $res["customer_name"] . " " . $res["customer_surname"] . ")";
  $content = array("en" => $message);
  // Notification ids
  $ids = [];
  $sql = "SELECT id FROM all_users WHERE account = '$account' AND status != 0";
  $rezultat = mysqli_query($konekcija, $sql);
  while($user = mysqli_fetch_assoc($rezultat)){
    $user_id = $user["id"];
    $sql2 = "SELECT * FROM all_sessions WHERE id = '$user_id' AND notification_id != ''";
    $rezultat2 = mysqli_query($konekcija, $sql2);
    while($user_session = mysqli_fetch_assoc($rezultat2)){
      array_push($ids, $user_session["notification_id"]);
    }
  }
  // Sending too all 3 apps
  // SB
  $fields = array(
      'app_id' => "671f8f35-b25a-42e9-9d8d-b48adfe4f088",
      'include_player_ids' => $ids,
      'data' => array("foo" => "bar"),
      'contents' => $content
  );
  $fields = json_encode($fields);
  $ch = curl_init();
  curl_setopt($ch, CURLOPT_URL, "https://onesignal.com/api/v1/notifications");
  curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json; charset=utf-8'));
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
  curl_setopt($ch, CURLOPT_HEADER, FALSE);
  curl_setopt($ch, CURLOPT_POST, TRUE);
  curl_setopt($ch, CURLOPT_POSTFIELDS, $fields);
  curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
  $response = curl_exec($ch);
  curl_close($ch);
  // OTASYNC
  $fields = array(
      'app_id' => "8cc3d05b-3255-4f9b-ad77-faa4d0858983",
      'include_player_ids' => $ids,
      'data' => array("foo" => "bar"),
      'contents' => $content
  );
  $fields = json_encode($fields);
  $ch = curl_init();
  curl_setopt($ch, CURLOPT_URL, "https://onesignal.com/api/v1/notifications");
  curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json; charset=utf-8'));
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
  curl_setopt($ch, CURLOPT_HEADER, FALSE);
  curl_setopt($ch, CURLOPT_POST, TRUE);
  curl_setopt($ch, CURLOPT_POSTFIELDS, $fields);
  curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
  $response = curl_exec($ch);
  curl_close($ch);
  // ME
  $fields = array(
      'app_id' => "2b001793-7618-4f89-aadc-bbe3475727a1",
      'include_player_ids' => $ids,
      'data' => array("foo" => "bar"),
      'contents' => $content
  );
  $fields = json_encode($fields);
  $ch = curl_init();
  curl_setopt($ch, CURLOPT_URL, "https://onesignal.com/api/v1/notifications");
  curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json; charset=utf-8'));
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
  curl_setopt($ch, CURLOPT_HEADER, FALSE);
  curl_setopt($ch, CURLOPT_POST, TRUE);
  curl_setopt($ch, CURLOPT_POSTFIELDS, $fields);
  curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
  $response = curl_exec($ch);
  curl_close($ch);
}

sendNotification($rcode, $lcode, $account, $konekcija);




$konekcija->close();

?>
