<?php
ob_start();
$content= file_get_contents("php://input");
$update = json_decode($content);
define("API_KEY","API شما اینجا");
$pictures = [
    [
        "file"=>"file.png",
        "caption"=>"یک تصویر زیبا به نام file.png",
        "text"=>"شما یک تصویر کاملا زیبا و استثنایی دریافت خواهید کرد ..."
    ],
    [
        "file"=>"test.jpg",
        "caption"=>"یک عکس رویایی به نام test.jpg",
        "text"=>"این تصویر کمی تا قسمتی رویایی بزودی دریافت خواهید کرد ..."
    ]
];

function sendMessage($datas){
    $url = "https://panel.botsaz.com/api/bot/sendMessage";
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL,$url);
    curl_setopt($ch, CURLOPT_POST, 1);
    $datas["api_key"]=API_KEY;
    curl_setopt($ch, CURLOPT_POSTFIELDS,
        http_build_query($datas));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $server_output = curl_exec ($ch);
    curl_close ($ch);
    return json_decode($server_output);
}

function curl_custom_postfields($ch, array $assoc = array(), array $files = array()) {

    // invalid characters for "name" and "filename"
    static $disallow = array("\0", "\"", "\r", "\n");

    // build normal parameters
    foreach ($assoc as $k => $v) {
        $k = str_replace($disallow, "_", $k);
        $body[] = implode("\r\n", array(
            "Content-Disposition: form-data; name=\"{$k}\"",
            "",
            filter_var($v),
        ));
    }

    // build file parameters
    foreach ($files as $k => $v) {
        switch (true) {
            case false === $v = realpath(filter_var($v)):
            case !is_file($v):
            case !is_readable($v):
                continue; // or return false, throw new InvalidArgumentException
        }
        $data = file_get_contents($v);
        $v = call_user_func("end", explode(DIRECTORY_SEPARATOR, $v));
        $k = str_replace($disallow, "_", $k);
        $v = str_replace($disallow, "_", $v);
        $body[] = implode("\r\n", array(
            "Content-Disposition: form-data; name=\"{$k}\"; filename=\"{$v}\"",
            "Content-Type: image/jpeg",
            "",
            $data,
        ));
    }

    // generate safe boundary
    do {
        $boundary = "---------------------" . md5(mt_rand() . microtime());
    } while (preg_grep("/{$boundary}/", $body));

    // add boundary for each parameters
    array_walk($body, function (&$part) use ($boundary) {
        $part = "--{$boundary}\r\n{$part}";
    });

    // add final boundary
    $body[] = "--{$boundary}--";
    $body[] = "";

    // set options
    return @curl_setopt_array($ch, array(
        CURLOPT_POST       => true,
        CURLOPT_POSTFIELDS => implode("\r\n", $body),
        CURLOPT_HTTPHEADER => array(
            "Expect: 100-continue",
            "Content-Type: multipart/form-data; boundary={$boundary}", // change Content-Type
        ),
    ));
}

function sendPhoto($filename,$datas=[]){
    $datas["api_key"] = API_KEY;
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL,"https://panel.botsaz.com/api/bot/sendPhoto");
    curl_custom_postfields($ch,$datas,["photo"=>$filename]);
    $server_output = curl_exec ($ch);
    curl_close ($ch);

    return json_decode($server_output);
}

$random_image = $pictures[rand(0,count($pictures)-1)];
sendMessage([
    "text"=>$random_image["text"],
    "chat_id"=>$update->message->chat->id,
    "reply_to_message_id"=>$update->message->message_id
]);
sendPhoto($random_image["file"],[
    "chat_id"=>$update->message->chat->id,
    "reply_markup"=>json_encode(
        ["keyboard"=>
            [
                [["text"=>"منوی اصلی"],["text"=>"دوباره"]]
            ]
            ,"resize_keyboard"=>true
        ]
    )
]);
file_put_contents("log",ob_get_clean());
