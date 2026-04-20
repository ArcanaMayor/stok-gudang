<?php
session_start();
require_once __DIR__ . '/../config/database.php';

checkAdmin();

$page_title = 'Manajemen Review';
$message = '';
$error = '';

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action   = $_POST['action'];
    $review_id = (int)($_POST['review_id'] ?? 0);

    if ($review_id <= 0) {
        $error = 'Review tidak valid!';
    } else {
        try {
            if ($action === 'approve') {
                $stmt = $pdo->prepare("UPDATE reviews SET status = 'approved' WHERE id = ?");
                $stmt->execute([$review_id]);
                $message = 'Review berhasil disetujui!';
            } elseif ($action === 'reject') {
                $stmt = $pdo->prepare("UPDATE reviews SET status = 'rejected' WHERE id = ?");
                $stmt->execute([$review_id]);
                $message = 'Review telah ditolak.';
            } elseif ($action === 'delete') {
                $stmt = $pdo->prepare("DELETE FROM reviews WHERE id = ?");
                $stmt->execute([$review_id]);
                $message = 'Review berhasil dihapus!';
            }
        } catch (PDOException $e) {
            $error = 'Error: ' . $e->getMessage();
        }
    }
}

// Filters
$status_filter = $_GET['status'] ?? 'all';
$search        = trim($_GET['search'] ?? '');
$page          = max(1, (int)($_GET['page'] ?? 1));
$per_page      = 10;
$offset        = ($page - 1) * $per_page;

// Build query
$where_clauses = [];
$params        = [];

if ($status_filter !== 'all') {
    $where_clauses[] = 'r.status = ?';
    $params[]        = $status_filter;
}
if ($search !== '') {
    $where_clauses[] = '(b.title LIKE ? OR u.full_name LIKE ? OR r.comment LIKE ?)';
    $like            = "%$search%";
    $params[]        = $like;
    $params[]        = $like;
    $params[]        = $like;
}

$where_sql = $where_clauses ? 'WHERE ' . implode(' AND ', $where_clauses) : '';

