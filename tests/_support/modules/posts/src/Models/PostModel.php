<?php

declare(strict_types=1);

namespace Tests\Support\Modules\Posts\Models;

use CodeIgniter\Model;

class PostModel extends Model
{
    protected $table            = 'posts';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = true;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'category_id',
        'title',
        'slug',
        'excerpt',
        'content',
        'featured_image',
        'status',
        'published_at',
        'author_id',
        'views_count',
    ];
    protected bool $allowEmptyInserts = false;
    protected bool $updateOnlyChanged = true;
    protected array $casts            = [
        'id'          => 'integer',
        'author_id'   => 'integer',
        'views_count' => 'integer',
    ];

    // Dates
    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'deleted_at';

    // Validation
    protected $validationRules = [
        'title'   => 'required|max_length[255]',
        'slug'    => 'required|max_length[255]|is_unique[posts.slug,id,{id}]',
        'content' => 'required',
        'status'  => 'required|in_list[draft,published,archived]',
    ];
    protected $validationMessages = [
        'title' => [
            'required' => 'Post title is required',
        ],
        'slug' => [
            'required'  => 'Post slug is required',
            'is_unique' => 'This slug is already in use',
        ],
    ];

    /**
     * Get published posts
     */
    public function getPublished(int $limit = 10): array
    {
        return $this->where('status', 'published')
            ->where('published_at <=', date('Y-m-d H:i:s'))
            ->orderBy('published_at', 'DESC')
            ->limit($limit)
            ->find();
    }
}
