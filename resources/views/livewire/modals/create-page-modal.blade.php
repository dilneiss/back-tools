<div id="livewire-create-page-modal">

    {{-- Modal Content --}}
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Create Page</h5>
                <button type="button" class="close btn-close" data-dismiss="modal" data-bs-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>

            <form wire:submit.prevent="formSubmit" onsubmit="createPageModalLoading()">
                <div class="modal-body">
                    <div style="display: grid; grid-template-columns: 1fr 3rem; grid-template-rows: repeat(2, 1fr);">
                        <div class="p-0 py-2">
                            <label>View name:</label>
                            <div class="input-group">
                                <div class="input-group-append">
                                    <span class="input-group-text">resources/views/</span>
                                </div>
                                <input autofocus name="name" required wire:model.live="name" class="form-control @if(isset($this->errors['view'])) is-invalid @endif" placeholder="page_name" pattern=".+\w$" title="Please provide a valid path/name." />
                                <div class="input-group-append">
                                    <span class="input-group-text">.blade.php</span>
                                </div>
                                <div class="invalid-feedback">
                                    {{ $this->errors['view'] ?? '' }}
                                </div>
                            </div>
                        </div>
                        <div class="p-0 py-2">
                            <label>Route:</label>
                            <div class="input-group">
                                <div class="input-group-append">
                                    <span class="input-group-text">{{ url($this->prefix) }}/</span>
                                </div>
                                <input name="route" wire:model.live="route" class="form-control @if(isset($this->errors['route'])) is-invalid @endif" />
                                <div class="invalid-feedback">
                                    {{ $this->errors['route'] ?? '' }}
                                </div>
                            </div>
                        </div>
                        <div class="text-{{ $routeEdited ? 'black-50' : 'primary' }}" style="grid-row: 1 / 3; grid-column: 2;">
                            <span class="d-block position-abolsute" style="border-left: 2px solid; height: 55%; transform: translate(1.8rem, 3.5rem)">
                                <span class="position-absolute rounded-circle text-center bg-white" style="top: 50%; border: 2px solid; width: 1.5rem; z-index: 1; transform: translate(-50%, -50%); aspect-ratio: 1; {{ $this->routeEdited ? 'cursor: pointer;' : '' }}" wire:click="resetRoute">
                                    <i class="las la-lock{{ $routeEdited ? '-open' : '' }}"></i>
                                </span>
                            </span>
                        </div>
                    </div>
                </div>

                @if ($this->preview)
                <div class="preview">
                    <div class="jumbotron mx-3 px-4 py-3 shadow">
                        @foreach(['view', 'url', 'controller'] as $value)
                        <label class="mb-0 text-black-50 text-uppercase font-weight-bold font-sm">{{ ucfirst($value) }}</label>
                        <p class="font-sm text-monospace text-primary" style="overflow-wrap: break-word;">{{ $preview[$value] ?? '' }}</p>
                        @endforeach
                    </div>
                </div>
                @endif

                <div class="modal-footer">
                    <button type="reset" class="btn btn-secondary" data-dismiss="modal" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="d-flex align-items-center btn btn-primary" @disabled(!$preview || count($this->errors))><i class="la la-circle-o-notch la-spin d-none"></i>Create</button>
                </div>
            </form>
        </div>
    </div>
</div>
@push('after_scripts')
    <script>
        document.addEventListener('livewire:initialized', () => {
        let $modal = $('#livewire-create-page-modal');
        let modalButton = $modal.find('[type="submit"]').get(0);

        // focus first input on modal show
        $modal.on('shown.bs.modal', () => $modal.find('input').first().focus());

        $modal.on('hide.bs.modal', () => @this.initModal());

        // listen for
        window.addEventListener('create-page-modal', (event) => {
            new Noty({
                type: event.detail[0].success ? 'success' : 'error',
                text: `<strong>${event.detail[0].title}</strong><br>${event.detail[0].message}`,
            }).show();

            if (event.detail[0].success) {
                $modal.modal('hide');
            }

            createPageModalLoading(false);
        });

        // toggle modal loading state
        window.createPageModalLoading = (loading = true) => {
            modalButton.firstChild.classList.toggle('d-none', !loading);
            loading ? modalButton.setAttribute('disabled', true) : modalButton.removeAttribute('disabled');
        };
    });
    </script>
@endpush
