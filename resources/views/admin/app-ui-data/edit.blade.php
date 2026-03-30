@use(Diglactic\Breadcrumbs\Breadcrumbs)

@extends('admin.layouts.app', [
    'pageTitle' => Breadcrumbs::current()->title,
    'breadcrumbs' => Breadcrumbs::render('admin.app-ui-data.edit'),
])

@section('content')
    <div class="row">
        <div class="col-12 col-md-8 mx-auto">
            @include('admin.app-ui-data.form', [
                'action' => route('admin.app-ui-data.update', ['app_ui_datum' => $appUiData->id]),
                'method' => 'PUT',
                'appUiData' => $appUiData,
            ])
        </div>
    </div>
@endsection
