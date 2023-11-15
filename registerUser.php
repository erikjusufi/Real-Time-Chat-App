<?php
session_start();
include("DBConnection.php");
include("links.php");

if(isset($_POST["username"]))
{
    $sql = "INSERT INTO users (User) VALUES('" . $_POST["username"] . "')";
    if ($connect->query($sql))
        header('Location: index.php');
    else
        echo "Error. Please Try Again";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=ž, initial-scale=1.0">
    <title></title>
</head>
<body>
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h4>Register your name</h4>
            </div>
            <div class="modal-body">
                <form action = "registerUser.php" method="POST">
                    <p>Name</p>
                    <input type="text" name="username" id="username" class="form-cointrol" autocomplete="off">
                    <br>
                    <input type="submit" name="submit" class="btn btn-primary" style="float:right;" value="OK"/>
                </form>
            </div>
        </div>
    </div>
</body>
</html>