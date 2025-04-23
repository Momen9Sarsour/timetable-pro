@if (session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

@if (session('error'))
     <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="fas fa-exclamation-triangle me-2"></i>{{ session('error') }}
         <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

{{-- // عرض أخطاء الـ Validation بشكل عام إذا لم يتم عرضها تحت كل حقل --}}
@if ($errors->any() && !isset($hideGeneralErrors)) {{-- // متغير للتحكم بإظهار الأخطاء العامة --}}
     <div class="alert alert-danger alert-dismissible fade show" role="alert">
         <i class="fas fa-exclamation-circle me-2"></i> Please check the form for errors.
         <ul>
             @foreach ($errors->all() as $error)
                 <li>{{ $error }}</li>
             @endforeach
         </ul>
         <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif
