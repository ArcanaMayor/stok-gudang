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
                        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Rating & Review</h1>
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
                        <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4">Buku untuk Direview</h2>

                        <?php if (count($can_review_books) > 0): ?>
                        <div class="space-y-3">
                            <?php foreach ($can_review_books as $book): ?>
                            <a href="/perpustakaan/user/book_detail.php?id=<?php echo $book['id']; ?>" class="block p-3 border border-gray-200 dark:border-gray-700 rounded-lg hover:bg-blue-50 dark:hover:bg-gray-800 transition">
                                <p class="font-500 text-gray-900 dark:text-white text-sm"><?php echo htmlspecialchars(substr($book['title'], 0, 40)); ?></p>
                                <p class="text-xs text-gray-500 mt-1"><?php echo htmlspecialchars($book['author_name']); ?></p>
                                <p class="text-xs text-blue-600 mt-2">Tulis Review →</p>
                            </a>
                            <?php endforeach; ?>
                        </div>
                        <?php else: ?>
                        <div class="text-center py-8">
                            <p class="text-gray-500 dark:text-gray-400 text-sm">Anda belum memiliki buku untuk direview</p>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="lg:col-span-2">
                    <div class="card p-6">
                        <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-6">Review Saya (<?php echo count($reviews); ?>)</h2>

                        <?php if (count($reviews) > 0): ?>
                        <div class="space-y-6">
                            <?php foreach ($reviews as $review): ?>
                            <div class="p-6 border-2 border-blue-200 dark:border-blue-800 rounded-lg bg-blue-50 dark:bg-blue-900/30">
                                <div class="flex items-start justify-between mb-4">
                                    <div class="flex-1">
                                        <a href="/perpustakaan/user/book_detail.php?id=<?php echo $review['book_id']; ?>" class="text-lg font-bold text-gray-900 dark:text-white hover:text-blue-600">
                                            <?php echo htmlspecialchars($review['title']); ?>
                                        </a>
                                        <p class="text-sm text-gray-500"><?php echo htmlspecialchars($review['author_name']); ?></p>
                                    </div>

                                    <form method="POST" class="inline">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="review_id" value="<?php echo $review['id']; ?>">
                                        <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Hapus review ini?');">🗑️</button>
                                    </form>
                                </div>

                                <div class="mb-4">
                                    <div class="flex items-center gap-2">
                                        <span class="text-yellow-500 text-lg"><?php echo str_repeat('★', $review['rating']); ?></span>
                                        <span class="text-gray-500 text-sm"><?php echo $review['rating']; ?>/5</span>
                                    </div>
                                </div>

                                <div class="mb-4">
                                    <p class="text-gray-700 dark:text-gray-300 leading-relaxed">
                                        <?php echo nl2br(htmlspecialchars($review['comment'])); ?>
                                    </p>
                                </div>

                                <div class="flex items-center justify-between text-sm text-gray-500 pt-4 border-t border-blue-200 dark:border-blue-800">
                                    <div>
                                        <?php if ($review['status'] === 'pending'): ?>
                                            <span class="badge badge-warning">⏳ Menunggu Persetujuan</span>
                                        <?php elseif ($review['status'] === 'approved'): ?>
                                            <span class="badge badge-success">✅ Disetujui</span>
                                        <?php else: ?>
                                            <span class="badge badge-danger">❌ Ditolak</span>
                                        <?php endif; ?>
                                    </div>
                                    <span><?php echo timeAgo($review['created_at']); ?></span>
                                </div>

                                <div class="mt-4 pt-4 border-t border-blue-200 dark:border-blue-800">
                                    <a href="/perpustakaan/user/book_detail.php?id=<?php echo $review['book_id']; ?>#review" class="text-blue-600 hover:underline text-sm font-500">
                                        ✏️ Edit Review
                                    </a>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <?php else: ?>
                        <div class="text-center py-12">
                            <p class="text-gray-500 dark:text-gray-400 text-lg">Anda belum menulis review apapun</p>
                            <p class="text-gray-500 dark:text-gray-400 text-sm mt-2">
                                Mulai membaca dan bagikan pendapat Anda tentang buku favorit!
                            </p>
                        </div>
                        <?php endif; ?>
                    </div>

                    <div class="mt-8 grid grid-cols-2 gap-4">
                        <div class="card p-6 text-center">
                            <p class="text-3xl font-bold text-blue-600 mb-2"><?php echo count($reviews); ?></p>
                            <p class="text-gray-600 dark:text-gray-400">Review Ditulis</p>
                        </div>
                        <div class="card p-6 text-center">
                            <p class="text-3xl font-bold text-green-600 mb-2">
                                <?php 
                                $approved = array_filter($reviews, function($r) { return $r['status'] === 'approved'; });
                                echo count($approved);
                                ?>
                            </p>
                            <p class="text-gray-600 dark:text-gray-400">Disetujui</p>
                        </div>
                    </div>
                </div>
            </div>

        </div>

        <?php require_once __DIR__ . '/../includes/footer.php'; ?>
    </div>
</div>
</body>
</html>
