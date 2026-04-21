<?php
session_start();
require_once __DIR__ . '/../config/database.php';

checkAdmin();

$page_title = 'Kelola Buku';
$message = '';
$error = '';

$UPLOAD_DIR = __DIR__ . '/../assets/uploads/covers/';
$UPLOAD_URL = '/perpustakaan/assets/uploads/covers/';

function handleCoverUpload($file, $upload_dir) {
    if (!isset($file['tmp_name']) || empty($file['tmp_name']) || $file['error'] !== UPLOAD_ERR_OK) {
        return null;
    }
    $allowed = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
    $mime = mime_content_type($file['tmp_name']);
    if (!in_array($mime, $allowed)) return null;
    if ($file['size'] > 5 * 1024 * 1024) return null; // 5MB max

    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = uniqid('cover_', true) . '.' . strtolower($ext);
    $dest = $upload_dir . $filename;
    if (move_uploaded_file($file['tmp_name'], $dest)) return $filename;
    return null;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $title            = sanitize($_POST['title'] ?? '');
        $isbn             = sanitize($_POST['isbn'] ?? '');
        $category_id      = (int)($_POST['category_id'] ?? 0);
        $author_id        = (int)($_POST['author_id'] ?? 0);
        $publisher_id     = (int)($_POST['publisher_id'] ?? 0);
        $description      = sanitize($_POST['description'] ?? '');
        $pages            = (int)($_POST['pages'] ?? 0);
        $stock            = (int)($_POST['stock'] ?? 0);
        $publication_date = $_POST['publication_date'] ?? null;
        $cover_image      = handleCoverUpload($_FILES['cover_image'] ?? [], $UPLOAD_DIR);

        if (empty($title) || $category_id == 0 || $author_id == 0 || $publisher_id == 0) {
            $error = 'Mohon isi semua field yang diperlukan!';
        } else {
            try {
                $slug = slugify($title);
                $stmt = $pdo->prepare("INSERT INTO books (title, slug, isbn, description, category_id, author_id, publisher_id, pages, stock, available_stock, publication_date, cover_image) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$title, $slug, $isbn, $description, $category_id, $author_id, $publisher_id, $pages, $stock, $stock, $publication_date, $cover_image]);
                $message = 'Buku berhasil ditambahkan!';
            } catch (PDOException $e) {
                $error = 'Error: ' . $e->getMessage();
            }
        }

    } elseif ($action === 'edit') {
        $id               = (int)($_POST['id'] ?? 0);
        $title            = sanitize($_POST['title'] ?? '');
        $isbn             = sanitize($_POST['isbn'] ?? '');
        $category_id      = (int)($_POST['category_id'] ?? 0);
        $author_id        = (int)($_POST['author_id'] ?? 0);
        $publisher_id     = (int)($_POST['publisher_id'] ?? 0);
        $description      = sanitize($_POST['description'] ?? '');
        $pages            = (int)($_POST['pages'] ?? 0);
        $stock            = (int)($_POST['stock'] ?? 0);
        $publication_date = $_POST['publication_date'] ?? null;
        $old_cover        = $_POST['old_cover_image'] ?? null;
        $new_cover        = handleCoverUpload($_FILES['cover_image'] ?? [], $UPLOAD_DIR);
        $cover_image      = $new_cover ?? $old_cover;

        if ($new_cover && $old_cover && file_exists($UPLOAD_DIR . $old_cover)) {
            @unlink($UPLOAD_DIR . $old_cover);
        }

        if (empty($id) || empty($title)) {
            $error = 'Data tidak valid!';
        } else {
            try {
                $stmt = $pdo->prepare("UPDATE books SET title=?, isbn=?, description=?, category_id=?, author_id=?, publisher_id=?, pages=?, stock=?, available_stock=?, publication_date=?, cover_image=? WHERE id=?");
                $stmt->execute([$title, $isbn, $description, $category_id, $author_id, $publisher_id, $pages, $stock, $stock, $publication_date, $cover_image, $id]);
                $message = 'Buku berhasil diperbarui!';
            } catch (PDOException $e) {
                $error = 'Error: ' . $e->getMessage();
            }
        }

    } elseif ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            try {
                $row = $pdo->prepare("SELECT cover_image FROM books WHERE id = ?");
                $row->execute([$id]);
                $cover = $row->fetchColumn();
                if ($cover && file_exists($UPLOAD_DIR . $cover)) @unlink($UPLOAD_DIR . $cover);

                $stmt = $pdo->prepare("DELETE FROM books WHERE id = ?");
                $stmt->execute([$id]);
                $message = 'Buku berhasil dihapus!';
            } catch (PDOException $e) {
                $error = 'Error: ' . $e->getMessage();
            }
        }
    }
}

