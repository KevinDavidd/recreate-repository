/**
 * assets/js/main.js — CECT Research Archive
 * AJAX search + filter + pagination for home.php
 */

(function () {
  'use strict';

  // Only run on home.php (check for the search grid element)
  const grid         = document.getElementById('researchGrid');
  if (!grid) return;

  const searchInput  = document.getElementById('searchInput');
  const searchBtn    = document.getElementById('searchBtn');
  const filterCourse = document.getElementById('filterCourse');
  const filterYear   = document.getElementById('filterYear');
  const filterType   = document.getElementById('filterType');
  const filterSort   = document.getElementById('filterSort');
  const counter      = document.getElementById('resultsCounter');
  const paginationNav= document.getElementById('paginationNav');

  let currentPage    = 1;
  let debounceTimer  = null;

  // ── Fetch and render results ─────────────────────────────────────
  function fetchResults(page) {
    page = page || 1;
    currentPage = page;

    const params = new URLSearchParams({
      ajax:          '1',
      search:        searchInput  ? searchInput.value.trim()  : '',
      course:        filterCourse ? filterCourse.value        : '',
      year:          filterYear   ? filterYear.value          : '',
      research_type: filterType   ? filterType.value          : '',
      sort:          filterSort   ? filterSort.value          : 'newest',
      page:          page,
    });

    // Visual loading state
    grid.style.opacity = '0.4';

    fetch('home.php?' + params.toString())
      .then(r => {
        if (!r.ok) throw new Error('Network error');
        return r.json();
      })
      .then(data => {
        grid.innerHTML    = data.html;
        grid.style.opacity= '1';

        // Update counter
        if (counter) {
          const shown = grid.querySelectorAll('.col-md-6, .col-xl-4').length;
          counter.innerHTML =
            'Showing <strong>' + data.total + '</strong> research paper' + (data.total !== 1 ? 's' : '');
        }

        // Rebuild pagination
        renderPagination(data.currentPage, data.totalPages);
      })
      .catch(() => {
        grid.style.opacity = '1';
      });
  }

  // ── Build pagination HTML ────────────────────────────────────────
  function renderPagination(current, total) {
    if (!paginationNav) return;
    if (total <= 1) { paginationNav.innerHTML = ''; return; }

    let html = '<ul class="pagination justify-content-center flex-wrap gap-1">';

    if (current > 1) {
      html += '<li class="page-item"><button class="page-link" data-page="' + (current - 1) + '">'
            + '<i class="bi bi-chevron-left"></i></button></li>';
    }

    const start = Math.max(1, current - 2);
    const end   = Math.min(total, current + 2);

    if (start > 1) {
      html += '<li class="page-item"><button class="page-link" data-page="1">1</button></li>';
      if (start > 2) html += '<li class="page-item disabled"><span class="page-link">…</span></li>';
    }
    for (let i = start; i <= end; i++) {
      html += '<li class="page-item ' + (i === current ? 'active' : '') + '">'
            + '<button class="page-link" data-page="' + i + '">' + i + '</button></li>';
    }
    if (end < total) {
      if (end < total - 1) html += '<li class="page-item disabled"><span class="page-link">…</span></li>';
      html += '<li class="page-item"><button class="page-link" data-page="' + total + '">' + total + '</button></li>';
    }

    if (current < total) {
      html += '<li class="page-item"><button class="page-link" data-page="' + (current + 1) + '">'
            + '<i class="bi bi-chevron-right"></i></button></li>';
    }

    html += '</ul>';
    paginationNav.innerHTML = html;

    // Attach click handlers to new page buttons
    paginationNav.querySelectorAll('[data-page]').forEach(btn => {
      btn.addEventListener('click', function () {
        fetchResults(parseInt(this.dataset.page, 10));
        window.scrollTo({ top: 0, behavior: 'smooth' });
      });
    });
  }

  // ── Event listeners ──────────────────────────────────────────────
  if (searchBtn) {
    searchBtn.addEventListener('click', () => fetchResults(1));
  }

  if (searchInput) {
    searchInput.addEventListener('keydown', e => {
      if (e.key === 'Enter') { fetchResults(1); return; }
      clearTimeout(debounceTimer);
      debounceTimer = setTimeout(() => fetchResults(1), 450);
    });
  }

  [filterCourse, filterYear, filterType, filterSort].forEach(el => {
    if (el) el.addEventListener('change', () => fetchResults(1));
  });

  // Attach handlers to server-rendered page buttons on initial load
  if (paginationNav) {
    paginationNav.querySelectorAll('[data-page]').forEach(btn => {
      btn.addEventListener('click', function () {
        fetchResults(parseInt(this.dataset.page, 10));
        window.scrollTo({ top: 0, behavior: 'smooth' });
      });
    });
  }

})();
