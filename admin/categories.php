<?php
session_start();
require_once __DIR__ . '/../config/database.php';

checkAdmin();

$page_title = 'Kelola Kategori';
$message = '';
$error = '';

$ph_icons = [
    'ph-book-open'         => 'Buku Terbuka',
    'ph-books'             => 'Koleksi Buku',
    'ph-graduation-cap'    => 'Pendidikan',
    'ph-flask'             => 'Sains',
    'ph-briefcase'         => 'Bisnis',
    'ph-heart'             => 'Romansa',
    'ph-sword'             => 'Petualangan',
    'ph-cpu'               => 'Teknologi',
    'ph-paint-brush'       => 'Seni',
    'ph-globe-hemisphere-west' => 'Geografi',
    'ph-brain'             => 'Psikologi',
    'ph-user-circle'       => 'Biografi',
    'ph-tree'              => 'Alam',
    'ph-star'              => 'Fiksi Ilmiah',
    'ph-music-note'        => 'Musik',
    'ph-chart-line'        => 'Ekonomi',
    'ph-shield'            => 'Hukum',
    'ph-cross'             => 'Agama',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $name        = sanitize($_POST['name'] ?? '');
        $description = sanitize($_POST['description'] ?? '');
        $icon        = sanitize($_POST['icon'] ?? 'ph-books');

        if (empty($name)) {
            $error = 'Nama kategori harus diisi!';
        } else {
            try {
                $slug = slugify($name);
                $stmt = $pdo->prepare("INSERT INTO categories (name, slug, description, icon) VALUES (?, ?, ?, ?)");
                $stmt->execute([$name, $slug, $description, $icon]);
                $message = 'Kategori berhasil ditambahkan!';
            } catch (PDOException $e) {
                $error = 'Error: ' . $e->getMessage();
            }
        }

    } elseif ($action === 'edit') {
        $id          = (int)($_POST['id'] ?? 0);
        $name        = sanitize($_POST['name'] ?? '');
        $description = sanitize($_POST['description'] ?? '');
        $icon        = sanitize($_POST['icon'] ?? 'ph-books');

        if ($id <= 0 || empty($name)) {
            $error = 'Data tidak valid!';
        } else {
            try {
                $stmt = $pdo->prepare("UPDATE categories SET name = ?, description = ?, icon = ? WHERE id = ?");
                $stmt->execute([$name, $description, $icon, $id]);
                $message = 'Kategori berhasil diperbarui!';
            } catch (PDOException $e) {
                $error = 'Error: ' . $e->getMessage();
            }
        }

    } elseif ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            try {
                $stmt = $pdo->prepare("DELETE FROM categories WHERE id = ?");
                $stmt->execute([$id]);
                $message = 'Kategori berhasil dihapus!';
            } catch (PDOException $e) {
                $error = 'Error: ' . $e->getMessage();
            }
        }
    }
}

