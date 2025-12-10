<?php

declare(strict_types=1);

namespace Tests\Support\Modules\Posts\Controllers;

use App\Controllers\BaseController;
use Tests\Support\Modules\Posts\Models\PostModel;

class PostsController extends BaseController
{
    private readonly PostModel $postModel;

    public function __construct()
    {
        $this->postModel = new PostModel();
    }

    public function api()
    {
        $posts = $this->postModel->getPublished(20);

        return $this->response->setJSON([
            'success' => true,
            'data'    => $posts,
        ]);
    }
}
