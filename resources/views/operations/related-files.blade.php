@extends(backpack_view('blank'))

@php
  $defaultBreadcrumbs = [
    trans('backpack::crud.admin') => url(config('backpack.base.route_prefix'), 'dashboard'),
    $crud->entity_name_plural => url($crud->route),
    trans('backpack::crud.preview') => false,
  ];

  // if breadcrumbs aren't defined in the CrudController, use the default breadcrumbs
  $breadcrumbs = $breadcrumbs ?? $defaultBreadcrumbs;

    Widget::add()
        ->to('after_content')
        ->type('view')
        ->view('backpack.devtools::widgets.preview-files')
        ->content(CRUD::getCurrentEntry()->getRelatedFiles());
@endphp

@section('header')
    <section class="header-operation container-fluid animated fadeIn d-flex mb-2 align-items-end" bp-section="page-header">
      <h1 bp-section="page-heading" class="text-capitalize mb-0">
        {{ $crud->entity_name_plural }}
      </h1>
      <p class="ms-2 ml-2 mb-2" bp-section="page-subheading">
        {!! 'Files related to the "'.$entry->file_name.'" model' !!}.
      </p>
      @if ($crud->hasAccess('list'))
      <p class="ms-2 ml-2 mb-2" bp-section="page-subheading-back-button">
        <small class=""><a href="{{ url($crud->route) }}" class="font-sm"><i class="la la-angle-double-left"></i> {{
            trans('backpack::crud.back_to_all') }} <span>{{ $crud->entity_name_plural }}</span></a></small>
      </p>
      @endif
    </section>
@endsection

@section('content')

@endsection


@section('after_styles')
  @basset(base_path('vendor/backpack/crud/src/resources/assets/css/common.css'))
@endsection

@section('after_scripts')
  @basset(base_path('vendor/backpack/crud/src/resources/assets/js/common.js'))
@endsection
