<?php
// api/auth.php - API per autenticazione

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $action = $data['action'] ?? '';
    $isSecure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');

    if ($action === 'logout') {
        // Logout
        setcookie(session_name(), '', [
            'expires' => time() - 3600,
            'path' => '/',
            'secure' => $isSecure,
            'httponly' => true,
            'samesite' => 'Lax'
        ]);
        session_destroy();
        successResponse(['message' => 'Logout effettuato']);
    } else {
        // Login
        $username = $data['username'] ?? '';
        $password = $data['password'] ?? '';
        $remember = filter_var($data['remember'] ?? false, FILTER_VALIDATE_BOOLEAN);

        if (empty($username) || empty($password)) {
            errorResponse('Username e password richiesti');
        }

        try {
            $stmt = getDB()->prepare("SELECT * FROM users WHERE email = ? OR name = ?");
            $stmt->execute([$username, $username]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user && password_verify($password, $user['password_hash'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['name'];
                $_SESSION['user_role'] = $user['role'];

                if ($remember) {
                    setcookie(session_name(), session_id(), [
                        'expires' => time() + (REMEMBER_ME_DAYS * 86400),
                        'path' => '/',
                        'secure' => $isSecure,
                        'httponly' => true,
                        'samesite' => 'Lax'
                    ]);
                }

                successResponse(['user' => ['name' => $user['name'], 'role' => $user['role']]]);
            } else {
                errorResponse('Credenziali non valide', 401);
            }
        } catch (Exception $e) {
            logError("Login error: " . $e->getMessage());
            errorResponse('Errore interno', 500);
        }
    }
} else {
    errorResponse('Metodo non supportato', 405);
}
?>