<?php
session_start();
require_once __DIR__ . '/../config/database.php';

checkUser();

$book_id = (int)($_GET['id'] ?? 0);

if ($book_id <= 0) {
    redirect('/perpustakaan/user/books.php');
}

try {
    $stmt = $pdo->prepare("
        SELECT b.*, c.name as category_name, a.name as author_name, a.bio as author_bio, p.name as publisher_name
        FROM books b
        JOIN categories c ON b.category_id = c.id
        JOIN authors a ON b.author_id = a.id
        JOIN publishers p ON b.publisher_id = p.id
        WHERE b.id = ?
    ");
    $stmt->execute([$book_id]);
    $book = $stmt->fetch();

    if (!$book) {
        redirect('/perpustakaan/user/books.php');
    }

    $page_title = $book['title'];

    $stmt = $pdo->prepare("
        SELECT r.*, u.full_name
        FROM reviews r
        JOIN users u ON r.user_id = u.id
        WHERE r.book_id = ? AND r.status = 'approved'
        ORDER BY r.created_at DESC
    ");
    $stmt->execute([$book_id]);
    $reviews = $stmt->fetchAll();

    $stmt = $pdo->prepare("SELECT * FROM reviews WHERE book_id = ? AND user_id = ?");
    $stmt->execute([$book_id, $_SESSION['user_id']]);
    $user_review = $stmt->fetch();
    $stmt = $pdo->prepare("SELECT * FROM loans WHERE book_id = ? AND user_id = ? AND status = 'borrowed'");
    $stmt->execute([$book_id, $_SESSION['user_id']]);
    $user_borrow = $stmt->fetch();

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
    exit;
}

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add_review') {
        $rating  = (int)($_POST['rating'] ?? 0);
        $comment = sanitize($_POST['comment'] ?? '');

        if ($rating < 1 || $rating > 5) {
            $error = 'Rating harus antara 1-5 bintang!';
        } else {
            try {
                if ($user_review) {
                    // Edit: reset to pending so admin re-moderates
                    $stmt = $pdo->prepare("UPDATE reviews SET rating = ?, comment = ?, status = 'pending', updated_at = NOW() WHERE book_id = ? AND user_id = ?");
                    $stmt->execute([$rating, $comment, $book_id, $_SESSION['user_id']]);
                    $message = 'Ulasan diperbarui dan menunggu persetujuan admin.';
                } else {
                    $stmt = $pdo->prepare("INSERT INTO reviews (book_id, user_id, rating, comment, status) VALUES (?, ?, ?, ?, 'pending')");
                    $stmt->execute([$book_id, $_SESSION['user_id'], $rating, $comment]);
                    $message = 'Ulasan berhasil dikirim! Menunggu persetujuan admin.';
                }
                // Refresh user_review data after save
                $stmt = $pdo->prepare("SELECT * FROM reviews WHERE book_id = ? AND user_id = ?");
                $stmt->execute([$book_id, $_SESSION['user_id']]);
                $user_review = $stmt->fetch();
            } catch (PDOException $e) {
                $error = 'Error: ' . $e->getMessage();
            }
        }
    }
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
                        <a href="/perpustakaan/user/books.php" class="text-gray-600 hover:text-gray-900">← Kembali</a>
                        <h1 class="text-xl font-bold text-gray-900 dark:text-white">Detail Buku</h1>
                    </div>
                    <div class="flex items-center gap-4">
                    </div>
                </div>
            </div>
        </nav>

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <div class="card p-8 mb-8">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                    <div class="flex flex-col items-center justify-center">
                        <?php
                        $cover_path = __DIR__ . '/../assets/uploads/covers/' . ($book['cover_image'] ?? '');
                        $has_cover  = !empty($book['cover_image']) && file_exists($cover_path);
                        ?>
                        <?php if ($has_cover): ?>
                        <div class="w-full aspect-[3/4] rounded-2xl overflow-hidden mb-6 border border-gray-200 dark:border-gray-700 shadow-md">
                            <img src="/perpustakaan/assets/uploads/covers/<?php echo htmlspecialchars($book['cover_image']); ?>"
                                 alt="<?php echo htmlspecialchars($book['title']); ?>"
                                 class="w-full h-full object-cover">
                        </div>
                        <?php else: ?>
                        <div class="w-full aspect-[3/4] bg-primary/10 rounded-2xl flex items-center justify-center text-primary text-8xl mb-6 border border-primary/20"><i class="ph-duotone ph-book"></i></div>
                        <?php endif; ?>
                        <?php if ($book['available_stock'] > 0): ?>
                        <a href="/perpustakaan/user/borrow.php?book_id=<?php echo $book['id']; ?>" class="w-full bg-primary hover:bg-primary-dark text-white font-semibold py-3 px-4 rounded-xl transition-all shadow-sm flex items-center justify-center gap-2">
                            <i class="ph ph-tray-arrow-down text-xl"></i> Pinjam Buku
                        </a>
                        <?php else: ?>
                        <button class="w-full bg-gray-300 text-gray-500 font-semibold py-3 px-4 rounded-xl cursor-not-allowed flex items-center justify-center gap-2" disabled>
                            Habis
                        </button>
                        <?php endif; ?>
                    </div>

                    <div class="md:col-span-2">
                        <h1 class="text-3xl font-bold text-gray-900 dark:text-white mb-2"><?php echo htmlspecialchars($book['title']); ?></h1>
                        
                        <div class="flex items-center gap-4 mb-6 flex-wrap">
                            <div class="flex items-center gap-2">
                                <span class="text-2xl"><?php echo generateStars(getAverageRating($book_id)); ?></span>
                                <span class="text-gray-600 dark:text-gray-400"><?php echo getAverageRating($book_id); ?> (<?php echo getReviewCount($book_id); ?> ulasan)</span>
                            </div>
                        </div>

                        <div class="space-y-4 mb-6">
                            <div class="flex items-start gap-4">
                                <span class="text-2xl">✍️</span>
                                <div>
                                    <p class="text-sm text-gray-500">Penulis</p>
                                    <p class="font-500 text-lg"><?php echo htmlspecialchars($book['author_name']); ?></p>
                                </div>
                            </div>

                            <div class="flex items-start gap-4">
                                <span class="text-2xl">🏢</span>
                                <div>
                                    <p class="text-sm text-gray-500">Penerbit</p>
                                    <p class="font-500 text-lg"><?php echo htmlspecialchars($book['publisher_name']); ?></p>
                                </div>
                            </div>

                            <div class="flex items-start gap-4">
                                <span class="text-2xl">🏷️</span>
                                <div>
                                    <p class="text-sm text-gray-500">Kategori</p>
                                    <p class="font-500 text-lg"><?php echo htmlspecialchars($book['category_name']); ?></p>
                                </div>
                            </div>

                            <div class="flex items-start gap-4">
                                <span class="text-2xl">📄</span>
                                <div>
                                    <p class="text-sm text-gray-500">Detail Buku</p>
                                    <p class="font-500 text-sm">
                                        <?php echo $book['pages']; ?> halaman | 
                                        ISBN: <?php echo htmlspecialchars($book['isbn'] ?? 'N/A'); ?> | 
                                        Terbit: <?php echo formatDate($book['publication_date']); ?>
                                    </p>
                                </div>
                            </div>

                            <div class="flex items-start gap-4">
                                <span class="text-2xl">📦</span>
                                <div>
                                    <p class="text-sm text-gray-500">Ketersediaan</p>
                                    <p class="font-500 text-lg">
                                        <?php echo $book['available_stock']; ?> / <?php echo $book['stock']; ?> tersedia
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mt-8 pt-8 border-t border-gray-200 dark:border-gray-700">
                    <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4">Deskripsi Buku</h2>
                    <p class="text-gray-600 dark:text-gray-400 leading-relaxed">
                        <?php echo nl2br(htmlspecialchars($book['description'] ?? 'Tidak ada deskripsi.')); ?>
                    </p>
                </div>
            </div>

            <div class="card p-8 mb-8">
                <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-6">Rating & Ulasan (<?php echo getReviewCount($book_id); ?>)</h2>

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

                <?php
                $review_status = $user_review['status'] ?? null;
                $can_edit      = !$user_review || in_array($review_status, ['approved', 'rejected']);
                $is_pending    = $user_review && $review_status === 'pending';
                ?>

                <?php if ($is_pending): ?>
                <!-- Pending state: read-only card -->
                <div class="mb-8 p-6 bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-700/50 rounded-xl">
                    <div class="flex items-start gap-4">
                        <div class="w-10 h-10 rounded-full bg-amber-100 dark:bg-amber-800/40 flex items-center justify-center shrink-0">
                            <i class="ph ph-hourglass text-xl text-amber-600 dark:text-amber-400"></i>
                        </div>
                        <div class="flex-1">
                            <h3 class="font-bold text-amber-800 dark:text-amber-300 mb-1">Ulasan Menunggu Persetujuan</h3>
                            <p class="text-sm text-amber-700 dark:text-amber-400 mb-4">Ulasan Anda sedang ditinjau oleh admin dan akan segera dipublikasikan.</p>

                            <!-- Read-only preview of their review -->
                            <div class="bg-white dark:bg-gray-800 border border-amber-200 dark:border-amber-700/40 rounded-xl p-4">
                                <div class="flex items-center gap-0.5 mb-2">
                                    <?php echo generateStars((int)$user_review['rating']); ?>
                                    <span class="text-xs text-gray-500 ml-2"><?php echo $user_review['rating']; ?>/5</span>
                                </div>
                                <p class="text-sm text-gray-600 dark:text-gray-400 italic"><?php echo nl2br(htmlspecialchars($user_review['comment'])); ?></p>
                            </div>

                            <p class="text-xs text-amber-600 dark:text-amber-500 mt-3">
                                <i class="ph ph-clock mr-1"></i>
                                Dikirim <?php echo timeAgo($user_review['created_at']); ?> · Tidak dapat diedit saat menunggu persetujuan
                            </p>
                        </div>
                    </div>
                </div>

                <?php else: ?>
                <!-- Form: new review or edit after approved/rejected -->
                <div class="mb-8 p-6 bg-gray-50 dark:bg-gray-800/60 border border-gray-100 dark:border-gray-700 rounded-xl">
                    <?php if ($user_review && $review_status === 'rejected'): ?>
                    <div class="flex items-center gap-2 mb-4 p-3 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-700/40 rounded-lg">
                        <i class="ph ph-x-circle text-red-500 text-lg"></i>
                        <p class="text-sm text-red-700 dark:text-red-400">Ulasan Anda sebelumnya ditolak. Silakan perbaiki dan kirim ulang.</p>
                    </div>
                    <?php elseif ($user_review && $review_status === 'approved'): ?>
                    <div class="flex items-center gap-2 mb-4 p-3 bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-200 dark:border-emerald-700/40 rounded-lg">
                        <i class="ph ph-check-circle text-emerald-500 text-lg"></i>
                        <p class="text-sm text-emerald-700 dark:text-emerald-400">Ulasan Anda telah disetujui. Edit di bawah jika ingin memperbarui (akan ditinjau ulang).</p>
                    </div>
                    <?php endif; ?>

                    <h3 class="font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                        <i class="ph ph-pencil-simple text-primary"></i>
                        <?php echo $user_review ? 'Edit Ulasan Anda' : 'Tulis Ulasan'; ?>
                    </h3>

                    <form method="POST">
                        <input type="hidden" name="action" value="add_review">

                        <div class="form-group mb-4">
                            <label class="block text-gray-700 dark:text-gray-300 font-semibold mb-2 text-sm">Rating</label>
                            <div class="flex gap-1" id="ratingStars">
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                <label class="cursor-pointer">
                                    <input type="radio" name="rating" value="<?php echo $i; ?>"
                                        <?php echo ($user_review && $user_review['rating'] == $i) ? 'checked' : ''; ?>
                                        style="display:none">
                                    <span class="text-4xl transition hover:scale-125"
                                          style="display:inline-block;"
                                          data-rating="<?php echo $i; ?>">★</span>
                                </label>
                                <?php endfor; ?>
                            </div>
                            <p class="text-xs text-gray-400 mt-1">Klik bintang untuk memberi rating (1–5)</p>
                        </div>

                        <div class="form-group mb-5">
                            <label class="block text-gray-700 dark:text-gray-300 font-semibold mb-2 text-sm">Komentar</label>
                            <textarea name="comment" placeholder="Tulis pendapat Anda tentang buku ini..."
                                      style="height:100px;" class="w-full px-4 py-3 border border-gray-200 dark:border-gray-600 rounded-xl focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary bg-white dark:bg-gray-900 text-gray-900 dark:text-white resize-none transition"
                            ><?php echo $user_review ? htmlspecialchars($user_review['comment']) : ''; ?></textarea>
                        </div>

                        <button type="submit" class="btn btn-primary flex items-center gap-2">
                            <i class="ph ph-paper-plane-tilt text-lg"></i>
                            <?php echo $user_review ? 'Perbarui & Kirim Ulang' : 'Kirim Ulasan'; ?>
                        </button>
                    </form>
                </div>
                <?php endif; ?>

                <div class="space-y-4">
                    <?php if (count($reviews) > 0): ?>
                        <?php foreach ($reviews as $review): ?>
                        <div class="p-5 border border-gray-100 dark:border-gray-700 rounded-xl hover:bg-gray-50 dark:hover:bg-slate-800/40 transition-colors">
                            <div class="flex items-start justify-between gap-4 mb-3">
                                <div class="flex items-center gap-3">
                                    <div class="w-9 h-9 rounded-full bg-primary/10 text-primary flex items-center justify-center font-bold text-sm shrink-0">
                                        <?php echo strtoupper(substr($review['full_name'], 0, 1)); ?>
                                    </div>
                                    <div>
                                        <p class="font-semibold text-gray-900 dark:text-white text-sm"><?php echo htmlspecialchars($review['full_name']); ?></p>
                                        <p class="text-xs text-gray-500"><?php echo timeAgo($review['created_at']); ?></p>
                                    </div>
                                </div>
                                <div class="flex items-center gap-0.5 shrink-0">
                                    <?php echo generateStars((int)$review['rating']); ?>
                                </div>
                            </div>
                            <p class="text-sm text-gray-600 dark:text-gray-400 leading-relaxed pl-12"><?php echo nl2br(htmlspecialchars($review['comment'])); ?></p>
                        </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                    <div class="text-center py-8">
                        <p class="text-gray-500">Belum ada ulasan. Jadilah yang pertama!</p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>


        <?php require_once __DIR__ . '/../includes/footer.php'; ?>
    </div>
</div>

<script>
document.querySelectorAll('[data-rating]').forEach(star => {
    star.addEventListener('click', function() {
        const rating = this.dataset.rating;
        document.querySelector('input[name="rating"]').value = rating;
        updateStars(rating);
    });

    star.addEventListener('mouseover', function() {
        const rating = this.dataset.rating;
        document.querySelectorAll('[data-rating]').forEach(s => {
            if (s.dataset.rating <= rating) {
                s.style.color = '#fbbf24'; 
                s.style.textShadow = '0 0 5px rgba(251, 191, 36, 0.5)';
            } else {
                s.style.color = '#d1d5db';
                s.style.textShadow = 'none';
            }
        });
    });

    star.addEventListener('mouseleave', function() {
        const rating = document.querySelector('input[name="rating"]').value;
        updateStars(rating);
    });
});

function updateStars(rating) {
    document.querySelectorAll('[data-rating]').forEach(s => {
        if (s.dataset.rating <= rating) {
            s.style.color = '#fbbf24';
            s.style.textShadow = '0 0 3px rgba(251, 191, 36, 0.3)';
        } else {
            s.style.color = '#d1d5db';
            s.style.textShadow = 'none';
        }
    });
}

const initialRating = document.querySelector('input[name="rating"]').value;
if (initialRating) {
    updateStars(initialRating);
}
</script>
</body>
</html>
