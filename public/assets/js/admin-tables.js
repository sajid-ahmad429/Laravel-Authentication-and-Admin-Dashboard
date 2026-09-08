/* ============================================================================
 * AdminTable — production Tabulator wrapper for server-side tables.
 *
 * Features
 *  - Remote pagination / sorting / filtering through a clean Laravel contract
 *    (page, size, sort_by, sort_dir + custom filter params).
 *  - Debounced global search, filter chips, page-size selector.
 *  - CSRF-protected POST transport, structured error handling (401/403/419/500).
 *  - Responsive collapse layout for phones & tablets.
 *  - Client-side CSV / JSON export of the current page.
 * ========================================================================== */
(function (window, document) {
  'use strict';

  var AdminUI = {

    csrf: function () {
      var meta = document.querySelector('meta[name="csrf-token"]');
      return meta ? meta.getAttribute('content') : '';
    },

    escape: function (value) {
      if (value === null || value === undefined) return '';
      return String(value)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');
    },

    toast: function (message, type) {
      type = type || 'info';
      var icons = { success: 'mdi-check-circle', danger: 'mdi-alert-circle', info: 'mdi-information-outline' };
      var stack = document.querySelector('.adt-toast-stack');
      if (!stack) {
        stack = document.createElement('div');
        stack.className = 'adt-toast-stack';
        document.body.appendChild(stack);
      }
      var el = document.createElement('div');
      el.className = 'adt-toast ' + type;
      el.innerHTML = '<i class="mdi ' + (icons[type] || icons.info) + '"></i><div>' + AdminUI.escape(message) + '</div>';
      stack.appendChild(el);
      setTimeout(function () {
        el.style.transition = 'opacity .3s ease';
        el.style.opacity = '0';
        setTimeout(function () { el.remove(); }, 320);
      }, 3500);
    },

    debounce: function (fn, wait) {
      var t = null;
      return function () {
        var args = arguments, ctx = this;
        clearTimeout(t);
        t = setTimeout(function () { fn.apply(ctx, args); }, wait || 350);
      };
    },

    confirm: function (options) {
      if (typeof Swal === 'undefined') {
        return Promise.resolve(window.confirm(options.text || options.title || 'Are you sure?'));
      }
      return Swal.fire({
        title: options.title || 'Are you sure?',
        text: options.text || '',
        icon: options.icon || 'warning',
        showCancelButton: true,
        confirmButtonText: options.confirmText || 'Yes, proceed',
        cancelButtonText: 'Cancel',
        confirmButtonColor: options.confirmColor || '#666cff',
        cancelButtonColor: '#6f6b7d',
        reverseButtons: true
      }).then(function (r) { return r.isConfirmed; });
    },

    /* ---------------------------------------------------------------- formatters */
    fmt: {
      avatarCell: function (cell) {
        var d = cell.getRow().getData();
        var name = AdminUI.escape(d.name || '—');
        var email = AdminUI.escape(d.email || '');
        var avatar;
        if (d.avatar) {
          avatar = '<img class="adt-avatar" src="' + AdminUI.escape(d.avatar) + '" alt="" loading="lazy">';
        } else {
          avatar = '<span class="adt-avatar">' + AdminUI.escape(d.initials || 'U') + '</span>';
        }
        return '<div class="adt-user-cell">' + avatar +
          '<div class="adt-user-meta"><div class="adt-user-name">' + name + '</div>' +
          (email ? '<div class="adt-user-email">' + email + '</div>' : '') +
          '</div></div>';
      },

      badge: function (text, tone, icon) {
        return '<span class="adt-badge ' + tone + '">' + (icon ? '<i class="mdi ' + icon + '"></i>' : '') +
          AdminUI.escape(text) + '</span>';
      },

      role: {
        icons: {
          superadmin: 'mdi-shield-crown-outline',
          admin: 'mdi-laptop',
          editor: 'mdi-pencil-outline',
          author: 'mdi-account-edit-outline',
          maintainer: 'mdi-chart-donut',
          subscriber: 'mdi-account-outline'
        },
        tones: {
          superadmin: 'danger',
          admin: 'primary',
          editor: 'info',
          author: 'warning',
          maintainer: 'success',
          subscriber: 'secondary'
        },
        cell: function (cell) {
          var raw = (cell.getValue() || '').toString();
          if (!raw) return '<span class="adt-badge secondary">—</span>';
          var key = raw.toLowerCase();
          return AdminUI.fmt.badge(raw, AdminUI.fmt.role.tones[key] || 'dark', AdminUI.fmt.role.icons[key] || 'mdi-account-circle-outline');
        }
      },

      statusToggle: function (cell, table, onToggle) {
        var d = cell.getRow().getData();
        var active = String(d.status) === '1';
        var btn = document.createElement('button');
        btn.className = 'adt-status-toggle';
        btn.setAttribute('type', 'button');
        btn.setAttribute('title', active ? 'Click to deactivate' : 'Click to activate');
        btn.innerHTML = active
          ? '<span class="adt-badge success"><i class="mdi mdi-check-circle-outline"></i>Active</span>'
          : '<span class="adt-badge secondary"><i class="mdi mdi-close-circle-outline"></i>Inactive</span>';
        btn.addEventListener('click', function () { onToggle(d, cell); });
        return btn;
      },

      simpleBadge: function (tone, icon) {
        return function (cell) {
          var v = cell.getValue();
          if (v === null || v === undefined || v === '') return '<span class="text-muted">—</span>';
          return AdminUI.fmt.badge(v, tone, icon);
        };
      },

      actions: function (actions) {
        return function (cell) {
          var d = cell.getRow().getData();
          var wrap = document.createElement('div');
          wrap.className = 'adt-actions-cell';
          actions.forEach(function (a) {
            if (a.when && !a.when(d)) return;
            var b = document.createElement('button');
            b.type = 'button';
            b.className = 'adt-icon-btn' + (a.danger ? ' danger' : '');
            b.title = (typeof a.title === 'function' ? a.title(d) : a.title) || '';
            b.setAttribute('aria-label', b.title);
            b.innerHTML = '<i class="mdi ' + (typeof a.icon === 'function' ? a.icon(d) : a.icon) + '"></i>';
            b.addEventListener('click', function () { a.onClick(d, cell); });
            wrap.appendChild(b);
          });
          return wrap;
        };
      },

      text: function (fallback) {
        return function (cell) {
          var v = cell.getValue();
          if (v === null || v === undefined || String(v) === '') {
            var el = document.createElement('span');
            el.className = 'text-muted';
            el.textContent = fallback || '—';
            return el;
          }
          return document.createTextNode(String(v));
        };
      },

      date: function (cell) {
        var v = cell.getValue();
        var el = document.createElement('span');
        el.className = 'text-muted';
        el.style.whiteSpace = 'nowrap';
        el.textContent = v || '—';
        return el;
      }
    }
  };

  /* ======================================================================== */

  function AdminTable(el, options) {
    if (typeof Tabulator === 'undefined') {
      console.error('AdminTable: Tabulator is not loaded.');
      return;
    }
    this.el = typeof el === 'string' ? document.querySelector(el) : el;
    this.options = Object.assign({
      url: '',
      columns: [],
      initialSort: [{ column: 'id', dir: 'desc' }],
      pageSize: 10,
      searchInput: null,
      filters: {},          // extra params e.g. {trash: 0, status: '', role: ''}
      onData: null,         // (payload, table) => void — expose stats etc.
      height: undefined,    // set to enable fixed-height scrolling
      exportName: 'export'
    }, options || {});

    this.state = Object.assign({ search: '' }, this.options.filters);
    this.errorBox = null;
    this.build();
  }

  AdminTable.prototype.build = function () {
    var self = this;

    // Wrapper structure: toolbar handled by the page; we only own the table + footer.
    var footer = document.createElement('div');
    footer.className = 'adt-footer';
    var info = document.createElement('div');
    info.className = 'adt-info-text';
    var pager = document.createElement('div');
    pager.className = 'adt-pager-wrap';
    footer.appendChild(info);
    footer.appendChild(pager);

    this.el.classList.add('adt-scope');
    this.el.innerHTML = '';
    var tableDiv = document.createElement('div');
    tableDiv.className = 'adt-table';
    this.el.appendChild(tableDiv);
    this.el.appendChild(footer);
    this.infoEl = info;

    var sorters = this.options.initialSort && this.options.initialSort[0]
      ? this.options.initialSort : [{ column: 'id', dir: 'desc' }];

    this.tabulator = new Tabulator(tableDiv, {
      layout: 'fitDataFill',
      responsiveLayout: 'collapse',
      responsiveLayoutCollapseStartExpanded: false,
      responsiveLayoutCollapseFormatter: function (data) {
        var list = document.createElement('div');
        list.style.width = '100%';
        Object.keys(data).forEach(function (label) {
          var row = document.createElement('div');
          row.style.cssText = 'display:flex;justify-content:space-between;gap:1rem;align-items:center;padding:.3rem 0;';
          var k = document.createElement('span');
          k.className = 'adt-m-label';
          k.textContent = label;
          var v = document.createElement('span');
          v.appendChild(data[label] instanceof Node ? data[label] : document.createTextNode(String(data[label])));
          row.appendChild(k);
          row.appendChild(v);
          list.appendChild(row);
        });
        return list;
      },
      pagination: 'remote',
      paginationSize: self.options.pageSize,
      paginationSizeSelect: [10, 25, 50, 100],
      paginationElement: pager,
      paginationButtonCount: 5,
      dataSendParams: { page: 'page', size: 'size', sorters: false },
      initialSort: sorters,
      ajaxURL: self.options.url,
      ajaxConfig: {
        method: 'POST',
        headers: {
          'X-CSRF-TOKEN': AdminUI.csrf(),
          'X-Requested-With': 'XMLHttpRequest',
          'Accept': 'application/json'
        }
      },
      ajaxURLGenerator: function (url, config, params) {
        // Map Tabulator's remote pagination params onto the Laravel contract.
        var sort = (params.sorters && params.sorters[0]) || sorters[0];
        var query = {
          page: params.page || 1,
          size: params.size || self.options.pageSize,
          sort_by: sort ? sort.column : 'id',
          sort_dir: sort ? sort.dir : 'desc'
        };
        Object.keys(self.state).forEach(function (k) {
          var v = self.state[k];
          if (v !== '' && v !== null && v !== undefined) query[k] = v;
        });
        var qs = Object.keys(query).map(function (k) {
          return encodeURIComponent(k) + '=' + encodeURIComponent(query[k]);
        }).join('&');
        return url + (url.indexOf('?') === -1 ? '?' : '&') + qs;
      },
      ajaxRequestFunc: function (url, config, params) {
        // Tabulator's ajaxURLGenerator gave us the final query string.
        return fetch(url, {
          method: config.method || 'POST',
          headers: config.headers || {},
          credentials: 'same-origin',
          body: JSON.stringify({})
        }).then(function (res) {
          return res.json().then(function (json) {
            if (!res.ok) {
              var err = new Error(json.message || ('Request failed (' + res.status + ')'));
              err.status = res.status;
              err.payload = json;
              throw err;
            }
            return json;
          });
        });
      },
      ajaxResponse: function (url, params, response) {
        // Normalize Laravel payload to Tabulator's remote contract.
        self.lastTotal = response.total || (response.data ? response.data.length : 0);
        if (typeof self.options.onData === 'function') {
          self.options.onData(response, self);
        }
        return {
          last_page: response.last_page || 1,
          data: response.data || []
        };
      },
      columns: (self.options.columns || []).map(function (c) {
        return Object.assign({ headerSortTristate: false }, c);
      }),
      placeholder: '<div class="adt-empty"><i class="mdi mdi-magnify"></i><p>No records match your filters.</p></div>',
      index: 'id',
      progressiveLoad: false
    });

    this.tabulator.on('dataLoaded', function () { self.hideError(); });

    this.tabulator.on('dataLoadError', function (error) {
      self.showError(error);
    });

    this.tabulator.on('renderComplete', function () {
      var size = self.tabulator.getPageSize();
      var count = self.tabulator.getDataCount('active');
      var page = self.tabulator.getPage();
      var last = self.tabulator.getPageMax();
      var from = last === 0 ? 0 : (page - 1) * size + 1;
      var to = last === 0 ? 0 : from + count - 1;
      var total = (self.lastTotal !== undefined) ? self.lastTotal : to;
      if (self.infoEl) {
        self.infoEl.textContent = 'Showing ' + from + '–' + to + ' of ' + total + ' records';
      }
      // Normalize built-in pager buttons styling.
      pager.classList.add('adt-pager');
      pager.querySelectorAll('button').forEach(function (b) {
        b.classList.add('adt-page-btn');
        b.removeAttribute('style');
        if (b.classList.contains('tabulator-page-active') || b.classList.contains('active')) {
          b.classList.add('active');
        }
      });
    });

    // Global search wiring.
    if (this.options.searchInput) {
      var input = typeof this.options.searchInput === 'string'
        ? document.querySelector(this.options.searchInput)
        : this.options.searchInput;
      if (input) {
        input.addEventListener('input', AdminUI.debounce(function () {
          self.state.search = input.value.trim();
          self.reload(true);
        }, 350));
      }
    }
  };

  AdminTable.prototype.reload = function (resetPage) {
    this.hideError();
    if (resetPage) this.tabulator.setPage(1);
    else this.tabulator.setData();
  };

  AdminTable.prototype.setFilter = function (key, value) {
    this.state[key] = value;
    this.reload(true);
  };

  AdminTable.prototype.showError = function (error) {
    var self = this;
    if (error && (error.status === 401 || error.status === 419)) {
      AdminUI.toast('Your session expired. Redirecting to sign-in…', 'danger');
      setTimeout(function () { window.location.href = '/login'; }, 1200);
      return;
    }
    var message = (error && error.message) || 'Failed to load data.';
    if (self.errorBox && self.errorBox.parentNode) {
      self.errorBox.querySelector('.adt-error-text').textContent = message;
      return;
    }
    self.errorBox = document.createElement('div');
    self.errorBox.className = 'adt-error-banner';
    self.errorBox.innerHTML = '<span class="adt-error-text">' + AdminUI.escape(message) + '</span>';
    var retry = document.createElement('button');
    retry.type = 'button';
    retry.className = 'adt-chip active';
    retry.textContent = 'Retry';
    retry.addEventListener('click', function () { self.reload(false); });
    self.errorBox.appendChild(retry);
    self.el.insertBefore(self.errorBox, self.el.firstChild);
  };

  AdminTable.prototype.hideError = function () {
    if (this.errorBox && this.errorBox.parentNode) this.errorBox.remove();
  };

  AdminTable.prototype.export = function (type) {
    type = type === 'json' ? 'json' : 'csv';
    this.tabulator.download(type, this.options.exportName + '.' + type);
  };

  AdminTable.prototype.getTabulator = function () { return this.tabulator; };

  /* Ajax helper for the CRUD forms. */
  AdminUI.ajax = function (url, data) {
    return fetch(url, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-CSRF-TOKEN': AdminUI.csrf(),
        'X-Requested-With': 'XMLHttpRequest'
      },
      credentials: 'same-origin',
      body: JSON.stringify(data || {})
    }).then(function (res) {
      return res.json().then(function (json) {
        if (!res.ok) {
          var err = new Error(json.message || 'Request failed');
          err.status = res.status;
          err.errors = json.errors || null;
          throw err;
        }
        return json;
      });
    });
  };

  /* Flash message bootstrap (Laravel session flash -> toast). */
  document.addEventListener('DOMContentLoaded', function () {
    ['success', 'danger', 'warning', 'info'].forEach(function (level) {
      var node = document.querySelector('[data-flash="' + level + '"]');
      if (node && node.textContent.trim()) {
        AdminUI.toast(node.textContent.trim(), level === 'warning' ? 'info' : (level === 'danger' ? 'danger' : 'success'));
      }
    });
  });

  window.AdminUI = AdminUI;
  window.AdminTable = AdminTable;
})(window, document);