try {
    $categories = $pdo->query("
        SELECT c.*, COUNT(b.id) as book_count
        FROM categories c
        LEFT JOIN books b ON c.id = b.category_id
        GROUP BY c.id
        ORDER BY c.name
    ")->fetchAll();
} catch (PDOException $e) {
    $error = $e->getMessage();
    $categories = [];
}

$palettes = [
    ['bg' => 'bg-indigo-100 dark:bg-indigo-900/30',  'text' => 'text-indigo-600 dark:text-indigo-400'],
    ['bg' => 'bg-emerald-100 dark:bg-emerald-900/30', 'text' => 'text-emerald-600 dark:text-emerald-400'],
    ['bg' => 'bg-amber-100 dark:bg-amber-900/30',     'text' => 'text-amber-600 dark:text-amber-400'],
    ['bg' => 'bg-rose-100 dark:bg-rose-900/30',       'text' => 'text-rose-600 dark:text-rose-400'],
    ['bg' => 'bg-cyan-100 dark:bg-cyan-900/30',       'text' => 'text-cyan-600 dark:text-cyan-400'],
    ['bg' => 'bg-violet-100 dark:bg-violet-900/30',   'text' => 'text-violet-600 dark:text-violet-400'],
];

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
                        <div>
                            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Kelola Kategori</h1>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5"><?php echo count($categories); ?> kategori terdaftar</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-3">
                        <button class="btn btn-primary flex items-center gap-2" onclick="openAddModal()">
                            <i class="ph ph-plus-circle text-xl"></i> Tambah Kategori
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
                <div class="p-6 border-b border-gray-100 dark:border-gray-700/60 flex items-center justify-between">
                    <div>
                        <h2 class="text-base font-bold text-gray-900 dark:text-white">Daftar Kategori</h2>
                        <p class="text-xs text-gray-500 mt-0.5">Kelola kategori buku perpustakaan</p>
                    </div>
                </div>

                <?php if (count($categories) > 0): ?>
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th style="width:56px">#</th>
                                <th>Kategori</th>
                                <th>Deskripsi</th>
                                <th class="text-center" style="width:110px">Jumlah Buku</th>
                                <th class="text-center" style="width:110px">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($categories as $i => $category):
                                $palette = $palettes[$i % count($palettes)];
                                $is_ph = str_starts_with($category['icon'] ?? '', 'ph-');
                            ?>
                            <tr>
                                <td>
                                    <div class="w-10 h-10 rounded-xl <?php echo $palette['bg']; ?> flex items-center justify-center <?php echo $palette['text']; ?>">
                                        <?php if ($is_ph): ?>
                                        <i class="ph <?php echo htmlspecialchars($category['icon']); ?> text-xl"></i>
                                        <?php else: ?>
                                        <span class="text-xl"><?php echo $category['icon']; ?></span>
                                        <?php endif; ?>
                                    </div>
                                </td>

                                <td>
                                    <p class="font-semibold text-gray-900 dark:text-white"><?php echo htmlspecialchars($category['name']); ?></p>
                                    <?php if (!empty($category['slug'])): ?>
                                    <p class="text-xs text-gray-400 font-mono mt-0.5"><?php echo htmlspecialchars($category['slug']); ?></p>
                                    <?php endif; ?>
                                </td>

                                <td>
                                    <p class="text-sm text-gray-500 dark:text-gray-400 line-clamp-2 max-w-xs">
                                        <?php echo htmlspecialchars(substr($category['description'] ?? '-', 0, 90)); ?>
                                    </p>
                                </td>

                                <td class="text-center">
                                    <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs font-semibold <?php echo $palette['bg']; ?> <?php echo $palette['text']; ?>">
                                        <i class="ph ph-books text-xs"></i>
                                        <?php echo $category['book_count']; ?> buku
                                    </span>
                                </td>

                                <td class="text-center">
                                    <div class="flex items-center justify-center gap-1">
                                        <button onclick="editCategory(<?php echo htmlspecialchars(json_encode($category)); ?>)"
                                                class="p-2 rounded-lg hover:bg-primary/10 text-primary transition" title="Edit">
                                            <i class="ph ph-pencil-simple text-lg"></i>
                                        </button>
                                        <button onclick="deleteCategory(<?php echo $category['id']; ?>)"
                                                class="p-2 rounded-lg hover:bg-red-50 dark:hover:bg-red-900/20 text-red-500 transition" title="Hapus">
                                            <i class="ph ph-trash text-lg"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <div class="py-20 text-center">
                    <div class="w-20 h-20 rounded-full bg-gray-100 dark:bg-gray-800 flex items-center justify-center mx-auto mb-4">
                        <i class="ph ph-tag text-4xl text-gray-400"></i>
                    </div>
                    <h3 class="text-base font-semibold text-gray-700 dark:text-gray-300 mb-1">Belum ada kategori</h3>
                    <p class="text-sm text-gray-500">Tambahkan kategori pertama untuk mengorganisir koleksi buku.</p>
                    <button onclick="openAddModal()" class="mt-4 btn btn-primary inline-flex items-center gap-2">
                        <i class="ph ph-plus-circle"></i> Tambah Sekarang
                    </button>
                </div>
                <?php endif; ?>
            </div>

        </div>

        <div id="categoryModal" class="modal">
            <div class="modal-content" style="max-width: 520px;">
                <div class="flex items-center justify-between mb-6">
                    <h2 class="text-xl font-bold text-gray-900 dark:text-white" id="modalTitle">Tambah Kategori Baru</h2>
                    <button onclick="closeModal()" class="w-8 h-8 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 flex items-center justify-center text-gray-500 transition">
                        <i class="ph ph-x text-lg"></i>
                    </button>
                </div>

                <form id="categoryForm" method="POST">
                    <input type="hidden" name="action" id="formAction" value="add">
                    <input type="hidden" name="id" id="categoryId">

                    <div class="form-group">
                        <label>Nama Kategori *</label>
                        <input type="text" name="name" id="name" required placeholder="Contoh: Fiksi, Teknologi...">
                    </div>

                    <div class="form-group">
                        <label class="block mb-2">Icon Kategori</label>
                        <input type="hidden" name="icon" id="icon" value="ph-books">
                        <div class="grid grid-cols-6 gap-2" id="iconPicker">
                            <?php foreach ($ph_icons as $ph_class => $label): ?>
                            <button type="button"
                                    onclick="selectIcon('<?php echo $ph_class; ?>')"
                                    title="<?php echo $label; ?>"
                                    id="icon-<?php echo $ph_class; ?>"
                                    class="icon-btn w-10 h-10 rounded-xl border-2 border-gray-200 dark:border-gray-700 flex items-center justify-center hover:border-primary hover:bg-primary/5 transition text-gray-500 dark:text-gray-400">
                                <i class="ph <?php echo $ph_class; ?> text-xl"></i>
                            </button>
                            <?php endforeach; ?>
                        </div>
                        <p class="text-xs text-gray-400 mt-1">Klik untuk memilih ikon</p>
                    </div>

                    <div class="form-group">
                        <label>Deskripsi</label>
                        <textarea name="description" id="description" placeholder="Deskripsi singkat kategori ini..."></textarea>
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
            <div class="modal-content" style="max-width: 420px;">
                <div class="text-center mb-6">
                    <div class="w-16 h-16 rounded-full bg-red-100 dark:bg-red-900/30 flex items-center justify-center mx-auto mb-4">
                        <i class="ph ph-trash text-3xl text-red-500"></i>
                    </div>
                    <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-2">Hapus Kategori?</h2>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Pastikan tidak ada buku yang menggunakan kategori ini. Tindakan ini tidak dapat dibatalkan.</p>
                </div>
                <form id="deleteForm" method="POST" class="flex gap-3">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" id="deleteCategoryId">
                    <button type="submit" class="btn btn-danger flex-1">Hapus</button>
                    <button type="button" class="btn btn-secondary flex-1" onclick="closeDeleteModal()">Batal</button>
                </form>
            </div>
        </div>

        <?php require_once __DIR__ . '/../includes/footer.php'; ?>
    </div>
</div>

<script>
function selectIcon(phClass) {
    document.getElementById('icon').value = phClass;
    document.querySelectorAll('.icon-btn').forEach(btn => {
        btn.classList.remove('border-primary', 'bg-primary/10', 'text-primary');
        btn.classList.add('border-gray-200', 'dark:border-gray-700', 'text-gray-500');
    });
    const selected = document.getElementById('icon-' + phClass);
    if (selected) {
        selected.classList.remove('border-gray-200', 'text-gray-500');
        selected.classList.add('border-primary', 'bg-primary/10', 'text-primary');
    }
}

function openAddModal() {
    document.getElementById('modalTitle').textContent = 'Tambah Kategori Baru';
    document.getElementById('formAction').value = 'add';
    document.getElementById('categoryForm').reset();
    document.getElementById('categoryId').value = '';
    selectIcon('ph-books');
    document.getElementById('categoryModal').style.display = 'block';
}

function editCategory(category) {
    document.getElementById('modalTitle').textContent = 'Edit Kategori';
    document.getElementById('formAction').value = 'edit';
    document.getElementById('categoryId').value = category.id;
    document.getElementById('name').value = category.name || '';
    document.getElementById('description').value = category.description || '';

    const icon = category.icon && category.icon.startsWith('ph-') ? category.icon : 'ph-books';
    selectIcon(icon);

    document.getElementById('categoryModal').style.display = 'block';
}

function closeModal() {
    document.getElementById('categoryModal').style.display = 'none';
}

function deleteCategory(id) {
    document.getElementById('deleteCategoryId').value = id;
    document.getElementById('deleteModal').style.display = 'block';
}

function closeDeleteModal() {
    document.getElementById('deleteModal').style.display = 'none';
}

window.onclick = function(event) {
    if (event.target === document.getElementById('categoryModal'))   closeModal();
    if (event.target === document.getElementById('deleteModal'))     closeDeleteModal();
};

selectIcon('ph-books');
</script>
</body>
</html>
