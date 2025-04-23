@extends('dashboard.layout')

@section('content')
<div class="main-content">
    <div class="data-entry-container">
        <div class="d-flex justify-content-between align-items-center mb-4">
             <h1 class="data-entry-header mb-0">Manage Users & Roles</h1>
             {{-- // زر لإظهار Modal الإضافة --}}
             <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addUserModal">
                 <i class="fas fa-user-plus me-1"></i> Add New User
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
                                <th scope="col">Name</th>
                                <th scope="col">Email</th>
                                <th scope="col">Role</th>
                                <th scope="col">Email Verified</th>
                                <th scope="col">Joined At</th>
                                <th scope="col">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($users as $index => $user)
                            <tr>
                                <td>{{ $index + 1 }}</td>
                                <td>{{ $user->name }}</td>
                                <td>{{ $user->email }}</td>
                                {{-- // عرض اسم الدور من العلاقة --}}
                                <td>
                                    {{-- <span class="badge rounded-pill bg-{{ $user->role->name === 'admin' ? 'danger' : ($user->role->name === 'hod' ? 'warning text-dark' : ($user->role->name === 'instructor' ? 'info text-dark' : 'secondary')) }}">
                                        {{ $user->role->display_name ?? 'N/A' }}
                                    </span> --}}
                                    <span class="badge rounded-pill bg-{{ $user->role?->name === 'admin' ? 'danger' : ($user->role?->name === 'hod' ? 'warning text-dark' : ($user->role?->name === 'instructor' ? 'info text-dark' : 'secondary')) }}">
                                        {{ $user->role?->display_name ?? 'No Role' }} {{-- // عرض No Role إذا كان الدور null --}}
                                    </span>
                                </td>
                                <td>
                                    @if($user->email_verified_at)
                                        <i class="fas fa-check-circle text-success" title="{{ $user->email_verified_at->format('Y-m-d H:i') }}"></i> Yes
                                    @else
                                        <i class="fas fa-times-circle text-danger"></i> No
                                    @endif
                                </td>
                                <td>{{ $user->created_at }}</td>
                                {{-- <td>{{ $user->created_at->format('Y-m-d') }}</td> --}}
                                <td>
                                    {{-- // زر التعديل --}}
                                    <button class="btn btn-sm btn-outline-primary me-1" data-bs-toggle="modal" data-bs-target="#editUserModal-{{ $user->id }}" title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    {{-- // زر الحذف --}}
                                    {{-- // لا نحذف الأدمن الأول عادةً --}}
                                    @if($user->id !== 1) {{-- // افترض أن ID الأدمن الأول هو 1 --}}
                                        <button class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#deleteUserModal-{{ $user->id }}" title="Delete">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    @else
                                         <button class="btn btn-sm btn-outline-secondary" disabled title="Cannot delete primary admin">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    @endif

                                    {{-- // تضمين Modals --}}
                                    @include('dashboard.data-entry.partials._user_modals', [
                                        'user' => $user,
                                        'roles' => $roles
                                    ])
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted">No users found. Click 'Add New User' to create one.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                 {{-- // Pagination إذا لزم الأمر --}}
                 <div class="mt-3 d-flex justify-content-center">
                  {{ $users->links('pagination::bootstrap-5') }}
                  {{-- // {{ $users->links() }} --}}
                </div>
            </div>
        </div>

         {{-- // Modal لإضافة مستخدم جديد --}}
         @include('dashboard.data-entry.partials._user_modals', [
             'user' => null,
             'roles' => $roles
         ])

    </div>
</div>
@endsection

@push('scripts')
{{-- // يمكن إضافة JavaScript خاص بهذه الصفحة هنا --}}
@endpush
