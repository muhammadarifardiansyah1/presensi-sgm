/**
 * Application JavaScript Core
 * Absensi Surya Gemilang Mojopahit (SGM)
 * Enhanced with Hotwire Turbo Drive Lifecycle Support & Event Delegation
 */

(() => {
    // 1. Digital Clock & Realtime Date
    let clockInterval = null;
    const initClock = () => {
        if (clockInterval) {
            clearInterval(clockInterval);
            clockInterval = null;
        }

        const clockElement = document.getElementById('digital-clock');
        const overlayTime = document.getElementById('camera-time-overlay');

        if (!clockElement && !overlayTime) return;

        const updateClock = () => {
            const now = new Date();
            const hours = String(now.getHours()).padStart(2, '0');
            const minutes = String(now.getMinutes()).padStart(2, '0');
            const seconds = String(now.getSeconds()).padStart(2, '0');

            if (clockElement) {
                clockElement.textContent = `${hours}:${minutes}:${seconds}`;
            }

            if (overlayTime) {
                const day = now.getDate();
                const months = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
                const month = months[now.getMonth()];
                const year = now.getFullYear();
                overlayTime.textContent = `${day} ${month} ${year} - ${hours}:${minutes}:${seconds}`;
            }
        };

        updateClock();
        clockInterval = setInterval(updateClock, 1000);
    };

    // 2. Dark Mode State & Titles
    const updateThemeTitle = () => {
        const themeBtn = document.getElementById('theme-toggle');
        if (!themeBtn) return;
        const currentTheme = document.documentElement.getAttribute('data-theme') || localStorage.getItem('sgm_theme') || 'light';
        themeBtn.title = (currentTheme === 'dark') ? 'Ganti ke Mode Terang' : 'Ganti ke Mode Gelap';
    };

    // 3. Global Delegated Click Listeners (Single Registration, Survives Turbo Body Swaps)
    let isDelegationInitialized = false;
    const initDelegatedListeners = () => {
        if (isDelegationInitialized) return;
        isDelegationInitialized = true;

        document.addEventListener('click', (e) => {
            // A. Dark Mode Toggle
            const themeBtn = e.target.closest('#theme-toggle');
            if (themeBtn) {
                e.preventDefault();
                document.documentElement.classList.add('theme-switching');
                const current = document.documentElement.getAttribute('data-theme') || 'light';
                const next = (current === 'dark') ? 'light' : 'dark';
                document.documentElement.setAttribute('data-theme', next);
                localStorage.setItem('sgm_theme', next);
                themeBtn.title = (next === 'dark') ? 'Ganti ke Mode Terang' : 'Ganti ke Mode Gelap';

                setTimeout(() => {
                    document.documentElement.classList.remove('theme-switching');
                }, 300);
                return;
            }

            // B. Mobile Navigation Toggle
            const navToggle = e.target.closest('#mobile-nav-toggle');
            if (navToggle) {
                e.preventDefault();
                const navBar = document.getElementById('nav-bar');
                if (navBar) {
                    navBar.classList.toggle('show');
                }
                return;
            }

            // C. Password Visibility Toggle
            const passBtn = e.target.closest('.toggle-password-btn');
            if (passBtn) {
                e.preventDefault();
                const targetId = passBtn.getAttribute('data-target');
                const input = document.getElementById(targetId);
                if (input) {
                    if (input.type === 'password') {
                        input.type = 'text';
                        passBtn.innerHTML = '👁️‍🗨️';
                    } else {
                        input.type = 'password';
                        passBtn.innerHTML = '👁️';
                    }
                }
                return;
            }

            // D. Navigation Dropdown Toggles
            const dropToggle = e.target.closest('.dropdown-toggle');
            if (dropToggle) {
                e.preventDefault();
                e.stopPropagation();
                const parent = dropToggle.closest('.nav-item.dropdown');
                if (parent) {
                    const isOpen = parent.classList.contains('show');
                    document.querySelectorAll('.nav-item.dropdown.show').forEach(el => {
                        if (el !== parent) el.classList.remove('show');
                    });
                    document.querySelectorAll('.dropdown-wrapper.show').forEach(el => el.classList.remove('show'));
                    if (isOpen) {
                        parent.classList.remove('show');
                    } else {
                        parent.classList.add('show');
                    }
                }
                return;
            }

            // E. Notification Toggle
            const notifToggle = e.target.closest('#notification-toggle');
            if (notifToggle) {
                e.preventDefault();
                e.stopPropagation();
                const notifWrapper = document.querySelector('.notification-wrapper');
                if (notifWrapper) {
                    const isOpen = notifWrapper.classList.contains('show');
                    document.querySelectorAll('.dropdown-wrapper.show').forEach(el => el.classList.remove('show'));
                    document.querySelectorAll('.nav-item.dropdown.show').forEach(el => el.classList.remove('show'));
                    if (!isOpen) {
                        notifWrapper.classList.add('show');
                    }
                }
                return;
            }

            // F. Profile Toggle
            const profileToggle = e.target.closest('#profile-toggle');
            if (profileToggle) {
                e.preventDefault();
                e.stopPropagation();
                const profileWrapper = document.querySelector('.profile-wrapper');
                if (profileWrapper) {
                    const isOpen = profileWrapper.classList.contains('show');
                    document.querySelectorAll('.dropdown-wrapper.show').forEach(el => el.classList.remove('show'));
                    document.querySelectorAll('.nav-item.dropdown.show').forEach(el => el.classList.remove('show'));
                    if (!isOpen) {
                        profileWrapper.classList.add('show');
                    }
                }
                return;
            }

            // F2. Generic Button Dropdowns (e.g. Action Button Groups)
            const genericDropToggle = e.target.closest('.dropdown-toggle-btn');
            if (genericDropToggle) {
                e.preventDefault();
                e.stopPropagation();
                const dropdown = genericDropToggle.closest('.dropdown');
                if (dropdown) {
                    const isOpen = dropdown.classList.contains('show');
                    document.querySelectorAll('.dropdown.show').forEach(d => d.classList.remove('show'));
                    if (!isOpen) dropdown.classList.add('show');
                }
                return;
            }

            // G. Close open dropdowns when clicking outside
            if (!e.target.closest('.nav-item.dropdown')) {
                document.querySelectorAll('.nav-item.dropdown.show').forEach(el => el.classList.remove('show'));
            }
            if (!e.target.closest('.dropdown-wrapper')) {
                document.querySelectorAll('.dropdown-wrapper.show').forEach(el => el.classList.remove('show'));
            }
            if (!e.target.closest('.dropdown')) {
                document.querySelectorAll('.dropdown.show').forEach(el => el.classList.remove('show'));
            }
            if (!e.target.closest('.looker-pill-wrapper')) {
                document.querySelectorAll('.looker-popover.open').forEach(p => p.classList.remove('open'));
                document.querySelectorAll('.looker-filter-pill.is-active').forEach(b => b.classList.remove('is-active'));
            }
        });

        // H. Close Looker popovers on Escape key
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                document.querySelectorAll('.looker-popover.open').forEach(p => p.classList.remove('open'));
                document.querySelectorAll('.looker-filter-pill.is-active').forEach(b => b.classList.remove('is-active'));
            }
        });
    };

    // 4. Interactive Table Sorting Engine (A-Z, Z-A, Terbaru, dll.)
    const initTableSort = () => {
        const sortIconNeutral = `<svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="sort-icon"><path d="m7 15 5 5 5-5"/><path d="m7 9 5-5 5 5"/></svg>`;
        const sortIconAsc = `<svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="sort-icon"><path d="m18 15-6-6-6 6"/></svg>`;
        const sortIconDesc = `<svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="sort-icon"><path d="m6 9 6 6 6-6"/></svg>`;

        const monthMap = {
            'januari': 1, 'februari': 2, 'maret': 3, 'april': 4, 'mei': 5, 'juni': 6,
            'juli': 7, 'agustus': 8, 'september': 9, 'oktober': 10, 'november': 11, 'desember': 12,
            'jan': 1, 'feb': 2, 'mar': 3, 'apr': 4, 'may': 5, 'jun': 6,
            'jul': 7, 'aug': 8, 'sep': 9, 'oct': 10, 'nov': 11, 'dec': 12
        };

        const parseDateVal = (str) => {
            if (!str || str === '-') return -1;
            if (/^\d{4}-\d{2}-\d{2}/.test(str)) {
                return new Date(str).getTime() || -1;
            }
            const parts = str.trim().split(/\s+/);
            if (parts.length >= 3) {
                const day = parseInt(parts[0], 10);
                const month = monthMap[parts[1].toLowerCase()] || 1;
                const year = parseInt(parts[2], 10);
                if (!isNaN(day) && !isNaN(year)) {
                    return new Date(year, month - 1, day).getTime();
                }
            }
            return Date.parse(str) || -1;
        };

        const parseTimeVal = (str) => {
            if (!str || str === '-') return -1;
            const match = str.match(/(\d{1,2}):(\d{2})(?::(\d{2}))?/);
            if (match) {
                return parseInt(match[1], 10) * 3600 + parseInt(match[2], 10) * 60 + (parseInt(match[3] || '0', 10));
            }
            return -1;
        };

        const parseDurationVal = (str) => {
            if (!str || str === '-') return -1;
            if (/tepat\s*waktu/i.test(str)) return 0;
            let totalMins = 0;
            const jamMatch = str.match(/(\d+)\s*jam/i);
            const minMatch = str.match(/(\d+)\s*menit/i);
            if (jamMatch) totalMins += parseInt(jamMatch[1], 10) * 60;
            if (minMatch) totalMins += parseInt(minMatch[1], 10);
            return (jamMatch || minMatch) ? totalMins : -1;
        };

        const getCellSortVal = (cell, type) => {
            if (!cell) return '';
            if (cell.dataset.sortValue !== undefined) {
                return cell.dataset.sortValue;
            }
            const strong = cell.querySelector('strong');
            if (strong) return strong.textContent.trim();
            const badge = cell.querySelector('.badge');
            if (badge) return badge.textContent.trim();
            return cell.textContent.trim();
        };

        const compareValues = (a, b, type, dir) => {
            const isEmptyA = (a === '' || a === '-');
            const isEmptyB = (b === '' || b === '-');
            if (isEmptyA && isEmptyB) return 0;
            if (isEmptyA) return 1;
            if (isEmptyB) return -1;

            let res = 0;
            if (type === 'number') {
                const numA = parseFloat(a.replace(/[^0-9.-]/g, '')) || 0;
                const numB = parseFloat(b.replace(/[^0-9.-]/g, '')) || 0;
                res = numA - numB;
            } else if (type === 'time') {
                res = parseTimeVal(a) - parseTimeVal(b);
            } else if (type === 'date') {
                res = parseDateVal(a) - parseDateVal(b);
            } else if (type === 'duration') {
                res = parseDurationVal(a) - parseDurationVal(b);
            } else {
                res = a.localeCompare(b, 'id', { sensitivity: 'base', numeric: true });
            }
            return dir === 'asc' ? res : -res;
        };

        const generatePaginationPages = (currentPage, totalPages) => {
            if (totalPages <= 7) {
                return Array.from({ length: totalPages }, (_, i) => i + 1);
            }
            if (currentPage <= 4) {
                return [1, 2, 3, 4, 5, '...', totalPages];
            }
            if (currentPage >= totalPages - 3) {
                return [1, '...', totalPages - 4, totalPages - 3, totalPages - 2, totalPages - 1, totalPages];
            }
            return [1, '...', currentPage - 1, currentPage, currentPage + 1, '...', totalPages];
        };

        const renumberTable = (table, startOffset = 1) => {
            const tbody = table.querySelector('tbody');
            if (!tbody) return;
            const firstTh = table.querySelector('thead th:first-child');
            if (firstTh && /no/i.test(firstTh.textContent)) {
                let counter = startOffset;
                Array.from(tbody.rows).forEach((row) => {
                    if (row.classList.contains('no-filter-match-row') || row.querySelector('td[colspan]')) return;
                    if (row.style.display !== 'none') {
                        if (row.cells.length > 0) {
                            row.cells[0].textContent = counter++;
                        }
                    }
                });
            }
        };

        const renderLookerPagination = (table, totalMatched, currentPage, totalPages, totalAll) => {
            const tableId = table.id;
            if (!tableId) return;

            const PAGE_SIZE = 20;
            const startIndex = totalMatched === 0 ? 0 : (currentPage - 1) * PAGE_SIZE + 1;
            const endIndex = Math.min(currentPage * PAGE_SIZE, totalMatched);

            // 1. Update Looker Filter Bar Top Right Toolbar (Looker Studio style "1 - 20 / 444 ⟨ ⟩")
            const filterBars = document.querySelectorAll(`.looker-filter-bar[data-table="#${tableId}"], .looker-filter-bar[data-table="${tableId}"]`);
            filterBars.forEach(bar => {
                const rightBox = bar.querySelector('.looker-filter-bar-right');
                if (!rightBox) return;

                const countIndicator = rightBox.querySelector('.looker-row-count');
                if (countIndicator) {
                    countIndicator.textContent = `${totalMatched} dari ${totalAll} data`;
                }

                let toolbarPagination = rightBox.querySelector('.looker-toolbar-pagination');
                if (!toolbarPagination) {
                    toolbarPagination = document.createElement('div');
                    toolbarPagination.className = 'looker-toolbar-pagination';
                    toolbarPagination.innerHTML = `
                        <span class="looker-nav-range"></span>
                        <button type="button" class="looker-nav-btn looker-nav-prev" title="Halaman Sebelumnya">⟨</button>
                        <button type="button" class="looker-nav-btn looker-nav-next" title="Halaman Berikutnya">⟩</button>
                    `;
                    rightBox.appendChild(toolbarPagination);

                    const prevBtn = toolbarPagination.querySelector('.looker-nav-prev');
                    const nextBtn = toolbarPagination.querySelector('.looker-nav-next');

                    prevBtn.addEventListener('click', (e) => {
                        e.preventDefault();
                        const cur = parseInt(table.dataset.currentPage, 10) || 1;
                        if (cur > 1) {
                            table.dataset.currentPage = cur - 1;
                            applyTableFilters(table);
                        }
                    });

                    nextBtn.addEventListener('click', (e) => {
                        e.preventDefault();
                        const cur = parseInt(table.dataset.currentPage, 10) || 1;
                        const tot = parseInt(table.dataset.totalPages, 10) || 1;
                        if (cur < tot) {
                            table.dataset.currentPage = cur + 1;
                            applyTableFilters(table);
                        }
                    });
                }

                table.dataset.totalPages = totalPages;

                const rangeEl = toolbarPagination.querySelector('.looker-nav-range');
                const prevBtn = toolbarPagination.querySelector('.looker-nav-prev');
                const nextBtn = toolbarPagination.querySelector('.looker-nav-next');

                if (rangeEl) {
                    rangeEl.textContent = totalMatched === 0 ? '0 / 0' : `${startIndex} - ${endIndex} / ${totalMatched}`;
                }
                if (prevBtn) prevBtn.disabled = (currentPage <= 1);
                if (nextBtn) nextBtn.disabled = (currentPage >= totalPages);
            });

            // 2. Update Looker Table Footer (Bottom of Table Card with Numerical Buttons)
            const tableCard = table.closest('.table-card');
            if (tableCard) {
                let footer = tableCard.querySelector('.looker-table-footer');
                if (!footer) {
                    footer = document.createElement('div');
                    footer.className = 'looker-table-footer';
                    footer.innerHTML = `
                        <div class="looker-footer-info"></div>
                        <div class="looker-footer-pagination"></div>
                    `;
                    tableCard.appendChild(footer);
                }

                const infoEl = footer.querySelector('.looker-footer-info');
                const pagesEl = footer.querySelector('.looker-footer-pagination');

                if (infoEl) {
                    if (totalMatched === 0) {
                        infoEl.innerHTML = `Tidak ada data yang ditampilkan`;
                    } else {
                        infoEl.innerHTML = `Menampilkan <strong>${startIndex} - ${endIndex}</strong> dari <strong>${totalMatched}</strong> data`;
                    }
                }

                if (pagesEl) {
                    pagesEl.innerHTML = '';

                    if (totalPages <= 1) {
                        const btn = document.createElement('button');
                        btn.type = 'button';
                        btn.className = 'looker-page-num-btn is-active';
                        btn.textContent = '1';
                        pagesEl.appendChild(btn);
                    } else {
                        // Previous Button
                        const prevBtn = document.createElement('button');
                        prevBtn.type = 'button';
                        prevBtn.className = 'looker-page-num-btn';
                        prevBtn.innerHTML = '⟨';
                        prevBtn.title = 'Halaman Sebelumnya';
                        prevBtn.disabled = (currentPage <= 1);
                        prevBtn.addEventListener('click', () => {
                            if (currentPage > 1) {
                                table.dataset.currentPage = currentPage - 1;
                                applyTableFilters(table);
                            }
                        });
                        pagesEl.appendChild(prevBtn);

                        // Smart Numerical Buttons
                        const pageNumbers = generatePaginationPages(currentPage, totalPages);
                        pageNumbers.forEach(p => {
                            if (p === '...') {
                                const ell = document.createElement('span');
                                ell.className = 'looker-page-num-ellipsis';
                                ell.textContent = '…';
                                pagesEl.appendChild(ell);
                            } else {
                                const btn = document.createElement('button');
                                btn.type = 'button';
                                btn.className = 'looker-page-num-btn' + (p === currentPage ? ' is-active' : '');
                                btn.textContent = p;
                                if (p !== currentPage) {
                                    btn.addEventListener('click', () => {
                                        table.dataset.currentPage = p;
                                        applyTableFilters(table);
                                    });
                                }
                                pagesEl.appendChild(btn);
                            }
                        });

                        // Next Button
                        const nextBtn = document.createElement('button');
                        nextBtn.type = 'button';
                        nextBtn.className = 'looker-page-num-btn';
                        nextBtn.innerHTML = '⟩';
                        nextBtn.title = 'Halaman Berikutnya';
                        nextBtn.disabled = (currentPage >= totalPages);
                        nextBtn.addEventListener('click', () => {
                            if (currentPage < totalPages) {
                                table.dataset.currentPage = currentPage + 1;
                                applyTableFilters(table);
                            }
                        });
                        pagesEl.appendChild(nextBtn);
                    }
                }
            }
        };

        const applyTableFilters = (table) => {
            const tbody = table.querySelector('tbody');
            if (!tbody) return;
            if (!table.id) {
                table.id = 'table_' + Math.random().toString(36).substr(2, 9);
            }
            const tableId = table.id;

            // 1. Global Quick Search Keyword
            const searchInput = document.querySelector(`.looker-search-input[data-table="#${tableId}"]`)
                || document.querySelector(`.looker-filter-bar[data-table="#${tableId}"] .looker-search-input`);
            const searchKeyword = (searchInput ? searchInput.value : '').trim().toLowerCase();

            // 2. Active Looker Pill Filters
            const pillWrappers = Array.from(document.querySelectorAll(`.looker-pill-wrapper[data-table="#${tableId}"]`));
            const activePillFilters = [];

            pillWrappers.forEach(wrap => {
                const colKey = wrap.dataset.filterCol;
                const targetTh = table.querySelector(`th[data-col="${colKey}"]`);
                const colIndex = targetTh ? Array.from(targetTh.parentNode.children).indexOf(targetTh) : -1;
                if (colIndex === -1) return;

                const allCheckboxes = Array.from(wrap.querySelectorAll('.looker-checklist input[type="checkbox"]'));
                if (allCheckboxes.length === 0) return;

                const checkedBoxes = allCheckboxes.filter(cb => cb.checked);
                const totalCount = allCheckboxes.length;
                const checkedCount = checkedBoxes.length;

                // Update UI state for Pill & Popover
                const pillBtn = wrap.querySelector('.looker-filter-pill');
                const badge = wrap.querySelector('.looker-pill-badge');
                const footerStatus = wrap.querySelector('.looker-footer-status');
                const selectAllCb = wrap.querySelector('.looker-select-all-input');

                if (selectAllCb) {
                    selectAllCb.checked = (checkedCount === totalCount);
                    selectAllCb.indeterminate = (checkedCount > 0 && checkedCount < totalCount);
                }

                if (checkedCount === totalCount) {
                    if (badge) badge.style.display = 'none';
                    if (pillBtn) pillBtn.classList.remove('is-filtered');
                    if (footerStatus) footerStatus.textContent = 'Semua dipilih';
                } else {
                    if (badge) {
                        badge.textContent = checkedCount;
                        badge.style.display = 'inline-block';
                    }
                    if (pillBtn) pillBtn.classList.add('is-filtered');
                    if (footerStatus) footerStatus.textContent = `${checkedCount} dipilih`;

                    const allowedVals = checkedBoxes.map(cb => cb.value.trim().toLowerCase());
                    activePillFilters.push({ colKey, colIndex, allowedVals });
                }
            });

            // 3. Legacy Select Filters (backward compatibility)
            const filterSelects = Array.from(document.querySelectorAll(`.table-filter-select[data-table="#${tableId}"]`));
            const activeSelectFilters = filterSelects
                .map(sel => {
                    const colKey = sel.dataset.filterCol;
                    const targetTh = table.querySelector(`th[data-col="${colKey}"]`);
                    const colIndex = targetTh ? Array.from(targetTh.parentNode.children).indexOf(targetTh) : -1;
                    const val = (sel.value || '').trim().toLowerCase();
                    return { colKey, colIndex, val };
                })
                .filter(f => f.val !== '' && f.val !== 'all' && f.colIndex !== -1);

            const dataRows = Array.from(tbody.rows).filter(r => !r.classList.contains('no-filter-match-row') && !r.querySelector('td[colspan]'));

            const matchedRows = [];
            dataRows.forEach(row => {
                let match = true;

                // A. Check Global Search across row cells
                if (searchKeyword) {
                    const rowText = row.textContent.toLowerCase();
                    if (!rowText.includes(searchKeyword)) {
                        match = false;
                    }
                }

                // B. Check Looker Pill Filters (Inter-pill: AND, Intra-pill: OR)
                if (match && activePillFilters.length > 0) {
                    for (const f of activePillFilters) {
                        const cell = row.cells[f.colIndex];
                        if (!cell) { match = false; break; }
                        const cellVal = (cell.dataset.sortValue !== undefined ? cell.dataset.sortValue : cell.textContent).trim().toLowerCase();

                        let pillMatch = false;
                        for (const allowed of f.allowedVals) {
                            if (f.colKey === 'terlambat') {
                                if (allowed === 'tepat' && (cellVal.includes('tepat') || cellVal === '0')) {
                                    pillMatch = true; break;
                                }
                                if (allowed === 'terlambat' && !cellVal.includes('tepat') && cellVal !== '-' && cellVal !== '0') {
                                    pillMatch = true; break;
                                }
                            } else if (cellVal.includes(allowed) || (allowed === '' && cellVal === '')) {
                                pillMatch = true;
                                break;
                            }
                        }
                        if (!pillMatch) {
                            match = false;
                            break;
                        }
                    }
                }

                // C. Check Legacy Select Filters
                if (match && activeSelectFilters.length > 0) {
                    for (const f of activeSelectFilters) {
                        const cell = row.cells[f.colIndex];
                        if (!cell) { match = false; break; }
                        const cellVal = (cell.dataset.sortValue !== undefined ? cell.dataset.sortValue : cell.textContent).trim().toLowerCase();

                        if (f.colKey === 'terlambat') {
                            if (f.val === 'tepat') {
                                if (!cellVal.includes('tepat') && cellVal !== '0') match = false;
                            } else if (f.val === 'terlambat') {
                                if (cellVal.includes('tepat') || cellVal === '-' || cellVal === '0') match = false;
                            }
                        } else {
                            if (!cellVal.includes(f.val)) {
                                match = false;
                            }
                        }
                        if (!match) break;
                    }
                }

                if (match) {
                    matchedRows.push(row);
                } else {
                    row.style.display = 'none';
                }
            });

            // 4. Client-side Pagination Slicing (Max 20 rows per page)
            const PAGE_SIZE = 20;
            const totalMatched = matchedRows.length;
            const totalPages = Math.max(1, Math.ceil(totalMatched / PAGE_SIZE));
            let currentPage = parseInt(table.dataset.currentPage, 10) || 1;
            if (currentPage > totalPages) {
                currentPage = 1;
            }
            table.dataset.currentPage = currentPage;

            const startIndex = (currentPage - 1) * PAGE_SIZE;
            const endIndex = startIndex + PAGE_SIZE;

            matchedRows.forEach((row, idx) => {
                if (idx >= startIndex && idx < endIndex) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });

            let noMatchRow = tbody.querySelector('.no-filter-match-row');
            if (totalMatched === 0 && dataRows.length > 0) {
                if (!noMatchRow) {
                    const colCount = table.querySelectorAll('thead th').length || 8;
                    noMatchRow = document.createElement('tr');
                    noMatchRow.className = 'no-filter-match-row';
                    noMatchRow.innerHTML = `
                        <td colspan="${colCount}" style="text-align: center; color: var(--text-muted); padding: 36px 16px;">
                            <div style="font-size: 1.3rem; margin-bottom: 6px;">🔍</div>
                            <div style="font-weight: 600; margin-bottom: 4px; color: var(--text-main);">Tidak ada data yang cocok dengan filter</div>
                            <div style="font-size: 0.82rem; color: var(--text-muted);">Coba ubah kata kunci pencarian atau reset filter untuk menampilkan semua data.</div>
                        </td>
                    `;
                    tbody.appendChild(noMatchRow);
                }
                noMatchRow.style.display = '';
            } else if (noMatchRow) {
                noMatchRow.style.display = 'none';
            }

            renumberTable(table, totalMatched > 0 ? startIndex + 1 : 1);
            renderLookerPagination(table, totalMatched, currentPage, totalPages, dataRows.length);
        };

        // Initialize table sorting on new tables
        document.querySelectorAll('table.table-custom').forEach(table => {
            const container = table.closest('.table-responsive');
            if (container) container.scrollLeft = 0;

            if (table.dataset.sortInitialized === 'true') return;
            table.dataset.sortInitialized = 'true';

            const tbody = table.querySelector('tbody');
            if (!tbody) return;
            if (tbody.querySelector('td[colspan]')) return;

            Array.from(tbody.rows).forEach((row, i) => {
                row.dataset.origIndex = i;
            });

            const headers = table.querySelectorAll('th.sortable-th');
            if (!headers.length) return;

            headers.forEach(th => {
                const originalHtml = th.innerHTML;
                th.innerHTML = `<span class="sort-th-content"><span>${originalHtml}</span><span class="sort-icon-box">${sortIconNeutral}</span></span>`;
                th.title = 'Klik untuk mengurutkan (A-Z / Terbaru)';

                th.addEventListener('click', () => {
                    const currentDir = th.dataset.sortDir || 'none';
                    let nextDir = 'asc';
                    if (currentDir === 'asc') nextDir = 'desc';
                    else if (currentDir === 'desc') nextDir = 'none';

                    headers.forEach(otherTh => {
                        if (otherTh !== th) {
                            delete otherTh.dataset.sortDir;
                            otherTh.classList.remove('sort-asc', 'sort-desc');
                            const iconBox = otherTh.querySelector('.sort-icon-box');
                            if (iconBox) iconBox.innerHTML = sortIconNeutral;
                            otherTh.title = 'Klik untuk mengurutkan (A-Z / Terbaru)';
                        }
                    });

                    th.dataset.sortDir = nextDir;
                    th.classList.remove('sort-asc', 'sort-desc');
                    const iconBox = th.querySelector('.sort-icon-box');

                    if (nextDir === 'none') {
                        if (iconBox) iconBox.innerHTML = sortIconNeutral;
                        th.title = 'Klik untuk mengurutkan (A-Z / Terbaru)';

                        const sortedRows = Array.from(tbody.rows).sort((a, b) => {
                            return parseInt(a.dataset.origIndex, 10) - parseInt(b.dataset.origIndex, 10);
                        });
                        sortedRows.forEach(r => tbody.appendChild(r));
                    } else {
                        if (nextDir === 'asc') {
                            th.classList.add('sort-asc');
                            if (iconBox) iconBox.innerHTML = sortIconAsc;
                            th.title = 'Klik untuk mengurutkan (Z-A / Terlama)';
                        } else {
                            th.classList.add('sort-desc');
                            if (iconBox) iconBox.innerHTML = sortIconDesc;
                            th.title = 'Klik untuk reset urutan default';
                        }

                        const colIndex = Array.from(th.parentNode.children).indexOf(th);
                        const sortType = th.dataset.sortType || 'text';

                        const sortedRows = Array.from(tbody.rows).sort((rowA, rowB) => {
                            const valA = getCellSortVal(rowA.cells[colIndex], sortType);
                            const valB = getCellSortVal(rowB.cells[colIndex], sortType);
                            return compareValues(valA, valB, sortType, nextDir);
                        });

                        sortedRows.forEach(r => tbody.appendChild(r));
                    }

                    const noMatch = tbody.querySelector('.no-filter-match-row');
                    if (noMatch) tbody.appendChild(noMatch);

                    applyTableFilters(table);

                    if (table.id) {
                        const select = document.querySelector(`.table-sort-select[data-table="#${table.id}"]`);
                        if (select) {
                            const colKey = th.dataset.col;
                            if (nextDir === 'none') {
                                select.value = 'default';
                            } else if (colKey) {
                                select.value = `${colKey}-${nextDir}`;
                            }
                        }
                    }
                });
            });

            if (table.id) {
                const select = document.querySelector(`.table-sort-select[data-table="#${table.id}"]`);
                if (select && !select.dataset.sortSelectBound) {
                    select.dataset.sortSelectBound = 'true';
                    select.addEventListener('change', () => {
                        const val = select.value;
                        if (!val || val === 'default') {
                            headers.forEach(h => {
                                delete h.dataset.sortDir;
                                h.classList.remove('sort-asc', 'sort-desc');
                                const ib = h.querySelector('.sort-icon-box');
                                if (ib) ib.innerHTML = sortIconNeutral;
                            });
                            const sortedRows = Array.from(tbody.rows).sort((a, b) => {
                                return parseInt(a.dataset.origIndex, 10) - parseInt(b.dataset.origIndex, 10);
                            });
                            sortedRows.forEach(r => tbody.appendChild(r));
                            const noMatch = tbody.querySelector('.no-filter-match-row');
                            if (noMatch) tbody.appendChild(noMatch);
                            applyTableFilters(table);
                            return;
                        }

                        const parts = val.split('-');
                        const dir = parts.pop();
                        const colKey = parts.join('-');
                        const targetTh = table.querySelector(`th.sortable-th[data-col="${colKey}"]`);
                        if (targetTh) {
                            targetTh.dataset.sortDir = (dir === 'asc') ? 'none' : 'asc';
                            targetTh.click();
                        }
                    });
                }
            }

            // Initial apply filters & pagination on table registration
            applyTableFilters(table);
        });

        // Connect legacy filter selects across all tables
        document.querySelectorAll('.table-filter-select').forEach(select => {
            if (select.dataset.filterBound === 'true') return;
            select.dataset.filterBound = 'true';
            select.addEventListener('change', () => {
                const table = document.querySelector(select.dataset.table);
                if (table) {
                    table.dataset.currentPage = '1';
                    applyTableFilters(table);
                }
            });
        });

        // 4b. Google Looker Studio Style Horizontal Filter Engine
        const initLookerFilterEngine = () => {
            document.querySelectorAll('.looker-filter-bar').forEach(bar => {
                const tableTarget = bar.dataset.table || bar.querySelector('[data-table]')?.dataset.table;
                if (!tableTarget) return;
                const table = document.querySelector(tableTarget);
                if (!table) return;

                // Quick Search input
                const searchInput = bar.querySelector('.looker-search-input');
                if (searchInput && !searchInput.dataset.bound) {
                    searchInput.dataset.bound = 'true';
                    searchInput.addEventListener('input', () => {
                        table.dataset.currentPage = '1';
                        applyTableFilters(table);
                    });
                }

                // Reset All button
                const resetBtn = bar.querySelector('.looker-reset-all-btn');
                if (resetBtn && !resetBtn.dataset.bound) {
                    resetBtn.dataset.bound = 'true';
                    resetBtn.addEventListener('click', () => {
                        table.dataset.currentPage = '1';
                        if (searchInput) searchInput.value = '';
                        bar.querySelectorAll('.looker-pill-wrapper').forEach(wrap => {
                            wrap.querySelectorAll('.looker-checklist input[type="checkbox"]').forEach(cb => {
                                cb.checked = true;
                            });
                            const selectAll = wrap.querySelector('.looker-select-all-input');
                            if (selectAll) { selectAll.checked = true; selectAll.indeterminate = false; }
                            const badge = wrap.querySelector('.looker-pill-badge');
                            if (badge) badge.style.display = 'none';
                            const pillBtn = wrap.querySelector('.looker-filter-pill');
                            if (pillBtn) pillBtn.classList.remove('is-filtered');
                            const footerStatus = wrap.querySelector('.looker-footer-status');
                            if (footerStatus) footerStatus.textContent = 'Semua dipilih';
                        });
                        const sortSelect = bar.querySelector('.table-sort-select');
                        if (sortSelect) {
                            sortSelect.value = 'default';
                            sortSelect.dispatchEvent(new Event('change'));
                        }
                        applyTableFilters(table);
                    });
                }

                // Filter Pills
                bar.querySelectorAll('.looker-pill-wrapper').forEach(wrap => {
                    const colKey = wrap.dataset.filterCol;
                    if (!colKey) return;
                    const targetTh = table.querySelector(`th[data-col="${colKey}"]`);
                    if (!targetTh) return;
                    const colIndex = Array.from(targetTh.parentNode.children).indexOf(targetTh);
                    if (colIndex === -1) return;

                    const checklist = wrap.querySelector('.looker-checklist');
                    if (!checklist) return;

                    // Auto-Discovery when checklist is empty
                    if (checklist.children.length === 0) {
                        const tbody = table.querySelector('tbody');
                        if (tbody) {
                            const counts = {};
                            const displayNames = {};
                            Array.from(tbody.rows).forEach(row => {
                                if (row.classList.contains('no-filter-match-row') || row.querySelector('td[colspan]')) return;
                                const cell = row.cells[colIndex];
                                if (!cell) return;
                                let rawVal = (cell.dataset.sortValue !== undefined ? cell.dataset.sortValue : cell.textContent).trim();
                                let cleanVal = rawVal;
                                if (colKey === 'terlambat') {
                                    if (rawVal.toLowerCase().includes('tepat') || rawVal === '0') {
                                        cleanVal = 'Tepat Waktu';
                                        rawVal = 'tepat';
                                    } else if (rawVal !== '-' && rawVal !== '') {
                                        cleanVal = 'Terlambat';
                                        rawVal = 'terlambat';
                                    }
                                }
                                if (cleanVal && cleanVal !== '-') {
                                    const valKey = rawVal.toLowerCase();
                                    counts[valKey] = (counts[valKey] || 0) + 1;
                                    if (!displayNames[valKey]) displayNames[valKey] = cleanVal;
                                }
                            });

                            const sortedKeys = Object.keys(counts).sort((a, b) => displayNames[a].localeCompare(displayNames[b], 'id'));
                            checklist.innerHTML = sortedKeys.map(k => `
                                <label class="looker-check-item">
                                    <span class="looker-check-left">
                                        <input type="checkbox" value="${k}" checked>
                                        <span class="looker-check-label">${displayNames[k]}</span>
                                    </span>
                                    <span class="looker-check-count">${counts[k]}</span>
                                </label>
                            `).join('');
                        }
                    } else {
                        // Pre-rendered items: calculate count dynamically from table
                        const tbody = table.querySelector('tbody');
                        if (tbody) {
                            const counts = {};
                            Array.from(tbody.rows).forEach(row => {
                                if (row.classList.contains('no-filter-match-row') || row.querySelector('td[colspan]')) return;
                                const cell = row.cells[colIndex];
                                if (!cell) return;
                                const rawVal = (cell.dataset.sortValue !== undefined ? cell.dataset.sortValue : cell.textContent).trim().toLowerCase();
                                counts[rawVal] = (counts[rawVal] || 0) + 1;
                            });
                            checklist.querySelectorAll('.looker-check-item').forEach(item => {
                                const cb = item.querySelector('input[type="checkbox"]');
                                if (!cb) return;
                                const val = cb.value.trim().toLowerCase();
                                let c = 0;
                                if (counts[val] !== undefined) {
                                    c = counts[val];
                                } else {
                                    Object.keys(counts).forEach(k => {
                                        if (k.includes(val) || val.includes(k)) c += counts[k];
                                    });
                                }
                                let countEl = item.querySelector('.looker-check-count');
                                if (!countEl) {
                                    countEl = document.createElement('span');
                                    countEl.className = 'looker-check-count';
                                    item.appendChild(countEl);
                                }
                                countEl.textContent = c;
                            });
                        }
                    }

                    // Pill Button Click Toggle
                    const pillBtn = wrap.querySelector('.looker-filter-pill');
                    const popover = wrap.querySelector('.looker-popover');
                    if (pillBtn && popover && !pillBtn.dataset.bound) {
                        pillBtn.dataset.bound = 'true';
                        pillBtn.addEventListener('click', (e) => {
                            e.stopPropagation();
                            const isOpen = popover.classList.contains('open');
                            document.querySelectorAll('.looker-popover.open').forEach(p => {
                                if (p !== popover) {
                                    p.classList.remove('open');
                                    p.closest('.looker-pill-wrapper')?.querySelector('.looker-filter-pill')?.classList.remove('is-active');
                                }
                            });
                            if (isOpen) {
                                popover.classList.remove('open');
                                pillBtn.classList.remove('is-active');
                            } else {
                                popover.classList.add('open');
                                pillBtn.classList.add('is-active');
                                const searchBox = popover.querySelector('.looker-popover-input');
                                if (searchBox) {
                                    searchBox.value = '';
                                    checklist.querySelectorAll('.looker-check-item').forEach(ci => ci.style.display = '');
                                    setTimeout(() => searchBox.focus(), 60);
                                }
                            }
                        });
                    }

                    // Popover internal search
                    const popoverSearch = wrap.querySelector('.looker-popover-input');
                    if (popoverSearch && !popoverSearch.dataset.bound) {
                        popoverSearch.dataset.bound = 'true';
                        popoverSearch.addEventListener('input', () => {
                            const term = popoverSearch.value.trim().toLowerCase();
                            checklist.querySelectorAll('.looker-check-item').forEach(ci => {
                                const lbl = ci.querySelector('.looker-check-label')?.textContent.toLowerCase() || '';
                                ci.style.display = lbl.includes(term) ? '' : 'none';
                            });
                        });
                    }

                    // Select All Checkbox
                    const selectAll = wrap.querySelector('.looker-select-all-input');
                    if (selectAll && !selectAll.dataset.bound) {
                        selectAll.dataset.bound = 'true';
                        selectAll.addEventListener('change', () => {
                            table.dataset.currentPage = '1';
                            const isChecked = selectAll.checked;
                            checklist.querySelectorAll('.looker-check-item input[type="checkbox"]').forEach(cb => {
                                cb.checked = isChecked;
                            });
                            applyTableFilters(table);
                        });
                    }

                    // Individual Checkboxes
                    checklist.querySelectorAll('input[type="checkbox"]').forEach(cb => {
                        if (cb.dataset.bound) return;
                        cb.dataset.bound = 'true';
                        cb.addEventListener('change', () => {
                            table.dataset.currentPage = '1';
                            applyTableFilters(table);
                        });
                    });

                    // Clear / Reset Button
                    const clearBtn = wrap.querySelector('.looker-clear-btn');
                    if (clearBtn && !clearBtn.dataset.bound) {
                        clearBtn.dataset.bound = 'true';
                        clearBtn.addEventListener('click', (e) => {
                            e.stopPropagation();
                            table.dataset.currentPage = '1';
                            checklist.querySelectorAll('input[type="checkbox"]').forEach(cb => cb.checked = true);
                            if (selectAll) { selectAll.checked = true; selectAll.indeterminate = false; }
                            applyTableFilters(table);
                        });
                    }
                });

                // Initial count & pagination
                applyTableFilters(table);
            });
        };

        initLookerFilterEngine();
        document.querySelectorAll('table.table-custom').forEach(t => applyTableFilters(t));
    };

    // 5. SGM In-App Dialog & Toast Notification System
    const dialogSVGs = {
        danger: `<svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18"></path><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"></path><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"></path><line x1="10" y1="11" x2="10" y2="17"></line><line x1="14" y1="11" x2="14" y2="17"></line></svg>`,
        warning: `<svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>`,
        success: `<svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>`,
        info: `<svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="16" x2="12" y2="12"></line><line x1="12" y1="8" x2="12.01" y2="8"></line></svg>`,
        logout: `<svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><polyline points="16 17 21 12 16 7"></polyline><line x1="21" y1="12" x2="9" y2="12"></line></svg>`
    };

    let activeConfirmResolver = null;

    window.SGMDialog = {
        confirm({
            title = 'Konfirmasi Tindakan',
            message = 'Apakah Anda yakin ingin melanjutkan tindakan ini?',
            type = 'danger',
            confirmText = 'Lanjutkan',
            cancelText = 'Batal',
            onConfirm = null
        } = {}) {
            return new Promise((resolve) => {
                const modal = document.getElementById('globalConfirmModal');
                if (!modal) {
                    const fallback = window.confirm(message);
                    if (fallback && typeof onConfirm === 'function') onConfirm();
                    resolve(fallback);
                    return;
                }

                const iconBox = document.getElementById('globalConfirmIcon');
                const titleEl = document.getElementById('globalConfirmTitle');
                const descEl = document.getElementById('globalConfirmDesc');
                const btnOk = document.getElementById('globalConfirmBtnOk');
                const btnCancel = document.getElementById('globalConfirmBtnCancel');

                if (titleEl) titleEl.textContent = title;
                if (descEl) descEl.innerHTML = message;

                if (iconBox) {
                    iconBox.className = type === 'warning' ? 'modal-icon-warning' 
                        : (type === 'success' || type === 'primary' ? 'modal-icon-success' 
                        : (type === 'info' ? 'modal-icon-info' : 'modal-icon-danger'));
                    iconBox.innerHTML = dialogSVGs[type] || dialogSVGs.danger;
                }

                if (btnOk) {
                    btnOk.className = 'btn ' + (type === 'warning' ? 'btn-warning' 
                        : (type === 'success' || type === 'primary' ? 'btn-primary' 
                        : (type === 'info' ? 'btn-blue' : 'btn-danger')));
                    btnOk.textContent = confirmText;
                }

                if (btnCancel) {
                    btnCancel.textContent = cancelText;
                }

                const closeModal = () => {
                    modal.classList.remove('show');
                    document.body.style.overflow = '';
                    activeConfirmResolver = null;
                };

                activeConfirmResolver = () => {
                    closeModal();
                    if (typeof onConfirm === 'function') {
                        onConfirm();
                    }
                    resolve(true);
                };

                btnCancel.onclick = () => {
                    closeModal();
                    resolve(false);
                };

                modal.onclick = (e) => {
                    if (e.target === modal) {
                        closeModal();
                        resolve(false);
                    }
                };

                const escHandler = (e) => {
                    if (e.key === 'Escape') {
                        document.removeEventListener('keydown', escHandler);
                        closeModal();
                        resolve(false);
                    }
                };
                document.addEventListener('keydown', escHandler, { once: true });

                modal.classList.add('show');
                document.body.style.overflow = 'hidden';
            });
        },

        toast(message, type = 'info', duration = 3500) {
            const container = document.getElementById('sgmToastContainer');
            if (!container) {
                console.log(`[Toast ${type}]: ${message}`);
                return;
            }

            const toast = document.createElement('div');
            toast.className = `sgm-toast sgm-toast-${type}`;
            
            const iconMap = {
                success: '✅',
                danger: '⚠️',
                warning: '🔔',
                info: 'ℹ️'
            };

            toast.innerHTML = `
                <div class="sgm-toast-icon">${iconMap[type] || 'ℹ️'}</div>
                <div class="sgm-toast-msg">${message}</div>
                <button type="button" class="sgm-toast-close" aria-label="Tutup">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
                </button>
            `;

            container.appendChild(toast);
            requestAnimationFrame(() => toast.classList.add('show'));

            let dismissed = false;
            const dismiss = () => {
                if (dismissed) return;
                dismissed = true;
                toast.classList.remove('show');
                setTimeout(() => toast.remove(), 300);
            };

            const closeBtn = toast.querySelector('.sgm-toast-close');
            if (closeBtn) closeBtn.onclick = dismiss;

            if (duration > 0) {
                setTimeout(dismiss, duration);
            }
        }
    };

    // 6. Global Camera Hardware Lifecycle Manager (Anti-Leak Guarantee)
    const activeCameraStreams = new Set();
    window.SGMCamera = {
        register(stream) {
            if (stream && typeof stream.getTracks === 'function') {
                activeCameraStreams.add(stream);
            }
        },

        unregister(stream) {
            if (stream) {
                activeCameraStreams.delete(stream);
            }
        },

        stopAll() {
            // 1. Matikan seluruh track yang terdaftar di registry
            activeCameraStreams.forEach(stream => {
                try {
                    if (stream && stream.getTracks) {
                        stream.getTracks().forEach(track => {
                            try {
                                track.enabled = false;
                                track.stop();
                            } catch (e) {}
                        });
                    }
                } catch (e) {}
            });
            activeCameraStreams.clear();

            // 2. Scan dan matikan seluruh video elements yang mungkin masih memiliki srcObject
            try {
                document.querySelectorAll('video').forEach(video => {
                    try {
                        if (video.srcObject) {
                            const stream = video.srcObject;
                            if (stream && stream.getTracks) {
                                stream.getTracks().forEach(track => {
                                    try {
                                        track.enabled = false;
                                        track.stop();
                                    } catch (e) {}
                                });
                            }
                            video.pause();
                            video.srcObject = null;
                        }
                    } catch (e) {}
                });
            } catch (e) {}
        }
    };

    // Hook pengamanan otomatis saat navigasi antarhalaman atau penutupan tab
    const handleGlobalNavigationTeardown = () => {
        window.SGMCamera.stopAll();
    };

    document.addEventListener('turbo:before-visit', handleGlobalNavigationTeardown);
    document.addEventListener('turbo:before-render', handleGlobalNavigationTeardown);
    document.addEventListener('turbo:visit', handleGlobalNavigationTeardown);
    window.addEventListener('popstate', handleGlobalNavigationTeardown);
    window.addEventListener('pagehide', handleGlobalNavigationTeardown);
    window.addEventListener('beforeunload', handleGlobalNavigationTeardown);

    // 7. Cleanup Before Turbo Caching Snapshot
    document.addEventListener('turbo:before-cache', () => {
        // Hentikan hardware kamera sebelum snapshot DOM disimpan ke cache
        window.SGMCamera.stopAll();

        // Close open dropdowns and nav drawers before page snapshot
        document.querySelectorAll('.nav-item.dropdown.show, .dropdown-wrapper.show, .dropdown.show').forEach(el => el.classList.remove('show'));
        const navBar = document.getElementById('nav-bar');
        if (navBar) navBar.classList.remove('show');

        // Stop digital clock timer while navigating
        if (clockInterval) {
            clearInterval(clockInterval);
            clockInterval = null;
        }

        // Close ALL modals and restore body scrolling before taking snapshot
        document.querySelectorAll('.modal.show, .modal[style*="display: flex"]').forEach(m => {
            m.classList.remove('show');
            if (m.style.display === 'flex') m.style.display = 'none';
        });
        document.body.style.overflow = '';

        // Close global modal if open
        const globalModal = document.getElementById('globalConfirmModal');
        if (globalModal) globalModal.classList.remove('show');

        // Bersihkan flash alert sementara agar tidak muncul kembali sebagai ghost snapshot
        document.querySelectorAll('.alert-auto-dismiss, [data-turbo-temporary]').forEach(el => el.remove());
    });

    // Anti-Double Submit / Rage-Click Protection for Turbo Forms
    document.addEventListener('turbo:submit-start', (e) => {
        const form = e.target;
        const submitBtn = form.querySelector('button[type="submit"], input[type="submit"]');
        if (submitBtn && !submitBtn.disabled) {
            submitBtn.dataset.originalHtml = submitBtn.innerHTML;
            submitBtn.disabled = true;
            submitBtn.style.opacity = '0.75';
            submitBtn.style.cursor = 'wait';
        }
    });

    document.addEventListener('turbo:submit-end', (e) => {
        const form = e.target;
        const submitBtn = form.querySelector('button[type="submit"], input[type="submit"]');
        if (submitBtn && submitBtn.dataset.originalHtml) {
            submitBtn.disabled = false;
            submitBtn.style.opacity = '';
            submitBtn.style.cursor = '';
        }
    });

    // Delegated click listener for data-confirm attributes
    document.addEventListener('click', (e) => {
        // Ok button on global confirm modal
        if (e.target.closest('#globalConfirmBtnOk')) {
            if (typeof activeConfirmResolver === 'function') activeConfirmResolver();
            return;
        }

        const trigger = e.target.closest('[data-confirm]');
        if (trigger) {
            e.preventDefault();
            const message = trigger.getAttribute('data-confirm');
            const title = trigger.getAttribute('data-confirm-title') || 'Konfirmasi Tindakan';
            const type = trigger.getAttribute('data-confirm-type') || 'danger';
            const confirmText = trigger.getAttribute('data-confirm-btn') || 'Ya, Lanjutkan';

            window.SGMDialog.confirm({
                title,
                message,
                type,
                confirmText,
                onConfirm: () => {
                    if (trigger.tagName === 'A') {
                        const href = trigger.getAttribute('href');
                        if (href && href !== '#' && !href.startsWith('javascript:')) {
                            window.location.href = href;
                        }
                    } else if (trigger.tagName === 'BUTTON' && trigger.type === 'submit') {
                        trigger.closest('form')?.submit();
                    }
                }
            });
        }
    });

    // 8. Master Lifecycle Initializer (Turbo + Standard Fallback)
    const initApp = () => {
        initDelegatedListeners();
        initClock();
        updateThemeTitle();
        initTableSort();

        // Bersihkan snapshot cache Turbo jika halaman memuat status mutasi data (?msg= atau ?err=)
        // Mencegah human error (kebingungan user melihat data lama saat menekan tombol Back)
        if (window.location.search.includes('msg=') || window.location.search.includes('err=')) {
            if (window.Turbo && window.Turbo.cache && typeof window.Turbo.cache.clear === 'function') {
                window.Turbo.cache.clear();
            }
        }

        // Fail-safe: jika pengguna berada di halaman non-kamera, pastikan tidak ada sisa camera stream
        if (!document.getElementById('webcam-video')) {
            window.SGMCamera.stopAll();
        }
    };

    document.addEventListener('turbo:load', initApp);

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initApp);
    } else {
        initApp();
    }
})();