<?php
session_start();
require_once __DIR__ . '/../config/database.php';

checkAdmin();

$page_title = 'Kelola Buku';

// Handle CRUD Operations
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $title = sanitize($_POST['title'] ?? '');
        $isbn = sanitize($_POST['isbn'] ?? '');
        $category_id = (int)($_POST['category_id'] ?? 0);
        $author_id = (int)($_POST['author_id'] ?? 0);
        $publisher_id = (int)($_POST['publisher_id'] ?? 0);
        $description = sanitize($_POST['description'] ?? '');
        $pages = (int)($_POST['pages'] ?? 0);
        $stock = (int)($_POST['stock'] ?? 0);
        $publication_date = $_POST['publication_date'] ?? null;

        if (empty($title) || $category_id == 0 || $author_id == 0 || $publisher_id == 0) {
            $error = 'Mohon isi semua field yang diperlukan!';
        } else {
            try {
                $slug = slugify($title);
                $stmt = $pdo->prepare("INSERT INTO books (title, slug, isbn, description, category_id, author_id, publisher_id, pages, stock, available_stock, publication_date) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$title, $slug, $isbn, $description, $category_id, $author_id, $publisher_id, $pages, $stock, $stock, $publication_date]);
                $message = 'Buku berhasil ditambahkan!';
            } catch (PDOException $e) {
                $error = 'Error: ' . $e->getMessage();
            }
        }
    } elseif ($action === 'edit') {
        $id = (int)($_POST['id'] ?? 0);
        $title = sanitize($_POST['title'] ?? '');
        $isbn = sanitize($_POST['isbn'] ?? '');
        $category_id = (int)($_POST['category_id'] ?? 0);
        $author_id = (int)($_POST['author_id'] ?? 0);
        $publisher_id = (int)($_POST['publisher_id'] ?? 0);
        $description = sanitize($_POST['description'] ?? '');
        $pages = (int)($_POST['pages'] ?? 0);
        $stock = (int)($_POST['stock'] ?? 0);
        $publication_date = $_POST['publication_date'] ?? null;

        if (empty($id) || empty($title)) {
            $error = 'Data tidak valid!';
        } else {
            try {
                $stmt = $pdo->prepare("UPDATE books SET title = ?, isbn = ?, description = ?, category_id = ?, author_id = ?, publisher_id = ?, pages = ?, stock = ?, available_stock = ?, publication_date = ? WHERE id = ?");
                $stmt->execute([$title, $isbn, $description, $category_id, $author_id, $publisher_id, $pages, $stock, $stock, $publication_date, $id]);
                $message = 'Buku berhasil diperbarui!';
            } catch (PDOException $e) {
                $error = 'Error: ' . $e->getMessage();
            }
        }
    } elseif ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            try {
                $stmt = $pdo->prepare("DELETE FROM books WHERE id = ?");
                $stmt->execute([$id]);
                $message = 'Buku berhasil dihapus!';
            } catch (PDOException $e) {
                $error = 'Error: ' . $e->getMessage();
            }
        }
    }
}

