<?php

namespace App\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\Auth;

final class OwnNotesScope implements Scope
{
    #[\Override]
    public function apply(Builder $builder, Model $model)
    {
        // Without a signed-in user there is no owner to match, so nothing matches: a
        // query outside a request, or on a route that lacks its auth middleware, finds
        // no notes instead of everyone's. Code that really means notes regardless of who
        // is signed in says so with withoutGlobalScope(OwnNotesScope::class).
        if (Auth::check()) {
            $builder->where($model->qualifyColumn('user_id'), Auth::id());

            return;
        }

        $builder->whereRaw('1 = 0');
    }
}
