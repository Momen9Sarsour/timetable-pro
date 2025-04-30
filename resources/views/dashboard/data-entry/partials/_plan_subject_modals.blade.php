{{-- ================================= --}}
{{-- Modal لتأكيد حذف مادة من الخطة --}}
{{-- ================================= --}}
<div class="modal fade" id="deletePlanSubjectModal" tabindex="-1" aria-labelledby="deletePlanSubjectModalLabel" aria-hidden="true">
    <div class="modal-dialog">
       <div class="modal-content">
           <div class="modal-header bg-danger text-white">
               <h5 class="modal-title" id="deletePlanSubjectModalLabel">Confirm Subject Removal</h5>
               <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
           </div>
            {{-- *** تأكد من ID الفورم *** --}}
            <form id="deletePlanSubjectForm" action="#" method="POST">
               @csrf
               @method('DELETE')
               <div class="modal-body">
                    {{-- *** تأكد من ID الـ span *** --}}
                   <p>Are you sure you want to remove the subject <strong id="subjectNameToDelete">this subject</strong> from the plan?</p>
                   <p class="text-danger small">This action cannot be undone.</p>
               </div>
               <div class="modal-footer">
                   <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                   <button type="submit" class="btn btn-danger">Yes, Remove Subject</button>
               </div>
           </form>
       </div>
   </div>
</div>
