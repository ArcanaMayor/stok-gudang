<?php
if (!isset($_SESSION)) {
    session_start();
}
require_once __DIR__ . '/../config/database.php';

$current_page = basename($_SERVER['PHP_SELF']);
?>

<div class="sidebar w-64 h-screen overflow-y-auto fixed left-0 top-0 z-40">
    <div class="p-6 border-b border-gray-200 dark:border-gray-700">
        <a href="<?php echo isAdmin() ? '/perpustakaan/admin/dashboard.php' : '/perpustakaan/user/dashboard.php'; ?>" class="flex items-center gap-3">
            <div class="w-10 h-10 bg-primary/10 dark:bg-primary/20 text-primary rounded-lg flex items-center justify-center">
                <i class="ph ph-books text-2xl"></i>
            </div>
            <div>
                <h1 class="font-bold text-lg text-gray-900 dark:text-white tracking-tight">Perpustakaan</h1>
                <p class="text-xs text-gray-500 dark:text-gray-400 font-medium">UKK26</p>
            </div>
        </a>
    </div>

    <?php if (isAdmin()): ?>
    <nav class="p-4">
        <div class="sidebar-section-title">DASHBOARD</div>
        <a href="/perpustakaan/admin/dashboard.php" class="sidebar-link <?php echo $current_page === 'dashboard.php' ? 'active' : ''; ?>">
            <i class="ph ph-squares-four text-xl"></i>
            <span>Dashboard</span>
        </a>

        <div class="sidebar-section-title">MANAJEMEN</div>
        <a href="/perpustakaan/admin/books.php" class="sidebar-link <?php echo $current_page === 'books.php' ? 'active' : ''; ?>">
            <i class="ph ph-book-open text-xl"></i>
            <span>Buku</span>
        </a>
        <a href="/perpustakaan/admin/categories.php" class="sidebar-link <?php echo $current_page === 'categories.php' ? 'active' : ''; ?>">
            <i class="ph ph-tag text-xl"></i>
            <span>Kategori</span>
        </a>
        <a href="/perpustakaan/admin/authors.php" class="sidebar-link <?php echo $current_page === 'authors.php' ? 'active' : ''; ?>">
            <i class="ph ph-pen-nib text-xl"></i>
            <span>Penulis</span>
        </a>
        <a href="/perpustakaan/admin/publishers.php" class="sidebar-link <?php echo $current_page === 'publishers.php' ? 'active' : ''; ?>">
            <i class="ph ph-buildings text-xl"></i>
            <span>Penerbit</span>
        </a>
        <a href="/perpustakaan/admin/users.php" class="sidebar-link <?php echo $current_page === 'users.php' ? 'active' : ''; ?>">
            <i class="ph ph-users text-xl"></i>
            <span>Pengguna</span>
        </a>

        <div class="sidebar-section-title">KONTEN</div>
        <a href="/perpustakaan/admin/reviews.php" class="sidebar-link <?php echo $current_page === 'reviews.php' ? 'active' : ''; ?>">
            <i class="ph ph-star text-xl"></i>
            <span>Review</span>
            <?php
            try {
                $pending_reviews = $pdo->query("SELECT COUNT(*) FROM reviews WHERE status = 'pending'")->fetchColumn();
                if ($pending_reviews > 0) echo '<span class="ml-auto text-xs bg-amber-500 text-white font-bold px-2 py-0.5 rounded-full">' . $pending_reviews . '</span>';
            } catch (Exception $e) {}
            ?>
        </a>


    </nav>

    <?php elseif (isUser()): ?>
    <nav class="p-4">
        <div class="sidebar-section-title">MENU UTAMA</div>
        <a href="/perpustakaan/user/dashboard.php" class="sidebar-link <?php echo $current_page === 'dashboard.php' ? 'active' : ''; ?>">
            <i class="ph ph-squares-four text-xl"></i>
            <span>Dashboard</span>
        </a>
        <a href="/perpustakaan/user/books.php" class="sidebar-link <?php echo $current_page === 'books.php' ? 'active' : ''; ?>">
            <i class="ph ph-books text-xl"></i>
            <span>Katalog Buku</span>
        </a>

        <div class="sidebar-section-title">AKTIVITAS</div>
        <a href="/perpustakaan/user/borrow.php" class="sidebar-link <?php echo $current_page === 'borrow.php' ? 'active' : ''; ?>">
            <i class="ph ph-tray-arrow-up text-xl"></i>
            <span>Peminjaman</span>
        </a>
        <a href="/perpustakaan/user/return.php" class="sidebar-link <?php echo $current_page === 'return.php' ? 'active' : ''; ?>">
            <i class="ph ph-tray-arrow-down text-xl"></i>
            <span>Pengembalian</span>
        </a>
        <a href="/perpustakaan/user/reviews.php" class="sidebar-link <?php echo $current_page === 'reviews.php' ? 'active' : ''; ?>">
            <i class="ph ph-star text-xl"></i>
            <span>Rating & Review</span>
        </a>


    </nav>
    <?php endif; ?>
</div>

<div class="sidebar-overlay fixed inset-0 bg-black bg-opacity-50 z-30 hidden lg:hidden" onclick="toggleSidebar()"></div>

<style>
    .sidebar {
        background: white;
        transition: transform 0.3s ease;
    }

    body.dark .sidebar {
        background: #1a1f2e;
    }

    @media (max-width: 768px) {
        .sidebar {
            transform: translateX(-100%);
        }

        .sidebar.active {
            transform: translateX(0);
        }
    }
</style>

<script>
function toggleSidebar() {
    const sidebar = document.querySelector('.sidebar');
    sidebar.classList.toggle('active');
}
</script>
