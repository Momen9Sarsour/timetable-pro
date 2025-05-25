// // هذه الروابط قد تحتاج لتعديل لتناسب عرض الشعب حسب الخطة أو القسم
        // Route::get('/sections', [SectionController::class, 'index'])->name('sections.index');
        // Route::post('/sections', [SectionController::class, 'store'])->name('sections.store');
        // Route::put('/sections/{section}', [SectionController::class, 'update'])->name('sections.update');
        // Route::delete('/sections/{section}', [SectionController::class, 'destroy'])->name('sections.destroy');
        // Route::resource('sections', SectionController::class)->except(['create', 'show', 'edit']);
        // Route::get('/sections', [SectionController::class, 'index'])->name('sections.index'); // صفحة العرض والفلاتر
        // Route::get('/sections/manage', [SectionController::class, 'manage'])->name('sections.manage');
        // // رابط تخزين شعبة جديدة (من المودال في صفحة manage)
        // Route::post('/sections', [SectionController::class, 'store'])->name('sections.store');
        // // رابط تحديث شعبة موجودة (من المودال في صفحة manage)
        // Route::put('/sections/{section}', [SectionController::class, 'update'])->name('sections.update');
        // // رابط حذف شعبة موجودة (من المودال في صفحة manage)
        // Route::delete('/sections/{section}', [SectionController::class, 'destroy'])->name('sections.destroy');
        // Route::get('/sections/manage/', [SectionController::class, 'manage'])->name('sections.manage');

        // Route::get('/sections', [SectionController::class, 'index'])->name('sections.index');
        // // روت لصفحة التحكم + تشغيل التقسيم إذا لزم الأمر
        // Route::get('/sections/manage', [SectionController::class, 'manageSectionsForSubjectContext'])->name('sections.manage'); // اسم دالة جديد
        // // روت لتشغيل التقسيم عبر الزر (سيستقبل البارامترات من الفورم)
        // Route::post('/sections/generate-for-subject-context', [SectionController::class, 'generateSectionsForSubjectContextButton'])->name('sections.generateForSubjectContext'); // اسم دالة جديد

        // // Route::get('/sections/manage', [SectionController::class, 'manage'])->name('sections.manage');
        // // // روت لتشغيل التقسيم يدوياً عبر الزر
        // // Route::post('/sections/generate', [SectionController::class, 'generateSectionsFromButton'])->name('sections.generateFromButton');
        // // CRUD للشعب
        // Route::post('/sections', [SectionController::class, 'store'])->name('sections.store');
        // Route::put('/sections/{section}', [SectionController::class, 'update'])->name('sections.update');
        // Route::delete('/sections/{section}', [SectionController::class, 'destroy'])->name('sections.destroy');

        // --- Sections Management ---
        // 1. صفحة عرض كل الشعب مع الفلاتر
        Route::get('/sections', [SectionController::class, 'index'])->name('sections.index');

        // 2. صفحة إدارة شعب مادة معينة في سياق (تستقبل البارامترات كـ query string)
        Route::get('/sections/manage-subject-context', [SectionController::class, 'manageSubjectContext'])->name('sections.manageSubjectContext');

        // 3. روت لتشغيل التوليد الآلي لشعب مادة معينة في سياق (يستقبل البارامترات من الفورم)
        Route::post('/sections/generate-for-subject', [SectionController::class, 'generateForSubject'])->name('sections.generateForSubject');

        // 4. روابط CRUD للشعب (ستُستخدم من صفحة التحكم التفصيلي)
        Route::post('/sections', [SectionController::class, 'store'])->name('sections.store'); // إضافة شعبة جديدة
        Route::put('/sections/{section}', [SectionController::class, 'update'])->name('sections.update'); // تعديل شعبة موجودة
        Route::delete('/sections/{section}', [SectionController::class, 'destroy'])->name('sections.destroy'); // حذف شعبة موجودة


        // --- الروابط الجديدة للتحكم بالشعب من سياق ExpectedCount ---
        // روت لعرض صفحة التحكم التفصيلي (يستقبل ID سجل الأعداد المتوقعة)
        Route::get('/sections/manage-context/{expectedCount}', [SectionController::class, 'manageSectionsForContext'])->name('sections.manageContext');
        // روت لتشغيل التقسيم الآلي من الزر
        Route::post('/sections/generate-for-context/{expectedCount}', [SectionController::class, 'generateSectionsForContextButton'])->name('sections.generateForContext');
        // روابط CRUD للشعب داخل هذا السياق (ستُستخدم من المودالات في صفحة manageContext)
        Route::post('/sections/store-in-context/{expectedCount}', [SectionController::class, 'storeSectionInContext'])->name('sections.storeInContext');
        Route::put('/sections/update-in-context/{section}', [SectionController::class, 'updateSectionInContext'])->name('sections.updateInContext'); // {section} هنا هو section_id
        Route::delete('/sections/destroy-in-context/{section}', [SectionController::class, 'destroySectionInContext'])->name('sections.destroyInContext');
