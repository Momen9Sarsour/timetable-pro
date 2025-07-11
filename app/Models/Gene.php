<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Gene extends Model
{
    use HasFactory;

    // protected $table = 'genes';
    protected $primaryKey = 'gene_id';

    // الحقول المسموح بتعبئتها
    protected $fillable = [
        'chromosome_id',
        'section_id',
        'instructor_id',
        'room_id',
        'timeslot_id', // هذا يربط بجدول timeslots الأساسي
    ];

    /**
     * علاقة: الجين يتبع لكروموسوم واحد.
     */
    public function chromosome()
    {
        // علاقة "واحد إلى واحد" مع جدول Chromosome
        return $this->belongsTo(Chromosome::class, 'chromosome_id', 'chromosome_id');
    }

    /**
     * علاقة: الجين يمثل شعبة واحدة.
     */
    public function section()
    {
        // علاقة "واحد إلى واحد" مع جدول Section
        return $this->belongsTo(Section::class, 'section_id', 'id');
    }

    /**
     * علاقة: الجين له مدرس واحد.
     */
    public function instructor()
    {
        // علاقة "واحد إلى واحد" مع جدول Instructor
        return $this->belongsTo(Instructor::class, 'instructor_id', 'id');
    }

    /**
     * علاقة: الجين له قاعة واحدة.
     */
    public function room()
    {
        // علاقة "واحد إلى واحد" مع جدول Room
        return $this->belongsTo(Room::class, 'room_id', 'id');
    }

    /**
     * علاقة: الجين له فترة زمنية واحدة (من الجدول الأساسي).
     */
    public function timeslot()
    {
        // علاقة "واحد إلى واحد" مع جدول Timeslot
        return $this->belongsTo(Timeslot::class, 'timeslot_id', 'id');
    }

    public function algorithmTimeslot()
    {
        return $this->hasOne(Timeslot::class, 'gene_id', 'gene_id');
    }
}
