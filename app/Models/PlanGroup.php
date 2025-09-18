<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PlanGroup extends Model
{
    use HasFactory;

    // تحديد اسم الجدول
    // protected $table = 'plan_groups';

    // تحديد Primary Key
    protected $primaryKey = 'group_id';

    // الحقول المسموح بتعبئتها
    protected $fillable = [
        'plan_id',
        'plan_level',
        'academic_year',
        'semester',
        'branch',
        'section_id',
        'group_no',
        'group_size',
        'gender',
    ];

    // تحويل البيانات للأنواع المناسبة
    protected $casts = [
        'plan_level' => 'integer',
        'academic_year' => 'integer',
        'semester' => 'integer',
        'group_no' => 'integer',
        'group_size' => 'integer',
    ];

    /**
     * علاقة: المجموعة تنتمي لخطة واحدة
     */
    public function plan()
    {
        return $this->belongsTo(Plan::class, 'plan_id', 'id');
    }

    /**
     * علاقة: المجموعة تنتمي لشعبة واحدة
     */
    public function section()
    {
        return $this->belongsTo(Section::class, 'section_id', 'id');
    }

    /**
     * علاقة: المجموعة مرتبطة بقسم من خلال الخطة
     */
    public function department()
    {
        return $this->hasOneThrough(
            Department::class,
            Plan::class,
            'id', // Foreign key على جدول plans
            'id', // Foreign key على جدول departments
            'plan_id', // Local key على plan_groups
            'department_id' // Local key على plans
        );
    }

    /**
     * Scope: البحث حسب السياق الكامل
     */
    public function scopeByContext($query, $planId, $planLevel, $academicYear, $semester, $branch = null)
    {
        return $query->where('plan_id', $planId)
                    ->where('plan_level', $planLevel)
                    ->where('academic_year', $academicYear)
                    ->where('semester', $semester)
                    ->where('branch', $branch);
    }

    /**
     * Scope: البحث حسب الشعبة
     */
    public function scopeBySectionId($query, $sectionId)
    {
        return $query->where('section_id', $sectionId);
    }

    /**
     * Scope: البحث حسب رقم المجموعة
     */
    public function scopeByGroupNumber($query, $groupNo)
    {
        return $query->where('group_no', $groupNo);
    }

    /**
     * Scope: البحث حسب نشاط الشعبة (نظري/عملي)
     */
    public function scopeByActivityType($query, $activityType)
    {
        return $query->whereHas('section', function ($q) use ($activityType) {
            $q->where('activity_type', $activityType);
        });
    }

    /**
     * Accessor: الحصول على معرف السياق الفريد
     */
    public function getContextIdentifierAttribute()
    {
        return implode('-', [
            $this->plan_id,
            $this->plan_level,
            $this->plan_semester ?? $this->semester,
            $this->academic_year,
            $this->branch ?? 'default'
        ]);
    }

    /**
     * دالة لجلب كل المجموعات في سياق معين
     */
    public static function getGroupsForContext($planId, $planLevel, $academicYear, $semester, $branch = null)
    {
        return static::byContext($planId, $planLevel, $academicYear, $semester, $branch)
                    ->with(['section.planSubject.subject'])
                    ->orderBy('group_no')
                    ->get();
    }

    /**
     * دالة لجلب المجموعات لشعبة معينة
     */
    public static function getGroupsForSection($sectionId)
    {
        return static::bySectionId($sectionId)
                    ->orderBy('group_no')
                    ->get();
    }

    /**
     * دالة للحصول على أرقام المجموعات لشعبة معينة (للاستخدام في الخوارزمية)
     */
    public static function getGroupNumbersForSection($sectionId)
    {
        return static::bySectionId($sectionId)
                    ->pluck('group_no')
                    ->toArray();
    }

    /**
     * دالة لحذف كل مجموعات سياق معين
     */
    public static function clearContextGroups($planId, $planLevel, $academicYear, $semester, $branch = null)
    {
        return static::byContext($planId, $planLevel, $academicYear, $semester, $branch)->delete();
    }

    /**
     * دالة لحذف مجموعات شعبة معينة
     */
    public static function clearSectionGroups($sectionId)
    {
        return static::bySectionId($sectionId)->delete();
    }
}
