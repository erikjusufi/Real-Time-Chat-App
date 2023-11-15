<?php

include("DBConnection.php");

$fromUser = $_POST["fromUser"];
$toUser = $_POST["toUser"];

$output = ""; 
$chats = mysqli_query($connect, "SELECT * FROM messages WHERE (FromUser = '".$toUser."' AND ToUser = '".$fromUser."') ORDER BY Id DESC LIMIT 1")
                or die("failed to query database" . mysql_error());
while($chat = mysqli_fetch_assoc($chats)) {
    if($chat["FromUser"] == $fromUser) 
    {
    $output = "<div style = 'text-align:right'>
            <p style='background-color:lightblue;;word-wrap:break-word;display:inline-block;padding:5px;border-radius:10px;max-width:70%;'>
                " . $chat["Message"] . "
            </p>
        </div>";
    }
    else {
        $output = "<div style = 'text-align:left'>
            <p style='background-color:yellow;;word-wrap:break-word;display:inline-block;padding:5px;border-radius:10px;max-width:70%;'>
                " . $chat["Message"] . "
            </p>
        </div>";
    }
    echo $output;
}
?>
