@extends('dashboard.layout')

@section('content')
<div class="main-content">
    <div class="data-entry-container p-3">

        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="data-entry-header">📋 Audit Log - Gene Edits</h1>
            <a href="{{ route('algorithm-control.timetable.results.index') }}" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-1"></i> Back
            </a>
        </div>

        <div class="table-responsive">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Subject</th>
                        <th>Field</th>
                        <th>Old Value</th>
                        <th>New Value</th>
                        <th>Changed By</th>
                        <th>Changed At</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="auditLogBody">
                    <!-- سيتم ملؤها بالـ AJAX -->
                </tbody>
            </table>
        </div>

    </div>
</div>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        fetch('/api/gene-edits')
            .then(res => res.json())
            .then(edits => {
                const body = document.getElementById('auditLogBody');
                edits.forEach(edit => {
                    const row = document.createElement('tr');
                    row.innerHTML = `
                        <td>${edit.id}</td>
                        <td>${edit.subject_name}</td>
                        <td>${edit.field}</td>
                        <td>${edit.old_value_id}</td>
                        <td>${edit.new_value_id}</td>
                        <td>${edit.changed_by_name}</td>
                        <td>${new Date(edit.changed_at).toLocaleString()}</td>
                        <td>
                            <button class="btn btn-sm btn-warning undo-btn" data-id="${edit.id}">
                                Undo
                            </button>
                        </td>
                    `;
                    body.appendChild(row);
                });

                document.querySelectorAll('.undo-btn').forEach(btn => {
                    btn.addEventListener('click', function () {
                        const id = this.dataset.id;
                        if (confirm('Are you sure you want to undo this edit?')) {
                            fetch(`/api/gene-edits/${id}/undo`, {
                                method: 'POST',
                                headers: {
                                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                                }
                            })
                            .then(res => res.json())
                            .then(data => {
                                alert(data.message);
                                location.reload();
                            });
                        }
                    });
                });
            });
    });
</script>
@endpush
@endsection
