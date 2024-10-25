<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

$con = require_once("php/db.php");

if(!$con) {
    die("Database connection failed");
}

if(isset($_SESSION['valid'])) {
    header("Location: home.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="style/style.css">
    <title>Bloyid - Login</title>
</head>
<body>
    <div class="container">
        <div class="box form-box">
            <?php 
            if(isset($_POST['submit'])){
                try {
                    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
                    $password = $_POST['password'];

                    $stmt = $con->prepare("SELECT * FROM users WHERE Email = ?");
                    $stmt->execute([$email]);
                    $user = $stmt->fetch(PDO::FETCH_ASSOC);

                    if($user && password_verify($password, $user['Password'])) {
                        $_SESSION['valid'] = $user['Email'];
                        $_SESSION['username'] = $user['Username'];
                        $_SESSION['age'] = $user['Age'];
                        $_SESSION['id'] = $user['id'];
                        
                        session_regenerate_id(true);
                        header("Location: home.php");
                        exit();
                    } else {
                        echo "<div class='err-message'>
                                <p>Wrong Email or Password</p>
                              </div><br>";
                        echo "<a href='index.php'><button class='btn'>Go Back</button></a>";
                    }
                } catch(PDOException $e) {
                    echo "<div class='err-message'>
                            <p>Database Error: " . htmlspecialchars($e->getMessage()) . "</p>
                          </div><br>";
                    echo "<a href='index.php'><button class='btn'>Go Back</button></a>";
                }
            } else {
            ?>
            <header>Login</header>
            <form action="" method="post">
                <div class="field input">
                    <label for="email">Email</label>
                    <input type="email" name="email" id="email" autocomplete="off" required>
                </div>

                <div class="field input">
                    <label for="password">Password</label>
                    <input type="password" name="password" id="password" autocomplete="off" required>
                </div>

                <div class="field">
                    <input type="submit" class="btn" name="submit" value="Login" required>
                </div>
                <div class="links">
                    Don't have account? <a href="register.php">Sign Up Now</a>
                </div>
            </form>
            <?php } ?>
        </div>
    </div>
</body>
</html>
