@extends('Layouts.LayoutApp')
@section('title', 'Settings')
@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <div>
        <h3 class="mb-0">Institution Settings</h3>
        <small class="text-muted">Manage system-wide configuration key/value settings.</small>
    </div>
    @can('update', App\Models\Setting::class)
    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createSettingModal">
        <i class="bi bi-plus-lg me-1"></i> Add Setting
    </button>
    @endcan
</div>

@include('Layouts.LayoutErrors')

<div class="card shadow-sm border-0">
    <div class="table-responsive">
        <table class="table table-hover mb-0 align-middle">
            <thead class="table-light">
                <tr>
                    <th>Key</th>
                    <th>Value</th>
                    <th>Type</th>
                    <th>Group</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
            @forelse ($settings as $setting)
                <tr>
                    <td class="fw-semibold">{{ $setting->key }}</td>
                    <td>{{ $setting->value }}</td>
                    <td><span class="badge bg-secondary">{{ $setting->type }}</span></td>
                    <td>{{ $setting->group }}</td>
                    <td class="text-end">
                        <div class="btn-group btn-group-sm">
                            @can('update', $setting)
                            <button type="button" class="btn btn-outline-primary edit-setting-btn"
                                data-id="{{ $setting->id }}"
                                data-key="{{ $setting->key }}"
                                data-value="{{ $setting->value }}"
                                data-type="{{ $setting->type }}"
                                data-group="{{ $setting->group }}"
                                data-action="{{ route('admin.settings.update', $setting) }}">
                                <i class="bi bi-pencil-square me-1"></i>Edit
                            </button>
                            @endcan
                            @can('delete', $setting)
                            <form method="POST" action="{{ route('admin.settings.destroy', $setting) }}" class="d-inline" onsubmit="return confirm('Delete setting {{ $setting->key }}?')">
                                @csrf @method('DELETE')
                                <button class="btn btn-outline-danger"><i class="bi bi-trash3 me-1"></i>Delete</button>
                            </form>
                            @endcan
                        </div>
                    </td>
                </tr>
            @empty
                <tr><td colspan="5" class="text-center text-muted py-4">No settings defined.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>

<!-- Create Setting Modal -->
<div class="modal fade" id="createSettingModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="{{ route('admin.settings.store') }}">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Add Setting</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Key <span class="text-danger">*</span></label>
                            <input type="text" name="key" class="form-control" required value="{{ old('key') }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Value</label>
                            <input type="text" name="value" class="form-control" value="{{ old('value') }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Type <span class="text-danger">*</span></label>
                            <select name="type" class="form-select" required>
                                <option value="string">String</option>
                                <option value="integer">Integer</option>
                                <option value="float">Float</option>
                                <option value="boolean">Boolean</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Group</label>
                            <input type="text" name="group" class="form-control" value="{{ old('group', 'general') }}">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg me-1"></i>Save</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Setting Modal -->
<div class="modal fade" id="editSettingModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" id="editSettingForm" action="">
                @csrf @method('PUT')
                <div class="modal-header">
                    <h5 class="modal-title">Edit Setting</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="key" id="edit_setting_key">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Value</label>
                            <input type="text" name="value" class="form-control" id="edit_setting_value">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Type <span class="text-danger">*</span></label>
                            <select name="type" class="form-select" id="edit_setting_type" required>
                                <option value="string">String</option>
                                <option value="integer">Integer</option>
                                <option value="float">Float</option>
                                <option value="boolean">Boolean</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Group</label>
                            <input type="text" name="group" class="form-control" id="edit_setting_group">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg me-1"></i>Update</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        var editModal = new bootstrap.Modal(document.getElementById('editSettingModal'));
        var form = document.getElementById('editSettingForm');

        document.querySelectorAll('.edit-setting-btn').forEach(function (btn) {
            btn.addEventListener('click', function () {
                document.getElementById('edit_setting_key').value = this.dataset.key;
                document.getElementById('edit_setting_value').value = this.dataset.value;
                document.getElementById('edit_setting_type').value = this.dataset.type;
                document.getElementById('edit_setting_group').value = this.dataset.group;
                form.action = this.dataset.action;
                editModal.show();
            });
        });
    });
</script>
@endsection
