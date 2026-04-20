<?php
session_start();
require_once __DIR__ . '/../config/database.php';

checkUser();

$page_title = 'Dashboard';

try {
    $user_id = $_SESSION['user_id'];

    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM loans WHERE user_id = ? AND status = 'borrowed'");
    $stmt->execute([$user_id]);
    $active_loans = $stmt->fetch()['count'];

    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM loans WHERE user_id = ? AND status = 'returned'");
    $stmt->execute([$user_id]);
    $completed_loans = $stmt->fetch()['count'];

    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM reviews WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $my_reviews = $stmt->fetch()['count'];

    $stmt = $pdo->prepare("
        SELECT l.*, b.title, b.cover_image, a.name as author_name
        FROM loans l
        JOIN books b ON l.book_id = b.id
        JOIN authors a ON b.author_id = a.id
        WHERE l.user_id = ? AND l.status = 'borrowed'
        ORDER BY l.due_date ASC
        LIMIT 5
    ");
    $stmt->execute([$user_id]);
    $active_borrows = $stmt->fetchAll();

    $stmt = $pdo->query("
        SELECT b.*, a.name as author_name, c.name as category_name
        FROM books b
        JOIN authors a ON b.author_id = a.id
        JOIN categories c ON b.category_id = c.id
        WHERE b.available_stock > 0
        ORDER BY b.created_at DESC
        LIMIT 5
    ");
    $recommended_books = $stmt->fetchAll();

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
                        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Dashboard</h1>
                    </div>
                    <div class="flex items-center gap-4 relative group">
                        <div class="flex items-center gap-3 bg-white dark:bg-slate-800 border border-gray-200 dark:border-slate-700 py-1.5 px-3 rounded-full hover:bg-gray-50 dark:hover:bg-slate-700 transition cursor-pointer shadow-sm">
                            <div class="w-8 h-8 rounded-full bg-primary/10 text-primary flex items-center justify-center font-bold text-sm shrink-0">
                                <?php 
                                    $initials = '';
                                    $name_parts = explode(' ', $_SESSION['full_name'] ?? 'User');
                                    foreach (array_slice($name_parts, 0, 2) as $part) {
                                        $initials .= strtoupper(substr($part, 0, 1));
                                    }
                                    echo sanitize($initials);
                                ?>
                            </div>
                            <div class="text-left pr-1">
                                <p class="text-sm font-semibold text-gray-900 dark:text-white leading-tight"><?php echo sanitize($_SESSION['full_name'] ?? 'User'); ?></p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">Member</p>
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
            <div class="bg-primary/5 dark:bg-primary/10 border border-primary/20 rounded-2xl p-8 mb-8 flex items-center justify-between">
                <div>
                    <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-2">Selamat Datang, <?php 
                        $fullname = $_SESSION['full_name'] ?? 'Pengguna';
                        $firstname = explode(' ', $fullname)[0];
                        echo sanitize($firstname);
                    ?>!</h2>
                    <p class="text-gray-600 dark:text-gray-400">Kelola peminjaman buku Anda dan jelajahi koleksi perpustakaan kami yang luas.</p>
                </div>
                <div class="hidden md:block">
                    <i class="ph-duotone ph-student text-6xl text-primary/40"></i>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                <div class="stat-card flex items-center gap-4">
                    <div class="w-14 h-14 rounded-full bg-primary/10 flex items-center justify-center text-primary shrink-0">
                        <i class="ph ph-book-open text-2xl"></i>
                    </div>
                    <div>
                        <p class="stat-label !mt-0">Buku Dipinjam</p>
                        <p class="stat-number"><?php echo $active_loans; ?></p>
                    </div>
                </div>

                <div class="stat-card flex items-center gap-4">
                    <div class="w-14 h-14 rounded-full bg-emerald-500/10 flex items-center justify-center text-emerald-500 shrink-0">
                        <i class="ph ph-check-circle text-2xl"></i>
                    </div>
                    <div>
                        <p class="stat-label !mt-0">Buku Dikembalikan</p>
                        <p class="stat-number"><?php echo $completed_loans; ?></p>
                    </div>
                </div>

                <div class="stat-card flex items-center gap-4">
                    <div class="w-14 h-14 rounded-full bg-amber-500/10 flex items-center justify-center text-amber-500 shrink-0">
                        <i class="ph ph-star text-2xl"></i>
                    </div>
                    <div>
                        <p class="stat-label !mt-0">Review Saya</p>
                        <p class="stat-number"><?php echo $my_reviews; ?></p>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                <div class="card p-6">
                    <div class="flex items-center justify-between mb-6">
                        <h2 class="text-xl font-bold text-gray-900 dark:text-white">Buku yang Sedang Dipinjam</h2>
                        <a href="/perpustakaan/user/borrow.php" class="text-blue-600 hover:text-blue-700 text-sm font-500">Lihat Semua →</a>
                    </div>

                    <div class="space-y-4">
                        <?php if (count($active_borrows) > 0): ?>
                            <?php foreach ($active_borrows as $loan): ?>
                            <?php
                            $cover_path_l = __DIR__ . '/../assets/uploads/covers/' . ($loan['cover_image'] ?? '');
                            $has_cover_l  = !empty($loan['cover_image']) && file_exists($cover_path_l);
                            $days_left    = (strtotime($loan['due_date']) - time()) / 86400;
                            ?>
                            <div class="flex items-center justify-between p-4 border border-gray-200 dark:border-gray-700 rounded-xl hover:bg-gray-50 dark:hover:bg-gray-800 transition">
                                <div class="flex items-center gap-3">
                                    <?php if ($has_cover_l): ?>
                                    <div class="w-10 h-14 rounded-lg overflow-hidden border border-gray-200 dark:border-gray-700 shrink-0 shadow-sm">
                                        <img src="/perpustakaan/assets/uploads/covers/<?php echo htmlspecialchars($loan['cover_image']); ?>"
                                             alt="<?php echo htmlspecialchars($loan['title']); ?>"
                                             class="w-full h-full object-cover">
                                    </div>
                                    <?php else: ?>
                                    <div class="w-10 h-14 rounded-lg shrink-0 flex items-center justify-center font-bold text-sm text-white shadow-sm"
                                         style="background: linear-gradient(135deg, hsl(<?php echo (crc32($loan['title']) % 360 + 360) % 360; ?>, 65%, 55%) 0%, hsl(<?php echo ((crc32($loan['title']) + 120) % 360 + 360) % 360; ?>, 65%, 45%) 100%)">
                                        <?php echo strtoupper(substr($loan['title'], 0, 1)); ?>
                                    </div>
                                    <?php endif; ?>
                                    <div>
                                        <p class="font-500 text-gray-900 dark:text-white text-sm"><?php echo htmlspecialchars(substr($loan['title'], 0, 28)); ?></p>
                                        <p class="text-xs text-gray-500 mt-0.5"><?php echo htmlspecialchars($loan['author_name']); ?></p>
                                    </div>
                                </div>
                                <div class="text-right shrink-0">
                                    <?php if ($days_left < 3): ?>
                                    <span class="badge badge-danger flex items-center gap-1">
                                        <i class="ph-fill ph-clock text-xs"></i> <?php echo max(0, ceil($days_left)); ?> hari
                                    </span>
                                    <?php else: ?>
                                    <span class="badge badge-primary"><?php echo ceil($days_left); ?> hari</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                        <div class="text-center py-8">
                            <p class="text-gray-500 dark:text-gray-400 text-sm">Anda belum meminjam buku apapun</p>
                            <a href="/perpustakaan/user/books.php" class="text-blue-600 hover:underline text-sm mt-2 inline-block">Jelajahi Koleksi →</a>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="card p-6">
                    <div class="flex items-center justify-between mb-6">
                        <h2 class="text-xl font-bold text-gray-900 dark:text-white">Koleksi Terbaru</h2>
                        <a href="/perpustakaan/user/books.php" class="text-blue-600 hover:text-blue-700 text-sm font-500">Lihat Semua →</a>
                    </div>

                    <div class="space-y-4">
                        <?php if (count($recommended_books) > 0): ?>
                            <?php foreach ($recommended_books as $book): ?>
                            <?php
                            $cover_path_b = __DIR__ . '/../assets/uploads/covers/' . ($book['cover_image'] ?? '');
                            $has_cover_b  = !empty($book['cover_image']) && file_exists($cover_path_b);
                            ?>
                            <a href="/perpustakaan/user/book_detail.php?id=<?php echo $book['id']; ?>" class="flex items-center gap-3 p-3 border border-gray-100 dark:border-gray-700 rounded-xl hover:border-primary/40 hover:bg-primary/5 transition-all group">
                                <?php if ($has_cover_b): ?>
                                <div class="w-10 h-14 rounded-lg overflow-hidden border border-gray-200 dark:border-gray-700 shrink-0 shadow-sm">
                                    <img src="/perpustakaan/assets/uploads/covers/<?php echo htmlspecialchars($book['cover_image']); ?>"
                                         alt="<?php echo htmlspecialchars($book['title']); ?>"
                                         class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300">
                                </div>
                                <?php else: ?>
                                <div class="w-10 h-14 rounded-lg shrink-0 flex items-center justify-center font-bold text-sm text-white shadow-sm"
                                     style="background: linear-gradient(135deg, hsl(<?php echo ($book['id'] * 47) % 360; ?>, 65%, 55%) 0%, hsl(<?php echo ($book['id'] * 83) % 360; ?>, 65%, 45%) 100%)">
                                    <?php echo strtoupper(substr($book['title'], 0, 1)); ?>
                                </div>
                                <?php endif; ?>
                                <div class="flex-1 min-w-0">
                                    <p class="font-semibold text-gray-900 dark:text-white text-sm truncate group-hover:text-primary transition-colors"><?php echo htmlspecialchars($book['title']); ?></p>
                                    <p class="text-xs text-gray-500 mt-0.5 truncate"><?php echo htmlspecialchars($book['author_name']); ?></p>
                                </div>
                                <span class="badge badge-success text-xs shrink-0">Tersedia</span>
                            </a>
                            <?php endforeach; ?>
                        <?php else: ?>
                        <div class="text-center py-8">
                            <p class="text-gray-500 dark:text-gray-400 text-sm">Belum ada buku yang direkomendasikan</p>
                            <a href="/perpustakaan/user/books.php" class="text-blue-600 hover:underline text-sm mt-2 inline-block">Jelajahi Katalog →</a>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="mt-8 card p-6">
                <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-6">Aksi Cepat</h2>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <a href="/perpustakaan/user/books.php" class="p-6 bg-white dark:bg-slate-800 border border-gray-200 dark:border-slate-700 rounded-xl hover:border-primary hover:bg-primary/5 transition flex flex-col items-center justify-center gap-3">
                        <i class="ph ph-magnifying-glass text-3xl text-primary"></i>
                        <p class="font-medium text-gray-900 dark:text-white">Cari Buku</p>
                    </a>
                    <a href="/perpustakaan/user/borrow.php" class="p-6 bg-white dark:bg-slate-800 border border-gray-200 dark:border-slate-700 rounded-xl hover:border-primary hover:bg-primary/5 transition flex flex-col items-center justify-center gap-3">
                        <i class="ph ph-tray-arrow-up text-3xl text-primary"></i>
                        <p class="font-medium text-gray-900 dark:text-white">Peminjaman</p>
                    </a>
                    <a href="/perpustakaan/user/reviews.php" class="p-6 bg-white dark:bg-slate-800 border border-gray-200 dark:border-slate-700 rounded-xl hover:border-primary hover:bg-primary/5 transition flex flex-col items-center justify-center gap-3">
                        <i class="ph ph-star text-3xl text-primary"></i>
                        <p class="font-medium text-gray-900 dark:text-white">Rating & Review</p>
                    </a>
                </div>
            </div>

        </div>

        <?php require_once __DIR__ . '/../includes/footer.php'; ?>
    </div>
</div>
</body>
</html>
