<?php
session_start();
require_once __DIR__ . '/../config/database.php';

checkUser();

$page_title = 'Pengembalian Buku';
$message = '';
$error = '';

$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    
    if ($action === 'return') {
        $loan_id = (int)($_POST['loan_id'] ?? 0);

        if ($loan_id <= 0) {
            $error = 'Pilih peminjaman terlebih dahulu!';
        } else {
            try {
                $stmt = $pdo->prepare("SELECT * FROM loans WHERE id = ? AND user_id = ? AND status = 'borrowed'");
                $stmt->execute([$loan_id, $user_id]);
                $loan = $stmt->fetch();

                if (!$loan) {
                    $error = 'Peminjaman tidak ditemukan!';
                } else {
                    $return_date = date('Y-m-d');
                    $new_status = strtotime($return_date) > strtotime($loan['due_date']) ? 'overdue' : 'returned';

                    $stmt = $pdo->prepare("UPDATE loans SET return_date = ?, status = ? WHERE id = ?");
                    $stmt->execute([$return_date, 'returned', $loan_id]);

                    $stmt = $pdo->prepare("UPDATE books SET available_stock = available_stock + 1 WHERE id = ?");
                    $stmt->execute([$loan['book_id']]);

                    $message = 'Buku berhasil dikembalikan!';
                }
            } catch (PDOException $e) {
                $error = 'Error: ' . $e->getMessage();
            }
        }
    }
}


