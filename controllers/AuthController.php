<?php
// controllers/AuthController.php
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../services/MailService.php';
require_once __DIR__ . '/../models/DB.php';

class AuthController {
    private $userModel;
    private $mailService;
    public function __construct() {
        $this->userModel = new User();
        $this->mailService = new MailService();
        if (session_status()===PHP_SESSION_NONE) session_start();
    }

    public function login($data) {
        $email = trim($data['email'] ?? '');
        $password = $data['password'] ?? '';
        $user = $this->userModel->verifyCredentials($email, $password);
        if ($user) {
            session_regenerate_id(true);
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_role'] = $user['role'];
            set_flash("Welcome back, " . ($user['name'] ?? $user['email']));
            header("Location: index.php?action=tickets");
            exit;
        } else {
            $error = "Invalid credentials";
            require __DIR__ . '/../views/login.php';
        }
    }

    public function register($data) {
        $email = trim($data['email'] ?? '');
        $password = $data['password'] ?? '';
        $name = trim($data['name'] ?? '');
        // basic checks
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Invalid email';
            require __DIR__ . '/../views/register.php';
            return;
        }
        if (strlen($password) < 6) {
            $error = 'Password too short';
            require __DIR__ . '/../views/register.php';
            return;
        }
        if ($this->userModel->findByEmail($email)) {
            $error = 'Email already registered';
            require __DIR__ . '/../views/register.php';
            return;
        }
        $this->userModel->create($email, $password, $name, 'user');
        set_flash('Registration successful. Please log in.');
        header('Location: index.php?action=login');
        exit;
    }

    // Password reset: generate token & email link
    public function sendPasswordReset($email) {
        $user = $this->userModel->findByEmail($email);
        if (!$user) {
            set_flash('If an account exists, a reset link was sent.');
            header('Location: index.php?action=forgot_password');
            exit;
        }
        // create token
        $token = bin2hex(random_bytes(24));
        $expires = (new DateTime('+1 hour'))->format('Y-m-d H:i:s');

        // store in DB
        $pdo = DB::getInstance();
        $stmt = $pdo->prepare("INSERT INTO password_resets (user_id, token, expires_at) VALUES (:uid, :token, :exp)");
        $stmt->execute([':uid'=>$user['id'], ':token'=>$token, ':exp'=>$expires]);

        // send email with link
        $link = (isset($_SERVER['HTTPS'])?'https://':'http://') . $_SERVER['HTTP_HOST'] . dirname($_SERVER['SCRIPT_NAME']) . "/index.php?action=reset_password&token=" . $token;
        $body = "Hello,\n\nTo reset your password, click the link below (valid 1 hour):\n\n{$link}\n\nIf you did not request this, ignore this message.";
        $this->mailService->send($user['email'], 'Password reset', $body);

        set_flash('If an account exists, a reset link was sent.');
        header('Location: index.php?action=forgot_password');
        exit;
    }

    public function resetPassword($post) {
        $token = $post['token'] ?? '';
        $new = $post['password'] ?? '';

        if (strlen($new) < 6) {
            $error = 'Password too short';
            require __DIR__ . '/../views/reset_password.php';
            return;
        }
        $pdo = DB::getInstance();
        $stmt = $pdo->prepare("SELECT * FROM password_resets WHERE token = :t AND used = 0 AND expires_at >= NOW() LIMIT 1");
        $stmt->execute([':t'=>$token]);
        $row = $stmt->fetch();
        if (!$row) {
            $error = 'Invalid or expired token';
            require __DIR__ . '/../views/reset_password.php';
            return;
        }
        // update user password
        $hash = password_hash($new, PASSWORD_DEFAULT);
        $stmt2 = $pdo->prepare("UPDATE users SET password_hash = :ph WHERE id = :id");
        $stmt2->execute([':ph'=>$hash, ':id'=>$row['user_id']]);

        // mark token used
        $stmt3 = $pdo->prepare("UPDATE password_resets SET used = 1 WHERE id = :id");
        $stmt3->execute([':id'=>$row['id']]);

        set_flash('Password updated. You can now log in.');
        header('Location: index.php?action=login');
        exit;
    }

    public function logout() {
        session_unset();
        session_destroy();
        header("Location: index.php?action=login");
        exit;
    }
}
