<?php
session_start();
require_once 'config.php';
function getUserData($id) {
    $user = array();
    $user["id"] = $id;
    $user["email"] = "test@sidet.eu";
    $user["name"] = "testuser";
    $user["avatar"] = "https://avatar.com/".$id."/avatar.png";
    return $user;
}
if (isset($_SESSION["user_id"]) && $_SESSION["user_id"] != "") {
    $user = getUserData($_SESSION["user_id"]);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <title>Train Viewer</title>
</head>
<body onload="updateTrainTable()">
    <?php if (isset($_SESSION['user_id'])): $user = getUserData($_SESSION["user_id"]);?>
        <button class="sso-login" onclick="logout()">Logout</button>
        <p id="name"><?php echo htmlspecialchars($user["name"]); ?></p>
        <img id="avatar" src="<?php echo htmlspecialchars($user["avatar"]); ?>" alt=""><br>
    <?php else: ?>
        <button class="sso-login" onclick="login()">Login</button><br>
    <?php endif; ?>
    <div class="search-bar">
        <input type="text" placeholder="Search... (NOT YET IMPLEMENTED!)" disabled>
        <button type="submit"><i class="fas fa-search"></i></button>
    </div>
    <br>
    <div id="trains" class="main">
        <!-- every row will be it's own div -->

    </div>
</body>
<script src="script.js"></script>
</html>