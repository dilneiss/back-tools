{{-- regular object attribute --}}
@php
	$value = $value ?? data_get($entry, $column['name']);
    $value = is_array($value) ? json_encode($value) : $value;

    $column['theme'] = $column['theme'] ?? 'dark';
    $column['prefix'] = $column['prefix'] ?? '';
    $column['suffix'] = $column['suffix'] ?? '';
    $column['text'] = $column['prefix'] . $value . $column['suffix'];
@endphp

<span>
    @includeWhen(!empty($column['wrapper']), 'crud::columns.inc.wrapper_start')
    <pre class="m-0 language-php line-numbers"><code class="language-php">{{ $column['text'] }}</code></pre>
    @includeWhen(!empty($column['wrapper']), 'crud::columns.inc.wrapper_end')
</span>

@push('after_scripts')
@basset('https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/prism.min.js')
@basset('https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/components/prism-php.min.js')
@basset('https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/components/prism-markup-templating.min.js')
@basset('https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/plugins/autoloader/prism-autoloader.min.js')
@basset('https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/plugins/line-numbers/prism-line-numbers.min.js')
@endpush

@push('after_styles')
@if($column['theme'] === 'dark')
@basset('https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/themes/prism-tomorrow.min.css')
@else
@basset('https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/themes/prism.min.css')
@endif
@basset('https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/plugins/line-numbers/prism-line-numbers.min.css')

@bassetBlock('code-column-css')
<style>
    pre.language-php {
        font-size: 85%;
        border-radius: 5px;
    }
</style>
@endBassetBlock
@endpush