<?php
session_start();
require_once __DIR__ . '/config/database.php';

$page_title = 'Beranda';

try {
    $total_books = $pdo->query("SELECT COUNT(*) as count FROM books")->fetch()['count'];
    $total_members = $pdo->query("SELECT COUNT(*) as count FROM users WHERE role = 'user'")->fetch()['count'];
    $total_loans = $pdo->query("SELECT COUNT(*) as count FROM loans")->fetch()['count'];
    $categories = $pdo->query("SELECT * FROM categories LIMIT 6")->fetchAll();
    $featured_books = $pdo->query("
        SELECT b.*, a.name as author_name,
               COALESCE((SELECT AVG(r.rating) FROM reviews r WHERE r.book_id = b.id AND r.status = 'approved'), 0) as avg_rating,
               COALESCE((SELECT COUNT(r.id)  FROM reviews r WHERE r.book_id = b.id AND r.status = 'approved'), 0) as review_count
        FROM books b
        JOIN authors a ON b.author_id = a.id
        ORDER BY b.created_at DESC
        LIMIT 8
    ")->fetchAll();
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}

require_once __DIR__ . '/includes/header.php';
?>

<body class="bg-white dark:bg-gray-900">
    <nav class="navbar sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-primary/10 rounded-xl flex items-center justify-center text-primary shrink-0">
                        <i class="ph-duotone ph-books text-2xl"></i>
                    </div>
                    <div>
                        <h1 class="font-bold text-xl text-gray-900 dark:text-white leading-tight">Perpustakaan</h1>
                        <p class="text-xs text-gray-500 dark:text-gray-400 font-medium">UKK26</p>
                    </div>
                </div>

                <div class="flex items-center gap-6">
                    <?php if (!isLoggedIn()): ?>
                    <a href="/perpustakaan/auth/login.php" class="btn btn-primary">Login</a>
                    <a href="/perpustakaan/auth/register.php" class="btn btn-secondary">Daftar</a>
                    <?php else: ?>
                    <a href="<?php echo isAdmin() ? '/perpustakaan/admin/dashboard.php' : '/perpustakaan/user/dashboard.php'; ?>" class="text-blue-600 hover:text-blue-700 font-500">Dashboard</a>
                    <a href="/perpustakaan/auth/logout.php" class="btn btn-secondary">Logout</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </nav>

    <section class="bg-primary/5 py-24 border-b border-gray-200 dark:border-gray-800">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <h1 class="text-5xl md:text-6xl font-extrabold mb-6 text-gray-900 dark:text-white tracking-tight">Perpustakaan <span class="text-primary">UKK 26</span></h1>
            <p class="text-xl text-gray-600 dark:text-gray-400 mb-10 max-w-2xl mx-auto">Jelajahi koleksi buku dan kelola peminjaman dengan mudah melalui platform Perpustakaan UKK 26</p>
            
            <div class="flex gap-4 justify-center">
                <?php if (!isLoggedIn()): ?>
                <a href="/perpustakaan/auth/register.php" class="btn btn-primary px-8 py-3 text-lg rounded-full shadow-lg hover:shadow-xl transition-all hover:-translate-y-0.5">Mulai Sekarang</a>
                <a href="#koleksi" class="btn bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 text-gray-700 dark:text-gray-300 hover:border-primary hover:text-primary px-8 py-3 text-lg rounded-full shadow-sm transition-all">Jelajahi Koleksi</a>
                <?php else: ?>
                <a href="<?php echo isAdmin() ? '/perpustakaan/admin/dashboard.php' : '/perpustakaan/user/dashboard.php'; ?>" class="btn btn-primary px-8 py-3 text-lg rounded-full shadow-lg hover:shadow-xl transition-all hover:-translate-y-0.5">Mulai Jelajah</a>
                <?php endif; ?>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mt-16 max-w-4xl mx-auto">
                <div class="bg-white dark:bg-gray-800 rounded-2xl p-6 border border-gray-100 dark:border-gray-700 shadow-sm flex items-center justify-center gap-4 hover:-translate-y-1 transition-transform">
                    <div class="w-14 h-14 rounded-full bg-primary/10 flex items-center justify-center text-primary shrink-0">
                        <i class="ph ph-books text-2xl"></i>
                    </div>
                    <div class="text-left">
                        <p class="text-3xl font-bold text-gray-900 dark:text-white"><?php echo $total_books; ?></p>
                        <p class="text-sm text-gray-500 font-medium mt-1">Judul Buku</p>
                    </div>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-2xl p-6 border border-gray-100 dark:border-gray-700 shadow-sm flex items-center justify-center gap-4 hover:-translate-y-1 transition-transform">
                    <div class="w-14 h-14 rounded-full bg-emerald-500/10 flex items-center justify-center text-emerald-500 shrink-0">
                        <i class="ph ph-users text-2xl"></i>
                    </div>
                    <div class="text-left">
                        <p class="text-3xl font-bold text-gray-900 dark:text-white"><?php echo $total_members; ?></p>
                        <p class="text-sm text-gray-500 font-medium mt-1">Anggota Aktif</p>
                    </div>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-2xl p-6 border border-gray-100 dark:border-gray-700 shadow-sm flex items-center justify-center gap-4 hover:-translate-y-1 transition-transform">
                    <div class="w-14 h-14 rounded-full bg-amber-500/10 flex items-center justify-center text-amber-500 shrink-0">
                        <i class="ph ph-tray-arrow-up text-2xl"></i>
                    </div>
                    <div class="text-left">
                        <p class="text-3xl font-bold text-gray-900 dark:text-white"><?php echo $total_loans; ?></p>
                        <p class="text-sm text-gray-500 font-medium mt-1">Peminjaman</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    
    <section class="py-16 bg-gray-50 dark:bg-gray-900">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <h2 class="text-3xl font-bold text-gray-900 dark:text-white mb-12 text-center">Kategori Buku</h2>
            
            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-6">
                <?php foreach ($categories as $index => $cat):
                    $cat_icon = !empty($cat['icon']) && str_starts_with($cat['icon'], 'ph-')
                        ? $cat['icon']
                        : 'ph-books';
                ?>
                <a href="/perpustakaan/user/books.php?category=<?php echo $cat['id']; ?>" class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 p-6 rounded-2xl text-center hover:border-primary hover:shadow-md transition group">
                    <div class="w-16 h-16 mx-auto bg-gray-50 dark:bg-gray-700 rounded-full flex items-center justify-center text-gray-500 dark:text-gray-400 group-hover:bg-primary/10 group-hover:text-primary transition-colors mb-4">
                        <i class="ph-duotone <?php echo htmlspecialchars($cat_icon); ?> text-3xl"></i>
                    </div>
                    <p class="font-semibold text-gray-900 dark:text-white group-hover:text-primary transition-colors"><?php echo htmlspecialchars($cat['name']); ?></p>
                    <?php if (!empty($cat['description'])): ?>
                    <p class="text-xs text-gray-400 mt-1 line-clamp-1"><?php echo htmlspecialchars(substr($cat['description'], 0, 40)); ?></p>
                    <?php endif; ?>
                </a>
                <?php endforeach; ?>
            </div>
        </div>
    </section>


    <section id="koleksi" class="py-16 bg-white dark:bg-gray-800">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center mb-12">
                <h2 class="text-3xl font-bold text-gray-900 dark:text-white">Koleksi Terbaru</h2>
                <?php if (isLoggedIn()): ?>
                <a href="/perpustakaan/user/books.php" class="text-blue-600 hover:text-blue-700 font-500">Lihat Semua →</a>
                <?php endif; ?>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                <?php foreach ($featured_books as $book):
                    $cover_path = __DIR__ . '/assets/uploads/covers/' . ($book['cover_image'] ?? '');
                    $has_cover  = !empty($book['cover_image']) && file_exists($cover_path);
                ?>
                <div class="book-card border border-gray-200 dark:border-gray-700 hover:border-primary transition group">
                    <?php if ($has_cover): ?>
                    <div class="book-card-image p-0 overflow-hidden">
                        <img src="/perpustakaan/assets/uploads/covers/<?php echo htmlspecialchars($book['cover_image']); ?>"
                             alt="<?php echo htmlspecialchars($book['title']); ?>"
                             class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500">
                    </div>
                    <?php else: ?>
                    <div class="book-card-image flex items-center justify-center group-hover:bg-primary/5 transition-colors border-b border-gray-200 dark:border-gray-700"
                         style="background: linear-gradient(135deg, hsl(<?php echo ($book['id'] * 47) % 360; ?>, 60%, 92%) 0%, hsl(<?php echo ($book['id'] * 83) % 360; ?>, 60%, 88%) 100%);">
                        <i class="ph-duotone ph-book-open text-6xl group-hover:text-primary transition-colors" style="color: hsl(<?php echo ($book['id'] * 47) % 360; ?>, 50%, 55%);"></i>
                    </div>
                    <?php endif; ?>
                    <div class="book-card-body">
                        <h3 class="book-card-title"><?php echo htmlspecialchars($book['title']); ?></h3>
                        <p class="book-card-author">oleh <?php echo htmlspecialchars(substr($book['author_name'], 0, 30)); ?></p>
                        <div class="flex items-center justify-between mt-3">
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
                </div>
                <?php endforeach; ?>
            </div>

            <?php if (!isLoggedIn()): ?>
            <div class="text-center mt-12">
                <p class="text-gray-600 dark:text-gray-400 mb-4">Login untuk melihat koleksi lengkap dan meminjam buku</p>
                <a href="/perpustakaan/auth/login.php" class="btn btn-primary">Login Sekarang</a>
            </div>
            <?php endif; ?>
        </div>
    </section>


    <section class="py-20 bg-gray-50 dark:bg-gray-900">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16">
                <h2 class="text-3xl md:text-4xl font-bold text-gray-900 dark:text-white mb-4">Platform Cerdas Untuk Literasi</h2>
                <p class="text-lg text-gray-600 dark:text-gray-400 max-w-2xl mx-auto">Kami menyediakan berbagai fitur untuk mempermudah pengalaman membaca dan meminjam buku Anda.</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                <div class="bg-white dark:bg-gray-800 p-8 rounded-2xl border border-gray-200 dark:border-gray-700 hover:border-primary hover:shadow-lg transition group">
                    <div class="w-14 h-14 bg-primary/10 text-primary rounded-xl flex items-center justify-center mb-6 group-hover:scale-110 transition-transform">
                        <i class="ph-duotone ph-books text-3xl"></i>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-3">Katalog Lengkap</h3>
                    <p class="text-gray-600 dark:text-gray-400">Ribuan judul buku dari berbagai kategori dan genre tersedia untuk dipinjam.</p>
                </div>

                <div class="bg-white dark:bg-gray-800 p-8 rounded-2xl border border-gray-200 dark:border-gray-700 hover:border-primary hover:shadow-lg transition group">
                    <div class="w-14 h-14 bg-primary/10 text-primary rounded-xl flex items-center justify-center mb-6 group-hover:scale-110 transition-transform">
                        <i class="ph-duotone ph-devices text-3xl"></i>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-3">Interface Modern</h3>
                    <p class="text-gray-600 dark:text-gray-400">Antarmuka yang responsif dan sangat nyaman untuk penggunaan lintas perangkat.</p>
                </div>

                <div class="bg-white dark:bg-gray-800 p-8 rounded-2xl border border-gray-200 dark:border-gray-700 hover:border-primary hover:shadow-lg transition group">
                    <div class="w-14 h-14 bg-primary/10 text-primary rounded-xl flex items-center justify-center mb-6 group-hover:scale-110 transition-transform">
                        <i class="ph-duotone ph-star text-3xl"></i>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-3">Rating & Ulasan</h3>
                    <p class="text-gray-600 dark:text-gray-400">Bagikan pendapat Anda dan baca ulasan dari pembaca lain tentang buku favorit.</p>
                </div>

                <div class="bg-white dark:bg-gray-800 p-8 rounded-2xl border border-gray-200 dark:border-gray-700 hover:border-primary hover:shadow-lg transition group">
                    <div class="w-14 h-14 bg-primary/10 text-primary rounded-xl flex items-center justify-center mb-6 group-hover:scale-110 transition-transform">
                        <i class="ph-duotone ph-tray-arrow-up text-3xl"></i>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-3">Kelola Peminjaman</h3>
                    <p class="text-gray-600 dark:text-gray-400">Pantau buku yang dipinjam dan kelola tenggat pengembalian dengan mudah.</p>
                </div>

                <div class="bg-white dark:bg-gray-800 p-8 rounded-2xl border border-gray-200 dark:border-gray-700 hover:border-primary hover:shadow-lg transition group">
                    <div class="w-14 h-14 bg-primary/10 text-primary rounded-xl flex items-center justify-center mb-6 group-hover:scale-110 transition-transform">
                        <i class="ph-duotone ph-magnifying-glass text-3xl"></i>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-3">Pencarian Pintar</h3>
                    <p class="text-gray-600 dark:text-gray-400">Cari buku berdasarkan judul, penulis, penerbit atau kategori secara realtime.</p>
                </div>

                <div class="bg-white dark:bg-gray-800 p-8 rounded-2xl border border-gray-200 dark:border-gray-700 hover:border-primary hover:shadow-lg transition group">
                    <div class="w-14 h-14 bg-primary/10 text-primary rounded-xl flex items-center justify-center mb-6 group-hover:scale-110 transition-transform">
                        <i class="ph-duotone ph-paint-brush text-3xl"></i>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-3">Desain Profesional</h3>
                    <p class="text-gray-600 dark:text-gray-400">Tampilan elegan yang tidak membosankan dan sangat nyaman di mata untuk pemakaian lama.</p>
                </div>
            </div>
        </div>
    </section>


    <section class="py-24 bg-gray-900 relative overflow-hidden">
        <div class="absolute inset-0 opacity-20">
            <div class="absolute -top-24 -left-24 w-96 h-96 rounded-full bg-primary blur-3xl"></div>
            <div class="absolute bottom-0 right-0 w-full h-1/2 bg-gradient-to-t from-primary/20 to-transparent"></div>
        </div>
        
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center relative z-10">
            <h2 class="text-4xl md:text-5xl font-bold mb-6 text-white tracking-tight">Siap Mulai Perjalanan Membaca?</h2>
            <p class="text-xl text-gray-300 mb-10 max-w-2xl mx-auto">Bergabunglah agar menjadi insan yang berguna bagi bangsa dan negara dengan membaca.</p>
            
            <?php if (!isLoggedIn()): ?>
            <div class="flex gap-4 justify-center">
                <a href="/perpustakaan/auth/register.php" class="btn bg-primary hover:bg-primary-dark text-white border-0 px-8 py-3 text-lg rounded-full shadow-[0_0_15px_rgba(79,70,229,0.5)] transition-all hover:-translate-y-0.5">Daftar Sekarang</a>
                <a href="/perpustakaan/auth/login.php" class="btn bg-transparent border border-gray-600 text-gray-300 hover:border-white hover:text-white px-8 py-3 text-lg rounded-full transition-all">Masuk Akun</a>
            </div>
            <?php else: ?>
            <a href="<?php echo isAdmin() ? '/perpustakaan/admin/dashboard.php' : '/perpustakaan/user/dashboard.php'; ?>" class="btn bg-primary hover:bg-primary-dark text-white border-0 px-8 py-3 text-lg rounded-full shadow-[0_0_15px_rgba(79,70,229,0.5)] transition-all hover:-translate-y-0.5">Buka Dashboard Saya</a>
            <?php endif; ?>
        </div>
    </section>


    <?php require_once __DIR__ . '/includes/footer.php'; ?>
</body>
</html>
