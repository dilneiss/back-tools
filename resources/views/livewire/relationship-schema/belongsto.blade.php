@include('backpack.devtools::livewire.relationship-schema._partial_model')

<div class="form-group col-md-3 mb-1 px-1">
    <label class="mb-1">Column</label>
    @if(isset($relationship['created_by_column']) && $relationship['created_by_column'])
    <input
        type="text"
        name="relationships[{{ $relationship_index }}][relationship_column]"
        wire:model="relationships.{{ $relationship_index }}.relationship_column"
        class="form-control"
        readonly />
    @else
        @if(!$relationship['relationship_column'])
        <button
            class="d-inline-block ml-1 ms-1 btn btn-sm btn-link"
            href="#"
            wire:click.prevent="addNewRelationshipColumn({{$relationship_index}})">
            + Create Column
        </button>
        @endif

        @if(!empty($current_available_columns_for_belongs_to) || $relationship['relationship_column'])
        <select
            name="relationships[{{ $relationship_index }}][relationship_column]"
            wire:model="relationships.{{ $relationship_index }}.relationship_column"
            class="form-control">
            @if($relationship['relationship_column'])
            <option value="{{$relationship['relationship_column']}}">{{$relationship['relationship_column']}}</option>
            @endif
            @if(!empty($current_available_columns_for_belongs_to))
            @foreach ($current_available_columns_for_belongs_to as $index => $column)
            <option value="{{$column['column_name']}}">{{$column['column_name']}}</option>
            @endforeach
            @endif
        </select>
        @else
            <br />
            <span> No valid columns to choose from.</span>

            @if($errors->any() && $errors->getBag('default')->has('relationships.'.$relationship_index.'.relationship_column'))
            <br />
            <span class="text-danger"> * {{ $errors->getBag('default')->first('relationships.'.$relationship_index.'.relationship_column') }} </span>
            @endif
        @endif
    @endif
</div>

<div class="form-group col-md-3 mb-1 px-1">
    <label class="mb-1">Relation Name</label>

    <input
        type="text"
        name="relationships[{{ $relationship_index }}][relationship_relation_name]"
        wire:model="relationships.{{ $relationship_index }}.relationship_relation_name"
        class="form-control"
        />
</div>