try {
    $books = $pdo->query("
        SELECT b.*, c.name as category_name, a.name as author_name, p.name as publisher_name
        FROM books b
        JOIN categories c ON b.category_id = c.id
        JOIN authors a ON b.author_id = a.id
        JOIN publishers p ON b.publisher_id = p.id
        ORDER BY b.created_at DESC
    ")->fetchAll();

    $categories = $pdo->query("SELECT * FROM categories ORDER BY name")->fetchAll();
    $authors    = $pdo->query("SELECT * FROM authors ORDER BY name")->fetchAll();
    $publishers = $pdo->query("SELECT * FROM publishers ORDER BY name")->fetchAll();
} catch (PDOException $e) {
    $error = $e->getMessage();
    $books = [];
}

require_once __DIR__ . '/../includes/header.php';
?>
<style>
.cover-dropzone {
    border: 2px dashed #c7d2fe;
    border-radius: 12px;
    padding: 1.5rem;
    text-align: center;
    cursor: pointer;
    transition: all .2s;
    background: #f8fafc;
    position: relative;
}
body.dark .cover-dropzone {
    background: #1e293b;
    border-color: #4338ca55;
}
.cover-dropzone:hover, .cover-dropzone.drag-over {
    border-color: #4f46e5;
    background: #eef2ff;
}
.cover-dropzone input[type=file] {
    position: absolute; inset: 0; opacity: 0; cursor: pointer; width: 100%; height: 100%;
}
.cover-preview {
    width: 100%; max-height: 180px; object-fit: contain; border-radius: 8px;
}
</style>

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
                        <div>
                            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Kelola Buku</h1>
                            <p class="text-xs text-gray-500 mt-0.5"><?php echo count($books); ?> buku terdaftar</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-4">
                        <button class="btn btn-primary flex items-center gap-2" onclick="openAddModal()">
                            <i class="ph ph-plus-circle text-xl"></i> Tambah Buku
                        </button>
                    </div>
                </div>
            </div>
        </nav>

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
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

            <div class="card overflow-hidden">
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th style="width:56px">Cover</th>
                                <th>Judul Buku</th>
                                <th>Kategori</th>
                                <th>Penulis</th>
                                <th>Penerbit</th>
                                <th>Stok</th>
                                <th>Status</th>
                                <th class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($books as $book): ?>
                            <tr>
                                <td>
                                    <?php if (!empty($book['cover_image']) && file_exists($UPLOAD_DIR . $book['cover_image'])): ?>
                                    <img src="<?php echo $UPLOAD_URL . htmlspecialchars($book['cover_image']); ?>"
                                         alt="Cover" class="w-10 h-14 object-cover rounded-lg shadow-sm border border-gray-100">
                                    <?php else: ?>
                                    <div class="w-10 h-14 bg-primary/10 rounded-lg flex items-center justify-center text-primary">
                                        <i class="ph ph-book text-lg"></i>
                                    </div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <p class="font-500 text-gray-900 dark:text-white"><?php echo htmlspecialchars(substr($book['title'], 0, 40)); ?></p>
                                    <p class="text-xs text-gray-500">ISBN: <?php echo htmlspecialchars($book['isbn'] ?? '-'); ?></p>
                                </td>
                                <td><?php echo htmlspecialchars($book['category_name']); ?></td>
                                <td><?php echo htmlspecialchars($book['author_name']); ?></td>
                                <td><?php echo htmlspecialchars($book['publisher_name']); ?></td>
                                <td><span class="font-500"><?php echo $book['available_stock']; ?> / <?php echo $book['stock']; ?></span></td>
                                <td>
                                    <?php if ($book['available_stock'] > 0): ?>
                                        <span class="badge badge-success">Tersedia</span>
                                    <?php else: ?>
                                        <span class="badge badge-danger">Habis</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <div class="flex items-center justify-center gap-1">
                                        <button class="p-2 rounded-lg hover:bg-primary/10 text-primary transition" title="Edit"
                                                onclick="editBook(<?php echo htmlspecialchars(json_encode($book)); ?>)">
                                            <i class="ph ph-pencil-simple text-lg"></i>
                                        </button>
                                        <button class="p-2 rounded-lg hover:bg-red-50 text-red-500 transition" title="Hapus"
                                                onclick="deleteBook(<?php echo $book['id']; ?>)">
                                            <i class="ph ph-trash text-lg"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div id="bookModal" class="modal">
            <div class="modal-content" style="max-width:680px; max-height:90vh; overflow-y:auto;">
                <div class="flex items-center justify-between mb-6">
                    <h2 class="text-xl font-bold text-gray-900 dark:text-white" id="modalTitle">Tambah Buku Baru</h2>
                    <button onclick="closeModal()" class="w-8 h-8 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 flex items-center justify-center text-gray-500 transition">
                        <i class="ph ph-x text-lg"></i>
                    </button>
                </div>

                <form id="bookForm" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="action" id="formAction" value="add">
                    <input type="hidden" name="id" id="bookId">
                    <input type="hidden" name="old_cover_image" id="oldCoverImage">

                    <div class="form-group mb-4">
                        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                            <i class="ph ph-image text-primary mr-1"></i> Cover Buku
                        </label>
                        <div class="flex gap-4 items-start">
                            <div id="coverPreviewWrap" class="w-24 h-32 rounded-xl overflow-hidden bg-primary/5 border border-primary/20 flex items-center justify-center shrink-0">
                                <img id="coverPreviewImg" src="" alt="" class="w-full h-full object-cover hidden">
                                <i id="coverPreviewIcon" class="ph ph-book text-3xl text-primary/40"></i>
                            </div>
                            <div class="cover-dropzone flex-1"
                                 ondragover="this.classList.add('drag-over'); event.preventDefault();"
                                 ondragleave="this.classList.remove('drag-over');"
                                 ondrop="this.classList.remove('drag-over'); handleDrop(event);">
                                <input type="file" name="cover_image" id="coverInput" accept="image/*"
                                       onchange="previewCover(this)">
                                <div id="dropzoneContent">
                                    <i class="ph ph-upload-simple text-3xl text-gray-400 mb-2 block"></i>
                                    <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Klik atau drag &amp; drop gambar</p>
                                    <p class="text-xs text-gray-400 mt-1">JPG, PNG, WEBP · Maks. 5MB</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Judul Buku *</label>
                        <input type="text" name="title" id="title" required placeholder="Masukkan judul buku...">
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div class="form-group">
                            <label>ISBN</label>
                            <input type="text" name="isbn" id="isbn" placeholder="978-xxx-xxx">
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
                        <textarea name="description" id="description" placeholder="Deskripsi singkat buku..."></textarea>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div class="form-group">
                            <label>Jumlah Halaman</label>
                            <input type="number" name="pages" id="pages" min="0">
                        </div>
                        <div class="form-group">
                            <label>Stok *</label>
                            <input type="number" name="stock" id="stock" min="0" required>
                        </div>
                    </div>

                    <div class="flex gap-3 pt-2">
                        <button type="submit" class="btn btn-primary flex-1 flex items-center justify-center gap-2">
                            <i class="ph ph-floppy-disk text-lg"></i> Simpan
                        </button>
                        <button type="button" class="btn btn-secondary flex-1" onclick="closeModal()">Batal</button>
                    </div>
                </form>
            </div>
        </div>

        <div id="deleteModal" class="modal">
            <div class="modal-content" style="max-width:420px">
                <div class="text-center mb-6">
                    <div class="w-16 h-16 rounded-full bg-red-100 flex items-center justify-center mx-auto mb-4">
                        <i class="ph ph-trash text-3xl text-red-500"></i>
                    </div>
                    <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-2">Hapus Buku?</h2>
                    <p class="text-gray-500 text-sm">Tindakan ini tidak dapat dibatalkan. Cover buku juga akan dihapus.</p>
                </div>
                <form id="deleteForm" method="POST" class="flex gap-3">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" id="deleteBookId">
                    <button type="submit" class="btn btn-danger flex-1">Hapus</button>
                    <button type="button" class="btn btn-secondary flex-1" onclick="closeDeleteModal()">Batal</button>
                </form>
            </div>
        </div>

        <?php require_once __DIR__ . '/../includes/footer.php'; ?>
    </div>
</div>

<script>
const UPLOAD_URL = '<?php echo $UPLOAD_URL; ?>';

function previewCover(input) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = e => showPreview(e.target.result);
        reader.readAsDataURL(input.files[0]);
        document.getElementById('dropzoneContent').innerHTML =
            '<i class="ph ph-check-circle text-2xl text-emerald-500 mb-1 block"></i>' +
            '<p class="text-sm font-medium text-emerald-600">' + input.files[0].name + '</p>' +
            '<p class="text-xs text-gray-400 mt-1">Klik untuk ganti</p>';
    }
}

