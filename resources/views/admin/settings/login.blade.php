@use(Diglactic\Breadcrumbs\Breadcrumbs)

@extends('admin.layouts.app', [
    'pageTitle' => Breadcrumbs::current()->title,
    'breadcrumbs' => Breadcrumbs::render('admin.settings.login'),
])

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <form action="">
                        <div class="row mb-3">
                            <div class="col-form-label">Panel Login</div>
                            <div>
                                <label class="form-check form-switch form-switch-lg fit-content">
                                    <input class="form-check-input" id="panelLoginSwitch" type="checkbox" role="button"
                                        @checked($settings->get('panel_login')?->value)>
                                    <span class="form-check-label" id="panelLoginSwitch_text" role="button">
                                    </span>
                                </label>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('script')
    <script>
        $(document).ready(function() {

            const api = "{{ route('admin.settings.login') }}";
            const settings = @js($settings);

            // Panel Login Switch Handle
            function panelLoginSwitchHandler() {
                function togglePanelLoginSwitchText(status) {
                    const on = `<span class="text-success">Panel Login is enabled for all.</span>`;
                    const off =
                        `<span class="text-danger">Panel Login is disabled for all (except for those with 'Bypass Disabled Login' permission).</span>`;
                    $('#panelLoginSwitch_text').html(status ? on : off);
                }
                togglePanelLoginSwitchText(Number(settings?.panel_login?.value));
                $('#panelLoginSwitch').on('change', function() {
                    const status = $(this).prop('checked');
                    $.ajax({
                        url: api,
                        method: 'POST',
                        beforeSend: () => overlayLoader.show(),
                        data: {
                            panel_login: status ? 1 : 0
                        },
                        success: (res) => {
                            togglePanelLoginSwitchText(status);
                        },
                        complete: () => overlayLoader.hide(),
                    });

                });
            }

            // calling handlers
            panelLoginSwitchHandler();


        });
    </script>
@endpush