// Get all books
try {
    $stmt = $pdo->query("
        SELECT b.*, c.name as category_name, a.name as author_name, p.name as publisher_name
        FROM books b
        JOIN categories c ON b.category_id = c.id
        JOIN authors a ON b.author_id = a.id
        JOIN publishers p ON b.publisher_id = p.id
        ORDER BY b.created_at DESC
    ");
    $books = $stmt->fetchAll();

    // Get categories, authors, publishers for dropdowns
    $categories = $pdo->query("SELECT * FROM categories ORDER BY name")->fetchAll();
    $authors = $pdo->query("SELECT * FROM authors ORDER BY name")->fetchAll();
    $publishers = $pdo->query("SELECT * FROM publishers ORDER BY name")->fetchAll();
} catch (PDOException $e) {
    $error = $e->getMessage();
    $books = [];
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
                        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Kelola Buku</h1>
                    </div>
                    <div class="flex items-center gap-4">
                        <button class="btn btn-primary flex items-center gap-2" onclick="openAddModal()"><i class="ph ph-plus-circle text-xl"></i> Tambah Buku</button>
                    </div>
                </div>
            </div>
        </nav>

        <!-- Content -->
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

            <!-- Books Table -->
            <div class="card overflow-hidden">
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>Judul Buku</th>
                                <th>Kategori</th>
                                <th>Penulis</th>
                                <th>Penerbit</th>
                                <th>Stok</th>
                                <th>Status</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($books as $book): ?>
                            <tr>
                                <td>
                                    <div>
                                        <p class="font-500"><?php echo htmlspecialchars(substr($book['title'], 0, 40)); ?></p>
                                        <p class="text-xs text-gray-500">ISBN: <?php echo htmlspecialchars($book['isbn'] ?? '-'); ?></p>
                                    </div>
                                </td>
                                <td><?php echo htmlspecialchars($book['category_name']); ?></td>
                                <td><?php echo htmlspecialchars($book['author_name']); ?></td>
                                <td><?php echo htmlspecialchars($book['publisher_name']); ?></td>
                                <td>
                                    <span class="font-500"><?php echo $book['available_stock']; ?> / <?php echo $book['stock']; ?></span>
                                </td>
                                <td>
                                    <?php if ($book['available_stock'] > 0): ?>
                                        <span class="badge badge-success">Tersedia</span>
                                    <?php else: ?>
                                        <span class="badge badge-danger">Habis</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <button class="btn btn-sm" onclick="editBook(<?php echo htmlspecialchars(json_encode($book)); ?>)"><i class="ph ph-pencil-simple text-lg"></i></button>
                                    <button class="btn btn-sm btn-danger" onclick="deleteBook(<?php echo $book['id']; ?>)"><i class="ph ph-trash text-lg"></i></button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

<!-- Add/Edit Modal -->
<div id="bookModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="closeModal()">&times;</span>
        <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4" id="modalTitle">Tambah Buku Baru</h2>

        <form id="bookForm" method="POST">
            <input type="hidden" name="action" id="formAction" value="add">
            <input type="hidden" name="id" id="bookId">

            <div class="form-group">
                <label>Judul Buku *</label>
                <input type="text" name="title" id="title" required>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div class="form-group">
                    <label>ISBN</label>
                    <input type="text" name="isbn" id="isbn">
                </div>
                <div class="form-group">
                    <label>Tanggal Terbit</label>
                    <input type="date" name="publication_date" id="publication_date">
                </div>
            </div>

            <div class="grid grid-cols-3 gap-4">
                <div class="form-group">
                    <label>Kategori *</label>
                    <select name="category_id" id="category_id" required>
                        <option value="">Pilih Kategori</option>
                        <?php foreach ($categories as $cat): ?>
                        <option value="<?php echo $cat['id']; ?>"><?php echo htmlspecialchars($cat['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Penulis *</label>
                    <select name="author_id" id="author_id" required>
                        <option value="">Pilih Penulis</option>
                        <?php foreach ($authors as $auth): ?>
                        <option value="<?php echo $auth['id']; ?>"><?php echo htmlspecialchars($auth['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Penerbit *</label>
                    <select name="publisher_id" id="publisher_id" required>
                        <option value="">Pilih Penerbit</option>
                        <?php foreach ($publishers as $pub): ?>
                        <option value="<?php echo $pub['id']; ?>"><?php echo htmlspecialchars($pub['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label>Deskripsi</label>
                <textarea name="description" id="description"></textarea>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div class="form-group">
                    <label>Jumlah Halaman</label>
                    <input type="number" name="pages" id="pages">
                </div>
                <div class="form-group">
                    <label>Stok *</label>
                    <input type="number" name="stock" id="stock" required>
                </div>
            </div>

            <div class="flex gap-4 pt-4">
                <button type="submit" class="btn btn-primary flex-1">Simpan</button>
                <button type="button" class="btn btn-secondary flex-1" onclick="closeModal()">Batal</button>
            </div>
        </form>
    </div>
</div>

<!-- Delete Confirmation -->
<div id="deleteModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="closeDeleteModal()">&times;</span>
        <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4">Konfirmasi Hapus</h2>
        <p class="text-gray-600 dark:text-gray-400 mb-6">Apakah Anda yakin ingin menghapus buku ini? Tindakan ini tidak dapat dibatalkan.</p>

        <form id="deleteForm" method="POST" class="flex gap-4">
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="id" id="deleteBookId">
            <button type="submit" class="btn btn-danger flex-1">Hapus</button>
            <button type="button" class="btn btn-secondary flex-1" onclick="closeDeleteModal()">Batal</button>
        </form>
    </div>
</div>

<script>
function openAddModal() {
    document.getElementById('modalTitle').textContent = 'Tambah Buku Baru';
    document.getElementById('formAction').value = 'add';
    document.getElementById('bookForm').reset();
    document.getElementById('bookId').value = '';
    document.getElementById('bookModal').style.display = 'block';
}

function editBook(book) {
    document.getElementById('modalTitle').textContent = 'Edit Buku';
    document.getElementById('formAction').value = 'edit';
    document.getElementById('bookId').value = book.id;
    document.getElementById('title').value = book.title;
    document.getElementById('isbn').value = book.isbn || '';
    document.getElementById('category_id').value = book.category_id;
    document.getElementById('author_id').value = book.author_id;
    document.getElementById('publisher_id').value = book.publisher_id;
    document.getElementById('description').value = book.description || '';
    document.getElementById('pages').value = book.pages || 0;
    document.getElementById('stock').value = book.stock;
    document.getElementById('publication_date').value = book.publication_date || '';
    document.getElementById('bookModal').style.display = 'block';
}

function closeModal() {
    document.getElementById('bookModal').style.display = 'none';
}

function deleteBook(id) {
    document.getElementById('deleteBookId').value = id;
    document.getElementById('deleteModal').style.display = 'block';
}

function closeDeleteModal() {
    document.getElementById('deleteModal').style.display = 'none';
}

// Close modals when clicking outside
window.onclick = function(event) {
    let modal = document.getElementById('bookModal');
    if (event.target === modal) {
        modal.style.display = 'none';
    }
    let deleteModal = document.getElementById('deleteModal');
    if (event.target === deleteModal) {
        deleteModal.style.display = 'none';
    }
}
</script>

        </div>

            <!-- Footer -->
            <?php require_once __DIR__ . '/../includes/footer.php'; ?>
    </div>
</div>
</body>
</html>
