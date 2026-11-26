<?php

namespace App\Policies;

use App\Models\Content;
use App\Models\User;
use App\Services\ContentAccessService;

class ContentPolicy
{
    public function __construct(private ContentAccessService $access)
    {
    }

    public function watch(User $user, Content $content): bool
    {
        return $this->access->canWatch($user, $content);
    }
}
