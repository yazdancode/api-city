<div style="width: 50%; margin: auto; line-height: 36px; zoom: 2">
    <form action="" method="post">
        <input type="email" name="email" placeholder="Email" required>
        <input type="submit" name="submit" value="Generate Token">
    </form>

    <?php
    include_once '../loader.php';

    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        $email = $_POST['email'];

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            echo "<p>Invalid email format.</p>";
            return;
        }

        $user = getUserById($email); // فرض بر این است که یک stdClass object برمی‌گرداند

        if ($user) {
            echo "<p>User found: " . htmlspecialchars($user->name) . "</p>";
            $jwt = createapitoken($user);
//            $jwt = 111;
            echo "<p>Token: <code>" . htmlspecialchars($jwt) . "</code></p>";
        } else {
            echo "<p>No user found with this email.</p>";
        }
    }
    ?>
</div>
