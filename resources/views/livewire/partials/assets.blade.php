@push('before_styles')
    @livewireStyles
@endpush

@push('after_scripts')
    @livewireScripts

    <script>
        window.addEventListener('focus', (e) => {
            window.Livewire.dispatch('updateModelList')
        });
    </script>

    @include('backpack.devtools::livewire.components.modal', [
        'componentName' => 'create-page-modal',
    ])
    @include('backpack.devtools::livewire.components.modal', [
        'componentName' => 'publish-modal',
    ])
@endpush