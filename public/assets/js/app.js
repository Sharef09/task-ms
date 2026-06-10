(function () {
  'use strict';

  /* ================================================================
   *  1. Auto-hide Flash Messages
   * ================================================================ */
  document.querySelectorAll('.alert-dismissible').forEach(function (alert) {
    setTimeout(function () {
      var bsAlert = bootstrap.Alert.getInstance(alert);
      if (bsAlert) {
        bsAlert.close();
      } else {
        alert.classList.remove('show');
        alert.style.display = 'none';
      }
    }, 5000);
  });

  /* ================================================================
   *  2. Notification Polling
   * ================================================================ */
  var notifBadge = document.getElementById('notification-count');
  var notifPollUrl = notifBadge ? notifBadge.getAttribute('data-poll-url') : null;

  function pollNotifications() {
    if (!notifBadge) return;
    var url = notifPollUrl || (window.location.origin + '/task-ms/notifications/unread-count');
    fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
      .then(function (r) { return r.json(); })
      .then(function (data) {
        var count = parseInt(data.count, 10) || 0;
        if (count > 0) {
          notifBadge.textContent = count > 99 ? '99+' : count;
          notifBadge.style.display = 'flex';
        } else {
          notifBadge.style.display = 'none';
        }
      })
      .catch(function () {});
  }

  if (notifBadge) {
    pollNotifications();
    setInterval(pollNotifications, 30000);
  }

  /* ================================================================
   *  3. Mark Notification as Read (via AJAX)
   * ================================================================ */
  document.addEventListener('click', function (e) {
    var markBtn = e.target.closest('[data-mark-read]');
    if (markBtn) {
      var url = markBtn.getAttribute('data-url');
      if (!url) return;
      e.preventDefault();
      fetch(url, {
        method: 'POST',
        headers: {
          'X-Requested-With': 'XMLHttpRequest',
          'Content-Type': 'application/x-www-form-urlencoded'
        },
        body: '_csrf_token=' + encodeURIComponent(getCsrfToken())
      })
      .then(function (r) { return r.json(); })
      .then(function (data) {
        if (data.success) {
          markBtn.closest('.notification-item').classList.remove('bg-light');
          markBtn.remove();
          pollNotifications();
        }
      })
      .catch(function () {});
      return;
    }

    var notifItem = e.target.closest('[data-mark-read-url]');
    if (notifItem) {
      var readUrl = notifItem.getAttribute('data-mark-read-url');
      if (readUrl) {
        fetch(readUrl, {
          method: 'POST',
          headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Content-Type': 'application/x-www-form-urlencoded'
          },
          body: '_csrf_token=' + encodeURIComponent(getCsrfToken())
        }).then(function () { pollNotifications(); }).catch(function () {});
      }
    }
  });

  /* ================================================================
   *  4. Mark All Notifications as Read (via AJAX)
   * ================================================================ */
  document.addEventListener('click', function (e) {
    var markAllBtn = e.target.closest('.mark-all-read');
    if (!markAllBtn) return;
    var url = markAllBtn.getAttribute('data-url');
    if (!url) return;
    e.preventDefault();
    fetch(url, {
      method: 'POST',
      headers: {
        'X-Requested-With': 'XMLHttpRequest',
        'Content-Type': 'application/x-www-form-urlencoded'
      },
      body: '_csrf_token=' + encodeURIComponent(getCsrfToken())
    })
    .then(function (r) { return r.json(); })
    .then(function (data) {
      if (data.success) {
        document.querySelectorAll('.notification-item').forEach(function (item) {
          item.classList.remove('bg-light');
        });
        markAllBtn.remove();
        if (notifBadge) { notifBadge.style.display = 'none'; }
      }
    })
    .catch(function () {});
  });

  /* ================================================================
   *  5. Delete Confirmation Handler (data-confirm)
   * ================================================================ */
  document.addEventListener('click', function (e) {
    var confirmBtn = e.target.closest('[data-confirm]');
    if (!confirmBtn) return;
    if (!confirm(confirmBtn.getAttribute('data-confirm') || 'Are you sure?')) {
      e.preventDefault();
    }
  });

  /* ================================================================
   *  6. Permission Matrix Checkboxes
   * ================================================================ */
  var permTable = document.getElementById('permissionTable');
  if (permTable) {
    // Column toggle all
    permTable.querySelectorAll('.column-toggle-all').forEach(function (cb) {
      cb.addEventListener('change', function () {
        var action = this.getAttribute('data-action');
        var checked = this.checked;
        permTable.querySelectorAll('.permission-checkbox[data-action="' + action + '"]').forEach(function (pcb) {
          if (pcb.checked !== checked) {
            pcb.checked = checked;
            savePermission(pcb);
          }
        });
        updateColumnToggleStates();
      });
    });

    // Individual checkbox save on change
    permTable.querySelectorAll('.permission-checkbox').forEach(function (cb) {
      cb.addEventListener('change', function () {
        savePermission(this);
        updateColumnToggleStates();
      });
    });

    function savePermission(checkbox) {
      var type = checkbox.getAttribute('data-type');
      var entityId = checkbox.getAttribute('data-entity-id');
      var permissionId = checkbox.getAttribute('data-permission-id');
      var granted = checkbox.checked ? 1 : 0;

      var params = new URLSearchParams();
      params.set('_csrf_token', getCsrfToken());
      params.set('type', type);
      params.set('entity_id', entityId);
      params.set('permission_id', permissionId);
      params.set('granted', granted);

      var toggleUrl = checkbox.getAttribute('data-toggle-url') || (window.location.origin + '/task-ms/permissions/toggle');

      fetch(toggleUrl, {
        method: 'POST',
        headers: {
          'X-Requested-With': 'XMLHttpRequest',
          'Content-Type': 'application/x-www-form-urlencoded'
        },
        body: params.toString()
      })
      .then(function (r) { return r.json(); })
      .then(function (data) {
        if (!data.success) {
          checkbox.checked = !checkbox.checked;
          if (data.message) { showToast(data.message, 'error'); }
        }
      })
      .catch(function () {
        checkbox.checked = !checkbox.checked;
        showToast('An error occurred while saving the permission.', 'error');
      });
    }

    function updateColumnToggleStates() {
      permTable.querySelectorAll('.column-toggle-all').forEach(function (cb) {
        var action = cb.getAttribute('data-action');
        var checkboxes = permTable.querySelectorAll('.permission-checkbox[data-action="' + action + '"]');
        if (checkboxes.length === 0) {
          cb.checked = false;
          cb.indeterminate = false;
          return;
        }
        var checkedCount = permTable.querySelectorAll('.permission-checkbox[data-action="' + action + '"]:checked').length;
        if (checkedCount === checkboxes.length) {
          cb.checked = true;
          cb.indeterminate = false;
        } else if (checkedCount === 0) {
          cb.checked = false;
          cb.indeterminate = false;
        } else {
          cb.checked = false;
          cb.indeterminate = true;
        }
      });
    }

    updateColumnToggleStates();
  }

  /* ================================================================
   *  7. Bulk Actions Handler
   * ================================================================ */
  document.addEventListener('change', function (e) {
    var selectAll = e.target.closest('[data-select-all]');
    if (!selectAll) return;
    var target = selectAll.getAttribute('data-select-all');
    var checked = selectAll.checked;
    document.querySelectorAll(target).forEach(function (cb) { cb.checked = checked; });
    updateBulkButtons();
  });

  document.addEventListener('change', function (e) {
    var childCb = e.target.closest('[data-bulk-child]');
    if (!childCb) return;
    updateBulkButtons();
  });

  function updateBulkButtons() {
    document.querySelectorAll('[data-bulk-apply]').forEach(function (btn) {
      var selector = btn.getAttribute('data-bulk-selector') || 'input[data-bulk-child]:checked';
      var count = document.querySelectorAll(selector).length;
      btn.disabled = count === 0;
      var countEl = document.getElementById(btn.getAttribute('data-bulk-count') || '');
      if (countEl) { countEl.textContent = count + ' selected'; }
    });
  }

  document.addEventListener('click', function (e) {
    var applyBtn = e.target.closest('[data-bulk-apply]');
    if (!applyBtn) return;
    var selector = applyBtn.getAttribute('data-bulk-selector') || 'input[data-bulk-child]:checked';
    var checked = document.querySelectorAll(selector);
    if (checked.length === 0) return;
    var actionEl = document.querySelector(applyBtn.getAttribute('data-bulk-action-selector') || '[data-bulk-action]');
    var action = actionEl ? actionEl.value : '';
    if (!action) return;
    var ids = Array.from(checked).map(function (cb) { return cb.value; }).join(',');
    var form = document.getElementById(applyBtn.getAttribute('data-bulk-form') || 'bulkActionForm');
    if (form) {
      var idsInput = form.querySelector('[data-bulk-ids]') || form.querySelector('#bulkIds');
      if (idsInput) { idsInput.value = ids; }
      var actionInput = form.querySelector('[data-bulk-type]') || form.querySelector('#bulkActionType');
      if (actionInput) { actionInput.value = action; }
      form.submit();
    }
  });

  /* ================================================================
   *  8. OTP Auto-Advance
   * ================================================================ */
  var otpForm = document.getElementById('otpForm');
  if (otpForm) {
    var otpInputs = otpForm.querySelectorAll('.otp-digit');

    otpInputs.forEach(function (input, index) {
      input.addEventListener('input', function (e) {
        this.value = this.value.replace(/[^0-9]/g, '').substring(0, 1);
        if (this.value && index < otpInputs.length - 1) {
          otpInputs[index + 1].focus();
        }
        updateOtpHidden();
      });

      input.addEventListener('keydown', function (e) {
        if (e.key === 'Backspace' && !this.value && index > 0) {
          otpInputs[index - 1].focus();
          otpInputs[index - 1].value = '';
          updateOtpHidden();
        }
        if (e.key === 'ArrowLeft' && index > 0) {
          otpInputs[index - 1].focus();
        }
        if (e.key === 'ArrowRight' && index < otpInputs.length - 1) {
          otpInputs[index + 1].focus();
        }
      });

      input.addEventListener('focus', function () { this.select(); });
    });

    function updateOtpHidden() {
      var hidden = document.getElementById('otpHidden');
      if (hidden) {
        var otp = '';
        otpInputs.forEach(function (inp) { otp += inp.value; });
        hidden.value = otp;
      }
    }

    otpForm.addEventListener('submit', function () { updateOtpHidden(); });
    if (otpInputs.length > 0) { otpInputs[0].focus(); }
  }

  /* ================================================================
   *  9. Character Counters
   * ================================================================ */
  document.addEventListener('input', function (e) {
    var textarea = e.target.closest('[data-char-counter]');
    if (!textarea) return;
    var max = parseInt(textarea.getAttribute('maxlength'), 10);
    var counterId = textarea.getAttribute('data-char-counter');
    var counter = document.getElementById(counterId);
    if (!counter || !max) return;
    var remaining = max - textarea.value.length;
    counter.textContent = remaining + ' characters remaining';
    counter.classList.toggle('text-danger', remaining < 20);
    counter.classList.toggle('text-muted', remaining >= 20);
  });

  /* ================================================================
   *  10. Form Validation
   * ================================================================ */
  document.querySelectorAll('form[data-validate]').forEach(function (form) {
    form.addEventListener('submit', function (e) {
      if (!form.checkValidity()) {
        e.preventDefault();
        e.stopPropagation();
      }
      form.classList.add('was-validated');
    });
  });

  /* ================================================================
   *  11. Password Show/Hide Toggle
   * ================================================================ */
  document.addEventListener('click', function (e) {
    var toggleBtn = e.target.closest('[data-toggle-password]');
    if (!toggleBtn) return;
    var targetId = toggleBtn.getAttribute('data-toggle-password');
    var input = document.getElementById(targetId);
    if (!input) return;
    var icon = toggleBtn.querySelector('i');
    if (input.type === 'password') {
      input.type = 'text';
      if (icon) { icon.className = 'fas fa-eye-slash'; }
    } else {
      input.type = 'password';
      if (icon) { icon.className = 'fas fa-eye'; }
    }
  });

  /* ================================================================
   *  12. File Input Preview
   * ================================================================ */
  document.addEventListener('change', function (e) {
    var fileInput = e.target.closest('[data-file-preview]');
    if (!fileInput) return;
    var targetId = fileInput.getAttribute('data-file-preview');
    var target = document.getElementById(targetId);
    if (!target) return;
    if (fileInput.files && fileInput.files.length > 0) {
      target.textContent = fileInput.files[0].name;
      target.classList.remove('text-muted');
    } else {
      target.textContent = 'No file chosen';
      target.classList.add('text-muted');
    }
  });

  /* ================================================================
   *  13. Search Form Submission
   * ================================================================ */
  document.querySelectorAll('form[data-search-form]').forEach(function (form) {
    form.addEventListener('submit', function (e) {
      var inputs = form.querySelectorAll('input, select, textarea');
      inputs.forEach(function (input) {
        if (input.value === '' || input.value === null) {
          input.disabled = true;
        }
      });
    });
  });

  /* ================================================================
   *  14. Data Table Sorting
   * ================================================================ */
  document.querySelectorAll('[data-sort]').forEach(function (header) {
    header.addEventListener('click', function () {
      var table = this.closest('table');
      if (!table) return;
      var tbody = table.querySelector('tbody');
      if (!tbody) return;
      var colIndex = Array.from(this.parentNode.children).indexOf(this);
      var rows = Array.from(tbody.querySelectorAll('tr'));
      var sortKey = this.getAttribute('data-sort');
      var dir = this.getAttribute('data-sort-dir') || 'asc';

      var headerRow = this.parentNode;
      headerRow.querySelectorAll('[data-sort]').forEach(function (h) {
        if (h !== this) h.removeAttribute('data-sort-dir');
      }.bind(this));

      rows.sort(function (a, b) {
        var aVal = a.children[colIndex] ? a.children[colIndex].textContent.trim().toLowerCase() : '';
        var bVal = b.children[colIndex] ? b.children[colIndex].textContent.trim().toLowerCase() : '';
        var aNum = parseFloat(aVal);
        var bNum = parseFloat(bVal);
        if (!isNaN(aNum) && !isNaN(bNum)) {
          return dir === 'asc' ? aNum - bNum : bNum - aNum;
        }
        if (aVal < bVal) return dir === 'asc' ? -1 : 1;
        if (aVal > bVal) return dir === 'asc' ? 1 : -1;
        return 0;
      });

      rows.forEach(function (row) { tbody.appendChild(row); });
      this.setAttribute('data-sort-dir', dir === 'asc' ? 'desc' : 'asc');

      // Update sort icon
      var icon = this.querySelector('[data-sort-icon]');
      if (icon) {
        var isAsc = this.getAttribute('data-sort-dir') === 'asc';
        icon.className = 'fas fa-sort-' + (isAsc ? 'up' : 'down') + ' text-primary small';
      }
    });
  });

  /* ================================================================
   *  15. Tooltip and Popover Initialization
   * ================================================================ */
  document.addEventListener('DOMContentLoaded', function () {
    var tooltipTriggerList = document.querySelectorAll('[data-bs-toggle="tooltip"]');
    tooltipTriggerList.forEach(function (el) {
      new bootstrap.Tooltip(el);
    });

    var popoverTriggerList = document.querySelectorAll('[data-bs-toggle="popover"]');
    popoverTriggerList.forEach(function (el) {
      new bootstrap.Popover(el);
    });
  });

  var tooltipTriggerList = document.querySelectorAll('[data-bs-toggle="tooltip"]');
  tooltipTriggerList.forEach(function (el) { new bootstrap.Tooltip(el); });
  var popoverTriggerList = document.querySelectorAll('[data-bs-toggle="popover"]');
  popoverTriggerList.forEach(function (el) { new bootstrap.Popover(el); });

  /* ================================================================
   *  16. Sidebar Toggle on Mobile
   * ================================================================ */
  var sidebarToggle = document.getElementById('sidebarToggle');
  var sidebarEl = document.getElementById('sidebar');

  if (sidebarToggle && sidebarEl) {
    sidebarToggle.addEventListener('click', function (e) {
      e.preventDefault();
      sidebarEl.classList.toggle('show');
      var backdrop = document.querySelector('.sidebar-backdrop');
      if (!backdrop) {
        backdrop = document.createElement('div');
        backdrop.className = 'sidebar-backdrop';
        backdrop.addEventListener('click', function () {
          sidebarEl.classList.remove('show');
          backdrop.classList.remove('show');
        });
        document.body.appendChild(backdrop);
      }
      backdrop.classList.toggle('show');
    });
  }

  /* ================================================================
   *  17. AJAX Global Setup with CSRF Token
   * ================================================================ */
  function getCsrfToken() {
    var meta = document.querySelector('meta[name="csrf-token"]');
    if (meta) return meta.getAttribute('content');
    var input = document.querySelector('input[name="_csrf_token"]');
    if (input) return input.value;
    return '';
  }

  // Fetch wrapper with CSRF
  window.apiFetch = function (url, options) {
    options = options || {};
    options.headers = options.headers || {};
    options.headers['X-Requested-With'] = 'XMLHttpRequest';
    options.headers['X-CSRF-Token'] = getCsrfToken();

    if (options.body && typeof options.body === 'object' && !(options.body instanceof FormData) && !(options.body instanceof URLSearchParams)) {
      options.body = JSON.stringify(options.body);
      options.headers['Content-Type'] = 'application/json';
    }

    return fetch(url, options);
  };

  /* ================================================================
   *  18. Pagination AJAX
   * ================================================================ */
  document.addEventListener('click', function (e) {
    var pageLink = e.target.closest('[data-pagination-ajax]');
    if (!pageLink) return;
    e.preventDefault();
    var url = pageLink.getAttribute('href');
    var targetId = pageLink.getAttribute('data-pagination-ajax');
    var target = document.getElementById(targetId);
    if (!target) return;

    var spinner = target.querySelector('.pagination-spinner');
    if (!spinner) {
      spinner = document.createElement('div');
      spinner.className = 'text-center py-3 pagination-spinner';
      spinner.innerHTML = '<div class="spinner-border spinner-border-sm text-primary" role="status"><span class="visually-hidden">Loading...</span></div>';
      target.appendChild(spinner);
    }
    spinner.style.display = 'block';

    fetch(url, {
      headers: {
        'X-Requested-With': 'XMLHttpRequest'
      }
    })
    .then(function (r) { return r.text(); })
    .then(function (html) {
      target.innerHTML = html;
    })
    .catch(function () {
      window.location.href = url;
    });
  });

  /* ================================================================
   *  19. CSRF Token Refresh
   * ================================================================ */
  document.addEventListener('submit', function (e) {
    var form = e.target;
    if (form.querySelector('input[name="_csrf_token"]')) {
      var metaToken = getCsrfToken();
      var input = form.querySelector('input[name="_csrf_token"]');
      if (input && metaToken) {
        input.value = metaToken;
      }
    }
  });

  /* ================================================================
   *  20. Toast Notification (Helper)
   * ================================================================ */
  function showToast(message, type) {
    type = type || 'info';
    var container = document.getElementById('toast-container');
    if (!container) {
      container = document.createElement('div');
      container.id = 'toast-container';
      container.style.cssText = 'position:fixed;top:16px;right:16px;z-index:9999;display:flex;flex-direction:column;gap:8px;';
      document.body.appendChild(container);
    }
    var toast = document.createElement('div');
    toast.className = 'toast align-items-center text-white bg-' + type + ' border-0 show';
    toast.setAttribute('role', 'alert');
    toast.innerHTML = '<div class="d-flex"><div class="toast-body">' + message + '</div><button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button></div>';
    container.appendChild(toast);
    setTimeout(function () {
      toast.classList.remove('show');
      setTimeout(function () { toast.remove(); }, 300);
    }, 4000);
  }
  window.showToast = showToast;

  /* ================================================================
   *  21. Select All / Bulk Checkbox Helper
   * ================================================================ */
  document.addEventListener('change', function (e) {
    var cb = e.target.closest('.checkbox-parent');
    if (!cb) return;
    var table = cb.closest('table');
    if (!table) return;
    var children = table.querySelectorAll('.checkbox-child');
    children.forEach(function (child) { child.checked = cb.checked; });
    var countEl = table.closest('.card') ? table.closest('.card').querySelector('.selected-count') : null;
    if (countEl) {
      var count = cb.checked ? children.length : 0;
      countEl.textContent = count + ' selected';
    }
  });

  document.addEventListener('change', function (e) {
    var child = e.target.closest('.checkbox-child');
    if (!child) return;
    var table = child.closest('table');
    if (!table) return;
    var parent = table.querySelector('.checkbox-parent');
    if (!parent) return;
    var children = table.querySelectorAll('.checkbox-child');
    var checked = table.querySelectorAll('.checkbox-child:checked');
    if (checked.length === children.length) {
      parent.checked = true;
      parent.indeterminate = false;
    } else if (checked.length === 0) {
      parent.checked = false;
      parent.indeterminate = false;
    } else {
      parent.checked = false;
      parent.indeterminate = true;
    }
    var countEl = table.closest('.card') ? table.closest('.card').querySelector('.selected-count') : null;
    if (countEl) {
      countEl.textContent = checked.length + ' selected';
    }
  });

  /* ================================================================
   *  22. Spinner on Form Submit
   * ================================================================ */
  document.addEventListener('submit', function (e) {
    var btn = e.target.querySelector('[data-loading]');
    if (!btn) return;
    var originalHtml = btn.innerHTML;
    btn.disabled = true;
    btn.setAttribute('data-original-html', originalHtml);
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status"></span>' + (btn.getAttribute('data-loading-text') || 'Processing...');
  });

})();
