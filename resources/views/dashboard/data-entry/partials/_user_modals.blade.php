{{-- // Modal لإضافة مستخدم جديد --}}
@if (!$user)
<div class="modal fade" id="addUserModal" tabindex="-1" aria-labelledby="addUserModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addUserModalLabel">Add New User</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            {{-- // تأكد من أن هذا الـ route معرف --}}
            <form action="{{ route('data-entry.users.store') }}" method="POST">
                @csrf
                <div class="modal-body">
                    {{-- // الاسم --}}
                    <div class="mb-3">
                        <label for="add_user_name" class="form-label">Full Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('name', 'store') is-invalid @enderror" id="add_user_name" name="name" value="{{ old('name') }}" required>
                        @error('name', 'store') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    {{-- // الإيميل --}}
                    <div class="mb-3">
                         <label for="add_user_email" class="form-label">Email Address <span class="text-danger">*</span></label>
                         <input type="email" class="form-control @error('email', 'store') is-invalid @enderror" id="add_user_email" name="email" value="{{ old('email') }}" required>
                         @error('email', 'store') <div class="invalid-feedback">{{ $message }}</div> @enderror
                     </div>
                    {{-- // كلمة المرور --}}
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="add_user_password" class="form-label">Password <span class="text-danger">*</span></label>
                            <input type="password" class="form-control @error('password', 'store') is-invalid @enderror" id="add_user_password" name="password" required>
                            @error('password', 'store') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="add_user_password_confirmation" class="form-label">Confirm Password <span class="text-danger">*</span></label>
                            <input type="password" class="form-control" id="add_user_password_confirmation" name="password_confirmation" required>
                        </div>
                    </div>
                    {{-- // الدور --}}
                    <div class="mb-3">
                         <label for="add_user_role_id" class="form-label">Assign Role <span class="text-danger">*</span></label>
                         <select class="form-select @error('role_id', 'store') is-invalid @enderror" id="add_user_role_id" name="role_id" required>
                             <option value="" selected disabled>Select role...</option>
                             {{-- // تأكد أن $roles تم تمريرها لهذا الـ include --}}
                             @if(isset($roles))
                                 @foreach ($roles as $role)
                                     <option value="{{ $role->id }}" {{ old('role_id') == $role->id ? 'selected' : '' }}>{{ $role->display_name }}</option>
                                 @endforeach
                             @endif
                         </select>
                         @error('role_id', 'store') <div class="invalid-feedback">{{ $message }}</div> @enderror
                     </div>
                    {{-- // تأكيد الإيميل --}}
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" id="add_user_verify_email" name="verify_email" value="1" {{ old('verify_email') ? 'checked' : '' }}>
                        <label class="form-check-label" for="add_user_verify_email">
                            Mark email as verified immediately
                        </label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Save User</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif


{{-- // Modals التعديل والحذف --}}
@if ($user)

{{-- // Modal لتعديل مستخدم موجود --}}
<div class="modal fade" id="editUserModal-{{ $user->id }}" tabindex="-1" aria-labelledby="editUserModalLabel-{{ $user->id }}" aria-hidden="true">
     <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editUserModalLabel-{{ $user->id }}">Edit User: {{ $user->name }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
             {{-- // تأكد من أن هذا الـ route معرف --}}
             <form action="{{ route('data-entry.users.update', $user->id) }}" method="POST">
                @csrf
                @method('PUT')
                <div class="modal-body">
                    {{-- // الاسم --}}
                    <div class="mb-3">
                        <label for="edit_user_name_{{ $user->id }}" class="form-label">Full Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('name', 'update_'.$user->id) is-invalid @enderror" id="edit_user_name_{{ $user->id }}" name="name" value="{{ old('name', $user->name) }}" required>
                        @error('name', 'update_'.$user->id) <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    {{-- // الإيميل --}}
                    <div class="mb-3">
                         <label for="edit_user_email_{{ $user->id }}" class="form-label">Email Address <span class="text-danger">*</span></label>
                         <input type="email" class="form-control @error('email', 'update_'.$user->id) is-invalid @enderror" id="edit_user_email_{{ $user->id }}" name="email" value="{{ old('email', $user->email) }}" required>
                         @error('email', 'update_'.$user->id) <div class="invalid-feedback">{{ $message }}</div> @enderror
                     </div>
                    {{-- // الدور --}}
                    <div class="mb-3">
                         <label for="edit_user_role_id_{{ $user->id }}" class="form-label">Assign Role <span class="text-danger">*</span></label>
                         <select class="form-select @error('role_id', 'update_'.$user->id) is-invalid @enderror" id="edit_user_role_id_{{ $user->id }}" name="role_id" required {{ $user->id === 1 ? 'disabled' : '' }}>
                             <option value="" disabled>Select role...</option>
                             {{-- // تأكد أن $roles تم تمريرها لهذا الـ include --}}
                              @if(isset($roles))
                                 @foreach ($roles as $role)
                                     {{-- // منع تغيير دور الأدمن الأول لدور آخر --}}
                                      @if($user->id === 1 && optional($user->role)->name === 'admin' && $role->name !== 'admin')
                                          @continue
                                      @endif
                                     <option value="{{ $role->id }}" {{ old('role_id', $user->role_id) == $role->id ? 'selected' : '' }}>{{ $role->display_name }}</option>
                                 @endforeach
                              @endif
                         </select>
                         @if($user->id === 1 && optional($user->role)->name === 'admin')
                            {{-- // إضافة حقل مخفي لإرسال دور الأدمن إذا كان معطلاً --}}
                            <input type="hidden" name="role_id" value="{{ $user->role_id }}">
                            <small class="text-muted d-block">Primary admin role cannot be changed.</small>
                         @endif
                         @error('role_id', 'update_'.$user->id) <div class="invalid-feedback">{{ $message }}</div> @enderror
                     </div>
                    {{-- // كلمة المرور --}}
                    <hr>
                     <p class="text-muted small">Leave password fields blank if you do not want to change the password.</p>
                     <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="edit_user_password_{{ $user->id }}" class="form-label">New Password</label>
                            <input type="password" class="form-control @error('password', 'update_'.$user->id) is-invalid @enderror" id="edit_user_password_{{ $user->id }}" name="password">
                            @error('password', 'update_'.$user->id) <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="edit_user_password_confirmation_{{ $user->id }}" class="form-label">Confirm New Password</label>
                            <input type="password" class="form-control" id="edit_user_password_confirmation_{{ $user->id }}" name="password_confirmation">
                        </div>
                    </div>
                    {{-- // تأكيد/إلغاء تأكيد الإيميل --}}
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" id="edit_user_verify_email_{{ $user->id }}" name="verify_email" value="1" {{ old('verify_email', $user->email_verified_at ? true : false) ? 'checked' : '' }}>
                        <label class="form-check-label" for="edit_user_verify_email_{{ $user->id }}">
                            Mark email as verified
                        </label>
                    </div>
                     <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" id="edit_user_unverify_email_{{ $user->id }}" name="unverify_email" value="1" {{ old('unverify_email') ? 'checked' : '' }}>
                        <label class="form-check-label" for="edit_user_unverify_email_{{ $user->id }}">
                            Mark email as unverified
                        </label>
                        <small class="text-muted d-block">(Overrides the checkbox above if selected)</small>
                    </div>

                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Update User</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- // Modal لتأكيد الحذف --}}
{{-- // هذا هو الجزء الذي كان يسبب الخطأ، الآن هو داخل الشرط @if ($user) --}}
@if ($user->id !== 1) {{-- // لا نظهر مودال الحذف للأدمن الأول --}}
<div class="modal fade" id="deleteUserModal-{{ $user->id }}" tabindex="-1" aria-labelledby="deleteUserModalLabel-{{ $user->id }}" aria-hidden="true">
     <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title" id="deleteUserModalLabel-{{ $user->id }}">Confirm Deletion</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
             {{-- // تأكد من أن هذا الـ route معرف --}}
             <form action="{{ route('data-entry.users.destroy', $user->id) }}" method="POST">
                @csrf
                @method('DELETE')
                <div class="modal-body">
                    <p>Are you sure you want to delete the user: <strong>{{ $user->name }} ({{ $user->email }})</strong>?</p>
                    {{-- // استخدام optional() أو ?-> للوصول للعلاقات بأمان --}}
                    @if($user->instructor)
                        <p class="text-warning small"><strong>Warning:</strong> This user is linked to an instructor record ({{ optional($user->instructor)->instructor_no ?? 'N/A' }}). Deleting the user will set the `user_id` in the instructor record to NULL.</p>
                    @endif
                    <p class="text-danger small">This action cannot be undone.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">Yes, Delete User</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif {{-- // نهاية الشرط if ($user->id !== 1) --}}

@endif {{-- // نهاية الشرط if ($user) --}}
