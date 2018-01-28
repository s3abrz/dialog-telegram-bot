<?php

include_once 'config.php';
include_once 'db.php';
include_once 'function.php';

//принимаем запрос от бота(то что напишет в чате пользователь)
$content = file_get_contents('php://input');

//превращаем из json в массив
$update = json_decode($content, true);

//получаем id чата
$chat_id = $update['message']['chat']['id'];

//получаем id пользователя
$user_id = $update['message']['from']['id'];

//получаем текст запроса
$text = $update['message']['text'];

//запись в лог
//toLog($update);

//обработка запроса
getUserRequest($text, $chat_id, $user_id);

?>
