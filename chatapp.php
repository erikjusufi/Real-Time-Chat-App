<?php
session_start();
include("links.php");
include("DBConnection.php");
$users = mysqli_query($connect, "SELECT * FROM users WHERE Id = '" . $_SESSION["userId"] . "'")
    or die("failed to query database" . mysql_error());
$user = mysqli_fetch_assoc($users);
if (isset($_POST["username"])) {
    $sql = "INSERT INTO users (User) VALUES('" . $_POST["username"] . "')";
    if ($connect->query($sql))
        header('Location: index.php');
    else
        echo "Error. Please Try Again";
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $fromUser = $_POST["fromUser"];
    $toUser = $_POST["toUser"];
    $message = $_POST["message"];

    $output = "";
    $sql = "INSERT INTO messages (FromUser, ToUser, Message) VALUES ('$fromUser', '$toUser', '$message')";

    if ($connect->query($sql)) {
        $output = "<div style='text-align:right'>
            <p style='background-color:lightblue;;word-wrap:break-word;display:inline-block;padding:5px;border-radius:10px;max-width:70%;'>
                $message
            </p>
        </div>";
    } else {
        $output .= "Error. Please Try Again!";
    }
    echo $output;
}
?>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Chat App</title>
<script type="text/javascript">
    let messages = [];
    let mode = "websocket";
    let mode_changed = true;
    let ws;
    $(document).ready(function() {

            

        $("#send").click(function(event) {
            if (mode=="websocket") {
                data= {
                    fromUser: $("#fromUser").val(),
                    toUser: $("#toUser").val(),
                    message: $("#message").val()
                } 
                ws.send(JSON.stringify(data));
                var div = "<div style = 'text-align:right'><p style='background-color:lightblue;;word-wrap:break-word;display:inline-block;padding:5px;border-radius:10px;max-width:70%;'>" + data.message + "</p></div>";
                $("#message").val("");
                $("#msgBody").append(div);
            }
            else {
                ajax_post();
            }
        })
        function polling_update() {
            let interval = setInterval(function() {
                if(mode != "polling") {
                    clearInterval(interval);
                    return;
                }
                var xhr = new XMLHttpRequest();
                var url = "http://127.0.0.1:5000/polling/" + $("#fromUser").val();
                xhr.open("GET", url);
                xhr.onload = function() {
                    if(xhr.status == 200) {
                        let output = "<div style = 'text-align:left'><p style='background-color:yellow;;word-wrap:break-word;display:inline-block;padding:5px;border-radius:10px;max-width:70%;'>" + xhr.response+ "</p></div>";
                        $("#msgBody").append(output);
                    }
                }
                xhr.send();
            },10000);
            
        }
        function long_polling_update() {
            if(mode != "long-polling") {
                return;
            }
            var xhr = new XMLHttpRequest();
            var url = "http://127.0.0.1:5000/long-polling/" + $("#fromUser").val();
            xhr.open('GET', url);
            xhr.onload = function() {
                if (this.status == 200) {
                    let output = "<div style = 'text-align:left'><p style='background-color:yellow;;word-wrap:break-word;display:inline-block;padding:5px;border-radius:10px;max-width:70%;'>" + xhr.response+ "</p></div>";
                    $("#msgBody").append(output);
                    
                }
                long_polling_update();
            };
            xhr.send();

        }
        function websocket_update() {
            $(document).ready(function(){
                ws = new WebSocket("ws://localhost:8765");
                ws.onopen = function () {
                    console.log("Connection established");
                    ws.send(JSON.stringify({username : $("#fromUser").val()}));
                    /*setInterval(function() {
                        ws.send({});
                    },1000);*/
                }
                ws.onmessage = (e) => {
                    var data = JSON.parse(e.data);
                    var div = "<div style = 'text-align:left'><p style='background-color:yellow;;word-wrap:break-word;display:inline-block;padding:5px;border-radius:10px;max-width:70%;'>" + data.message + "</p></div>";
                    $("#msgBody").append(div);
                }
                ws.onclose = function(ev) {
                    console.log("Connection closed!")
                }
        }   )
        }
        function call_updates() {
            if(mode == "polling" && mode_changed) {
                if (ws && ws.readyState === WebSocket.OPEN) {
                    ws.close()
                }
                mode_changed = false;
                polling_update();
                console.log("polling");
            }
            if(mode == "long-polling" && mode_changed) {
                if (ws && ws.readyState === WebSocket.OPEN) {
                    ws.close()
                }
                mode_changed = false;
                long_polling_update();
                console.log("long-polling");
            }
            if(mode == "websocket" && mode_changed) {
                mode_changed = false;
                websocket_update();
                console.log("websocket");
            }
        }
        setInterval(call_updates,5000);
        
        
        
    });
