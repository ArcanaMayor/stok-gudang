<?php
session_start();
require_once __DIR__ . '/../config/database.php';

checkUser();

$page_title = 'Peminjaman Buku';
$message = '';
$error = '';

$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    
    if ($action === 'borrow') {
        $book_id = (int)($_POST['book_id'] ?? 0);
        $duration = (int)($_POST['duration'] ?? 7);

        if ($book_id <= 0) {
            $error = 'Pilih buku terlebih dahulu!';
        } else {
            try {
                $stmt = $pdo->prepare("SELECT available_stock FROM books WHERE id = ?");
                $stmt->execute([$book_id]);
                $book = $stmt->fetch();

                if (!$book || $book['available_stock'] <= 0) {
                    $error = 'Buku tidak tersedia!';
                } else {
                    $stmt = $pdo->prepare("SELECT * FROM loans WHERE book_id = ? AND user_id = ? AND status = 'borrowed'");
                    $stmt->execute([$book_id, $user_id]);
                    if ($stmt->fetch()) {
                        $error = 'Anda sudah meminjam buku ini!';
                    } else {
                        $loan_date = date('Y-m-d');
                        $due_date = date('Y-m-d', strtotime("+$duration days"));

                        $stmt = $pdo->prepare("INSERT INTO loans (user_id, book_id, loan_date, due_date, status) VALUES (?, ?, ?, ?, 'borrowed')");
                        $stmt->execute([$user_id, $book_id, $loan_date, $due_date]);

                        $stmt = $pdo->prepare("UPDATE books SET available_stock = available_stock - 1 WHERE id = ?");
                        $stmt->execute([$book_id]);

                        $message = 'Buku berhasil dipinjam! Batas pengembalian: ' . formatDate($due_date);
                    }
                }
            } catch (PDOException $e) {
                $error = 'Error: ' . $e->getMessage();
            }
        }
    }
}

