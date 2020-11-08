<?php

namespace TeamTeaTime\Forum\Http\Requests;

use TeamTeaTime\Forum\Events\UserDeletedCategory;
use TeamTeaTime\Forum\Interfaces\FulfillableRequest;
use TeamTeaTime\Forum\Models\Category;

class DestroyCategory extends BaseRequest implements FulfillableRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('delete', $this->route('category'));
    }

    public function fulfill()
    {
        $category = $this->route('category');
        $category->delete();

        event(new UserDeletedCategory($this->user(), $category));

        return $category;
    }
}