function handleDrop(e) {
    e.preventDefault();
    const dt = e.dataTransfer;
    if (dt.files.length) {
        const input = document.getElementById('coverInput');
        input.files = dt.files;
        previewCover(input);
    }
}

function showPreview(src) {
    const img  = document.getElementById('coverPreviewImg');
    const icon = document.getElementById('coverPreviewIcon');
    img.src = src;
    img.classList.remove('hidden');
    icon.classList.add('hidden');
}

function clearPreview() {
    const img  = document.getElementById('coverPreviewImg');
    const icon = document.getElementById('coverPreviewIcon');
    img.src = '';
    img.classList.add('hidden');
    icon.classList.remove('hidden');
    document.getElementById('dropzoneContent').innerHTML =
        '<i class="ph ph-upload-simple text-3xl text-gray-400 mb-2 block"></i>' +
        '<p class="text-sm font-medium text-gray-600 dark:text-gray-400">Klik atau drag &amp; drop gambar</p>' +
        '<p class="text-xs text-gray-400 mt-1">JPG, PNG, WEBP · Maks. 5MB</p>';
}

function openAddModal() {
    document.getElementById('modalTitle').textContent = 'Tambah Buku Baru';
    document.getElementById('formAction').value = 'add';
    document.getElementById('bookForm').reset();
    document.getElementById('bookId').value = '';
    document.getElementById('oldCoverImage').value = '';
    clearPreview();
    document.getElementById('bookModal').style.display = 'block';
}

