<?php
session_start();
require_once __DIR__ . '/../config/database.php';

checkUser();

$page_title = 'Katalog Buku';

$search = sanitize($_GET['search'] ?? '');
$category_id = (int)($_GET['category'] ?? 0);

try {
    $categories = $pdo->query("SELECT * FROM categories ORDER BY name")->fetchAll();

    $query = "SELECT b.*, c.name as category_name, a.name as author_name,
               COALESCE((SELECT AVG(r.rating) FROM reviews r WHERE r.book_id = b.id AND r.status = 'approved'), 0) as avg_rating,
               COALESCE((SELECT COUNT(r.id)  FROM reviews r WHERE r.book_id = b.id AND r.status = 'approved'), 0) as review_count
               FROM books b JOIN categories c ON b.category_id = c.id JOIN authors a ON b.author_id = a.id WHERE 1=1";
    $params = [];

    if ($search) {
        $query .= " AND (b.title LIKE ? OR a.name LIKE ?)";
        $params[] = "%$search%";
        $params[] = "%$search%";
    }

    if ($category_id > 0) {
        $query .= " AND b.category_id = ?";
        $params[] = $category_id;
    }

    $query .= " ORDER BY b.created_at DESC";

    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $books = $stmt->fetchAll();

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
    $books = [];
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
                        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Katalog Buku</h1>
                    </div>
                    <div class="flex items-center gap-4">
                    </div>
                </div>
            </div>
        </nav>

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <div class="card p-6 mb-8">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-500 mb-2">Cari Buku</label>
                        <form method="GET" class="flex gap-2">
                            <input type="text" name="search" placeholder="Judul atau penulis..." value="<?php echo htmlspecialchars($search); ?>" class="flex-1 px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary">
                            <button type="submit" class="bg-primary hover:bg-primary-dark text-white px-4 py-2 rounded-lg transition-colors flex items-center justify-center"><i class="ph ph-magnifying-glass text-lg"></i></button>
                        </form>
                    </div>
                    <div>
                        <label class="block text-sm font-500 mb-2">Kategori</label>
                        <form method="GET" class="flex gap-2">
                            <select name="category" onchange="this.form.submit()" class="flex-1 px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary">
                                <option value="">Semua Kategori</option>
                                <?php foreach ($categories as $cat): ?>
                                <option value="<?php echo $cat['id']; ?>" <?php echo $category_id == $cat['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($cat['name']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </form>
                    </div>
                    <div>
                        <label class="block text-sm font-500 mb-2">Status</label>
                        <div class="flex gap-2 items-center h-10">
                            <span class="text-sm text-gray-600"><?php echo count($books); ?> buku ditemukan</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                <?php if (count($books) > 0): ?>
                    <?php foreach ($books as $book): ?>
                    <a href="/perpustakaan/user/book_detail.php?id=<?php echo $book['id']; ?>" class="book-card">
                        <?php
                        $cover_path = __DIR__ . '/../assets/uploads/covers/' . ($book['cover_image'] ?? '');
                        $has_cover  = !empty($book['cover_image']) && file_exists($cover_path);
                        ?>
                        <?php if ($has_cover): ?>
                        <div class="book-card-image p-0 overflow-hidden">
                            <img src="/perpustakaan/assets/uploads/covers/<?php echo htmlspecialchars($book['cover_image']); ?>"
                                 alt="<?php echo htmlspecialchars($book['title']); ?>"
                                 class="w-full h-full object-cover">
                        </div>
                        <?php else: ?>
                        <div class="book-card-image" style="background: linear-gradient(135deg, hsl(<?php echo ($book['id'] * 47) % 360; ?>, 70%, 60%) 0%, hsl(<?php echo ($book['id'] * 83) % 360; ?>, 70%, 60%) 100%);">
                            <i class="ph-duotone ph-book text-5xl text-white/80"></i>
                        </div>
                        <?php endif; ?>
                        <div class="book-card-body">
                            <h3 class="book-card-title"><?php echo htmlspecialchars($book['title']); ?></h3>
                            <p class="book-card-author">oleh <?php echo htmlspecialchars(substr($book['author_name'], 0, 30)); ?></p>
                            <div class="flex items-center justify-between">
                                <span class="book-card-rating flex items-center gap-0.5">
                                    <?php echo generateStars((float)$book['avg_rating']); ?>
                                    <?php if ($book['review_count'] > 0): ?>
                                    <span class="text-xs text-gray-500 ml-1">(<?php echo $book['review_count']; ?>)</span>
                                    <?php endif; ?>
                                </span>
                                <?php if ($book['available_stock'] > 0): ?>
                                    <span class="badge badge-success">Tersedia</span>
                                <?php else: ?>
                                    <span class="badge badge-danger">Habis</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </a>
                    <?php endforeach; ?>
                <?php else: ?>
                <div class="col-span-full text-center py-12">
                    <p class="text-gray-500 dark:text-gray-400 text-lg">Tidak ada buku yang ditemukan</p>
                </div>
                <?php endif; ?>
            </div>

        </div>

            <?php require_once __DIR__ . '/../includes/footer.php'; ?>
    </div>
</div>
</body>
</html>
