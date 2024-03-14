<div id="livewire-publish-modal">

    {{-- Modal Content --}}
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Create {{ ucfirst($selectedFileType) }}</h5>
                <button type="button" class="close btn-close" data-dismiss="modal" data-bs-dismiss="modal" aria-label="Close">
                </button>
            </div>

            <form wire:submit.prevent="formSubmit" onsubmit="publishModalLoading()">
                <div class="modal-body">
                    {{-- Create --}}
                    <div>
                        <span class="d-inline-block rounded-circle text-center text-primary border-primary" style="border: 2px solid; width: 1.7rem; line-height: 1.4rem; aspect-ratio: 1;">A</span>
                        <h5 class="mb-0 ml-1 ms-1 d-inline-block">
                            <button type="button" wire:click="changeMode('create')" class="btn btn-link p-0 m-0">
                                Create a new {{ $selectedFileType }}.
                            </button>
                        </h5>

                        @if($mode === 'create')
                        <div class="border-left border-start pl-3 ps-3 my-2 p-0 py-2" style="margin: 0 0.8rem;">
                            <label>Pick a name for the new {{ $selectedFileType }}:</label>
                            <input autofocus id="inputName" name="inputName" required wire:model="inputName" class="form-control" id="file-to-create" placeholder="test{{ ucfirst($selectedFileType) }}" />
                        </div>
                        @endif
                    </div>

                    {{-- hr --}}
                    <div class="position-relative py-3 text-center">
                        <hr class="m-0">
                        <label class="position-absolute py-1 px-2 bg-white" style="top: 0">or</label>
                    </div>

                    {{-- Publish --}}
                    <div @if(!count($visibleOptions)) style="opacity: 0.5;" title="No {{ Str::of($selectedFileType)->plural() }} available" @endif>
                        <span class="d-inline-block rounded-circle text-center text-primary border-primary" style="border: 2px solid; width: 1.7rem; line-height: 1.4rem; aspect-ratio: 1;">B</span>
                        <h5 class="mb-0 ml-1 ms-1 d-inline-block">
                            <button type="button" wire:click="changeMode('publish')" class="btn btn-link p-0 m-0" @if(!count($visibleOptions)) style="pointer-events: none;" @endif>
                                Publish an existing {{ $selectedFileType }}.
                            </button>
                        </h5>

                        @if($mode === 'publish')
                        <div class="border-left border-start pl-3 ps-3 my-2 p-0 py-2" style="margin: 0 0.8rem;">
                            <label>Select an existing {{ $selectedFileType }} to publish:</label>
                            <select autofocus id="selectedFile" name="selectedFile" wire:model="selectedFile" class="form-control" id="file-to-publish">
                                {{-- <option value="">-</option> --}}
                                @foreach($visibleOptions as $project => $options)
                                <optgroup label="{{ $project }}" class="text-uppercase">
                                    @foreach($options as $key => $option)
                                    <option value="{{ $key }}" wire:key="file_option_{{ $loop->index }}" style="text-transform: initial;">{{ $option }}</option>
                                    @endforeach
                                </optgroup>
                                @endforeach
                            </select>
                            <div class="text-muted font-sm mt-4">
                                <p>
                                    This will copy-paste the blade file from the Backpack package to your <code>{{ $selectedFileTypePath }}</code>,
                                    where you can customize it to fit your needs. Backpack will automatically use the published file if present.
                                </p>
                                <p>
                                    Take into consideration that by publishing (aka overriding) a blade file, you will no longer get the updates for that blade
                                    file when you do <code>composer update</code>. For an easy-to-upgrade admin panel, it's recommended that you override blade
                                    files as little as possible. In most cases, it would be better to <i>rename</i> the file after it's been published,
                                    and only use it inside the Controllers/Views where strictly needed.
                                </p>
                            </div>
                        </div>
                        @endif
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="reset" class="btn btn-secondary" data-dismiss="modal" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="d-flex align-items-center btn btn-primary" action="{{ $mode }}"><i class="la la-circle-o-notch la-spin d-none"></i>{{ ucfirst($mode) }}</button>
                </div>
            </form>
        </div>
    </div>
</div>
@push('after_scripts')
    <script>
        document.addEventListener('livewire:initialized', () => {
        let $modal = $('#livewire-publish-modal');
        let modalButton = $modal.find('[type="submit"]').get(0);

        // when a menu item that triggers this modal is clicked, set
        // the value of selectedFileType to what the menu intended 
        // so that the dropdown gets populated with the blade
        // files for that type of file
        $("#devToolsNavBar a.dropdown-item[data-bs-target='#livewire-publish-modal']").click(function() {
            @this.initModal(this.dataset.fileType);
        });

        // focus first input on modal show
        $modal.on('shown.bs.modal', () => $modal.find('input').focus());

        // reset modal on close
        $modal.on('hidden.bs.modal', () => @this.set('mode', 'create'));

        // focus input on mode change
        window.addEventListener('publish-modal-mode', () => $modal.find('input, select').focus());

        // listen for
        window.addEventListener('publish-modal', (event) => {
            new Noty({
                type: event.detail[0].success ? 'success' : 'error',
                text: `<strong>${event.detail[0].title}</strong><br>${event.detail[0].message}`,
            }).show();

            if (event.detail[0].success) {
                $modal.modal('hide');
            }

            publishModalLoading(false);
        });

        // toggle modal loadiong state
        window.publishModalLoading = (loading = true) => {
            modalButton.firstChild.classList.toggle('d-none', !loading);
            loading ? modalButton.setAttribute('disabled', true) : modalButton.removeAttribute('disabled');
        }
    });
</script>
@endpush

