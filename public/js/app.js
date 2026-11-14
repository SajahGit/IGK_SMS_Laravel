(function () {
    // ---- theme toggle (dark / light), remembered in the browser ----
    var root = document.documentElement;
    function setTheme(t) {
        root.setAttribute('data-bs-theme', t);
        try { localStorage.setItem('igk-theme', t); } catch (e) {}
        document.querySelectorAll('[data-theme-icon]').forEach(function (i) {
            i.className = t === 'dark' ? 'bi bi-sun' : 'bi bi-moon-stars';
        });
    }
    document.querySelectorAll('[data-theme-toggle]').forEach(function (b) {
        b.addEventListener('click', function () {
            setTheme(root.getAttribute('data-bs-theme') === 'dark' ? 'light' : 'dark');
        });
    });
    setTheme(root.getAttribute('data-bs-theme') || 'dark');

    function refreshLiveDates() {
        document.querySelectorAll('[data-live-date]').forEach(function (element) {
            var options = { weekday: 'short', day: '2-digit', month: 'short', year: 'numeric' };
            var timezone = element.getAttribute('data-timezone');
            if (timezone) options.timeZone = timezone;
            element.textContent = new Intl.DateTimeFormat('en-GB', options).format(new Date());
        });
    }
    refreshLiveDates();
    window.setInterval(refreshLiveDates, 60_000);
    document.addEventListener('visibilitychange', function () {
        if (!document.hidden) refreshLiveDates();
    });

    // GET-based list and report filters submit after a short pause while typing.
    document.querySelectorAll('form[method="GET" i]:not([data-no-auto-filter])').forEach(function (form) {
        var timer;
        form.querySelectorAll('input:not([type="hidden"]):not([type="submit"]):not([type="button"]), select, textarea')
            .forEach(function (field) {
                if (field.matches('[data-no-auto-filter]')) return;
                var submitFilter = function () {
                    window.clearTimeout(timer);
                    timer = window.setTimeout(function () {
                        if (form.reportValidity()) form.requestSubmit();
                    }, field.matches('select, input[type="checkbox"], input[type="radio"], input[type="date"]') ? 0 : 450);
                };
                field.addEventListener('input', submitFilter);
                field.addEventListener('change', submitFilter);
            });
    });

    // ---- mobile sidebar ----
    var sb = document.getElementById('sidebar'), bd = document.getElementById('sbBackdrop');
    var navScroll = sb && sb.querySelector('.nav-scroll');
    var sidebarScrollKey = 'igk-sidebar-scroll';
    function saveSidebarScroll() {
        if (!navScroll) return;
        try { window.sessionStorage.setItem(sidebarScrollKey, String(navScroll.scrollTop)); } catch (e) {}
    }
    if (navScroll) {
        try {
            var savedSidebarScroll = Number(window.sessionStorage.getItem(sidebarScrollKey));
            if (Number.isFinite(savedSidebarScroll) && savedSidebarScroll >= 0) {
                navScroll.scrollTop = savedSidebarScroll;
            }
        } catch (e) {}
        navScroll.addEventListener('scroll', saveSidebarScroll);
        sb.querySelectorAll('.nav-glass').forEach(function (link) {
            link.addEventListener('click', saveSidebarScroll);
        });
    }
    function toggle(open) {
        if (!sb) return;
        sb.classList.toggle('open', open);
        if (bd) bd.classList.toggle('show', open);
    }
    document.querySelectorAll('[data-sidebar-toggle]').forEach(function (b) {
        b.addEventListener('click', function () { toggle(!sb.classList.contains('open')); });
    });
    if (bd) bd.addEventListener('click', function () { toggle(false); });

    function configurePhoneLength(picker, lengths, nationalPrefix) {
        var input = picker.querySelector('[data-phone-number]');
        var help = picker.parentElement.querySelector('[data-phone-length-help]');
        if (!input) return;

        var allowedLengths = String(lengths || '')
            .split(',')
            .map(Number)
            .filter(function (length) { return Number.isInteger(length) && length > 0; });
        if (allowedLengths.length === 0) return;

        var maximum = Math.max.apply(null, allowedLengths);
        var prefix = nationalPrefix === '0' ? '0' : '';
        input.dataset.phoneLengths = allowedLengths.join(',');
        input.dataset.phoneMaxLength = String(maximum);
        input.dataset.nationalPrefix = prefix;
        input.maxLength = maximum + prefix.length;
        if (help) {
            help.textContent = 'Valid national number length' + (allowedLengths.length === 1 ? '' : 's')
                + ': ' + allowedLengths.join(', ') + ' digits.'
                + (prefix ? ' A leading ' + prefix + ' is removed when entered.' : '');
        }
        validatePhoneLength(input, allowedLengths, prefix);
    }

    function validatePhoneLength(input, configuredLengths, configuredPrefix) {
        var lengths = configuredLengths || String(input.dataset.phoneLengths || '')
            .split(',')
            .map(Number)
            .filter(function (length) { return Number.isInteger(length) && length > 0; });
        var prefix = configuredPrefix || input.dataset.nationalPrefix || '';
        var maximum = lengths.length ? Math.max.apply(null, lengths) : 0;
        var value = input.value;

        if (prefix && value.length === maximum + 1 && value.charAt(0) === prefix
            && lengths.indexOf(value.length - 1) !== -1) {
            input.value = value.slice(1);
            value = input.value;
        }

        if (value === '') {
            input.setCustomValidity('');
            return;
        }

        if (lengths.indexOf(value.length) === -1) {
            input.setCustomValidity('Enter a phone number with a valid length for the selected country: '
                + lengths.join(', ') + ' digits.');
        } else {
            input.setCustomValidity('');
        }
    }

    // Keep person-name fields limited to international letters and spaces.
    document.addEventListener('input', function (event) {
        var input = event.target;
        if (input.matches('[data-phone-number]')) {
            var digits = input.value.replace(/[^0-9]/g, '');
            if (digits !== input.value) {
                var phoneCursor = input.selectionStart;
                var removedBeforeCursor = input.value.slice(0, phoneCursor).replace(/[0-9]/g, '').length;
                input.value = digits;
                input.setSelectionRange(Math.max(0, phoneCursor - removedBeforeCursor), Math.max(0, phoneCursor - removedBeforeCursor));
            }
            validatePhoneLength(input);
            return;
        }
        if (!input.matches('[data-person-name]')) return;

        var value = Array.from(input.value)
            .filter(function (character) { return /[\p{L}\p{M} .'-]/u.test(character); })
            .join('');
        if (value.length > 100) value = value.slice(0, 100);
        if (value !== input.value) {
            var cursor = input.selectionStart;
            input.value = value;
            input.setSelectionRange(Math.min(cursor, value.length), Math.min(cursor, value.length));
        }
    });

    document.addEventListener('paste', function (event) {
        var input = event.target;
        if (!input.matches('[data-phone-number]')) return;

        var clipboard = event.clipboardData || window.clipboardData;
        if (!clipboard) return;
        event.preventDefault();
        var pasted = clipboard.getData('text').replace(/[^0-9]/g, '');
        var start = input.selectionStart;
        var end = input.selectionEnd;
        var value = input.value.slice(0, start) + pasted + input.value.slice(end);
        input.value = value;
        var cursor = start + pasted.length;
        if (input.dataset.nationalPrefix === '0'
            && value.length === Number(input.dataset.phoneMaxLength) + 1
            && value.charAt(0) === '0'
            && input.dataset.phoneLengths.split(',').indexOf(String(value.length - 1)) !== -1) {
            input.value = value.slice(1);
            cursor = Math.max(0, cursor - (start === 0 ? 1 : 0));
        }
        input.setSelectionRange(Math.min(cursor, input.value.length), Math.min(cursor, input.value.length));
        validatePhoneLength(input);
    });

    document.addEventListener('click', function (event) {
        var countryOption = event.target.closest('[data-country-option]');
        if (countryOption) {
            var countryPicker = countryOption.closest('[data-international-country]');
            var countrySelect = countryPicker.querySelector('[data-country-value]');
            var countryToggle = countryPicker.querySelector('[data-country-toggle]');
            countrySelect.value = countryOption.dataset.region;
            countrySelect.dispatchEvent(new Event('change', { bubbles: true }));
            var countryFlag = countryPicker.querySelector('[data-country-flag]');
            countryFlag.className = 'phone-flag fi fi-' + countryOption.dataset.region.toLowerCase();
            countryPicker.querySelector('[data-country-name]').textContent = countryOption.dataset.name;
            countryToggle.setAttribute('aria-label', 'Issuing country: ' + countryOption.dataset.name);
            countryToggle.removeAttribute('aria-invalid');
            countryToggle.classList.remove('is-invalid');
            var countryError = countryPicker.parentElement.querySelector('[data-country-client-error]');
            if (countryError) countryError.remove();
            countryPicker.querySelectorAll('[data-country-option]').forEach(function (item) {
                item.setAttribute('aria-selected', item === countryOption ? 'true' : 'false');
            });
            if (window.bootstrap && window.bootstrap.Dropdown) {
                window.bootstrap.Dropdown.getOrCreateInstance(countryToggle).hide();
            }
            return;
        }

        var option = event.target.closest('[data-phone-option]');
        if (!option) return;

        var picker = option.closest('[data-international-phone]');
        var select = picker.querySelector('[data-phone-region]');
        var toggle = picker.querySelector('[data-phone-toggle]');
        select.value = option.dataset.region;
        select.dispatchEvent(new Event('change', { bubbles: true }));
        var selectedFlag = picker.querySelector('[data-phone-selected-flag]');
        selectedFlag.className = 'phone-flag fi fi-' + option.dataset.region.toLowerCase();
        picker.querySelector('[data-phone-selected-code]').textContent = '+' + option.dataset.callingCode;
        toggle.dataset.phoneLengths = option.dataset.phoneLengths;
        toggle.dataset.nationalPrefix = option.dataset.nationalPrefix;
        configurePhoneLength(picker, option.dataset.phoneLengths, option.dataset.nationalPrefix);
        picker.querySelectorAll('[data-phone-option]').forEach(function (item) {
            item.setAttribute('aria-selected', item === option ? 'true' : 'false');
        });

        toggle.setAttribute('aria-label', 'Country calling code, ' + option.dataset.region + ', +' + option.dataset.callingCode);
        if (window.bootstrap && window.bootstrap.Dropdown) {
            window.bootstrap.Dropdown.getOrCreateInstance(toggle).hide();
        }
    });

    document.querySelectorAll('[data-guardian-selector]').forEach(function (selector) {
        var search = selector.querySelector('[data-guardian-search]');
        var options = Array.from(selector.querySelectorAll('[data-guardian-option]'));
        var multiple = selector.dataset.multiple === 'true';
        var updateSelection = function () {
            var checked = options.filter(function (option) { return option.querySelector('input').checked; });
            var count = selector.querySelector('[data-guardian-count]');
            if (count) count.textContent = multiple ? checked.length + ' selected' : (checked[0] ? checked[0].dataset.name : 'No guardian linked');
            if (multiple && search) {
                search.setCustomValidity(checked.length === 0 ? 'Choose at least one guardian.' : '');
            }
        };

        if (search) {
            search.addEventListener('input', function () {
                var query = search.value.trim().toLocaleLowerCase();
                options.forEach(function (option) {
                    option.hidden = query !== '' && !option.dataset.search.includes(query);
                });
            });
        }

        options.forEach(function (option) {
            option.querySelector('input').addEventListener('change', updateSelection);
        });
        updateSelection();
    });

    document.addEventListener('submit', function (event) {
        var form = event.target;
        var picker = form.querySelector('[data-international-country]');
        if (!picker) return;

        var select = picker.querySelector('[data-country-value]');
        if (select.value !== '') return;

        event.preventDefault();
        var toggle = picker.querySelector('[data-country-toggle]');
        toggle.classList.add('is-invalid');
        toggle.setAttribute('aria-invalid', 'true');
        toggle.focus();
        var error = picker.parentElement.querySelector('[data-country-client-error]');
        if (!error) {
            error = document.createElement('div');
            error.className = 'invalid-feedback d-block';
            error.dataset.countryClientError = 'true';
            error.textContent = 'Select the country that issued this document.';
            picker.insertAdjacentElement('afterend', error);
        }
    });

    document.querySelectorAll('[data-international-phone]').forEach(function (picker) {
        var toggle = picker.querySelector('[data-phone-toggle]');
        var input = picker.querySelector('[data-phone-number]');
        input.dataset.phoneLengths = toggle.dataset.phoneLengths;
        configurePhoneLength(picker, toggle.dataset.phoneLengths, toggle.dataset.nationalPrefix);
    });

    document.querySelectorAll('[data-international-country]').forEach(function (picker) {
        var search = picker.querySelector('[data-country-search]');
        if (!search) return;
        search.addEventListener('input', function () {
            var query = search.value.trim().toLocaleLowerCase();
            picker.querySelectorAll('[data-country-option]').forEach(function (option) {
                var name = (option.dataset.name || '').toLocaleLowerCase();
                var code = (option.dataset.region || '').toLocaleLowerCase();
                option.hidden = query !== '' && !name.includes(query) && !code.includes(query);
            });
        });
        picker.querySelector('[data-country-toggle]').addEventListener('shown.bs.dropdown', function () {
            search.value = '';
            search.dispatchEvent(new Event('input'));
            search.focus();
        });
    });

    document.querySelectorAll('[data-international-phone]').forEach(function (picker) {
        var search = picker.querySelector('[data-phone-search]');
        var toggle = picker.querySelector('[data-phone-toggle]');
        if (!search || !toggle) return;

        search.addEventListener('input', function () {
            var query = search.value.trim().toLocaleLowerCase();
            picker.querySelectorAll('[data-phone-option]').forEach(function (option) {
                option.hidden = query !== '' && !option.dataset.search.includes(query);
            });
        });
        toggle.addEventListener('shown.bs.dropdown', function () {
            search.value = '';
            search.dispatchEvent(new Event('input'));
            search.focus();
        });
    });

    document.querySelectorAll('[data-nationality-picker]').forEach(function (picker) {
        var search = picker.querySelector('[data-nationality-search]');
        if (search) {
            search.addEventListener('input', function () {
                var query = search.value.trim().toLocaleLowerCase();
                picker.querySelectorAll('[data-nationality-option]').forEach(function (option) {
                    option.hidden = query !== '' && !option.dataset.value.toLocaleLowerCase().includes(query);
                });
            });
            picker.querySelector('[data-nationality-toggle]').addEventListener('shown.bs.dropdown', function () {
                search.value = '';
                search.dispatchEvent(new Event('input'));
                search.focus();
            });
        }
    });

    document.addEventListener('click', function (event) {
        var option = event.target.closest('[data-nationality-option]');
        if (!option) return;

        var picker = option.closest('[data-nationality-picker]');
        picker.querySelector('[data-nationality-value]').value = option.dataset.value;
        picker.querySelector('[data-nationality-value]').dispatchEvent(new Event('change', { bubbles: true }));
        picker.querySelector('[data-nationality-flag]').className = 'phone-flag fi fi-' + option.dataset.flag;
        picker.querySelector('[data-nationality-label]').textContent = option.dataset.value;
        var toggle = picker.querySelector('[data-nationality-toggle]');
        toggle.setAttribute('aria-label', 'Nationality: ' + option.dataset.value);
        toggle.classList.remove('is-invalid');
        picker.querySelectorAll('[data-nationality-option]').forEach(function (item) {
            item.setAttribute('aria-selected', item === option ? 'true' : 'false');
        });
        if (window.bootstrap && window.bootstrap.Dropdown) {
            window.bootstrap.Dropdown.getOrCreateInstance(toggle).hide();
        }
    });

    var nationalIdPatterns = {
        LK: /^(?:[0-9]{9}[VX]|[0-9]{12})$/,
        IN: /^(?:[0-9]{12}|[A-Z]{5}[0-9]{4}[A-Z])$/,
        GB: /^[A-CEGHJ-PR-TW-Z]{2}[0-9]{6}[A-D]$/,
        US: /^[0-9]{9}$/,
        AE: /^[0-9]{15}$/,
        FR: /^[A-Z0-9]{12}$/,
        KE: /^[0-9]{7,8}$/,
        CN: /^(?:[0-9]{15}|[0-9]{17}[0-9X])$/,
        JP: /^[0-9]{12}$/,
        SG: /^[STFGM][0-9]{7}[A-Z]$/,
        ZA: /^[0-9]{13}$/
    };

    document.querySelectorAll('[data-document-number]').forEach(function (input) {
        var form = input.form;
        var type = form.querySelector('[data-document-type]');
        var country = form.querySelector('[data-country-value]');

        function validateDocument() {
            var documentType = type.value;
            var countryCode = country.value;
            input.disabled = !documentType || !countryCode;
            input.maxLength = documentType === 'passport' ? 9 : 20;

            var value = input.value.toUpperCase().replace(/\s+/g, '');
            value = value.replace(documentType === 'passport' ? /[^A-Z0-9]/g : /[^A-Z0-9-]/g, '');
            if (input.value !== value) {
                var cursor = input.selectionStart;
                input.value = value;
                input.setSelectionRange(Math.min(cursor, value.length), Math.min(cursor, value.length));
            }

            if (!value) {
                input.setCustomValidity('');
            } else if (documentType === 'passport' && !/^[A-Z0-9]{6,9}$/.test(value)) {
                input.setCustomValidity('Passport numbers must contain 6 to 9 letters or digits.');
            } else if (documentType === 'national_id' && countryCode === 'AU') {
                input.setCustomValidity('Australia does not issue a universal national ID. Choose Passport or another issuing country.');
            } else if (documentType === 'national_id'
                && !(nationalIdPatterns[countryCode] || /^[A-Z0-9-]{5,20}$/).test(value)) {
                input.setCustomValidity('Enter a National ID in the format used by the selected issuing country.');
            } else {
                input.setCustomValidity('');
            }
        }

        type.addEventListener('change', validateDocument);
        country.addEventListener('change', validateDocument);
        input.addEventListener('input', validateDocument);
        validateDocument();
    });

    function enhanceSearchableSelect(select) {
        if (select.closest('[data-international-country], [data-international-phone], [data-nationality-picker], [data-guardian-selector]')) return;
        if (select.dataset.searchableEnhanced) return;

        select.dataset.searchableEnhanced = 'true';
        var wrapper = document.createElement('div');
        wrapper.className = 'dropdown searchable-select';
        var button = document.createElement('button');
        button.type = 'button';
        button.className = 'form-select searchable-select-toggle dropdown-toggle';
        button.setAttribute('data-bs-toggle', 'dropdown');
        button.setAttribute('data-bs-auto-close', 'outside');
        button.setAttribute('data-bs-display', 'static');
        button.setAttribute('aria-haspopup', 'listbox');
        button.setAttribute('aria-expanded', 'false');
        var menu = document.createElement('div');
        menu.className = 'dropdown-menu searchable-select-menu';
        menu.setAttribute('role', 'listbox');
        if (select.multiple) menu.setAttribute('aria-multiselectable', 'true');
        var searchWrap = document.createElement('div');
        searchWrap.className = 'p-2';
        var search = document.createElement('input');
        search.type = 'search';
        search.className = 'form-control form-control-sm';
        search.placeholder = 'Search options';
        search.setAttribute('aria-label', 'Search options');
        search.dataset.noAutoFilter = 'true';
        searchWrap.appendChild(search);
        menu.appendChild(searchWrap);
        var optionsWrap = document.createElement('div');
        optionsWrap.className = 'searchable-select-options';
        menu.appendChild(optionsWrap);

        select.parentNode.insertBefore(wrapper, select);
        wrapper.appendChild(select);
        wrapper.appendChild(button);
        wrapper.appendChild(menu);
        select.classList.add('visually-hidden');
        select.tabIndex = -1;

        function renderOptions() {
            optionsWrap.replaceChildren();
            Array.from(select.options).forEach(function (option) {
                var item = document.createElement('button');
                item.type = 'button';
                item.className = 'dropdown-item searchable-select-option';
                item.textContent = option.textContent.trim();
                item.dataset.value = option.value;
                item.disabled = option.disabled;
                item.hidden = option.hidden;
                item.setAttribute('role', 'option');
                item.setAttribute('aria-selected', option.selected ? 'true' : 'false');
                item.addEventListener('click', function () {
                    if (select.multiple) {
                        option.selected = !option.selected;
                    } else {
                        select.value = option.value;
                    }
                    select.dispatchEvent(new Event('change', { bubbles: true }));
                    syncSelection();
                    if (!select.multiple && window.bootstrap && window.bootstrap.Dropdown) {
                        window.bootstrap.Dropdown.getOrCreateInstance(button).hide();
                    }
                });
                optionsWrap.appendChild(item);
            });
        }

        function syncSelection() {
            var selected = select.options[select.selectedIndex];
            var selectedOptions = Array.from(select.selectedOptions);
            button.textContent = select.multiple
                ? (selectedOptions.length ? selectedOptions.map(function (option) { return option.textContent.trim(); }).join(', ') : 'Select options')
                : (selected ? selected.textContent.trim() : 'Select an option');
            button.disabled = select.disabled;
            button.classList.toggle('is-invalid', select.classList.contains('is-invalid'));
            optionsWrap.querySelectorAll('[role="option"]').forEach(function (item) {
                var isSelected = select.multiple
                    ? selectedOptions.some(function (option) { return option.value === item.dataset.value; })
                    : item.dataset.value === select.value;
                item.setAttribute('aria-selected', isSelected ? 'true' : 'false');
            });
            select.setCustomValidity('');
        }

        select.addEventListener('invalid', function (event) {
            if (!select.required || select.disabled || select.value) return;
            event.preventDefault();
            button.classList.add('is-invalid');
            button.focus();
            if (window.bootstrap && window.bootstrap.Dropdown) {
                window.bootstrap.Dropdown.getOrCreateInstance(button).show();
                search.focus();
            }
        });
        search.addEventListener('input', function () {
            var query = search.value.trim().toLocaleLowerCase();
            optionsWrap.querySelectorAll('[role="option"]').forEach(function (item) {
                item.hidden = query !== '' && !item.textContent.toLocaleLowerCase().includes(query);
            });
        });
        select.addEventListener('change', syncSelection);
        new MutationObserver(function () {
            renderOptions();
            syncSelection();
        }).observe(select, { childList: true, subtree: true, attributes: true, attributeFilter: ['disabled', 'hidden', 'label', 'value'] });
        button.addEventListener('shown.bs.dropdown', function () {
            search.value = '';
            search.dispatchEvent(new Event('input'));
            search.focus();
        });
        button.addEventListener('show.bs.dropdown', function () {
            renderOptions();
            syncSelection();
        });
        renderOptions();
        syncSelection();
    }

    document.querySelectorAll('select').forEach(enhanceSearchableSelect);
    new MutationObserver(function (records) {
        records.forEach(function (record) {
            record.addedNodes.forEach(function (node) {
                if (node.nodeType !== 1) return;
                if (node.matches('select')) enhanceSearchableSelect(node);
                node.querySelectorAll('select').forEach(enhanceSearchableSelect);
            });
        });
    }).observe(document.body, { childList: true, subtree: true });
    var importEntitySelect = document.querySelector('[data-import-entity]');
    var importTemplateLink = document.querySelector('[data-import-template-link]');
    if (importEntitySelect && importTemplateLink) {
        var templateUrl = importTemplateLink.dataset.templateUrl;
        var templatePrefix = templateUrl.slice(0, templateUrl.lastIndexOf('/') + 1);
        var updateTemplateLink = function () {
            importTemplateLink.href = templatePrefix + encodeURIComponent(importEntitySelect.value);
        };
        importEntitySelect.addEventListener('change', updateTemplateLink);
        updateTemplateLink();
    }
    document.querySelectorAll('[data-uppercase-filter]').forEach(function (input) {
        input.addEventListener('input', function () {
            var cursor = input.selectionStart;
            var value = input.value.toUpperCase();
            value = input.dataset.uppercaseFilter === 'spaced-code'
                ? value.replace(/[^A-Z0-9\s-]/g, '')
                : value.replace(/[^A-Z0-9-]/g, '');
            if (input.value !== value) {
                input.value = value;
                input.setSelectionRange(Math.min(cursor, value.length), Math.min(cursor, value.length));
            }
        });
    });
    document.querySelectorAll('[data-live-filter]').forEach(function (input) {
        input.addEventListener('input', function () {
            var value = input.value;
            var filter = input.dataset.liveFilter;
            var patterns = {
                'letter-start-name': /[^A-Za-z0-9\s()\-]/g,
                'letter-start-book': /[^A-Za-z0-9\s\-:()'&]/g,
                'letter-start-fee': /[^A-Za-z0-9\s\-()&]/g,
                'letter-start-account': /[^A-Za-z0-9\s\-()&]/g,
                'role-name': /[^A-Za-z0-9\s_-]/g,
                'announcement-title': /[^A-Za-z0-9\s\-:()!]/g,
                'cashflow-description': /[^A-Za-z0-9\s.&'():!-]/g,
                'branch-place': /[^A-Za-z0-9\s-]/g,
                'branch-principal': /[^A-Za-z\s.'-]/g,
                'letter-start-class': /[^A-Za-z0-9\s-]/g,
                'letter-start-term': /[^A-Za-z0-9\s-]/g,
                'author-name': /[^A-Za-z\s.'-]/g,
                'letters-spaces-hyphens': /[^A-Za-z\s-]/g,
                'uppercase-code': /[^A-Z0-9-]/g,
                'uppercase-payment-reference': /[^A-Z0-9\-\/]/g,
                'uppercase-cashflow-reference': /[^A-Z0-9\/-]/g,
                'uppercase-section': /[^A-Z0-9]/g,
                'uppercase-subject-code': /[^A-Z0-9-]/g,
                isbn: /[^0-9X-]/g,
                'academic-year': /[^0-9-]/g,
                description: /[^A-Za-z0-9\s.,!?'"()\-:/]/g,
                'account-description': /[=|;\\]/g,
                'procurement-notes': /[\u0000-\u0008\u000B\u000C\u000E-\u001F=\[\]]/g,
                'journal-text': /[\u0000-\u0008\u000B\u000C\u000E-\u001F]/g
            };
            var allowed = patterns[filter];
            if (!allowed) return;
            var cursor = input.selectionStart;
            if (filter === 'uppercase-code' || filter === 'uppercase-payment-reference'
                || filter === 'uppercase-cashflow-reference' || filter === 'uppercase-section'
                || filter === 'uppercase-subject-code' || filter === 'isbn') {
                value = value.toUpperCase();
            }
            value = value.replace(allowed, '');
            if (filter.indexOf('letter-start-') === 0
                || ['role-name', 'announcement-title', 'cashflow-description', 'branch-place'].indexOf(filter) !== -1
                || filter === 'uppercase-subject-code') {
                value = value.replace(/^[^A-Za-z]+/, '');
            }
            if (value !== input.value) {
                input.value = value;
                input.setSelectionRange(Math.min(cursor, value.length), Math.min(cursor, value.length));
            }
            if (filter === 'journal-text') {
                input.setCustomValidity(value !== '' && !/[A-Za-z]/.test(value)
                    ? 'Enter text containing at least one letter.'
                    : '');
            }
        });
    });
    document.querySelectorAll('[data-contains-letters]').forEach(function (input) {
        function validateLetters() {
            input.setCustomValidity(input.value !== '' && !/[A-Za-z]/.test(input.value)
                ? 'Enter text containing at least one letter.'
                : '');
        }
        input.addEventListener('input', validateLetters);
        validateLetters();
    });
    document.querySelectorAll('[data-date-after]').forEach(function (endDate) {
        var startDate = document.getElementById(endDate.dataset.dateAfter);
        if (!startDate) return;
        var updateMinimum = function () {
            endDate.min = startDate.value;
            if (endDate.value && startDate.value && endDate.value <= startDate.value) {
                endDate.setCustomValidity('End date must be after start date.');
            } else {
                endDate.setCustomValidity('');
            }
        };
        startDate.addEventListener('input', updateMinimum);
        startDate.addEventListener('change', updateMinimum);
        endDate.addEventListener('input', updateMinimum);
        endDate.addEventListener('change', updateMinimum);
        updateMinimum();
    });
    document.querySelectorAll('[data-person-name]').forEach(function (input) {
        input.minLength = 2;
        input.maxLength = 100;
    });

    // ---- flash messages fade out after a few seconds ----
    document.querySelectorAll('.alert[data-autohide]').forEach(function (a) {
        setTimeout(function () {
            a.style.transition = 'opacity .5s, transform .5s';
            a.style.opacity = '0'; a.style.transform = 'translateY(-6px)';
            setTimeout(function () { a.remove(); }, 520);
        }, 5000);
    });
})();
