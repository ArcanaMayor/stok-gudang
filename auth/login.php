<?php
session_start();
require_once __DIR__ . '/../config/database.php';

if (isLoggedIn()) {
    redirect(isAdmin() ? '/perpustakaan/admin/dashboard.php' : '/perpustakaan/user/dashboard.php');
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitize($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        $error = 'Username dan password harus diisi!';
    } else {
        try {
            $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? AND status = 'active'");
            $stmt->execute([$username]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['full_name'] = $user['full_name'];
                $_SESSION['role'] = $user['role'];

                redirect($user['role'] === 'admin' ? '/perpustakaan/admin/dashboard.php' : '/perpustakaan/user/dashboard.php');
            } else {
                $error = 'Username atau password salah!';
            }
        } catch (PDOException $e) {
            $error = 'Terjadi kesalahan: ' . $e->getMessage();
        }
    }
}

$page_title = 'Login';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Perpustakaan UKK26</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#4f46e5',
                        'primary-dark': '#4338ca',
                    }
                }
            }
        }
    </script>
    <script src="https://unpkg.com/@phosphor-icons/web"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { font-family: 'Inter', sans-serif; }
    </style>
</head>
<body class="bg-slate-50 min-h-screen flex flex-col justify-center py-12 sm:px-6 lg:px-8">
    <div class="sm:mx-auto sm:w-full sm:max-w-md">
        <div class="flex justify-center mb-6">
            <div class="w-16 h-16 bg-primary/10 rounded-2xl flex items-center justify-center text-primary shadow-sm border border-primary/20">
                <i class="ph-duotone ph-books text-4xl"></i>
            </div>
        </div>
        <h2 class="text-center text-3xl font-bold tracking-tight text-gray-900">
            Perpustakaan UKK26
        </h2>
        <p class="mt-2 text-center text-sm text-gray-600">
            Silakan masuk ke akun Anda
        </p>
    </div>

    <div class="mt-8 sm:mx-auto sm:w-full sm:max-w-md">
        <div class="bg-white py-8 px-4 shadow-xl shadow-slate-200/50 sm:rounded-2xl sm:px-10 border border-slate-100">
            <?php if ($error): ?>
            <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-xl mb-6 flex items-center gap-3 text-sm">
                <i class="ph-fill ph-warning-circle text-xl shrink-0"></i>
                <span class="font-medium"><?php echo htmlspecialchars($error); ?></span>
            </div>
            <?php endif; ?>

            <?php if ($success): ?>
            <div class="bg-emerald-50 border border-emerald-200 text-emerald-700 px-4 py-3 rounded-xl mb-6 flex items-center gap-3 text-sm">
                <i class="ph-fill ph-check-circle text-xl shrink-0"></i>
                <span class="font-medium"><?php echo htmlspecialchars($success); ?></span>
            </div>
            <?php endif; ?>

            <form class="space-y-6" method="POST">
                <div>
                    <label for="username" class="block text-sm font-medium text-gray-700">Username</label>
                    <div class="mt-2 relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-400">
                            <i class="ph ph-user text-lg"></i>
                        </div>
                        <input id="username" name="username" type="text" required placeholder="admin atau user1"
                            class="block w-full pl-10 rounded-xl border-0 py-2.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-primary sm:text-sm sm:leading-6 transition-all">
                    </div>
                </div>

                <div>
                    <label for="password" class="block text-sm font-medium text-gray-700">Password</label>
                    <div class="mt-2 relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-400">
                            <i class="ph ph-lock-key text-lg"></i>
                        </div>
                        <input id="password" name="password" type="password" required placeholder="••••••••"
                            class="block w-full pl-10 rounded-xl border-0 py-2.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-primary sm:text-sm sm:leading-6 transition-all">
                    </div>
                </div>

                <div>
                    <button type="submit"
                        class="flex w-full justify-center rounded-xl bg-primary px-3 py-3 text-sm font-semibold text-white shadow-sm hover:bg-primary-dark focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary transition-all active:scale-[0.98]">
                        Masuk
                    </button>
                </div>
            </form>

            <div class="mt-8 text-center text-sm text-gray-600">
                Belum punya akun?
                <a href="register.php" class="font-semibold text-primary hover:text-primary-dark transition-colors">Daftar sekarang</a>
            </div>
            
        </div>
    </div>
</body>
</html>