try {
    $stmt = $pdo->prepare("
        SELECT l.*, b.title, b.cover_image, a.name as author_name
        FROM loans l
        JOIN books b ON l.book_id = b.id
        JOIN authors a ON b.author_id = a.id
        WHERE l.user_id = ? AND l.status = 'borrowed'
        ORDER BY l.due_date ASC
    ");
    $stmt->execute([$user_id]);
    $current_loans = $stmt->fetchAll();

    $stmt = $pdo->prepare("
        SELECT l.*, b.title, a.name as author_name
        FROM loans l
        JOIN books b ON l.book_id = b.id
        JOIN authors a ON b.author_id = a.id
        WHERE l.user_id = ? AND l.status IN ('returned', 'overdue')
        ORDER BY l.return_date DESC
        LIMIT 10
    ");
    $stmt->execute([$user_id]);
    $past_loans = $stmt->fetchAll();

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="flex">
    <?php require_once __DIR__ . '/../includes/sidebar.php'; ?>

    <div class="flex-1 ml-64 min-h-screen bg-gray-50 dark:bg-gray-900">
        <nav class="navbar sticky top-0 z-20">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between items-center h-16">
                    <div class="flex items-center gap-4">
                        <button class="lg:hidden" onclick="toggleSidebar()">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                            </svg>
                        </button>
                        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Pengembalian Buku</h1>
                    </div>
                    <div class="flex items-center gap-4">
                    </div>
                </div>
            </div>
        </nav>

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <?php if ($message): ?>
            <div class="alert alert-success mb-6">
                <i class="ph-fill ph-check-circle text-xl"></i>
                <span><?php echo htmlspecialchars($message); ?></span>
            </div>
            <?php endif; ?>

            <?php if ($error): ?>
            <div class="alert alert-danger mb-6">
                <i class="ph-fill ph-warning-circle text-xl"></i>
                <span><?php echo htmlspecialchars($error); ?></span>
            </div>
            <?php endif; ?>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <div class="lg:col-span-1">
                    <div class="card p-6">
                        <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4">Kembalikan Buku</h2>

                        <form method="POST" id="returnForm">
                            <input type="hidden" name="action" value="return">

                            <div class="form-group mb-4">
                                <label>Pilih Buku yang Dikembalikan *</label>
                                <select name="loan_id" id="loan_id" required>
                                    <option value="">-- Pilih Buku --</option>
                                    <?php foreach ($current_loans as $loan): ?>
                                    <option value="<?php echo $loan['id']; ?>">
                                        <?php echo htmlspecialchars(substr($loan['title'], 0, 40)); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="p-4 bg-green-50 dark:bg-green-900 rounded-lg mb-4">
                                <p class="text-xs text-green-600 dark:text-green-300">
                                    <strong>Info:</strong> Pastikan buku dalam kondisi baik sebelum dikembalikan.
                                </p>
                            </div>

                            <button type="submit" class="w-full bg-primary hover:bg-primary-dark text-white font-semibold py-3 px-4 rounded-xl transition-all shadow-sm flex items-center justify-center gap-2" <?php echo count($current_loans) === 0 ? 'disabled' : ''; ?>>
                                <i class="ph ph-tray-arrow-down text-xl"></i> Kembalikan Buku
                            </button>
                        </form>
                    </div>
                </div>

                <div class="lg:col-span-2">
                    <div class="card p-6 mb-8">
                        <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-6">Buku yang Harus Dikembalikan (<?php echo count($current_loans); ?>)</h2>

                        <?php if (count($current_loans) > 0): ?>
                        <div class="space-y-4">
                            <?php foreach ($current_loans as $loan): ?>
                            <div class="p-4 border-2 border-yellow-200 dark:border-yellow-700 rounded-lg bg-yellow-50 dark:bg-yellow-900/30">
                                <div class="flex items-start justify-between mb-3">
                                    <div class="flex items-start gap-4">
                                        <div class="w-16 h-20 bg-primary/10 rounded-lg flex items-center justify-center text-primary text-3xl flex-shrink-0 border border-primary/20"><i class="ph-duotone ph-book"></i></div>
                                        <div class="flex-1">
                                            <h3 class="font-500 text-gray-900 dark:text-white"><?php echo htmlspecialchars(substr($loan['title'], 0, 50)); ?></h3>
                                            <p class="text-sm text-gray-500"><?php echo htmlspecialchars($loan['author_name']); ?></p>
                                        </div>
                                    </div>
                                    <div class="text-right">
                                        <?php
                                        $days_left = (strtotime($loan['due_date']) - time()) / 86400;
                                        if ($days_left < 0) {
                                            echo '<span class="badge badge-danger flex items-center gap-1"><i class="ph-fill ph-clock"></i> Terlambat ' . abs(ceil($days_left)) . ' hari</span>';
                                        } elseif ($days_left < 3) {
                                            echo '<span class="badge badge-warning">' . ceil($days_left) . ' hari lagi</span>';
                                        } else {
                                            echo '<span class="badge badge-primary">' . ceil($days_left) . ' hari lagi</span>';
                                        }
                                        ?>
                                    </div>
                                </div>

                                <div class="grid grid-cols-3 gap-4 text-sm mb-3 pt-3 border-t border-yellow-200 dark:border-yellow-700">
                                    <div>
                                        <p class="text-gray-500 text-xs">Tanggal Pinjam</p>
                                        <p class="font-500"><?php echo formatDate($loan['loan_date']); ?></p>
                                    </div>
                                    <div>
                                        <p class="text-gray-500 text-xs">Batas Kembali</p>
                                        <p class="font-500 <?php echo strtotime($loan['due_date']) < time() ? 'text-red-600' : ''; ?>">
                                            <?php echo formatDate($loan['due_date']); ?>
                                        </p>
                                    </div>
                                    <div class="text-right">
                                        <form method="POST" class="inline">
                                            <input type="hidden" name="action" value="return">
                                            <input type="hidden" name="loan_id" value="<?php echo $loan['id']; ?>">
                                            <button type="submit" class="btn btn-sm btn-success">Kembalikan</button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <?php else: ?>
                        <div class="text-center py-12">
                            <p class="text-gray-500 dark:text-gray-400 text-lg">✅ Anda tidak memiliki buku yang dipinjam</p>
                        </div>
                        <?php endif; ?>
                    </div>

                    <div class="card p-6">
                        <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-6">Riwayat Pengembalian</h2>

                        <?php if (count($past_loans) > 0): ?>
                        <div class="space-y-4">
                            <?php foreach ($past_loans as $loan): ?>
                            <div class="p-4 border border-gray-200 dark:border-gray-700 rounded-lg">
                                <div class="flex items-start justify-between mb-2">
                                    <div>
                                        <h3 class="font-500 text-gray-900 dark:text-white"><?php echo htmlspecialchars(substr($loan['title'], 0, 50)); ?></h3>
                                        <p class="text-sm text-gray-500"><?php echo htmlspecialchars($loan['author_name']); ?></p>
                                    </div>
                                    <?php if ($loan['status'] === 'overdue'): ?>
                                        <span class="badge badge-danger">Terlambat</span>
                                    <?php else: ?>
                                        <span class="badge badge-success">Dikembalikan</span>
                                    <?php endif; ?>
                                </div>
                                <div class="grid grid-cols-3 gap-4 text-xs text-gray-500 pt-3 border-t border-gray-200 dark:border-gray-700">
                                    <div>
                                        <p class="font-500 text-gray-900 dark:text-white"><?php echo formatDate($loan['loan_date']); ?></p>
                                        <p>Pinjam</p>
                                    </div>
                                    <div>
                                        <p class="font-500 text-gray-900 dark:text-white"><?php echo formatDate($loan['due_date']); ?></p>
                                        <p>Batas</p>
                                    </div>
                                    <div>
                                        <p class="font-500 text-gray-900 dark:text-white"><?php echo formatDate($loan['return_date']); ?></p>
                                        <p>Dikembalikan</p>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <?php else: ?>
                        <div class="text-center py-8">
                            <p class="text-gray-500 dark:text-gray-400">Belum ada riwayat pengembalian</p>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <?php require_once __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>
