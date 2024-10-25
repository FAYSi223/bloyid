<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

if (isset($_SESSION['valid'])) {
    header("Location: home.php");
    exit();
}

if (!file_exists("php/db.php")) {
    die("Error: db.php not found");
}

$con = require_once("php/db.php");

if(!$con) {
    die("Database connection failed");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="style/style.css">
    <title>Bloyid - Register</title>
</head>
<body>
    <div class="container">
        <div class="box form-box">
            <?php 
            if(isset($_POST['submit'])){
                try {
                    $username = filter_var($_POST['username'], FILTER_SANITIZE_STRING);
                    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
                    $age = !empty($_POST['age']) ? filter_var($_POST['age'], FILTER_SANITIZE_NUMBER_INT) : null;
                    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

                    $verify_stmt = $con->prepare("SELECT Email FROM users WHERE Email = ?");
                    $verify_stmt->execute([$email]);
                    
                    if($verify_stmt->rowCount() > 0){
                        echo "<div class='err-message'>
                              <p>This email is already registered!</p>
                          </div> <br>";
                        echo "<a href='javascript:self.history.back()'><button class='btn'>Go Back</button></a>";
                    } else {
                        $insert_stmt = $con->prepare("INSERT INTO users (Username, Email, Age, Password) VALUES (?, ?, ?, ?)");
                        $insert_stmt->execute([$username, $email, $age, $password]);
                        
                        if($insert_stmt->rowCount() > 0){
                            echo "<div class='succ-message'>
                                  <p>Registration successful!</p>
                              </div> <br>";
                            echo "<a href='index.php'><button class='btn'>Login Now</button></a>";
                        } else {
                            echo "<div class='err-message'>
                                  <p>Registration failed! Please try again.</p>
                              </div> <br>";
                            echo "<a href='javascript:self.history.back()'><button class='btn'>Go Back</button></a>";
                        }
                    }
                } catch(PDOException $e) {
                    echo "<div class='err-message'>
                            <p>Database Error: " . htmlspecialchars($e->getMessage()) . "</p>
                          </div> <br>";
                    echo "<a href='javascript:self.history.back()'><button class='btn'>Go Back</button></a>";
                }
            } else {
            ?>
            <header>Sign Up</header>
            <form action="" method="post">
                <div class="field input">
                    <label for="username">Username</label>
                    <input type="text" name="username" id="username" autocomplete="off" required>
                </div>

                <div class="field input">
                    <label for="email">Email</label>
                    <input type="email" name="email" id="email" autocomplete="off" required>
                </div>

                <div class="field input">
                    <label for="age">Age (Optional)</label>
                    <input type="number" name="age" id="age" autocomplete="off">
                </div>
                <div class="field input">
                    <label for="password">Password</label>
                    <input type="password" name="password" id="password" autocomplete="off" required>
                </div>

                <div class="field">
                    <input type="submit" class="btn" name="submit" value="Register" required>
                </div>
                <div class="links">
                    Already a member? <a href="index.php">Sign In</a>
                </div>
            </form>
            <?php } ?>
        </div>
    </div>
</body>
</html>