/**
 * Application JavaScript Core
 * Absensi Surya Gading Mandiri (SGM)
 */

document.addEventListener('DOMContentLoaded', () => {
    // 1. Digital Clock & Realtime Date
    const clockElement = document.getElementById('digital-clock');
    if (clockElement) {
        const updateClock = () => {
            const now = new Date();
            const hours = String(now.getHours()).padStart(2, '0');
            const minutes = String(now.getMinutes()).padStart(2, '0');
            const seconds = String(now.getSeconds()).padStart(2, '0');
            clockElement.textContent = `${hours}:${minutes}:${seconds}`;

            // Optional timestamp overlay for webcam
            const overlayTime = document.getElementById('camera-time-overlay');
            if (overlayTime) {
                const day = now.getDate();
                const months = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
                const month = months[now.getMonth()];
                const year = now.getFullYear();
                overlayTime.textContent = `${day} ${month} ${year} - ${hours}:${minutes}:${seconds}`;
            }
        };
        updateClock();
        setInterval(updateClock, 1000);
    }

    // 2. Dark Mode Toggle
    const themeBtn = document.getElementById('theme-toggle');
    const currentTheme = document.documentElement.getAttribute('data-theme') || localStorage.getItem('sgm_theme') || 'light';

    if (themeBtn) {
        const updateThemeTitle = (theme) => {
            themeBtn.title = (theme === 'dark') ? 'Ganti ke Mode Terang' : 'Ganti ke Mode Gelap';
        };
        updateThemeTitle(currentTheme);

        themeBtn.addEventListener('click', () => {
            document.documentElement.classList.add('theme-switching');
            const current = document.documentElement.getAttribute('data-theme') || 'light';
            const next = (current === 'dark') ? 'light' : 'dark';
            document.documentElement.setAttribute('data-theme', next);
            localStorage.setItem('sgm_theme', next);
            updateThemeTitle(next);

            setTimeout(() => {
                document.documentElement.classList.remove('theme-switching');
            }, 300);
        });
    }

    // 3. Mobile Navigation Toggle
    const navToggle = document.getElementById('mobile-nav-toggle');
    const navBar = document.getElementById('nav-bar');
    if (navToggle && navBar) {
        navToggle.addEventListener('click', () => {
            navBar.classList.toggle('show');
        });
    }

    // 4. Password Visibility Toggle
    const togglePasswordButtons = document.querySelectorAll('.toggle-password-btn');
    togglePasswordButtons.forEach(btn => {
        btn.addEventListener('click', () => {
            const targetId = btn.getAttribute('data-target');
            const input = document.getElementById(targetId);
            if (input) {
                if (input.type === 'password') {
                    input.type = 'text';
                    btn.innerHTML = '👁️‍🗨️';
                } else {
                    input.type = 'password';
                    btn.innerHTML = '👁️';
                }
            }
        });
    });

    // 5. Dropdown Navigation Click & Touch Toggle
    const dropdownToggles = document.querySelectorAll('.dropdown-toggle');
    dropdownToggles.forEach(toggle => {
        toggle.addEventListener('click', (e) => {
            e.preventDefault();
            e.stopPropagation();
            const parent = toggle.closest('.nav-item.dropdown');
            if (parent) {
                const isOpen = parent.classList.contains('show');
                // Close other open dropdowns
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
        });
    });

    // 6. Header Action Dropdowns (Notifications & Profile)
    const notifToggle = document.getElementById('notification-toggle');
    const notifWrapper = document.querySelector('.notification-wrapper');
    if (notifToggle && notifWrapper) {
        notifToggle.addEventListener('click', (e) => {
            e.stopPropagation();
            const isOpen = notifWrapper.classList.contains('show');
            document.querySelectorAll('.dropdown-wrapper.show').forEach(el => el.classList.remove('show'));
            document.querySelectorAll('.nav-item.dropdown.show').forEach(el => el.classList.remove('show'));
            if (!isOpen) {
                notifWrapper.classList.add('show');
            }
        });
    }

    const profileToggle = document.getElementById('profile-toggle');
    const profileWrapper = document.querySelector('.profile-wrapper');
    if (profileToggle && profileWrapper) {
        profileToggle.addEventListener('click', (e) => {
            e.stopPropagation();
            const isOpen = profileWrapper.classList.contains('show');
            document.querySelectorAll('.dropdown-wrapper.show').forEach(el => el.classList.remove('show'));
            document.querySelectorAll('.nav-item.dropdown.show').forEach(el => el.classList.remove('show'));
            if (!isOpen) {
                profileWrapper.classList.add('show');
            }
        });
    }

    // Close dropdowns when clicking outside
    document.addEventListener('click', (e) => {
        if (!e.target.closest('.nav-item.dropdown')) {
            document.querySelectorAll('.nav-item.dropdown.show').forEach(el => el.classList.remove('show'));
        }
        if (!e.target.closest('.dropdown-wrapper')) {
            document.querySelectorAll('.dropdown-wrapper.show').forEach(el => el.classList.remove('show'));
        }
    });

    // 7. Interactive Table Sorting Engine (A-Z, Z-A, Terbaru, dll.)
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
                if (isEmptyA) return 1;  // empty cells always sink to the bottom
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

            const renumberTable = (table) => {
                const tbody = table.querySelector('tbody');
                if (!tbody) return;
                const firstTh = table.querySelector('thead th:first-child');
                if (firstTh && /no/i.test(firstTh.textContent)) {
                    let counter = 1;
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

            const applyTableFilters = (table) => {
                const tbody = table.querySelector('tbody');
                if (!tbody) return;
                const tableId = table.id;
                if (!tableId) return;

                const filterSelects = Array.from(document.querySelectorAll(`.table-filter-select[data-table="#${tableId}"]`));
                const activeFilters = filterSelects
                    .map(sel => {
                        const colKey = sel.dataset.filterCol;
                        const targetTh = table.querySelector(`th[data-col="${colKey}"]`);
                        const colIndex = targetTh ? Array.from(targetTh.parentNode.children).indexOf(targetTh) : -1;
                        const val = (sel.value || '').trim().toLowerCase();
                        return { sel, colKey, colIndex, val };
                    })
                    .filter(f => f.val !== '' && f.val !== 'all' && f.colIndex !== -1);

                const dataRows = Array.from(tbody.rows).filter(r => !r.classList.contains('no-filter-match-row') && !r.querySelector('td[colspan]'));

                let visibleCount = 0;
                dataRows.forEach(row => {
                    let match = true;
                    for (const f of activeFilters) {
                        const cell = row.cells[f.colIndex];
                        if (!cell) {
                            match = false;
                            break;
                        }
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

                    if (match) {
                        row.style.display = '';
                        visibleCount++;
                    } else {
                        row.style.display = 'none';
                    }
                });

                let noMatchRow = tbody.querySelector('.no-filter-match-row');
                if (visibleCount === 0 && dataRows.length > 0) {
                    if (!noMatchRow) {
                        const colCount = table.querySelectorAll('thead th').length || 8;
                        noMatchRow = document.createElement('tr');
                        noMatchRow.className = 'no-filter-match-row';
                        noMatchRow.innerHTML = `
                            <td colspan="${colCount}" style="text-align: center; color: var(--text-muted); padding: 32px 16px;">
                                <div style="font-size: 1.1rem; margin-bottom: 6px;">🔍</div>
                                <div style="font-weight: 600; margin-bottom: 4px;">Tidak ada data yang cocok dengan filter</div>
                                <div style="font-size: 0.82rem; color: var(--text-subtle);">Coba ubah atau kembalikan pilihan filter ke "Semua"</div>
                            </td>
                        `;
                        tbody.appendChild(noMatchRow);
                    }
                    noMatchRow.style.display = '';
                } else if (noMatchRow) {
                    noMatchRow.style.display = 'none';
                }

                renumberTable(table);
            };

            document.querySelectorAll('table.table-custom').forEach(table => {
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

                        // Keep noMatchRow at the bottom if exists
                        const noMatch = tbody.querySelector('.no-filter-match-row');
                        if (noMatch) tbody.appendChild(noMatch);

                        renumberTable(table);

                        // Sync with quick-sort select if present
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

                // Quick sort select dropdown connector
                if (table.id) {
                    const select = document.querySelector(`.table-sort-select[data-table="#${table.id}"]`);
                    if (select) {
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
                                renumberTable(table);
                                return;
                            }

                            const parts = val.split('-');
                            const dir = parts.pop();
                            const colKey = parts.join('-');
                            const targetTh = table.querySelector(`th.sortable-th[data-col="${colKey}"]`);
                            if (targetTh) {
                                // Set direction so that click transitions to desired dir
                                targetTh.dataset.sortDir = (dir === 'asc') ? 'none' : 'asc';
                                targetTh.click();
                            }
                        });
                    }
                }
            });

            // Connect filter selects across all tables
            document.querySelectorAll('.table-filter-select').forEach(select => {
                select.addEventListener('change', () => {
                    const table = document.querySelector(select.dataset.table);
                    if (table) {
                        applyTableFilters(table);
                    }
                });
            });
        };

        initTableSort();
    });