<div class="d-inline d-print-none with-border">
    <a href="{{ url($crud->route.'/migrate-rollback') }}" class="btn btn-outline-danger" onclick="return confirm('CAUTION! This will run a migration rollback, the last batch of migrations will be rolled back. Are you sure you want to do this?')">
        <span class="ladda-label"><i class="la la-terminal"></i> Migrate rollback</span>
    </a>
</div>