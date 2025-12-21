<div class="select-wrapper mb-3">
    <label class="form-label {{ isset($required) ? 'required' : '' }}">
        {{ $label }}
    </label>
    <select {{ $attributes->class(['form-select'])->except(['route', 'placeholder', 'paginate', 'default', 'minimumInputLength', 'dropdownParent']) }}>
        @isset($default)
            <option value="{{ $default['id'] }}" selected>{{ $default['label'] }}</option>
        @endisset
    </select>
    @isset($name)
        @error($name)
            <div class="invalid-feedback">
                {{ $message }}
            </div>
        @enderror
    @endisset
</div>


@push('script')
<script>
    $(document).ready(function() {
        setTimeout(() => {
            const props = {!! json_encode([
                'id' => $id,
                'placeholder' => $placeholder ?? 'search',
                'route' => $route,
                'paginate' => !!($paginate ?? false),
                'minimumInputLength' => $minimumInputLength ?? 1,
            ]) !!};
    
            const dropdownParent = {!! $dropdownParent ?? 'null' !!};
                $('#' + props.id).select2({
                    theme: "bootstrap-5",
                    placeholder: props.placeholder,
                    allowClear: true,
                    minimumInputLength: props.minimumInputLength,
                    dropdownParent : dropdownParent,
                    ajax: {
                        url: props.route,
                        dataType: 'json',
                        delay: 250,
                        data: function(params) {
                            const data = {
                                search: params.term
                            };
                            if (props.paginate)
                                data.page = params.page || 1;
                            return data;
                        },
                        processResults: function(res, params) {
                            params.page = params.page || 1;
                            const resData = res.data;
                            const data = {
                                results: resData.records
                            };
                            if (props.paginate) {
                                data.pagination = {
                                    more: resData.has_more_pages
                                }
                            }
                            return data;
                        }
                    }
                });
        }, 50);
    });
</script>
@endpush