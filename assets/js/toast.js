/**
 * Toast Notification Manager
 */
const Toast = {
  container: null,

  init() {
    if (!this.container) {
      this.container = document.getElementById('toastContainer');
      if (!this.container) {
        this.container = document.createElement('div');
        this.container.id = 'toastContainer';
        this.container.className = 'toast-container';
        document.body.appendChild(this.container);
      }
    }
  },

  show(type = 'info', title = '', message = '', duration = 4000) {
    this.init();

    const icons = {
      success: '✨',
      error: '⚠️',
      warning: '🔔',
      info: 'ℹ️'
    };

    const toast = document.createElement('div');
    toast.className = `toast toast-${type}`;
    toast.innerHTML = `
      <div class="toast-icon">${icons[type] || 'ℹ️'}</div>
      <div class="toast-content">
        <div class="toast-title">${title}</div>
        <div class="toast-message">${message}</div>
      </div>
      <button class="toast-close" aria-label="Close">&times;</button>
    `;

    const removeToast = () => {
      toast.classList.add('hiding');
      setTimeout(() => {
        if (toast.parentElement) toast.remove();
      }, 250);
    };

    toast.querySelector('.toast-close').addEventListener('click', removeToast);

    this.container.appendChild(toast);

    if (duration > 0) {
      setTimeout(removeToast, duration);
    }
  },

  success(title, message, duration) {
    this.show('success', title || 'สำเร็จ (Success)', message, duration);
  },

  error(title, message, duration) {
    this.show('error', title || 'เกิดข้อผิดพลาด (Error)', message, duration || 5000);
  },

  warning(title, message, duration) {
    this.show('warning', title || 'แจ้งเตือน (Warning)', message, duration);
  },

  info(title, message, duration) {
    this.show('info', title || 'ข้อมูล (Info)', message, duration);
  }
};
