<?php

namespace App\Models\Project;

use Illuminate\Database\Eloquent\Model;

class ProjectDocument extends Model
{
    protected $table = 'nx_project_documents';

    protected $fillable = [
        'nx_project_id',
        'document_name',
        'file_path',
        'document_type',
        'notes'
    ];

    public function project()
    {
        return $this->belongsTo(Project::class, 'nx_project_id');
    }
}
