<?php
session_start();

if (isset($_SESSION['user_id'])) {
    if ($_SESSION['role'] === 'admin') {
        header('Location: /gallery.php');
    } else {
        header('Location: /login.php?error=unauthorized');
    }
    exit;
}

$loginResult = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['id']) && !empty($_POST['pw'])) {
    require_once 'config.php';
    require_once 'api.php';

    $id = trim($_POST['id']);
    $pw = $_POST['pw'];

    $id = $db->quote($id);
    $sql = "SELECT * FROM users WHERE username = %s AND password = ?";
    $query = sprintf($sql, $id);

    try {
        $result = executeQuery($db, $query, $pw);
        
        if ($data = $result->fetch(PDO::FETCH_ASSOC)) {
            if ($data['role'] === 'admin') {
                $_SESSION['user_id'] = $data['id'];
                $_SESSION['username'] = $data['username'];
                $_SESSION['email'] = $data['email'];
                $_SESSION['role'] = $data['role'];
                $_SESSION['login_time'] = time();
                
                header('Location: /gallery.php');
                exit;
            } else {
                $loginResult = '<div class="alert error">Access denied: Admin privileges required.</div>';
            }
        } else {
            $loginResult = '<div class="alert error">Authentication failed: Please check your credentials.</div>';
        }
    } catch (Exception $e) {
        $loginResult = '<div class="alert error">System error occurred.</div>';
    }
}

if (isset($_GET['error']) && $_GET['error'] === 'unauthorized') {
    $loginResult = '<div class="alert error">Access denied: Admin privileges required.</div>';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Media Server Login</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', system-ui, sans-serif;
            background-color: #000000;
            color: #e1e1e1;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .login-container {
            background-color: #111111;
            border: 1px solid #333333;
            border-radius: 8px;
            padding: 40px;
            width: 100%;
            max-width: 400px;
        }

        .logo {
            text-align: center;
            margin-bottom: 32px;
        }

        .logo h1 {
            font-size: 24px;
            font-weight: 300;
            color: #ffffff;
            margin-bottom: 8px;
            letter-spacing: 0.5px;
        }

        .logo p {
            font-size: 14px;
            color: #666666;
            font-weight: 400;
        }

        .form-group {
            margin-bottom: 24px;
        }

        label {
            display: block;
            font-size: 14px;
            font-weight: 500;
            color: #cccccc;
            margin-bottom: 8px;
        }

        input[type="text"],
        input[type="password"] {
            width: 100%;
            padding: 12px;
            background-color: #222222;
            border: 1px solid #444444;
            border-radius: 4px;
            color: #ffffff;
            font-size: 14px;
            transition: all 0.2s ease;
        }

        input[type="text"]:focus,
        input[type="password"]:focus {
            outline: none;
            border-color: #666666;
            background-color: #333333;
        }

        input::placeholder {
            color: #888888;
        }

        .submit-btn {
            width: 100%;
            padding: 12px;
            background-color: #333333;
            border: 1px solid #555555;
            border-radius: 4px;
            color: #ffffff;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .submit-btn:hover {
            background-color: #444444;
        }

        .alert {
            padding: 12px;
            border-radius: 4px;
            margin-bottom: 24px;
            font-size: 14px;
        }

        .alert.error {
            background-color: rgba(220, 38, 38, 0.1);
            border: 1px solid rgba(220, 38, 38, 0.3);
            color: #fca5a5;
        }

        .footer {
            text-align: center;
            margin-top: 32px;
            padding-top: 24px;
            border-top: 1px solid #333333;
        }

        .footer p {
            font-size: 12px;
            color: #666666;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="logo">
            <h1>Media Server</h1>
            <p>Secure Access Portal</p>
        </div>

        <?php if (!empty($loginResult)) echo $loginResult; ?>

        <form method="POST">
            <div class="form-group">
                <label for="id">Username</label>
                <input type="text" id="id" name="id" placeholder="Enter username" required>
            </div>

            <div class="form-group">
                <label for="pw">Password</label>
                <input type="password" id="pw" name="pw" placeholder="Enter password" required>
            </div>

            <button type="submit" class="submit-btn">Login</button>
        </form>

        <div class="footer">
            <p>Authorized Access Only</p>
        </div>
    </div>
</body>
</html>