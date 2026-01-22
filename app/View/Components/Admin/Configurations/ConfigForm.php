<?php

namespace App\View\Components\Admin\Configurations;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class ConfigForm extends Component
{
    public string $id;
    public string $action;
    public string $method;
    public string $saveLabel;
    public bool $validate;
    public string $autocomplete;

    /**
     * Create a new component instance.
     */
    public function __construct(
        string $id,
        ?string $action = null,
        string $method = 'POST',
        string $saveLabel = 'Save',
        bool $validate = true,
        string $autocomplete = 'off'
    ) {
        $this->id = $id;
        $this->action = $action ?? url()->current();
        $this->method = $method;
        $this->saveLabel = $saveLabel;
        $this->validate = $validate;
        $this->autocomplete = $autocomplete;
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.admin.configurations.config-form');
    }
}
