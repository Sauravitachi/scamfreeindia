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
                    <form action="" onsubmit="event.preventDefault();">
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <div class="col-form-label fw-bold">Panel Login</div>
                                <div>
                                    <label class="form-check form-switch form-switch-lg fit-content">
                                        <input class="form-check-input" id="panelLoginSwitch" type="checkbox" role="button"
                                            @checked($settings->get('panel_login')?->value)>
                                        <span class="form-check-label" id="panelLoginSwitch_text" role="button">
                                        </span>
                                    </label>
                                </div>
                            </div>
                            <div class="col-md-6 d-flex align-items-center justify-content-md-end mt-3 mt-md-0">
                                <div class="alert alert-info py-2 px-3 mb-0 d-inline-flex align-items-center gap-2 small">
                                    <i class="ti ti-info-circle"></i>
                                    <span>Your Current IP: </span>
                                    <strong class="cursor-pointer text-primary" id="copyCurrentIp" title="Click to copy">{{ request()->ip() }}</strong>
                                </div>
                            </div>
                        </div>

                        <hr class="my-4">

                        <div class="row">
                            <div class="col-12 mb-3">
                                <h5 class="fw-semibold text-primary d-flex align-items-center">
                                    <i class="ti ti-shield-lock me-2 fs-5"></i>
                                    Role-Based IP Login Restrictions
                                </h5>
                                <p class="text-muted small">
                                    Configure allowed IP addresses on a per-role basis. Leave a role's input blank to allow access from any IP address.
                                </p>
                            </div>

                            @foreach ($roles as $role)
                                <div class="col-md-6 mb-3">
                                    <div class="card border border-light shadow-sm h-100">
                                        <div class="card-body p-3 d-flex flex-column justify-content-between">
                                            <div>
                                                <div class="d-flex align-items-center justify-content-between mb-2">
                                                    <span class="fw-bold text-dark text-capitalize fs-6">{{ $role->name }}</span>
                                                    <span class="badge bg-light text-secondary border px-2 py-1 small">Role ID: {{ $role->id }}</span>
                                                </div>
                                                <textarea
                                                    class="form-control allowed-ips-textarea"
                                                    data-role-id="{{ $role->id }}"
                                                    rows="3"
                                                    placeholder="e.g. 192.168.1.1, 49.36.10.2"
                                                >{{ old('allowed_ips_' . $role->id, is_array($role->allowed_ips) ? implode(',', $role->allowed_ips) : $role->allowed_ips) }}</textarea>
                                            </div>
                                            <div class="form-text small text-muted mt-2">
                                                <i class="ti ti-info-circle me-1"></i> Comma separated IPs
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
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
                            if (typeof toastr !== 'undefined') {
                                toastr.success('Panel Login status updated!');
                            }
                        },
                        complete: () => overlayLoader.hide(),
                    });

                });
            }

            // Role IP Textarea handle
            function allowedIpsHandle() {
                $('.allowed-ips-textarea').on('change', function() {
                    const $textarea = $(this);
                    const roleId = $textarea.data('role-id');
                    const value = $textarea.val();

                    $.ajax({
                        url: api,
                        method: 'POST',
                        beforeSend: () => overlayLoader.show(),
                        data: {
                            role_id: roleId,
                            allowed_ips: value
                        },
                        success: (res) => {
                            if (typeof toastr !== 'undefined') {
                                toastr.success('Allowed IPs updated successfully!');
                            } else {
                                alert('Allowed IPs updated successfully!');
                            }
                        },
                        error: (xhr) => {
                            if (typeof toastr !== 'undefined') {
                                toastr.error('Failed to update allowed IPs.');
                            } else {
                                alert('Failed to update allowed IPs.');
                            }
                        },
                        complete: () => overlayLoader.hide(),
                    });
                });
            }

            // calling handlers
            panelLoginSwitchHandler();
            allowedIpsHandle();

            $('#copyCurrentIp').on('click', function() {
                const ip = $(this).text();
                navigator.clipboard.writeText(ip);
                if (typeof toastr !== 'undefined') {
                    toastr.success('IP Address copied to clipboard!');
                } else {
                    alert('IP Copied!');
                }
            });

        });
    </script>
@endpush
