<?php
$content = array("en" => "123");
$fields = array(
    'app_id' => "671f8f35-b25a-42e9-9d8d-b48adfe4f088",
    'include_player_ids' => array("d663c3c3-dabb-418f-b2d6-cac89ddd1fd4"),
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
print_r($response);
curl_close($ch);

 ?>
