<div class="container-fluid">

    @if (!app()->environment('local'))
    <nav class="navbar rounded alert-warning justify-content-center mb-2">
        <span class="text-center text-error font-weight-bold mr-3 me-3">Do not install DevTools in production. Have your deploy script run <code class="text-primary">composer install --no-dev</code> instead.</span>
    </nav>
    @endif

    <nav class="navbar navbar-expand-lg navbar-dark bg-dark rounded mb-2" style="border-radius: 15rem!important;border: 1px solid #ffffff22;">
        <a class="navbar-brand ms-3" href="{{ backpack_url('devtools') }}">
            <svg width="32" height="32" viewBox="0 0 32 32" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M0 16C0 7.16344 7.16344 0 16 0V0C24.8366 0 32 7.16344 32 16V16C32 24.8366 24.8366 32 16 32V32C7.16344 32 0 24.8366 0 16V16Z" fill="url(#paint0_linear_203_99)" /><path d="M15.6475 6.44161V18.8139C15.6475 18.9778 15.566 19.1268 15.4346 19.2041L11.5855 21.457C11.3194 21.6116 11 21.3999 11 21.0668V9.27609C11 8.89878 11.1813 8.54907 11.4774 8.35949L15.047 6.06061C15.3132 5.88944 15.6458 6.1011 15.6458 6.44161H15.6475ZM12.0646 23.2608L16.2247 25.9186C16.3944 26.0271 16.6056 26.0271 16.7753 25.9186L20.6576 23.4375C20.9271 23.2644 20.9221 22.8319 20.6477 22.6681L16.5141 20.1999C16.3528 20.1042 16.1582 20.1023 15.9952 20.1981L12.0795 22.4896C11.8018 22.6515 11.7951 23.0878 12.0662 23.2608H12.0646ZM22 21.4662V16.0862C22 15.8488 21.8852 15.6297 21.7006 15.512L17.3758 12.7511C17.1096 12.5818 16.7786 12.7934 16.7786 13.1339V18.6943C16.7786 18.9391 16.9 19.1618 17.093 19.2778L21.4128 21.8564C21.679 22.0147 22 21.8012 22 21.468V21.4662Z" fill="white" /><defs><linearGradient id="paint0_linear_203_99" x1="25.9088" y1="26.5094" x2="-1.32976" y2="-0.600534" gradientUnits="userSpaceOnUse"><stop stop-color="#FF9900" /><stop offset="1" stop-color="#FFDE2E" /></linearGradient></defs></svg>
        </a>
        <button class="navbar-toggler" type="button" data-toggle="collapse" data-bs-toggle="collapse" data-target="#devToolsNavBar" data-bs-target="#devToolsNavBar" aria-controls="devToolsNavBar" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="devToolsNavBar">
            <ul class="navbar-nav mr-auto me-auto mt-2 mt-lg-0">
                <li class="nav-item">
                    <a class="nav-link text-warning" href="{{ backpack_url('devtools/model') }}">Models <span class="sr-only">(current)</span></a>
                </li>
                <li class="nav-item">
                    <a class="nav-link text-warning" href="{{ backpack_url('devtools/migration') }}">Migrations <span class="sr-only">(current)</span></a>
                </li>
                <li class="nav-item dropdown">
                    <a class="nav-link text-warning" href="#" id="newNavbarDropdown" role="button" data-toggle="dropdown" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                        Backpack {{-- <i class="la la-caret-down"></i> --}}
                    </a>
                    <div class="dropdown-menu" aria-labelledby="newNavbarDropdown">

                        <div class="dropdown-header"><strong>Create:</strong></div>
                        @if(config('backpack.devtools.show_future_features'))
                            <a class="dropdown-item disabled" href="#">CRUD</a>
                        @endif
                        <a class="dropdown-item" href="#" data-toggle="modal" data-bs-toggle="modal" data-target="#livewire-create-page-modal" data-bs-target="#livewire-create-page-modal" data-file-type="page">Page</a>
                        <a class="dropdown-item" href="{{ backpack_url('devtools/operation/create') }}">Operation</a>
                        <a class="dropdown-item" href="#" data-toggle="modal" data-bs-toggle="modal" data-target="#livewire-publish-modal" data-bs-target="#livewire-publish-modal" data-file-type="button">Button</a>
                        <a class="dropdown-item" href="#" data-toggle="modal" data-bs-toggle="modal" data-target="#livewire-publish-modal" data-bs-target="#livewire-publish-modal" data-file-type="column">Column</a>
                        <a class="dropdown-item" href="#" data-toggle="modal" data-bs-toggle="modal" data-target="#livewire-publish-modal" data-bs-target="#livewire-publish-modal" data-file-type="field">Field</a>
                        <a class="dropdown-item" href="#" data-toggle="modal" data-bs-toggle="modal" data-target="#livewire-publish-modal" data-bs-target="#livewire-publish-modal" data-file-type="filter">Filter</a>
                        <a class="dropdown-item" href="#" data-toggle="modal" data-bs-toggle="modal" data-target="#livewire-publish-modal" data-bs-target="#livewire-publish-modal" data-file-type="widget">Widget</a>
                        @if(config('backpack.devtools.show_future_features'))
                            <a class="dropdown-item disabled" href="#">Add-on</a>
                        @endif

                        <div class="dropdown-header"><strong>List:</strong></div>
                        @if(config('backpack.devtools.show_future_features'))
                        <a class="dropdown-item disabled" href="#">CRUDs</a>
                        <a class="dropdown-item disabled" href="#">Pages</a>
                        @endif
                        <a class="dropdown-item" href="{{ backpack_url('devtools/operation') }}">Operations</a>
                    </div>
                </li>
            </ul>
            {{-- <form class="form-inline my-2 mr-lg-0 ml-lg-4 ms-lg-4">
                <input class="form-control mr-sm-2 me-sm-2" type="search" placeholder="Search" aria-label="Search">
                <button class="btn btn-outline-default my-2 my-sm-0" type="submit">Search</button>
            </form> --}}
        </div>
    </nav>
</div>

@include('backpack.devtools::livewire.partials.assets')
