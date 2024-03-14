@if (!$entry->executed)
    <a href="{{ url($crud->route.'/'.$entry->getKey().'/run-migration') }}" class="btn btn-sm btn-outline-success">
        <i class="la la-terminal"></i>
        <span>Migrate</span>
    </a>
@endif