try {
    $stmt = $pdo->prepare("
        SELECT b.*, c.name as category_name, a.name as author_name
        FROM books b
        JOIN categories c ON b.category_id = c.id
        JOIN authors a ON b.author_id = a.id
        WHERE b.available_stock > 0
        ORDER BY b.title
    ");
    $stmt->execute();
    $available_books = $stmt->fetchAll();

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
                        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Peminjaman Buku</h1>
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
                        <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4">Pinjam Buku</h2>

                        <form method="POST" id="borrowForm">
                            <input type="hidden" name="action" value="borrow">

                            <div class="form-group mb-5 relative" id="bookSearchContainer">
                                <label class="block text-sm font-medium text-gray-700 mb-2">Cari Buku *</label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-400">
                                        <i class="ph ph-magnifying-glass text-lg"></i>
                                    </div>
                                    <input type="text" id="book_search" placeholder="Ketik judul buku..." 
                                        class="block w-full pl-10 pr-10 py-2.5 bg-gray-50 border border-gray-200 rounded-xl focus:bg-white focus:border-primary focus:ring-2 focus:ring-primary/20 outline-none transition-all text-gray-900" autocomplete="off" required>
                                    <input type="hidden" name="book_id" id="book_id" required>
                                    
                                    <div class="absolute inset-y-0 right-0 flex items-center pr-3">
                                        <button type="button" id="clear_search" class="text-gray-400 hover:text-gray-600 hidden focus:outline-none">
                                            <i class="ph-fill ph-x-circle text-lg"></i>
                                        </button>
                                    </div>
                                </div>
                                <ul id="book_dropdown" class="absolute z-50 w-full mt-1 bg-white border border-gray-100 rounded-xl shadow-lg max-h-60 overflow-y-auto hidden">
                                </ul>
                            </div>

                            <div class="form-group mb-6">
                                <label class="block text-sm font-medium text-gray-700 mb-2">Durasi Peminjaman *</label>
                                <div class="relative">
                                    <select name="duration" required class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl focus:bg-white focus:border-primary focus:ring-2 focus:ring-primary/20 outline-none transition-all appearance-none text-gray-900 cursor-pointer">
                                        <option value="7">7 Hari Peminjaman</option>
                                        <option value="14">14 Hari Peminjaman</option>
                                        <option value="21">21 Hari Peminjaman</option>
                                        <option value="30">30 Hari Peminjaman</option>
                                    </select>
                                    <div class="absolute inset-y-0 right-0 flex items-center px-4 pointer-events-none text-gray-400">
                                        <i class="ph ph-caret-down text-lg"></i>
                                    </div>
                                </div>
                            </div>

                            <div class="p-4 bg-primary/5 border border-primary/10 rounded-xl mb-6 flex gap-3">
                                <i class="ph-duotone ph-info text-primary text-xl flex-shrink-0"></i>
                                <p class="text-xs text-gray-600 leading-relaxed">
                                    <strong>Catatan:</strong> Peminjaman maksimal 30 hari. Pastikan Anda mengembalikan tepat waktu untuk menghindari denda.
                                </p>
                            </div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const availableBooks = <?php echo json_encode(array_values(array_map(function($b) { return ['id' => $b['id'], 'title' => $b['title'], 'author' => $b['author_name']]; }, $available_books))); ?>;
    const selectedBookId = <?php echo isset($_GET['book_id']) ? (int)$_GET['book_id'] : 'null'; ?>;
    
    const searchInput = document.getElementById('book_search');
    const hiddenInput = document.getElementById('book_id');
    const dropdown = document.getElementById('book_dropdown');
    const clearBtn = document.getElementById('clear_search');
    const container = document.getElementById('bookSearchContainer');

    function renderDropdown(books) {
        dropdown.innerHTML = '';
        if (books.length === 0) {
            dropdown.innerHTML = '<li class="px-4 py-3 text-sm text-gray-500 text-center">Buku tidak ditemukan</li>';
            dropdown.classList.remove('hidden');
            return;
        }

        books.forEach(book => {
            const li = document.createElement('li');
            li.className = 'px-4 py-3 hover:bg-gray-50 cursor-pointer border-b border-gray-50 last:border-0 transition-colors flex flex-col gap-1';
            li.innerHTML = `<span class="font-medium text-sm text-gray-900">${book.title}</span><span class="text-xs text-gray-500">oleh ${book.author}</span>`;
            
            li.addEventListener('click', () => {
                selectBook(book);
            });
            dropdown.appendChild(li);
        });
        dropdown.classList.remove('hidden');
    }

    function selectBook(book) {
        searchInput.value = book.title;
        hiddenInput.value = book.id;
        dropdown.classList.add('hidden');
        clearBtn.classList.remove('hidden');
        searchInput.classList.add('bg-primary/5', 'font-medium');
        searchInput.readOnly = true;
    }

    function clearSelection() {
        searchInput.value = '';
        hiddenInput.value = '';
        clearBtn.classList.add('hidden');
        searchInput.classList.remove('bg-primary/5', 'font-medium');
        searchInput.readOnly = false;
        searchInput.focus();
        renderDropdown(availableBooks);
    }

    if (selectedBookId) {
        const book = availableBooks.find(b => b.id == selectedBookId);
        if (book) selectBook(book);
    }
    searchInput.addEventListener('focus', () => {
        if (!searchInput.readOnly) renderDropdown(availableBooks);
    });

    searchInput.addEventListener('input', (e) => {
        const query = e.target.value.toLowerCase();
        const filtered = availableBooks.filter(b => b.title.toLowerCase().includes(query) || b.author.toLowerCase().includes(query));
        renderDropdown(filtered);
    });

    clearBtn.addEventListener('click', clearSelection);

    document.addEventListener('click', (e) => {
        if (!container.contains(e.target)) {
            dropdown.classList.add('hidden');
        }
    });
});
</script>

                            <button type="submit" class="w-full bg-primary hover:bg-primary-dark text-white font-semibold py-3 px-4 rounded-xl transition-all shadow-sm flex items-center justify-center gap-2">
                                <i class="ph ph-tray-arrow-up text-xl"></i> Pinjam Sekarang
                            </button>
                        </form>
                    </div>
                </div>

                <div class="lg:col-span-2">
                    <div class="card p-6">
                        <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-6">Buku yang Sedang Dipinjam (<?php echo count($current_loans); ?>)</h2>

                        <?php if (count($current_loans) > 0): ?>
                        <div class="space-y-4">
                            <?php foreach ($current_loans as $loan):
                                $cov_path = __DIR__ . '/../assets/uploads/covers/' . ($loan['cover_image'] ?? '');
                                $has_cov   = !empty($loan['cover_image']) && file_exists($cov_path);
                                $days_left = (strtotime($loan['due_date']) - time()) / 86400;
                            ?>
                            <div class="flex gap-4 p-4 border border-gray-200 dark:border-gray-700 rounded-xl hover:bg-gray-50 dark:hover:bg-gray-800/50 transition">
                                <?php if ($has_cov): ?>
                                <div class="w-12 h-16 rounded-lg overflow-hidden shrink-0 border border-gray-200 dark:border-gray-600 shadow-sm">
                                    <img src="/perpustakaan/assets/uploads/covers/<?php echo htmlspecialchars($loan['cover_image']); ?>"
                                         alt="<?php echo htmlspecialchars($loan['title']); ?>"
                                         class="w-full h-full object-cover">
                                </div>
                                <?php else: ?>
                                <div class="w-12 h-16 rounded-lg shrink-0 flex items-center justify-center font-bold text-white text-sm shadow-sm"
                                     style="background: linear-gradient(135deg, hsl(<?php echo ($loan['book_id'] * 47) % 360; ?>, 60%, 55%) 0%, hsl(<?php echo ($loan['book_id'] * 83) % 360; ?>, 60%, 45%) 100%)">
                                    <?php echo strtoupper(substr($loan['title'], 0, 1)); ?>
                                </div>
                                <?php endif; ?>

                                <div class="flex-1 min-w-0">
                                    <div class="flex items-start justify-between gap-2 mb-2">
                                        <div class="min-w-0">
                                            <h3 class="font-semibold text-gray-900 dark:text-white truncate"><?php echo htmlspecialchars($loan['title']); ?></h3>
                                            <p class="text-xs text-gray-500 mt-0.5"><?php echo htmlspecialchars($loan['author_name']); ?></p>
                                        </div>
                                        <div class="shrink-0">
                                            <?php if ($days_left < 0): ?>
                                            <span class="badge badge-danger flex items-center gap-1">
                                                <i class="ph-fill ph-clock text-xs"></i> Terlambat <?php echo abs(ceil($days_left)); ?> hari
                                            </span>
                                            <?php elseif ($days_left < 3): ?>
                                            <span class="badge badge-warning flex items-center gap-1">
                                                <i class="ph-fill ph-clock text-xs"></i> <?php echo ceil($days_left); ?> hari
                                            </span>
                                            <?php else: ?>
                                            <span class="badge badge-primary"><?php echo ceil($days_left); ?> hari</span>
                                            <?php endif; ?>
                                        </div>
                                    </div>

                                    <div class="flex items-center justify-between pt-2 border-t border-gray-100 dark:border-gray-700">
                                        <div class="flex gap-6 text-xs">
                                            <div>
                                                <p class="text-gray-400">Tanggal Pinjam</p>
                                                <p class="font-semibold text-gray-700 dark:text-gray-300"><?php echo formatDate($loan['loan_date']); ?></p>
                                            </div>
                                            <div>
                                                <p class="text-gray-400">Batas Kembali</p>
                                                <p class="font-semibold <?php echo $days_left < 0 ? 'text-red-600' : 'text-gray-700 dark:text-gray-300'; ?>"><?php echo formatDate($loan['due_date']); ?></p>
                                            </div>
                                        </div>
                                        <a href="/perpustakaan/user/return.php" class="flex items-center gap-1 text-xs font-medium text-primary hover:underline">
                                            Kembalikan <i class="ph ph-arrow-right text-sm"></i>
                                        </a>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <?php else: ?>
                        <div class="text-center py-12">
                            <p class="text-gray-500 dark:text-gray-400 text-lg">Anda belum meminjam buku apapun</p>
                            <a href="/perpustakaan/user/books.php" class="text-blue-600 hover:underline inline-block mt-2">Jelajahi Koleksi →</a>
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