</script>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <div class = "col-md-4">
                <p>Hi, <?php echo $user["User"]; ?></p>
                <input type="text" id="fromUser" value=<?php echo $user["Id"]; ?> hidden />

                <p>Send message to:</p>
                <ul>
                    <?php
                        $msgs = mysqli_query($connect, "SELECT * FROM users where Id != '" . $_SESSION["userId"] . "'")
                        or die("failed to query database" . mysql_error());
                        while($msg = mysqli_fetch_assoc($msgs))
                        {
                            echo '<li><a href="?toUser=' . $msg["Id"] . '">' . $msg["User"] . '</a></li>';
                        }
                        
                    ?>
                </ul>
                <a href="index.php"> GO BACK </a>

                <p>Choose communication way: </p>
                <button class="btn btn btn-primary" onclick="mode = 'polling';mode_changed=true">Polling</button>
                <button class="btn btn btn-primary" onclick="mode = 'long-polling';mode_changed=true">Long polling</button>
                <button class="btn btn btn-primary" onclick="mode = 'websocket';mode_changed=true">Websocket</button>

            </div>
            <div class = "col-md-4">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h4><?php
                                if(isset($_GET["toUser"])) {
                                    $userName = mysqli_query($connect, "SELECT * FROM users WHERE Id='".$_GET["toUser"]."' AND Id !='".$_SESSION["userId"] ."'")
                                    or die("failed to query database" . mysql_error());
                                    $uName = mysqli_fetch_assoc($userName);
                                    echo '<input type="text" value=' . $_GET["toUser"] . ' id="toUser" hidden />';
                                    echo $uName["User"];  
                                }
                                else {
                                    $userName = mysqli_query($connect, "SELECT * FROM users WHERE Id !='".$_SESSION["userId"] ."'")
                                    or die("failed to query database" . mysql_error());
                                    $uName = mysqli_fetch_assoc($userName);
                                    $_SESSION["toUser"] = $uName["Id"];
                                    echo '<input type="text" value=' . $_SESSION["toUser"] . ' id="toUser" hidden />';
                                    echo $uName["User"];  
                                }
                            ?>
                            </h4>
                        </div>
                        <div class="modal-body">
                        <div class = "col-md-12" id="msgBody" style="height:400px;overflow-y:scroll;overflow-x:hidden;width:60vw;">
                        </div>
                        <div class ="modal-footer">
                            <textarea id="message" class="form-control" style="height:70px;"></textarea>
                            <button id="send" class="btn btn-primary" style="height:70%;">send</button>
                        </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class = "col-md-4">
                
            </div>
        </div>

    </div>
</body>
<script>
    function ajax_post() {
        if(mode != "websocket") {
            var xhr = new XMLHttpRequest();
            var url = "http://127.0.0.1:5000/polling/" + $("#toUser").val();
            let data = {
                "message":$("#message").val(),
                "fromUser":$("#fromUser").val(),
                "toUser":$("#toUser").val()
            }
            console.log("SENDING...")
            
            xhr.open("POST", url, true);
            xhr.setRequestHeader("Content-Type","application/json");
            xhr.onload = function() {
                if(xhr.status == 200) {
                    let output = "<div style = 'text-align:right'><p style='background-color:lightblue;;word-wrap:break-word;display:inline-block;padding:5px;border-radius:10px;max-width:70%;'>" + xhr.response + "</p></div>";
                    $("#message").val("");
                    $("#msgBody").append(output);
                }
            }
            xhr.send(JSON.stringify(data));
        }
        else{
            return;
        }
    }


</script>
</html>