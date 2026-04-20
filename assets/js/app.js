function toggleSidebar() {
    const sidebar = document.querySelector('.sidebar');
    if (sidebar) {
        sidebar.classList.toggle('active');
    }
}


document.querySelectorAll('.sidebar-link').forEach(link => {
    link.addEventListener('click', function() {
        const sidebar = document.querySelector('.sidebar');
        if (sidebar && window.innerWidth < 1024) {
            sidebar.classList.remove('active');
        }
    });
});

function searchBooks(searchTerm) {
    if (searchTerm.length > 0) {
        window.location.href = '/perpustakaan/user/books.php?search=' + encodeURIComponent(searchTerm);
    }
}


document.querySelectorAll('.btn').forEach(button => {
    button.addEventListener('click', function(e) {
        const ripple = document.createElement('span');
        const rect = this.getBoundingClientRect();
        const size = Math.max(rect.width, rect.height);
        const x = e.clientX - rect.left - size / 2;
        const y = e.clientY - rect.top - size / 2;

        ripple.style.width = ripple.style.height = size + 'px';
        ripple.style.left = x + 'px';
        ripple.style.top = y + 'px';
        ripple.classList.add('ripple');

        this.appendChild(ripple);

        setTimeout(() => {
            ripple.remove();
        }, 600);
    });
});


document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function (e) {
        e.preventDefault();
        const target = document.querySelector(this.getAttribute('href'));
        if (target) {
            target.scrollIntoView({ behavior: 'smooth' });
        }
    });
});


function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}


function formatCurrency(amount) {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR'
    }).format(amount);
}


function showNotification(message, type = 'info') {
    const notification = document.createElement('div');
    notification.className = `alert alert-${type} fixed top-4 right-4 z-50`;
    notification.innerHTML = `
        <span>${type === 'success' ? '✅' : type === 'error' ? '❌' : 'ℹ️'}</span>
        <span>${message}</span>
    `;
    document.body.appendChild(notification);

    setTimeout(() => {
        notification.remove();
    }, 3000);
}


function confirmDelete(message = 'Anda yakin ingin menghapus item ini?') {
    return confirm(message);
}
function formatDate(dateString) {
    const options = { year: 'numeric', month: 'long', day: 'numeric' };
    return new Date(dateString).toLocaleDateString('id-ID', options);
}


document.querySelectorAll('form').forEach(form => {
    form.addEventListener('submit', function() {
        const submitBtn = this.querySelector('button[type="submit"]');
        if (submitBtn) {
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span class="loading"></span> Loading...';
        }
    });
});


document.addEventListener('submit', function(e) {
    if (e.target.classList.contains('no-double-submit')) {
        if (e.target.dataset.submitted) {
            e.preventDefault();
        }
        e.target.dataset.submitted = true;
    }
}, true);


document.querySelectorAll('[data-tooltip]').forEach(element => {
    element.addEventListener('mouseenter', function() {
        const tooltip = document.createElement('div');
        tooltip.className = 'tooltip';
        tooltip.textContent = this.dataset.tooltip;
        tooltip.style.position = 'absolute';
        tooltip.style.background = '#333';
        tooltip.style.color = '#fff';
        tooltip.style.padding = '4px 8px';
        tooltip.style.borderRadius = '4px';
        tooltip.style.fontSize = '12px';
        tooltip.style.whiteSpace = 'nowrap';
        tooltip.style.zIndex = '1000';

        document.body.appendChild(tooltip);

        const rect = this.getBoundingClientRect();
        tooltip.style.left = rect.left + 'px';
        tooltip.style.top = (rect.top - tooltip.offsetHeight - 8) + 'px';

        this.tooltipElement = tooltip;
    });

    element.addEventListener('mouseleave', function() {
        if (this.tooltipElement) {
            this.tooltipElement.remove();
        }
    });
});

document.querySelectorAll('.alert').forEach(alert => {
    setTimeout(() => {
        alert.style.animation = 'fadeOut 0.5s ease';
        setTimeout(() => {
            alert.remove();
        }, 500);
    }, 5000);
});


const style = document.createElement('style');
style.textContent = `
    @keyframes fadeOut {
        from { opacity: 1; }
        to { opacity: 0; }
    }

    .ripple {
        position: absolute;
        border-radius: 50%;
        background: rgba(255, 255, 255, 0.5);
        transform: scale(0);
        animation: ripple 0.6s ease-out;
        pointer-events: none;
    }

    @keyframes ripple {
        to {
            transform: scale(4);
            opacity: 0;
        }
    }

    .tooltip {
        animation: tooltipFadeIn 0.2s ease;
    }

    @keyframes tooltipFadeIn {
        from {
            opacity: 0;
            transform: translateY(5px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
`;
document.head.appendChild(style);

console.log('App initialized successfully!');
