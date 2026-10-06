<?php

namespace App\Http\Controllers\Admin;

use App\Models\Redirect;
use Illuminate\Database\Eloquent\Model;

class RedirectController extends ResourceController
{
    protected string $model = Redirect::class;
    protected string $view = 'admin.redirects';
    protected string $route = 'admin.redirects';
    protected string $title = 'Redirect';
    protected array $searchable = ['from', 'to'];
    protected array $jsonFields = [];
    protected array $imageFields = [];
    protected array $boolFields = ['is_active'];
    protected string $orderBy = 'id';
    protected bool $sortable = false;

    protected function rules(?Model $item = null): array
    {
        return [
            'from' => ['required', 'string', 'max:255'],
            // A 410 ("gone") has no destination — it's not a redirect, just a
            // deliberate "this no longer exists" response — so `to` is only
            // required for the statuses that actually redirect somewhere.
            'to' => ['required_unless:status,410', 'nullable', 'string', 'max:255'],
            'status' => ['required', 'integer', 'in:301,302,307,308,410'],
        ];
    }
}
