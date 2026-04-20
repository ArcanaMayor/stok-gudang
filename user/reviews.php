<?php
session_start();
require_once __DIR__ . '/../config/database.php';

checkUser();

$page_title = 'Rating & Review';
$message = '';
$error = '';

$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];

    if ($action === 'delete') {
        $review_id = (int)($_POST['review_id'] ?? 0);

        if ($review_id <= 0) {
            $error = 'Review tidak valid!';
        } else {
            try {
                $stmt = $pdo->prepare("DELETE FROM reviews WHERE id = ? AND user_id = ?");
                $stmt->execute([$review_id, $user_id]);
                $message = 'Review berhasil dihapus!';
            } catch (PDOException $e) {
                $error = 'Error: ' . $e->getMessage();
            }
        }
    }
}

$reviews = [];
$can_review_books = [];

try {
    $stmt = $pdo->prepare("
        SELECT r.*, b.title, b.id as book_id, a.name as author_name
        FROM reviews r
        JOIN books b ON r.book_id = b.id
        JOIN authors a ON b.author_id = a.id
        WHERE r.user_id = ?
        ORDER BY r.created_at DESC
    ");
    $stmt->execute([$user_id]);
    $reviews = $stmt->fetchAll();

    $stmt = $pdo->prepare("
        SELECT b.*, a.name as author_name, MAX(l.return_date) as last_return
        FROM loans l
        JOIN books b ON l.book_id = b.id
        JOIN authors a ON b.author_id = a.id
        WHERE l.user_id = ? AND l.status = 'returned'
        AND b.id NOT IN (SELECT book_id FROM reviews WHERE user_id = ?)
        GROUP BY b.id, a.id
        ORDER BY MAX(l.return_date) DESC
        LIMIT 10
    ");
    $stmt->execute([$user_id, $user_id]);
    $can_review_books = $stmt->fetchAll();

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}

$approved_count = count(array_filter($reviews, fn($r) => $r['status'] === 'approved'));
$pending_count  = count(array_filter($reviews, fn($r) => $r['status'] === 'pending'));

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
                            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Rating &amp; Review</h1>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Kelola ulasan buku Anda</p>
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

            <!-- Stats Row -->
            <div class="grid grid-cols-3 gap-4 mb-8">
                <div class="stat-card flex items-center gap-4">
                    <div class="w-12 h-12 rounded-full bg-primary/10 flex items-center justify-center text-primary shrink-0">
                        <i class="ph ph-chat-dots text-2xl"></i>
                    </div>
                    <div>
                        <p class="stat-label !mt-0 text-xs">Total Review</p>
                        <p class="text-2xl font-bold text-gray-900 dark:text-white"><?php echo count($reviews); ?></p>
                    </div>
                </div>
                <div class="stat-card flex items-center gap-4">
                    <div class="w-12 h-12 rounded-full bg-emerald-100 dark:bg-emerald-900/30 flex items-center justify-center text-emerald-600 shrink-0">
                        <i class="ph ph-check-circle text-2xl"></i>
                    </div>
                    <div>
                        <p class="stat-label !mt-0 text-xs">Disetujui</p>
                        <p class="text-2xl font-bold text-emerald-600"><?php echo $approved_count; ?></p>
                    </div>
                </div>
                <div class="stat-card flex items-center gap-4">
                    <div class="w-12 h-12 rounded-full bg-amber-100 dark:bg-amber-900/30 flex items-center justify-center text-amber-600 shrink-0">
                        <i class="ph ph-clock text-2xl"></i>
                    </div>
                    <div>
                        <p class="stat-label !mt-0 text-xs">Menunggu</p>
                        <p class="text-2xl font-bold text-amber-600"><?php echo $pending_count; ?></p>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <!-- Sidebar: Books to Review -->
                <div class="lg:col-span-1">
                    <div class="card p-6">
                        <div class="flex items-center gap-2 mb-5">
                            <div class="w-8 h-8 rounded-lg bg-primary/10 flex items-center justify-center text-primary">
                                <i class="ph ph-book-open-text text-lg"></i>
                            </div>
                            <h2 class="text-base font-bold text-gray-900 dark:text-white">Buku untuk Direview</h2>
                        </div>

                        <?php if (count($can_review_books) > 0): ?>
                        <div class="space-y-2">
                            <?php foreach ($can_review_books as $book): ?>
                            <a href="/perpustakaan/user/book_detail.php?id=<?php echo $book['id']; ?>#review"
                               class="flex items-start gap-3 p-3 rounded-xl border border-gray-100 dark:border-gray-700 hover:border-primary/40 hover:bg-primary/5 transition-all group">
                                <div class="w-9 h-9 rounded-lg bg-primary/10 flex items-center justify-center text-primary font-bold text-sm shrink-0">
                                    <?php echo strtoupper(substr($book['title'], 0, 1)); ?>
                                </div>
                                <div class="min-w-0 flex-1">
                                    <p class="text-sm font-semibold text-gray-900 dark:text-white truncate">
                                        <?php echo htmlspecialchars(substr($book['title'], 0, 35)); ?>
                                    </p>
                                    <p class="text-xs text-gray-500 mt-0.5"><?php echo htmlspecialchars($book['author_name']); ?></p>
                                    <p class="text-xs text-primary mt-1 flex items-center gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                                        <i class="ph ph-pencil-simple text-xs"></i> Tulis Review
                                    </p>
                                </div>
                                <i class="ph ph-arrow-right text-gray-400 group-hover:text-primary transition-colors shrink-0 mt-1"></i>
                            </a>
                            <?php endforeach; ?>
                        </div>
                        <?php else: ?>
                        <div class="py-10 text-center">
                            <div class="w-14 h-14 rounded-full bg-gray-100 dark:bg-gray-800 flex items-center justify-center mx-auto mb-3">
                                <i class="ph ph-books text-2xl text-gray-400"></i>
                            </div>
                            <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Belum ada buku</p>
                            <p class="text-xs text-gray-500 mt-1">Kembalikan buku untuk dapat mereviewnya</p>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Main: My Reviews -->
                <div class="lg:col-span-2">
                    <div class="card overflow-hidden">
                        <div class="p-6 border-b border-gray-100 dark:border-gray-700">
                            <h2 class="text-base font-bold text-gray-900 dark:text-white">Review Saya
                                <span class="ml-1 text-gray-400 font-normal">(<?php echo count($reviews); ?>)</span>
                            </h2>
                        </div>

                        <?php if (count($reviews) > 0): ?>
                        <div class="divide-y divide-gray-100 dark:divide-gray-700/50">
                            <?php foreach ($reviews as $review): ?>
                            <div class="p-6 hover:bg-gray-50 dark:hover:bg-slate-800/40 transition-colors">
                                <!-- Header -->
                                <div class="flex items-start justify-between gap-4 mb-3">
                                    <div class="flex-1 min-w-0">
                                        <a href="/perpustakaan/user/book_detail.php?id=<?php echo $review['book_id']; ?>"
                                           class="text-base font-bold text-gray-900 dark:text-white hover:text-primary transition-colors line-clamp-1">
                                            <?php echo htmlspecialchars($review['title']); ?>
                                        </a>
                                        <p class="text-sm text-gray-500 mt-0.5"><?php echo htmlspecialchars($review['author_name']); ?></p>
                                    </div>

                                    <!-- Status Badge -->
                                    <div class="shrink-0">
                                        <?php if ($review['status'] === 'pending'): ?>
                                        <span class="badge badge-warning flex items-center gap-1 text-xs">
                                            <i class="ph ph-clock text-xs"></i> Menunggu
                                        </span>
                                        <?php elseif ($review['status'] === 'approved'): ?>
                                        <span class="badge badge-success flex items-center gap-1 text-xs">
                                            <i class="ph ph-check text-xs"></i> Disetujui
                                        </span>
                                        <?php else: ?>
                                        <span class="badge badge-danger flex items-center gap-1 text-xs">
                                            <i class="ph ph-x text-xs"></i> Ditolak
                                        </span>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <!-- Star Rating -->
                                <div class="flex items-center gap-1.5 mb-3">
                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                    <i class="ph-fill ph-star text-sm <?php echo $i <= $review['rating'] ? 'text-amber-400' : 'text-gray-200 dark:text-gray-600'; ?>"></i>
                                    <?php endfor; ?>
                                    <span class="text-xs text-gray-500 ml-1"><?php echo $review['rating']; ?>/5</span>
                                </div>

                                <!-- Comment -->
                                <p class="text-sm text-gray-600 dark:text-gray-400 leading-relaxed mb-4 line-clamp-3">
                                    <?php echo nl2br(htmlspecialchars($review['comment'])); ?>
                                </p>

                                <!-- Footer -->
                                <div class="flex items-center justify-between">
                                    <span class="text-xs text-gray-400 flex items-center gap-1">
                                        <i class="ph ph-clock text-xs"></i>
                                        <?php echo timeAgo($review['created_at']); ?>
                                    </span>

                                    <div class="flex items-center gap-2">
                                        <a href="/perpustakaan/user/book_detail.php?id=<?php echo $review['book_id']; ?>#review"
                                           class="flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium text-primary border border-primary/30 rounded-lg hover:bg-primary/5 transition-colors">
                                            <i class="ph ph-pencil-simple text-sm"></i> Edit
                                        </a>

                                        <form method="POST" class="inline">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="review_id" value="<?php echo $review['id']; ?>">
                                            <button type="submit"
                                                    onclick="return confirm('Hapus review ini?')"
                                                    class="flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium text-red-500 border border-red-200 dark:border-red-800/50 rounded-lg hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors">
                                                <i class="ph ph-trash text-sm"></i> Hapus
                                            </button>
                                        </form>
                                    </div>
                                </div>

                                <?php if ($review['status'] === 'pending'): ?>
                                <div class="mt-3 p-3 bg-amber-50 dark:bg-amber-900/10 border border-amber-100 dark:border-amber-800/30 rounded-lg text-xs text-amber-700 dark:text-amber-400 flex items-start gap-2">
                                    <i class="ph ph-info text-sm shrink-0 mt-0.5"></i>
                                    Review Anda sedang menunggu persetujuan admin. Setelah disetujui, ulasan akan tampil di halaman buku.
                                </div>
                                <?php elseif ($review['status'] === 'rejected'): ?>
                                <div class="mt-3 p-3 bg-red-50 dark:bg-red-900/10 border border-red-100 dark:border-red-800/30 rounded-lg text-xs text-red-600 dark:text-red-400 flex items-start gap-2">
                                    <i class="ph ph-warning text-sm shrink-0 mt-0.5"></i>
                                    Review ini telah ditolak oleh admin. Anda dapat mengedit dan mengirim ulang.
                                </div>
                                <?php endif; ?>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <?php else: ?>
                        <!-- Empty State -->
                        <div class="py-20 text-center">
                            <div class="w-20 h-20 rounded-full bg-gray-100 dark:bg-gray-800 flex items-center justify-center mx-auto mb-4">
                                <i class="ph ph-chat-dots text-4xl text-gray-400"></i>
                            </div>
                            <h3 class="text-base font-semibold text-gray-700 dark:text-gray-300 mb-1">Belum ada review</h3>
                            <p class="text-sm text-gray-500 dark:text-gray-400 max-w-xs mx-auto">
                                Mulai pinjam &amp; kembalikan buku, lalu bagikan pendapat Anda!
                            </p>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

        </div>

        <?php require_once __DIR__ . '/../includes/footer.php'; ?>
    </div>
</div>
</body>
</html>
