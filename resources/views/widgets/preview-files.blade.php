@php
    $files = $widget['content'];
    $url = Str::of(URL::current())
            ->replaceLast('related-files/migration', 'related-files')
            ->replaceLast('related-files/model', 'related-files')
            ->replaceLast('related-files/seeder', 'related-files')
            ->replaceLast('related-files/factory', 'related-files')
            ->replaceLast('related-files/crud_controller', 'related-files')
            ->replaceLast('related-files/crud_request', 'related-files')
            ->replaceLast('related-files/crud_route', 'related-files')
            ->replaceLast('related-files/operation', 'related-files')
            ->replaceLast('related-files/sidebar_item', 'related-files');
@endphp

<div class="row" bp-section="devtools-preview-files">
  <div class="col-2">
    <div class="nav flex-column nav-pills" id="v-pills-tab" role="tablist" aria-orientation="vertical">
        @foreach($files as $key => $file)
            <a class="nav-link {{ $file?'':'disabled' }} {{ $key==$selected?'active':'' }}" id="v-pills-{{ $key }}-tab" data-toggle="pill" data-bs-toggle="pill" data-file="{{ $key }}" href="#v-pills-{{ $key }}" role="tab" aria-controls="v-pills-{{ $key }}" aria-selected="{{ $key==$selected?'true':'false' }}">{{ Str::of($key)->replace('_', ' ')->title() }}
                @if (is_array($file))
                <span class="badge badge-{{ $key==$selected?'light':'primary' }} badge-pill float-right float-end d-block" style="margin-top: 2px;">{{ count($file) }}</span>
                @foreach($file as $f)
                    @if (!empty($f) && $f->isClass() && !$f->isValid())
                        <span class="badge badge-warning badge-pill float-right float-end d-block" style="margin-top: 3px;" title="Syntax errors">!</span>
                    @endif
                @endforeach
                @else
                @if (!empty($file) && $file->isClass() && !$file->isValid())
                <span class="badge badge-warning badge-pill float-right float-end d-block" style="margin-top: 3px;" title="Syntax errors">!</span>
                @endif
                @endif
            </a>
        @endforeach
    </div>
  </div>
  <div class="col-10">
    <div class="tab-content border-0" id="v-pills-tabContent">
        @foreach($files as $key => $file)
            <div class="tab-pane fade p-0 {{ $key==$selected?'show active':'' }}"
                id="v-pills-{{ $key }}"
                role="tabpanel"
                aria-labelledby="v-pills-{{ $key }}-tab">
                @if ($file)
                    @if (is_array($file))
                        @foreach ($file as $item)
                            @include('backpack.devtools::widgets.partials.unknown-file-preview', ['item' => $item])

                            @if (!$loop->last)
                            <div class="bg-light text-center text-muted py-3"> & </div>
                            @endif
                        @endforeach
                    @else
                        @include('backpack.devtools::widgets.partials.unknown-file-preview', ['item' => $file])
                    @endif
                @else
                    File is missing.
                @endif
            </div>
        @endforeach
    </div>
  </div>
</div>

@php
    // ------------------------------------------------
    // Get the Migration and Model (as Eloquent models)
    // ------------------------------------------------
    if (isset($files['migration']) && !empty($files['migration'])) {
        $migration = \Backpack\DevTools\Models\Migration::where('file_path', $files['migration']->file_path)->first();
    } else {
        $migration = false;
    }

    if (isset($files['model']) && !empty($files['model'])) {
        $model = \Backpack\DevTools\Models\Model::where('file_path', $files['model']->file_path)->first();
    } else {
        $model = false;
    }

    if (isset($files['operation']) && !empty($files['operation'])) {
        $operation = \Backpack\DevTools\Models\Operation::where('file_path', $files['operation']->file_path)->first();
    } else {
        $operation = false;
    }
@endphp

@push('after_scripts')
<script>
    // update the URL when pills are clicked;
    // that way, if the dev clicks an action button, they'll be redirected
    // back to the correct tab;
    $('a[data-toggle="pill"]').on('shown.bs.tab', function (event) {
        var file = $(event.target).attr('data-file');
        var new_url = "{{ $url }}" + "/" + file;

        window.history.replaceState(window.history.state, '', new_url);
    });

    @if ($migration)
    $.ajax({
      method: "GET",
      url: "{{ backpack_url('devtools/migration/'.$migration->id.'/stripped-show') }}"
    })
      .done(function( msg ) {
        $('#v-pills-migration').html(msg);
        let codeElement = document.getElementById('v-pills-migration').querySelector('pre>code');
        Prism.highlightElement(codeElement);
      });
    @endif

    @if ($model)
    $.ajax({
      method: "GET",
      url: "{{ backpack_url('devtools/model/'.$model->id.'/stripped-show') }}"
    })
      .done(function( msg ) {
        $('#v-pills-model').html(msg);
        let codeElement = document.getElementById('v-pills-model').querySelector('pre>code');
        Prism.highlightElement(codeElement);
      });
    @endif

    @if ($operation)
    $.ajax({
        method: "GET",
        url: "{{ backpack_url('devtools/operation/'.$operation->id.'/stripped-show') }}"
    })
        .done(function( msg ) {
            $('#v-pills-model').html(msg);
            let codeElement = document.getElementById('v-pills-model').querySelector('pre>code');
            Prism.highlightElement(codeElement);
        });
    @endif
</script>
@endpush
