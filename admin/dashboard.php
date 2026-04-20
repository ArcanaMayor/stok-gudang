<?php
session_start();
require_once __DIR__ . '/../config/database.php';

checkAdmin();

$page_title = 'Dashboard';

// Get Statistics
$stats = [
    'total_books'   => 0,
    'total_users'   => 0,
    'total_loans'   => 0,
    'overdue_loans' => 0,
];

try {
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM books");
    $stats['total_books'] = $stmt->fetch()['count'];

    $stmt = $pdo->query("SELECT COUNT(*) as count FROM users WHERE role = 'user'");
    $stats['total_users'] = $stmt->fetch()['count'];

    $stmt = $pdo->query("SELECT COUNT(*) as count FROM loans WHERE status = 'borrowed'");
    $stats['total_loans'] = $stmt->fetch()['count'];

    $stmt = $pdo->query("SELECT COUNT(*) as count FROM loans WHERE status IN ('borrowed', 'overdue') AND due_date < CURDATE()");
    $stats['overdue_loans'] = $stmt->fetch()['count'];

    // Recent Loans
    $stmt = $pdo->query("
        SELECT l.*, u.full_name, b.title, b.cover_image
        FROM loans l
        JOIN users u ON l.user_id = u.id
        JOIN books b ON l.book_id = b.id
        ORDER BY l.created_at DESC
        LIMIT 5
    ");
    $recent_loans = $stmt->fetchAll();

    // Popular Books
    $stmt = $pdo->query("
        SELECT b.*, a.name as author_name, COUNT(l.id) as loan_count
        FROM books b
        LEFT JOIN authors a ON b.author_id = a.id
        LEFT JOIN loans l ON b.id = l.book_id
        GROUP BY b.id
        ORDER BY loan_count DESC
        LIMIT 5
    ");
    $popular_books = $stmt->fetchAll();

    // Pending Reviews
    $pending_reviews = $pdo->query("SELECT COUNT(*) FROM reviews WHERE status = 'pending'")->fetchColumn();

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="flex">
    <?php require_once __DIR__ . '/../includes/sidebar.php'; ?>

    <!-- Main Content -->
    <div class="flex-1 ml-64 min-h-screen bg-gray-50 dark:bg-gray-900">
        <!-- Top Navbar -->
        <nav class="navbar sticky top-0 z-20">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between items-center h-16">
                    <div class="flex items-center gap-4">
                        <button class="lg:hidden" onclick="toggleSidebar()">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                            </svg>
                        </button>
                        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Dashboard Admin</h1>
                    </div>
                    <div class="flex items-center gap-4 relative group">
                        <div class="flex items-center gap-3 bg-white dark:bg-slate-800 border border-gray-200 dark:border-slate-700 py-1.5 px-3 rounded-full hover:bg-gray-50 dark:hover:bg-slate-700 transition cursor-pointer shadow-sm">
                            <div class="w-8 h-8 rounded-full bg-primary/10 text-primary flex items-center justify-center font-bold text-sm shrink-0">
                                <?php
                                    $initials = '';
                                    $name_parts = explode(' ', $_SESSION['full_name'] ?? 'Admin');
                                    foreach (array_slice($name_parts, 0, 2) as $part) {
                                        $initials .= strtoupper(substr($part, 0, 1));
                                    }
                                    echo htmlspecialchars($initials);
                                ?>
                            </div>
                            <div class="text-left pr-1">
                                <p class="text-sm font-semibold text-gray-900 dark:text-white leading-tight"><?php echo htmlspecialchars($_SESSION['full_name']); ?></p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">Administrator</p>
                            </div>
                            <i class="ph ph-caret-down text-gray-500 ml-1"></i>
                        </div>

                        <!-- Dropdown Menu -->
                        <div class="absolute right-0 top-full mt-2 w-48 bg-white dark:bg-slate-800 border border-gray-200 dark:border-slate-700 rounded-xl shadow-lg opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-200 z-50">
                            <div class="p-1">
                                <a href="/perpustakaan/auth/logout.php" class="flex items-center gap-2 px-3 py-2 text-sm text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg transition-colors">
                                    <i class="ph ph-sign-out text-lg"></i>
                                    <span class="font-medium">Keluar</span>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </nav>

        <!-- Content -->
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <!-- Statistics Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                <div class="stat-card flex items-center gap-4">
                    <div class="w-14 h-14 rounded-full bg-primary/10 flex items-center justify-center text-primary shrink-0">
                        <i class="ph ph-books text-2xl"></i>
                    </div>
                    <div>
                        <p class="stat-label !mt-0">Total Buku</p>
                        <p class="stat-number"><?php echo $stats['total_books']; ?></p>
                    </div>
                </div>

                <div class="stat-card flex items-center gap-4">
                    <div class="w-14 h-14 rounded-full bg-emerald-500/10 flex items-center justify-center text-emerald-500 shrink-0">
                        <i class="ph ph-users text-2xl"></i>
                    </div>
                    <div>
                        <p class="stat-label !mt-0">Total Pengguna</p>
                        <p class="stat-number"><?php echo $stats['total_users']; ?></p>
                    </div>
                </div>

                <div class="stat-card flex items-center gap-4">
                    <div class="w-14 h-14 rounded-full bg-amber-500/10 flex items-center justify-center text-amber-500 shrink-0">
                        <i class="ph ph-tray-arrow-up text-2xl"></i>
                    </div>
                    <div>
                        <p class="stat-label !mt-0">Peminjaman Aktif</p>
                        <p class="stat-number"><?php echo $stats['total_loans']; ?></p>
                    </div>
                </div>

                <div class="stat-card flex items-center gap-4">
                    <div class="w-14 h-14 rounded-full bg-red-500/10 flex items-center justify-center text-red-500 shrink-0">
                        <i class="ph ph-warning-circle text-2xl"></i>
                    </div>
                    <div>
                        <p class="stat-label !mt-0">Terlambat</p>
                        <p class="stat-number"><?php echo $stats['overdue_loans']; ?></p>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                <!-- Recent Loans -->
                <div class="card p-6">
                    <div class="flex items-center justify-between mb-6">
                        <h2 class="text-xl font-bold text-gray-900 dark:text-white">Peminjaman Terbaru</h2>
                        <a href="/perpustakaan/admin/books.php" class="text-blue-600 hover:text-blue-700 text-sm font-500">Lihat Semua →</a>
                    </div>

                    <div class="space-y-4">
                        <?php foreach ($recent_loans as $loan): ?>
                        <div class="flex items-center justify-between p-4 border border-gray-200 dark:border-gray-700 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-800 transition">
                            <div class="flex items-center gap-4">
                                <div class="w-12 h-12 bg-primary/10 text-primary rounded-lg flex items-center justify-center font-bold text-sm shrink-0">
                                    <?php echo strtoupper(substr($loan['title'], 0, 1)); ?>
                                </div>
                                <div>
                                    <p class="font-500 text-gray-900 dark:text-white text-sm"><?php echo htmlspecialchars(substr($loan['title'], 0, 30)); ?></p>
                                    <p class="text-xs text-gray-500"><?php echo htmlspecialchars($loan['full_name']); ?></p>
                                </div>
                            </div>
                            <div class="text-right">
                                <span class="badge badge-primary"><?php echo $loan['status'] === 'borrowed' ? 'Dipinjam' : 'Dikembalikan'; ?></span>
                                <p class="text-xs text-gray-500 mt-1"><?php echo formatDate($loan['due_date']); ?></p>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Popular Books -->
                <div class="card p-6">
                    <div class="flex items-center justify-between mb-6">
                        <h2 class="text-xl font-bold text-gray-900 dark:text-white">Buku Populer</h2>
                        <a href="/perpustakaan/admin/books.php" class="text-blue-600 hover:text-blue-700 text-sm font-500">Lihat Semua →</a>
                    </div>

                    <div class="space-y-4">
                        <?php foreach ($popular_books as $book): ?>
                        <div class="flex items-center justify-between p-4 border border-gray-200 dark:border-gray-700 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-800 transition">
                            <div class="flex items-center gap-4">
                                <div class="w-12 h-12 bg-primary/10 text-primary rounded-lg flex items-center justify-center font-bold text-sm shrink-0">
                                    <?php echo strtoupper(substr($book['title'], 0, 1)); ?>
                                </div>
                                <div>
                                    <p class="font-500 text-gray-900 dark:text-white text-sm"><?php echo htmlspecialchars($book['title']); ?></p>
                                    <p class="text-xs text-gray-500"><?php echo htmlspecialchars($book['author_name']); ?></p>
                                </div>
                            </div>
                            <div class="text-right">
                                <span class="font-bold text-lg text-blue-600"><?php echo $book['loan_count']; ?></span>
                                <p class="text-xs text-gray-500">peminjaman</p>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="mt-8 card p-6">
                <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-6">Aksi Cepat</h2>
                <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
                    <a href="/perpustakaan/admin/books.php" class="p-6 bg-white dark:bg-slate-800 border border-gray-200 dark:border-slate-700 rounded-xl hover:border-primary hover:bg-primary/5 transition flex flex-col items-center justify-center gap-3">
                        <i class="ph ph-books text-3xl text-primary"></i>
                        <p class="font-medium text-gray-900 dark:text-white text-sm text-center">Kelola Buku</p>
                    </a>
                    <a href="/perpustakaan/admin/categories.php" class="p-6 bg-white dark:bg-slate-800 border border-gray-200 dark:border-slate-700 rounded-xl hover:border-primary hover:bg-primary/5 transition flex flex-col items-center justify-center gap-3">
                        <i class="ph ph-tag text-3xl text-primary"></i>
                        <p class="font-medium text-gray-900 dark:text-white text-sm text-center">Kategori</p>
                    </a>
                    <a href="/perpustakaan/admin/users.php" class="p-6 bg-white dark:bg-slate-800 border border-gray-200 dark:border-slate-700 rounded-xl hover:border-primary hover:bg-primary/5 transition flex flex-col items-center justify-center gap-3">
                        <i class="ph ph-users text-3xl text-primary"></i>
                        <p class="font-medium text-gray-900 dark:text-white text-sm text-center">Pengguna</p>
                    </a>
                    <a href="/perpustakaan/admin/publishers.php" class="p-6 bg-white dark:bg-slate-800 border border-gray-200 dark:border-slate-700 rounded-xl hover:border-primary hover:bg-primary/5 transition flex flex-col items-center justify-center gap-3">
                        <i class="ph ph-buildings text-3xl text-primary"></i>
                        <p class="font-medium text-gray-900 dark:text-white text-sm text-center">Penerbit</p>
                    </a>
                    <a href="/perpustakaan/admin/reviews.php?status=pending" class="p-6 bg-white dark:bg-slate-800 border border-gray-200 dark:border-slate-700 rounded-xl hover:border-amber-400 hover:bg-amber-50 dark:hover:bg-amber-900/10 transition flex flex-col items-center justify-center gap-3 relative">
                        <i class="ph ph-star text-3xl text-amber-500"></i>
                        <p class="font-medium text-gray-900 dark:text-white text-sm text-center">Review</p>
                        <?php if (!empty($pending_reviews) && $pending_reviews > 0): ?>
                        <span class="absolute top-3 right-3 text-xs bg-amber-500 text-white font-bold w-5 h-5 rounded-full flex items-center justify-center"><?php echo $pending_reviews; ?></span>
                        <?php endif; ?>
                    </a>
                </div>
            </div>

        </div>

            <!-- Footer -->
            <?php require_once __DIR__ . '/../includes/footer.php'; ?>
    </div>
</div>
</body>
</html>
