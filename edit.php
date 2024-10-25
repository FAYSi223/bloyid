<?php 
   session_start();

   include("php/db.php");
   if(!isset($_SESSION['valid'])){
    header("Location: index.php");
   }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="style/style.css">
    <title>Change Profile</title>
</head>
<body>
    <div class="nav">
        <div class="logo">
            <a href="home.php"><img class="edit-logo" src="img/logo.png" alt=""></a>
        </div>

        <div class="right-links">
            <a href="php/logout.php"> <button class="btn">Log Out</button> </a>
        </div>
    </div>
    <div class="container">
        <div class="box form-box">
            <?php 
               if(isset($_POST['submit'])){
                $username = $_POST['username'];
                $email = $_POST['email'];
                $age = $_POST['age'];

                $id = $_SESSION['id'];

                $edit_query = $con->prepare("UPDATE users SET Username = :username, Email = :email, Age = :age WHERE Id = :id");
                $edit_query->bindParam(':username', $username);
                $edit_query->bindParam(':email', $email);
                $edit_query->bindParam(':age', $age);
                $edit_query->bindParam(':id', $id);

                if($edit_query->execute()){
                    echo "<div class='succ-message'>
                    <p>Profile Updated!</p>
                </div> <br>";
                    echo "<a href='home.php'><button class='btn'>Go Home</button></a>";
                } else {
                    echo "<div class='err-message'>
                    <p>An error occurred while updating.</p>
                </div> <br>";
                }
               } else {

                $id = $_SESSION['id'];

                $query = $con->prepare("SELECT * FROM users WHERE Id = :id");
                $query->bindParam(':id', $id);
                $query->execute();

                $result = $query->fetch(PDO::FETCH_ASSOC);

                $res_Uname = $result['Username'];
                $res_Email = $result['Email'];
                $res_Age = $result['Age'];
            ?>
            <header>Change Profile</header>
            <form action="" method="post">
                <div class="field input">
                    <label for="username">Username</label>
                    <input type="text" name="username" id="username" value="<?php echo $res_Uname; ?>" autocomplete="off" required>
                </div>

                <div class="field input">
                    <label for="email">Email</label>
                    <input type="text" name="email" id="email" value="<?php echo $res_Email; ?>" autocomplete="off" required>
                </div>

                <div class="field input">
                    <label for="age">Age</label>
                    <input type="number" name="age" id="age" value="<?php echo $res_Age; ?>" autocomplete="off" required>
                </div>
                
                <div class="field">
                    
                    <input type="submit" class="btn" name="submit" value="Update" required>
                </div>
                
            </form>
        </div>
        <?php } ?>
      </div>
</body>
</html>
