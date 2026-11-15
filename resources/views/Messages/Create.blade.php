@extends('Layouts.LayoutApp')
@section('title', 'Compose Private Message')
@section('content')
<div class="row justify-content-center">
    <div class="col-12 col-xl-10">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h3 class="mb-1 text-body fw-bold"><i class="bi bi-envelope-plus text-primary me-2"></i>New Private Message</h3>
                <p class="text-muted small mb-0">Confidential 1-on-1 communication. Only you and the selected recipient can view this conversation.</p>
            </div>
            <a class="btn btn-outline-secondary btn-sm" href="{{ route('messages.index') }}">
                <i class="bi bi-arrow-left me-1"></i>Back to Inbox
            </a>
        </div>

        <div class="card shadow-sm mb-4">
            <div class="card-body p-4">
                <form method="POST" action="{{ route('messages.store') }}" id="messageForm">
                    @csrf

                    <!-- Recipient Selection Section -->
                    <div class="mb-4 position-relative">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <label for="recipient_id" class="form-label fw-semibold text-body mb-0">
                                <i class="bi bi-person-check text-info me-1"></i>Select Recipient (Students, Teachers, Admin Staff, Guardians)
                            </label>
                            <span class="badge bg-secondary-subtle text-body border" id="recipientCountBadge">
                                {{ $recipients->count() }} members available
                            </span>
                        </div>

                        <!-- Role Category Filter Pills -->
                        <div class="d-flex flex-wrap gap-2 mb-3" id="roleFilterButtons">
                            <button type="button" class="btn btn-sm btn-outline-primary active role-filter-btn" data-filter="all">
                                All Members ({{ $recipients->count() }})
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-info role-filter-btn" data-filter="staff">
                                <i class="bi bi-shield-check me-1"></i>Admin Staff ({{ $groupedRecipients['staff']->count() }})
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-warning role-filter-btn" data-filter="teachers">
                                <i class="bi bi-person-workspace me-1"></i>Teachers ({{ $groupedRecipients['teachers']->count() }})
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-success role-filter-btn" data-filter="students">
                                <i class="bi bi-mortarboard me-1"></i>Students ({{ $groupedRecipients['students']->count() }})
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-secondary role-filter-btn" data-filter="guardians">
                                <i class="bi bi-people me-1"></i>Guardians ({{ $groupedRecipients['guardians']->count() }})
                            </button>
                        </div>

                        <!-- Live Search Input -->
                        <div class="input-group mb-2">
                            <span class="input-group-text"><i class="bi bi-search"></i></span>
                            <input type="text" id="recipientSearchInput" class="form-control" placeholder="Type a name, admission number, teacher department, or role..." autocomplete="off">
                            <button class="btn btn-outline-secondary" type="button" id="clearSearchBtn" title="Clear search"><i class="bi bi-x-lg"></i></button>
                        </div>

                        <!-- Live Interactive Search Results Panel (Opens directly when typing or focused) -->
                        <div id="liveSearchResultsPanel" class="card shadow-lg border border-primary-subtle mb-3" style="display: none; max-height: 360px; overflow-y: auto; z-index: 1050;">
                            <div class="card-header py-2 px-3 bg-body-tertiary d-flex justify-content-between align-items-center sticky-top">
                                <small class="text-body fw-semibold" id="searchResultsHeader">
                                    <i class="bi bi-list-check me-1 text-primary"></i>Matching Members (<span id="resultsCount">0</span>)
                                </small>
                                <button type="button" class="btn btn-sm btn-link text-muted p-0 text-decoration-none" id="closeResultsPanelBtn">
                                    <i class="bi bi-x-circle me-1"></i>Close
                                </button>
                            </div>
                            <div class="list-group list-group-flush" id="searchResultsList">
                                <!-- Populated dynamically by JavaScript -->
                            </div>
                        </div>

                        <!-- Selected Recipient Confirmation Banner -->
                        <div id="selectedRecipientBanner" class="alert alert-success border-success-subtle bg-success-subtle text-success py-2 px-3 mb-3 d-flex justify-content-between align-items-center" style="display: none;">
                            <div class="d-flex align-items-center gap-2">
                                <i class="bi bi-check-circle-fill fs-5"></i>
                                <div>
                                    <strong id="selectedBannerName">No recipient selected</strong>
                                    <span class="badge bg-success text-white ms-1 text-uppercase" id="selectedBannerRole" style="font-size: 0.7rem;"></span>
                                    <span class="small text-body-secondary ms-2" id="selectedBannerMeta"></span>
                                </div>
                            </div>
                            <button type="button" class="btn btn-sm btn-outline-success" id="changeRecipientBtn">
                                <i class="bi bi-arrow-repeat me-1"></i>Change
                            </button>
                        </div>

                        <!-- The Select Dropdown with Optgroups (Preserves id="recipient_id" and name="recipient_id" for test assertions) -->
                        <div class="mb-2">
                            <select id="recipient_id" name="recipient_id" class="form-select recipient-select py-2" required>
                                <option value="">-- Choose a recipient from the list below --</option>

                                @if ($groupedRecipients['staff']->isNotEmpty())
                                    <optgroup label="── Administrative Staff ──" data-category="staff">
                                        @foreach ($groupedRecipients['staff'] as $staff)
                                            <option value="{{ $staff->id }}"
                                                data-category="staff"
                                                data-name="{{ strtolower($staff->name) }}"
                                                data-role="{{ strtolower($staff->role) }}"
                                                data-branch="{{ strtolower($staff->branch?->name ?? '') }}"
                                                @selected(old('recipient_id', $selectedRecipientId) == $staff->id)>
                                                {{ $staff->name }} — {{ strtoupper(str_replace('-', ' ', $staff->role)) }} ({{ $staff->branch?->name ?? 'Main Campus' }})
                                            </option>
                                        @endforeach
                                    </optgroup>
                                @endif

                                @if ($groupedRecipients['teachers']->isNotEmpty())
                                    <optgroup label="── Teachers & Academic Faculty ──" data-category="teachers">
                                        @foreach ($groupedRecipients['teachers'] as $teacher)
                                            <option value="{{ $teacher->id }}"
                                                data-category="teachers"
                                                data-name="{{ strtolower($teacher->name) }}"
                                                data-role="teacher"
                                                data-dept="{{ strtolower($teacher->teacher?->department?->name ?? '') }}"
                                                data-branch="{{ strtolower($teacher->branch?->name ?? '') }}"
                                                @selected(old('recipient_id', $selectedRecipientId) == $teacher->id)>
                                                {{ $teacher->name }} — Teacher ({{ $teacher->teacher?->department?->name ?? 'Faculty' }} • {{ $teacher->branch?->name ?? 'Main Campus' }})
                                            </option>
                                        @endforeach
                                    </optgroup>
                                @endif

                                @if ($groupedRecipients['students']->isNotEmpty())
                                    <optgroup label="── Students ──" data-category="students">
                                        @foreach ($groupedRecipients['students'] as $student)
                                            @php
                                                $adm = $student->student?->admission_no ?? '';
                                                $cls = $student->student?->class ? ($student->student->class->name . ' - ' . $student->student->class->section) : '';
                                            @endphp
                                            <option value="{{ $student->id }}"
                                                data-category="students"
                                                data-name="{{ strtolower($student->name) }}"
                                                data-role="student"
                                                data-admission="{{ strtolower($adm) }}"
                                                data-class="{{ strtolower($cls) }}"
                                                data-branch="{{ strtolower($student->branch?->name ?? '') }}"
                                                @selected(old('recipient_id', $selectedRecipientId) == $student->id)>
                                                {{ $student->name }} — Student [{{ $adm ?: 'ID:'.$student->id }}] ({{ $cls ?: 'Enrolled' }} • {{ $student->branch?->name ?? 'Main Campus' }})
                                            </option>
                                        @endforeach
                                    </optgroup>
                                @endif

                                @if ($groupedRecipients['guardians']->isNotEmpty())
                                    <optgroup label="── Guardians & Parents ──" data-category="guardians">
                                        @foreach ($groupedRecipients['guardians'] as $guardian)
                                            <option value="{{ $guardian->id }}"
                                                data-category="guardians"
                                                data-name="{{ strtolower($guardian->name) }}"
                                                data-role="guardian"
                                                data-branch="{{ strtolower($guardian->branch?->name ?? '') }}"
                                                @selected(old('recipient_id', $selectedRecipientId) == $guardian->id)>
                                                {{ $guardian->name }} — Guardian / Parent ({{ $guardian->branch?->name ?? 'Main Campus' }})
                                            </option>
                                        @endforeach
                                    </optgroup>
                                @endif
                            </select>
                        </div>
                        @error('recipient_id')<div class="text-danger small mt-1"><i class="bi bi-exclamation-circle me-1"></i>{{ $message }}</div>@enderror
                    </div>

                    <!-- Subject Input -->
                    <div class="mb-4">
                        <label for="subject" class="form-label fw-semibold text-body">
                            <i class="bi bi-chat-left-text text-warning me-1"></i>Subject <span class="text-muted fw-normal small">(Optional)</span>
                        </label>
                        <input id="subject" name="subject" value="{{ old('subject') }}" maxlength="120" pattern="(?=.*[A-Za-z]).+" data-contains-letters class="form-control" placeholder="e.g. Inquiry regarding Term 2 exam schedule">
                        <div class="d-flex justify-content-between mt-1">
                            <span class="form-text text-muted small">Subject must contain at least one letter (maximum 120 characters).</span>
                            <span class="small text-muted" id="subjectCharCount">0 / 120</span>
                        </div>
                        @error('subject')<div class="text-danger small mt-1"><i class="bi bi-exclamation-circle me-1"></i>{{ $message }}</div>@enderror
                    </div>

                    <!-- Body Input -->
                    <div class="mb-4">
                        <label for="body" class="form-label fw-semibold text-body">
                            <i class="bi bi-envelope-paper text-success me-1"></i>Message Body <span class="text-danger">*</span>
                        </label>
                        <textarea id="body" name="body" rows="6" minlength="5" maxlength="2000" data-contains-letters required class="form-control" placeholder="Write your private message here...">{{ old('body') }}</textarea>
                        <div class="d-flex justify-content-between mt-1">
                            <span class="form-text text-muted small">Minimum 5 characters, maximum 2,000 characters. Must contain letters.</span>
                            <span class="small text-muted" id="bodyCharCount">0 / 2,000</span>
                        </div>
                        @error('body')<div class="text-danger small mt-1"><i class="bi bi-exclamation-circle me-1"></i>{{ $message }}</div>@enderror
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary px-4">
                            <i class="bi bi-send-fill me-2"></i>Send Private Message
                        </button>
                        <a class="btn btn-outline-secondary px-4" href="{{ route('messages.index') }}">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    // Member dataset pre-loaded from server
    const members = [
        @foreach ($recipients as $recipient)
            @php
                $roleClean = strtolower($recipient->role);
                $isStaff = in_array($roleClean, ['super-admin', 'super admin', 'admin', 'institution admin', 'registrar', 'accountant'], true);
                $cat = $isStaff ? 'staff' : ($roleClean === 'teacher' ? 'teachers' : ($roleClean === 'student' ? 'students' : 'guardians'));
                $adm = $recipient->student?->admission_no ?? '';
                $cls = $recipient->student?->class ? ($recipient->student->class->name . ' - ' . $recipient->student->class->section) : '';
                $dept = $recipient->teacher?->department?->name ?? '';
                $branch = $recipient->branch?->name ?? 'Main Campus';
                $meta = $isStaff ? ($branch) : ($roleClean === 'teacher' ? ($dept . ' • ' . $branch) : ($roleClean === 'student' ? ($adm . ($cls ? ' • ' . $cls : '') . ' • ' . $branch) : $branch));
            @endphp
            {
                id: {{ $recipient->id }},
                name: @json($recipient->name),
                role: @json($recipient->role),
                roleLabel: @json(strtoupper(str_replace('-', ' ', $recipient->role))),
                category: @json($cat),
                meta: @json($meta),
                branch: @json($branch),
                initial: @json(strtoupper(mb_substr($recipient->name, 0, 1)))
            },
        @endforeach
    ];

    const searchInput = document.getElementById('recipientSearchInput');
    const clearBtn = document.getElementById('clearSearchBtn');
    const select = document.getElementById('recipient_id');
    const filterButtons = document.querySelectorAll('.role-filter-btn');
    const resultsPanel = document.getElementById('liveSearchResultsPanel');
    const resultsList = document.getElementById('searchResultsList');
    const resultsCount = document.getElementById('resultsCount');
    const closePanelBtn = document.getElementById('closeResultsPanelBtn');
    const selectedBanner = document.getElementById('selectedRecipientBanner');
    const selectedBannerName = document.getElementById('selectedBannerName');
    const selectedBannerRole = document.getElementById('selectedBannerRole');
    const selectedBannerMeta = document.getElementById('selectedBannerMeta');
    const changeRecipientBtn = document.getElementById('changeRecipientBtn');
    const subjectInput = document.getElementById('subject');
    const bodyInput = document.getElementById('body');
    const subjectCharCount = document.getElementById('subjectCharCount');
    const bodyCharCount = document.getElementById('bodyCharCount');

    let currentFilter = 'all';

    function getRoleBadgeClass(category) {
        switch (category) {
            case 'staff': return 'bg-info-subtle text-info border-info-subtle';
            case 'teachers': return 'bg-warning-subtle text-warning border-warning-subtle';
            case 'students': return 'bg-success-subtle text-success border-success-subtle';
            case 'guardians': return 'bg-secondary-subtle text-body border-secondary-subtle';
            default: return 'bg-secondary-subtle text-body border-secondary-subtle';
        }
    }

    function renderSearchResults() {
        const query = (searchInput.value || '').trim().toLowerCase();
        
        // Filter members
        const matches = members.filter(m => {
            const matchesCat = (currentFilter === 'all' || m.category === currentFilter);
            if (!matchesCat) return false;
            if (!query) return true; // if no query, show top of category
            
            const nameMatch = m.name.toLowerCase().includes(query);
            const roleMatch = m.role.toLowerCase().includes(query) || m.roleLabel.toLowerCase().includes(query);
            const metaMatch = m.meta.toLowerCase().includes(query);
            return nameMatch || roleMatch || metaMatch;
        });

        // Update count
        resultsCount.textContent = matches.length;

        // Render up to 50 results
        const displayMatches = matches.slice(0, 50);

        if (displayMatches.length === 0) {
            resultsList.innerHTML = `
                <div class="py-4 text-center text-muted">
                    <i class="bi bi-person-x fs-3 d-block mb-1"></i>
                    No members found matching "<strong>${escapeHtml(query)}</strong>" in this category.
                </div>
            `;
        } else {
            let html = '';
            displayMatches.forEach(m => {
                const isSelected = select.value == m.id;
                const badgeClass = getRoleBadgeClass(m.category);
                html += `
                    <button type="button" class="list-group-item list-group-item-action border-secondary-subtle py-2 px-3 d-flex align-items-center justify-content-between recipient-item-btn ${isSelected ? 'active bg-primary-subtle' : ''}" data-id="${m.id}">
                        <div class="d-flex align-items-center gap-3">
                            <div class="avatar rounded-circle d-flex align-items-center justify-content-center text-white fw-bold shadow-sm"
                                 style="width: 38px; height: 38px; background: linear-gradient(135deg, #0ea5e9, #6366f1); flex-shrink: 0;">
                                ${m.initial}
                            </div>
                            <div class="text-start">
                                <div class="d-flex align-items-center gap-2">
                                    <strong class="text-body">${escapeHtml(m.name)}</strong>
                                    <span class="badge ${badgeClass} text-uppercase" style="font-size: 0.68rem;">${m.roleLabel}</span>
                                </div>
                                <div class="small text-muted">${escapeHtml(m.meta)}</div>
                            </div>
                        </div>
                        <div>
                            <span class="btn btn-sm btn-outline-primary py-0 px-2" style="font-size: 0.75rem;">
                                <i class="bi bi-check2 me-1"></i>Select
                            </span>
                        </div>
                    </button>
                `;
            });
            resultsList.innerHTML = html;

            // Attach click listener to each result
            resultsList.querySelectorAll('.recipient-item-btn').forEach(btn => {
                btn.addEventListener('click', function () {
                    const id = this.getAttribute('data-id');
                    selectRecipient(id);
                });
            });
        }

        resultsPanel.style.display = 'block';
    }

    function selectRecipient(id) {
        select.value = id;
        const member = members.find(m => m.id == id);
        if (member) {
            selectedBannerName.textContent = member.name;
            selectedBannerRole.textContent = member.roleLabel;
            selectedBannerMeta.textContent = member.meta;
            selectedBanner.style.display = 'flex';
            searchInput.value = member.name;
        }
        resultsPanel.style.display = 'none';
        
        // Focus subject if empty
        if (subjectInput && !subjectInput.value) {
            subjectInput.focus();
        }
    }

    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    // Input event on live search
    searchInput.addEventListener('input', function () {
        renderSearchResults();
    });

    // Focus event on search input to show panel
    searchInput.addEventListener('focus', function () {
        renderSearchResults();
    });

    clearBtn.addEventListener('click', function () {
        searchInput.value = '';
        renderSearchResults();
        searchInput.focus();
    });

    closePanelBtn.addEventListener('click', function () {
        resultsPanel.style.display = 'none';
    });

    changeRecipientBtn.addEventListener('click', function () {
        searchInput.value = '';
        searchInput.focus();
        renderSearchResults();
    });

    // When select changes natively
    select.addEventListener('change', function () {
        if (this.value) {
            selectRecipient(this.value);
        } else {
            selectedBanner.style.display = 'none';
        }
    });

    // Filter pills
    filterButtons.forEach(btn => {
        btn.addEventListener('click', function () {
            filterButtons.forEach(b => b.classList.remove('active', 'btn-primary', 'btn-info', 'btn-warning', 'btn-success', 'btn-secondary'));
            filterButtons.forEach(b => {
                if (b.dataset.filter === 'all') b.classList.add('btn-outline-primary');
                if (b.dataset.filter === 'staff') b.classList.add('btn-outline-info');
                if (b.dataset.filter === 'teachers') b.classList.add('btn-outline-warning');
                if (b.dataset.filter === 'students') b.classList.add('btn-outline-success');
                if (b.dataset.filter === 'guardians') b.classList.add('btn-outline-secondary');
            });

            this.classList.add('active');
            currentFilter = this.dataset.filter;
            renderSearchResults();
        });
    });

    // If an initial value is preselected
    if (select.value) {
        selectRecipient(select.value);
    }

    // Character counters
    if (subjectInput && subjectCharCount) {
        subjectInput.addEventListener('input', () => {
            subjectCharCount.textContent = subjectInput.value.length + ' / 120';
        });
        subjectCharCount.textContent = subjectInput.value.length + ' / 120';
    }

    if (bodyInput && bodyCharCount) {
        bodyInput.addEventListener('input', () => {
            bodyCharCount.textContent = bodyInput.value.length + ' / 2,000';
        });
        bodyCharCount.textContent = bodyInput.value.length + ' / 2,000';
    }
});
</script>
@endsection
