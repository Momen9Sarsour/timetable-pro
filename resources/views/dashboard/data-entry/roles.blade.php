@extends('dashboard.layout')

@section('content')
    <div class="main-content">
        <div class="data-entry-container">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="data-entry-header mb-0">Manage Roles</h1>
                {{-- // زر لإظهار Modal الإضافة --}}
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addRoleModal">
                    <i class="fas fa-plus me-1"></i> Add New Role
                </button>
            </div>

            {{-- // عرض رسائل الحالة --}}
            @include('dashboard.data-entry.partials._status_messages')

            <div class="card shadow-sm">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th scope="col">#</th>
                                    <th scope="col">System Name (Code)</th> {{-- // اسم النظام (name) --}}
                                    <th scope="col">Display Name</th> {{-- // الاسم المعروض --}}
                                    <th scope="col">Description</th>
                                    <th scope="col">User Count</th> {{-- // عدد المستخدمين بالدور --}}
                                    <th scope="col">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($roles as $index => $role)
                                    @php $isCoreRole = in_array($role->name, ['admin', 'hod', 'instructor', 'student']); @endphp
                                    <tr>
                                        <td>{{ $index + 1 }}</td>
                                        <td><code>{{ $role->name }}</code></td>
                                        <td>{{ $role->display_name }}</td>
                                        <td>{{ $role->description ?? '-' }}</td>
                                        <td>{{ $role->users()->count() }}</td> {{-- // عرض عدد المستخدمين --}}
                                        <td>
                                            {{-- // زر التعديل (يمكن تعطيله للأدوار الأساسية) --}}
                                            <button class="btn btn-sm btn-outline-primary me-1" data-bs-toggle="modal"
                                                data-bs-target="#editRoleModal-{{ $role->id }}" title="Edit"
                                                {{-- $isCoreRole ? 'disabled' : '' --}}>
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            {{-- // زر الحذف (معطل للأدوار الأساسية أو إذا كان عدد المستخدمين > 0) --}}
                                            <button class="btn btn-sm btn-outline-danger" data-bs-toggle="modal"
                                                data-bs-target="#deleteRoleModal-{{ $role->id }}" title="Delete"
                                                {{-- {{ ($isCoreRole || $role->users()->count() > 0) ? 'disabled' : '' }} --}}>
                                                <i class="fas fa-trash"></i>
                                            </button>

                                            {{-- // تضمين Modals --}}
                                            @include('dashboard.data-entry.partials._role_modals', [
                                                'role' => $role,
                                            ])
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center text-muted">No roles found. Core roles are
                                            typically seeded.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    {{-- إضافة روابط الـ Pagination --}}
                    <div class="mt-3 d-flex justify-content-center">
                        {{-- {{ $roles->links() }} هذا يعرض روابط التنقل بين الصفحات --}}
                        {{ $roles->links('pagination::bootstrap-5') }}
                    </div>
                </div>
            </div>

            {{-- // Modal لإضافة دور جديد --}}
            @include('dashboard.data-entry.partials._role_modals', ['role' => null])

        </div>
    </div>
@endsection

@push('scripts')
    {{-- // يمكن إضافة JavaScript خاص بهذه الصفحة هنا --}}
@endpush
