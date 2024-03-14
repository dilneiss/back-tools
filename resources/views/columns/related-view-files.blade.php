@php
    $value = data_get($entry, $column['name']);
@endphp

@foreach($value as $val)
    <div>
        @includeWhen(!empty($column['wrapper']), 'crud::columns.inc.wrapper_start')
        @if($val->file_path)
            <a
                    class="file-link"
                    href="{{ htmlentities(link_to_code_editor($val->file_path)) }}"
                    style="padding: .2em .4em; margin: 0; font-size: 95%; background-color: #1b1f230d; border-radius: 6px;"
            >
                {{ $val->file_path_from_base }}
                <i class="la la-external-link-alt"></i>
            </a>
            @if(request()->has('new'))
                <span class="badge badge-success">New</span>
            @endif
        @else
            -
        @endif
        @includeWhen(!empty($column['wrapper']), 'crud::columns.inc.wrapper_end')
    </div>
@endforeach
