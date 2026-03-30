@use(Diglactic\Breadcrumbs\Breadcrumbs)

@extends('admin.layouts.app', [
    'pageTitle' => Breadcrumbs::current()->title,
    'breadcrumbs' => Breadcrumbs::render('admin.app-ui-data.create'),
])

@section('content')
    <div class="row">
        <div class="col-12 col-md-8 mx-auto">
            @include('admin.app-ui-data.form', ['action' => route('admin.app-ui-data.store')])
        </div>
    </div>
@endsection