try {
    // Total count
    $count_stmt = $pdo->prepare("SELECT COUNT(*) FROM reviews r JOIN books b ON r.book_id = b.id JOIN users u ON r.user_id = u.id $where_sql");
    $count_stmt->execute($params);
    $total_reviews = $count_stmt->fetchColumn();
    $total_pages   = ceil($total_reviews / $per_page);

    // Reviews
    $stmt = $pdo->prepare("
        SELECT r.*, b.title as book_title, b.id as book_id,
               u.full_name, a.name as author_name
        FROM reviews r
        JOIN books b  ON r.book_id  = b.id
        JOIN users u  ON r.user_id  = u.id
        JOIN authors a ON b.author_id = a.id
        $where_sql
        ORDER BY
            CASE r.status WHEN 'pending' THEN 0 WHEN 'approved' THEN 1 ELSE 2 END,
            r.created_at DESC
        LIMIT $per_page OFFSET $offset
    ");
    $stmt->execute($params);
    $reviews = $stmt->fetchAll();

    // Status counts
    $counts_stmt = $pdo->query("SELECT status, COUNT(*) as cnt FROM reviews GROUP BY status");
    $counts_raw  = $counts_stmt->fetchAll();
    $status_counts = ['all' => 0, 'pending' => 0, 'approved' => 0, 'rejected' => 0];
    foreach ($counts_raw as $row) {
        $status_counts[$row['status']] = (int)$row['cnt'];
        $status_counts['all'] += (int)$row['cnt'];
    }

} catch (PDOException $e) {
    $error = 'Error: ' . $e->getMessage();
    $reviews = [];
    $total_reviews = 0;
    $total_pages = 1;
    $status_counts = ['all' => 0, 'pending' => 0, 'approved' => 0, 'rejected' => 0];
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="flex">
    <?php require_once __DIR__ . '/../includes/sidebar.php'; ?>

    <div class="flex-1 ml-64 min-h-screen bg-gray-50 dark:bg-gray-900">
        <!-- Navbar -->
        <nav class="navbar sticky top-0 z-20">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between items-center h-16">
                    <div class="flex items-center gap-4">
                        <button class="lg:hidden" onclick="toggleSidebar()">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                            </svg>
                        </button>
                        <div>
                            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Manajemen Review</h1>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Moderasi ulasan dari pengguna</p>
                        </div>
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

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

            <!-- Alerts -->
            <?php if ($message): ?>
            <div class="alert alert-success mb-6 fade-in">
                <i class="ph-fill ph-check-circle text-xl"></i>
                <span><?php echo htmlspecialchars($message); ?></span>
            </div>
            <?php endif; ?>
            <?php if ($error): ?>
            <div class="alert alert-danger mb-6 fade-in">
                <i class="ph-fill ph-warning-circle text-xl"></i>
                <span><?php echo htmlspecialchars($error); ?></span>
            </div>
            <?php endif; ?>

            <!-- Status Summary Cards -->
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
                <a href="?status=all" class="stat-card flex items-center gap-4 hover:border-primary transition <?php echo $status_filter === 'all' ? 'border-primary border-2' : ''; ?>">
                    <div class="w-12 h-12 rounded-full bg-slate-100 dark:bg-slate-700 flex items-center justify-center text-slate-600 dark:text-slate-300 shrink-0">
                        <i class="ph ph-chat-dots text-2xl"></i>
                    </div>
                    <div>
                        <p class="stat-label !mt-0 text-xs">Total Review</p>
                        <p class="text-2xl font-bold text-gray-900 dark:text-white"><?php echo $status_counts['all']; ?></p>
                    </div>
                </a>
                <a href="?status=pending" class="stat-card flex items-center gap-4 hover:border-amber-400 transition <?php echo $status_filter === 'pending' ? 'border-amber-400 border-2' : ''; ?>">
                    <div class="w-12 h-12 rounded-full bg-amber-100 dark:bg-amber-900/30 flex items-center justify-center text-amber-600 shrink-0">
                        <i class="ph ph-clock text-2xl"></i>
                    </div>
                    <div>
                        <p class="stat-label !mt-0 text-xs">Menunggu</p>
                        <p class="text-2xl font-bold text-amber-600"><?php echo $status_counts['pending']; ?></p>
                    </div>
                </a>
                <a href="?status=approved" class="stat-card flex items-center gap-4 hover:border-emerald-400 transition <?php echo $status_filter === 'approved' ? 'border-emerald-400 border-2' : ''; ?>">
                    <div class="w-12 h-12 rounded-full bg-emerald-100 dark:bg-emerald-900/30 flex items-center justify-center text-emerald-600 shrink-0">
                        <i class="ph ph-check-circle text-2xl"></i>
                    </div>
                    <div>
                        <p class="stat-label !mt-0 text-xs">Disetujui</p>
                        <p class="text-2xl font-bold text-emerald-600"><?php echo $status_counts['approved']; ?></p>
                    </div>
                </a>
                <a href="?status=rejected" class="stat-card flex items-center gap-4 hover:border-red-400 transition <?php echo $status_filter === 'rejected' ? 'border-red-400 border-2' : ''; ?>">
                    <div class="w-12 h-12 rounded-full bg-red-100 dark:bg-red-900/30 flex items-center justify-center text-red-500 shrink-0">
                        <i class="ph ph-x-circle text-2xl"></i>
                    </div>
                    <div>
                        <p class="stat-label !mt-0 text-xs">Ditolak</p>
                        <p class="text-2xl font-bold text-red-500"><?php echo $status_counts['rejected']; ?></p>
                    </div>
                </a>
            </div>

            <!-- Filters & Search -->
            <div class="card p-4 mb-6">
                <form method="GET" class="flex flex-col sm:flex-row gap-3 items-center">
                    <input type="hidden" name="status" value="<?php echo htmlspecialchars($status_filter); ?>">
                    <div class="relative flex-1 w-full">
                        <i class="ph ph-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-lg"></i>
                        <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>"
                               placeholder="Cari buku, pengguna, atau komentar..."
                               class="w-full pl-10 pr-4 py-2.5 border border-gray-200 dark:border-gray-700 rounded-lg bg-white dark:bg-slate-800 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary text-sm">
                    </div>
                    <div class="flex gap-2 shrink-0">
                        <button type="submit" class="btn btn-primary flex items-center gap-2 py-2.5 px-4 text-sm">
                            <i class="ph ph-funnel text-base"></i> Filter
                        </button>
                        <?php if ($search): ?>
                        <a href="?status=<?php echo htmlspecialchars($status_filter); ?>" class="btn btn-secondary flex items-center gap-2 py-2.5 px-4 text-sm">
                            <i class="ph ph-x text-base"></i> Reset
                        </a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>

            <!-- Reviews Table -->
            <div class="card overflow-hidden">
                <div class="p-6 border-b border-gray-100 dark:border-gray-700 flex items-center justify-between">
                    <div>
                        <h2 class="text-lg font-bold text-gray-900 dark:text-white">
                            Daftar Review
                            <?php if ($status_filter !== 'all'): ?>
                            <span class="ml-2 badge <?php echo $status_filter === 'pending' ? 'badge-warning' : ($status_filter === 'approved' ? 'badge-success' : 'badge-danger'); ?>">
                                <?php echo ucfirst($status_filter); ?>
                            </span>
                            <?php endif; ?>
                        </h2>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">
                            <?php echo $total_reviews; ?> review ditemukan
                        </p>
                    </div>
                </div>

                <?php if (count($reviews) > 0): ?>
                <div class="divide-y divide-gray-100 dark:divide-gray-700/50">
                    <?php foreach ($reviews as $review): ?>
                    <div class="p-6 hover:bg-gray-50 dark:hover:bg-slate-800/50 transition-colors group">
                        <div class="flex flex-col lg:flex-row lg:items-start gap-4">
                            <!-- User Info -->
                            <div class="flex items-start gap-3 lg:w-48 shrink-0">
                                <div class="w-10 h-10 rounded-full bg-primary/10 text-primary flex items-center justify-center font-bold text-sm shrink-0">
                                    <?php echo strtoupper(substr($review['full_name'], 0, 1)); ?>
                                </div>
                                <div class="min-w-0">
                                    <p class="font-semibold text-gray-900 dark:text-white text-sm truncate"><?php echo htmlspecialchars($review['full_name']); ?></p>
                                    <p class="text-xs text-gray-500 mt-0.5"><?php echo timeAgo($review['created_at']); ?></p>
                                </div>
                            </div>

                            <!-- Review Content -->
                            <div class="flex-1 min-w-0">
                                <div class="flex items-start justify-between gap-2 mb-2">
                                    <div>
                                        <p class="font-semibold text-gray-900 dark:text-white text-sm">
                                            <?php echo htmlspecialchars($review['book_title']); ?>
                                        </p>
                                        <p class="text-xs text-gray-500"><?php echo htmlspecialchars($review['author_name']); ?></p>
                                    </div>
                                    <!-- Star Rating -->
                                    <div class="flex items-center gap-1 shrink-0">
                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                        <i class="ph-fill ph-star text-sm <?php echo $i <= $review['rating'] ? 'text-amber-400' : 'text-gray-200 dark:text-gray-600'; ?>"></i>
                                        <?php endfor; ?>
                                        <span class="text-xs text-gray-500 ml-1"><?php echo $review['rating']; ?>/5</span>
                                    </div>
                                </div>
                                <p class="text-sm text-gray-600 dark:text-gray-400 leading-relaxed line-clamp-3">
                                    <?php echo nl2br(htmlspecialchars($review['comment'])); ?>
                                </p>
                            </div>

                            <!-- Status & Actions -->
                            <div class="flex flex-row lg:flex-col items-center lg:items-end gap-3 shrink-0">
                                <!-- Status Badge -->
                                <div>
                                    <?php if ($review['status'] === 'pending'): ?>
                                    <span class="badge badge-warning flex items-center gap-1">
                                        <i class="ph ph-clock text-xs"></i> Menunggu
                                    </span>
                                    <?php elseif ($review['status'] === 'approved'): ?>
                                    <span class="badge badge-success flex items-center gap-1">
                                        <i class="ph ph-check text-xs"></i> Disetujui
                                    </span>
                                    <?php else: ?>
                                    <span class="badge badge-danger flex items-center gap-1">
                                        <i class="ph ph-x text-xs"></i> Ditolak
                                    </span>
                                    <?php endif; ?>
                                </div>

                                <!-- Action Buttons -->
                                <div class="flex items-center gap-2">
                                    <?php if ($review['status'] !== 'approved'): ?>
                                    <form method="POST" class="inline">
                                        <input type="hidden" name="action" value="approve">
                                        <input type="hidden" name="review_id" value="<?php echo $review['id']; ?>">
                                        <?php if ($search) echo '<input type="hidden" name="search_redirect" value="' . htmlspecialchars($search) . '">'; ?>
                                        <button type="submit"
                                                class="p-2 rounded-lg bg-emerald-50 dark:bg-emerald-900/20 text-emerald-600 hover:bg-emerald-100 dark:hover:bg-emerald-900/40 transition"
                                                title="Setujui Review">
                                            <i class="ph ph-check-circle text-lg"></i>
                                        </button>
                                    </form>
                                    <?php endif; ?>

                                    <?php if ($review['status'] !== 'rejected'): ?>
                                    <form method="POST" class="inline">
                                        <input type="hidden" name="action" value="reject">
                                        <input type="hidden" name="review_id" value="<?php echo $review['id']; ?>">
                                        <button type="submit"
                                                class="p-2 rounded-lg bg-red-50 dark:bg-red-900/20 text-red-500 hover:bg-red-100 dark:hover:bg-red-900/40 transition"
                                                title="Tolak Review"
                                                onclick="return confirm('Tolak review ini?')">
                                            <i class="ph ph-x-circle text-lg"></i>
                                        </button>
                                    </form>
                                    <?php endif; ?>

                                    <form method="POST" class="inline">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="review_id" value="<?php echo $review['id']; ?>">
                                        <button type="submit"
                                                class="p-2 rounded-lg bg-gray-100 dark:bg-gray-700 text-gray-500 hover:bg-red-50 hover:text-red-500 dark:hover:bg-red-900/20 dark:hover:text-red-400 transition"
                                                title="Hapus Review"
                                                onclick="return confirm('Hapus review ini secara permanen?')">
                                            <i class="ph ph-trash text-lg"></i>
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>

                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                <div class="p-4 border-t border-gray-100 dark:border-gray-700 flex items-center justify-between">
                    <p class="text-sm text-gray-500">
                        Halaman <?php echo $page; ?> dari <?php echo $total_pages; ?>
                    </p>
                    <div class="flex gap-2">
                        <?php if ($page > 1): ?>
                        <a href="?status=<?php echo urlencode($status_filter); ?>&search=<?php echo urlencode($search); ?>&page=<?php echo $page - 1; ?>"
                           class="px-3 py-1.5 text-sm border border-gray-200 dark:border-gray-700 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-800 text-gray-700 dark:text-gray-300 transition">
                            <i class="ph ph-caret-left"></i>
                        </a>
                        <?php endif; ?>
                        <?php for ($p = max(1, $page - 2); $p <= min($total_pages, $page + 2); $p++): ?>
                        <a href="?status=<?php echo urlencode($status_filter); ?>&search=<?php echo urlencode($search); ?>&page=<?php echo $p; ?>"
                           class="px-3 py-1.5 text-sm rounded-lg transition <?php echo $p === $page ? 'bg-primary text-white' : 'border border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-800 text-gray-700 dark:text-gray-300'; ?>">
                            <?php echo $p; ?>
                        </a>
                        <?php endfor; ?>
                        <?php if ($page < $total_pages): ?>
                        <a href="?status=<?php echo urlencode($status_filter); ?>&search=<?php echo urlencode($search); ?>&page=<?php echo $page + 1; ?>"
                           class="px-3 py-1.5 text-sm border border-gray-200 dark:border-gray-700 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-800 text-gray-700 dark:text-gray-300 transition">
                            <i class="ph ph-caret-right"></i>
                        </a>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>

                <?php else: ?>
                <!-- Empty State -->
                <div class="py-20 text-center">
                    <div class="w-20 h-20 rounded-full bg-gray-100 dark:bg-gray-800 flex items-center justify-center mx-auto mb-4">
                        <i class="ph ph-chat-dots text-4xl text-gray-400"></i>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-700 dark:text-gray-300 mb-1">Tidak ada review</h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        <?php echo $search ? "Tidak ditemukan hasil untuk \"$search\"" : 'Belum ada review dengan status ini.'; ?>
                    </p>
                    <?php if ($search || $status_filter !== 'all'): ?>
                    <a href="/perpustakaan/admin/reviews.php" class="mt-4 inline-flex items-center gap-2 text-primary hover:underline text-sm">
                        <i class="ph ph-arrow-left"></i> Tampilkan semua
                    </a>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>

        </div>

        <?php require_once __DIR__ . '/../includes/footer.php'; ?>
    </div>
</div>
</body>
</html>
