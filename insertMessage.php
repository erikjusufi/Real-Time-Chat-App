<?php
session_start();
include(("DBConnection.php"));

$fromUser = $_POST["fromUser"];
$toUser = $_POST["toUser"];
$message = $_POST["message"];

$output = "";
$sql = "INSERT INTO messages (FromUser, ToUser, Message) VALUES ('$fromUser', '$toUser', '$message')";

if($connect -> query($sql))
{
    $output = "<div style = 'text-align:right'>
    <p style='background-color:lightblue;;word-wrap:break-word;display:inline-block;padding:5px;border-radius:10px;max-width:70%;'>
        " . $message. "
    </p>
    </div>";

}
else
{
    $output .= "Error. Please Try Again!";
}
echo $output;
?>