<?php

function toLog($log)
{
    $myFile = 'log.txt';
    $fh = fopen($myFile, 'a') or die('can\'t open file');
    if ((is_array($log)) || (is_object($log))) {
        $updateArray = print_r($log, TRUE);
        fwrite($fh, $updateArray . "\n");
    } else {
        fwrite($fh, $log . "\n");
    }
    fclose($fh);
}

function getUserRequest($text, $chat_id, $user_id = null)
{
    if ($text == '/start') {
        $data = array(
            'text' => 'Введите /help чтобы получить помощь по командам этого бота.',
            'chat_id' => $chat_id,
        );
        requestToTelegram($data);
    } elseif ($text == '/help') {
        $data = array(
            'text' => 'Текст помощи по работе с ботом.' . PHP_EOL . 'Ваш user_id: ' . $user_id,
            'chat_id' => $chat_id,
        );
        requestToTelegram($data);
    } else {
        $checkAnswer = checkUserInDB($user_id, $text);

        $data = array(
            'text' => $checkAnswer,
            'chat_id' => $chat_id,
        );

        requestToTelegram($data);
    }
}

function commIsUser($text)
{
    $text = trim($text); //обрезаем пробелы в начале и в конце
    $space = strpos($text, ' ');
    if ($space === false) {
        switch (mb_substr($text, 0, 3)) {
            case 'Арт':
                $answer = dbSearch(mb_substr($text, 3));

                $data = [
                    'text' => 'Вы запросили артикул: ' . mb_substr($text, 3) . PHP_EOL . PHP_EOL . 'Ответ: ' . $answer,
                ];

                return $data;
                break;

            case 'Имя':
                $data = [
                    'text' => 'Вы запросили наименование: ' . mb_substr($text, 3),
                ];

                return $data;
                break;

            default:
                $data = [
                    'text' => 'Текст помощи по работе с ботом (2).',
                ];

                return $data;
                break;
        }
    }

    return null;
}

function checkUserInDB($user_id, $text)
{
    //Подключение к базе данных mySQL с помощью PDO
    try {
        $db = new PDO(
            'mysql:host=' . DBSERVER . ';dbname=' . DATABASE, DBUSER, DBPASSWORD, [
                PDO::ATTR_PERSISTENT => true,
            ]
        );
    } catch (PDOException $e) {
        print "Ошибка соединения!: " . $e->getMessage() . "<br/>";
        die();
    }
    $db->exec("set names utf8");
    $db->exec("SET CHARACTER SET 'utf8'");
    $db->exec("SET SESSION collation_connection = 'utf8_general_ci'");

    $sql = 'SELECT * FROM `telegramusers` WHERE `user_id` = :user_id';
    $stmt = $db->prepare($sql);
    $stmt->bindValue(':user_id', $user_id);
    $stmt->execute();
    $row = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (count($row) < 1) {
        $sql = 'INSERT INTO `telegramusers` (`user_id`, `user_message`) VALUES (:user_id, :user_message)';
        $stmt = $db->prepare($sql);
        $stmt->bindValue(':user_id', $user_id);
        $stmt->bindValue(':user_message', mb_strtolower($text));
        $stmt->execute();
    } else {
        $sql = 'SELECT * FROM `telegramusers` WHERE `user_id` = :user_id';
        $stmt = $db->prepare($sql);
        $stmt->bindValue(':user_id', $user_id);
        $stmt->execute();
        $row = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $user_message = $row[0]['user_message'];
        $add_date = $row[0]['add_date'];

        $checkAnswer = 'Ваше последнее сообщение: ' . $user_message . ', отправленно: ' . $add_date;

        return $checkAnswer;
    }
}

function dbSearch($query)
{
    //Подключение к базе данных mySQL с помощью PDO
    try {
        $db = new PDO(
            'mysql:host=' . DBSERVER . ';dbname=' . DATABASE, DBUSER, DBPASSWORD, [
                PDO::ATTR_PERSISTENT => true,
            ]
        );
    } catch (PDOException $e) {
        print "Ошибка соединения!: " . $e->getMessage() . "<br/>";
        die();
    }
    $db->exec("set names utf8");
    $db->exec("SET CHARACTER SET 'utf8'");
    $db->exec("SET SESSION collation_connection = 'utf8_general_ci'");

    $sql = 'SELECT * FROM `telegrambot` WHERE `art` = :art';
    $stmt = $db->prepare($sql);
    $stmt->bindValue(':art', $query, PDO::PARAM_STR);
    $stmt->execute();
    $row = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $price = $row[0]['price'];
    $description = $row[0]['description'];

    $answer = PHP_EOL . PHP_EOL . 'Цена:' . PHP_EOL . $price . ' руб.' . PHP_EOL . PHP_EOL;
    $answer .= 'Описание:' . PHP_EOL . $description;

    return $answer;
}

function requestToTelegram($data, $type = 'sendMessage')
{
    if ($curl = curl_init()) {
        curl_setopt($curl, CURLOPT_URL, API_URL . $type);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($curl, CURLOPT_POST, TRUE);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
        curl_exec($curl);
        curl_close($curl);
    }
}

?>
