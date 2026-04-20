<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title . ' - Perpustakaan' : 'Perpustakaan'; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        primary: '#4f46e5',
                        'primary-dark': '#4338ca',
                    }
                }
            }
        }
    </script>
    <script src="https://unpkg.com/@phosphor-icons/web"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * {
            font-family: 'Inter', sans-serif;
        }

        :root {
            --primary: #4f46e5;
            --primary-dark: #4338ca;
            --success: #10b981;
            --warning: #f59e0b;
            --danger: #ef4444;
            --secondary: #64748b;
            --bg-light: #f8fafc;
            --bg-white: #ffffff;
            --text-dark: #0f172a;
            --text-light: #64748b;
            --border-color: #e2e8f0;
        }

        body {
            background-color: #f0f4f8;
            color: var(--text-dark);
            transition: all 0.3s ease;
        }

        body.dark {
            background-color: #0f172a;
            color: #f8fafc;
        }

        .sidebar {
            background: white;
            border-right: 1px solid var(--border-color);
        }

        body.dark .sidebar {
            background: #1e293b;
            border-right: 1px solid #334155;
        }

        .navbar {
            background: white;
            border-bottom: 1px solid var(--border-color);
        }

        body.dark .navbar {
            background: #1e293b;
            border-bottom: 1px solid #334155;
        }

        .card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
            border: 1px solid var(--border-color);
            transition: all 0.3s ease;
        }

        .card:hover {
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        }

        body.dark .card {
            background: #1e293b;
            border-color: #334155;
            box-shadow: none;
        }

        .btn {
            border-radius: 8px;
            font-weight: 500;
            transition: all 0.3s ease;
            cursor: pointer;
            padding: 0.5rem 1rem;
            border: none;
            font-size: 0.875rem;
        }

        .btn-primary {
            background: var(--primary);
            color: white;
        }

        .btn-primary:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.4);
        }

        .btn-secondary {
            background: var(--secondary);
            color: white;
        }

        .btn-secondary:hover {
            background: #4f46e5;
            transform: translateY(-2px);
        }

        .btn-success {
            background: var(--success);
            color: white;
        }

        .btn-success:hover {
            background: #059669;
            transform: translateY(-2px);
        }

        .btn-danger {
            background: var(--danger);
            color: white;
        }

        .btn-danger:hover {
            background: #dc2626;
            transform: translateY(-2px);
        }

        .btn-sm {
            padding: 0.375rem 0.75rem;
            font-size: 0.75rem;
        }

        .badge {
            display: inline-block;
            padding: 0.375rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 600;
        }

        .badge-primary {
            background: #dbeafe;
            color: #1e40af;
        }

        .badge-success {
            background: #d1fae5;
            color: #065f46;
        }

        .badge-warning {
            background: #fef3c7;
            color: #92400e;
        }

        .badge-danger {
            background: #fee2e2;
            color: #991b1b;
        }

        .badge-info {
            background: #e0e7ff;
            color: #312e81;
        }

        .table-responsive {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th {
            background: var(--bg-light);
            padding: 1rem;
            text-align: left;
            font-weight: 600;
            color: var(--text-dark);
            border-bottom: 2px solid var(--border-color);
        }

        body.dark th {
            background: #2d3748;
            color: #e2e8f0;
            border-bottom-color: #374151;
        }

        td {
            padding: 1rem;
            border-bottom: 1px solid var(--border-color);
        }

        body.dark td {
            border-bottom-color: #2d3748;
        }

        tbody tr {
            transition: all 0.2s ease;
        }

        tbody tr:hover {
            background: var(--bg-light);
        }

        body.dark tbody tr:hover {
            background: #252d3d;
        }

        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            animation: fadeIn 0.3s ease;
        }

        .modal-content {
            background-color: white;
            margin: 5% auto;
            padding: 2rem;
            border-radius: 12px;
            width: 90%;
            max-width: 600px;
            box-shadow: 0 20px 25px rgba(0, 0, 0, 0.15);
            animation: slideIn 0.3s ease;
        }

        body.dark .modal-content {
            background-color: #1a1f2e;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        @keyframes slideIn {
            from {
                transform: translateY(-50px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        .close {
            color: #999;
            float: right;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
        }

        .close:hover {
            color: #000;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: var(--text-dark);
        }

        body.dark label {
            color: #e2e8f0;
        }

        input, textarea, select {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            font-family: 'Inter', sans-serif;
            font-size: 0.875rem;
            transition: all 0.3s ease;
        }

        input:focus, textarea:focus, select:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }

        body.dark input,
        body.dark textarea,
        body.dark select {
            background: #2d3748;
            border-color: #374151;
            color: #e2e8f0;
        }

        textarea {
            resize: vertical;
            min-height: 120px;
        }

        .alert {
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .alert-success {
            background: #d1fae5;
            color: #065f46;
            border: 1px solid #a7f3d0;
        }

        .alert-danger {
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #fecaca;
        }

        .alert-warning {
            background: #fef3c7;
            color: #92400e;
            border: 1px solid #fde68a;
        }

        .alert-info {
            background: #dbeafe;
            color: #1e40af;
            border: 1px solid #bfdbfe;
        }

        .grid-auto {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 1.5rem;
        }

        .book-card {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08);
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .book-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 12px 24px rgba(0, 0, 0, 0.15);
        }

        body.dark .book-card {
            background: #1a1f2e;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.3);
        }

        .book-card-image {
            width: 100%;
            height: 300px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 3rem;
        }

        .book-card-body {
            padding: 1rem;
        }

        .book-card-title {
            font-weight: 700;
            font-size: 1rem;
            color: var(--text-dark);
            margin-bottom: 0.5rem;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        body.dark .book-card-title {
            color: #e2e8f0;
        }

        .book-card-author {
            font-size: 0.875rem;
            color: var(--text-light);
            margin-bottom: 0.5rem;
        }

        .book-card-rating {
            font-size: 0.875rem;
            color: #f59e0b;
            margin-bottom: 1rem;
        }

        .sidebar-link {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.625rem 1rem;
            color: var(--text-light);
            text-decoration: none;
            border-radius: 8px;
            transition: all 0.2s ease;
            margin-bottom: 0.25rem;
            font-weight: 500;
        }

        .sidebar-link:hover,
        .sidebar-link.active {
            background: #f1f5f9;
            color: var(--primary);
        }

        body.dark .sidebar-link:hover,
        body.dark .sidebar-link.active {
            background: #334155;
            color: #818cf8;
        }

        .sidebar-section-title {
            font-weight: 600;
            font-size: 0.75rem;
            text-transform: uppercase;
            color: var(--text-light);
            margin-top: 1.5rem;
            margin-bottom: 0.75rem;
            padding: 0 1rem;
        }

        .stat-card {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
            border: 1px solid var(--border-color);
        }

        body.dark .stat-card {
            background: #1e293b;
            border-color: #334155;
            box-shadow: none;
        }

        .stat-number {
            font-size: 2.25rem;
            font-weight: 700;
            color: var(--text-dark);
        }

        body.dark .stat-number {
            color: #e2e8f0;
        }

        .stat-label {
            font-size: 0.875rem;
            color: var(--text-light);
            margin-top: 0.5rem;
        }

        .loading {
            display: inline-block;
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: var(--primary);
            margin-right: 0.5rem;
            animation: pulse 1.5s infinite;
        }

        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }

        .spinner {
            border: 3px solid #f3f3f3;
            border-top: 3px solid var(--primary);
            border-radius: 50%;
            width: 40px;
            height: 40px;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .fade-in {
            animation: fadeIn 0.3s ease;
        }

        .slide-in-left {
            animation: slideInLeft 0.3s ease;
        }

        @keyframes slideInLeft {
            from {
                transform: translateX(-20px);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }

        @media (max-width: 768px) {
            .sidebar-toggle {
                display: block;
            }

            .sidebar {
                position: fixed;
                left: 0;
                top: 0;
                height: 100vh;
                z-index: 999;
                transform: translateX(-100%);
                transition: transform 0.3s ease;
            }

            .sidebar.active {
                transform: translateX(0);
            }

            .sidebar-overlay {
                display: none;
                position: fixed;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                background: rgba(0, 0, 0, 0.5);
                z-index: 998;
            }

            .sidebar.active ~ .sidebar-overlay {
                display: block;
            }
        }
    </style>
</head>
<body>