function editBook(book) {
    document.getElementById('modalTitle').textContent = 'Edit Buku';
    document.getElementById('formAction').value = 'edit';
    document.getElementById('bookId').value = book.id;
    document.getElementById('title').value = book.title || '';
    document.getElementById('isbn').value = book.isbn || '';
    document.getElementById('category_id').value = book.category_id;
    document.getElementById('author_id').value = book.author_id;
    document.getElementById('publisher_id').value = book.publisher_id;
    document.getElementById('description').value = book.description || '';
    document.getElementById('pages').value = book.pages || 0;
    document.getElementById('stock').value = book.stock;
    document.getElementById('publication_date').value = book.publication_date || '';
    document.getElementById('oldCoverImage').value = book.cover_image || '';

    // Show existing cover
    clearPreview();
    if (book.cover_image) {
        showPreview(UPLOAD_URL + book.cover_image);
        document.getElementById('dropzoneContent').innerHTML =
            '<i class="ph ph-image text-2xl text-primary mb-1 block"></i>' +
            '<p class="text-sm font-medium text-gray-600 dark:text-gray-400">Cover sudah ada</p>' +
            '<p class="text-xs text-gray-400 mt-1">Klik untuk ganti cover</p>';
    }

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

window.onclick = function(event) {
    if (event.target === document.getElementById('bookModal'))   closeModal();
    if (event.target === document.getElementById('deleteModal')) closeDeleteModal();
};
</script>
</body>
</html>
