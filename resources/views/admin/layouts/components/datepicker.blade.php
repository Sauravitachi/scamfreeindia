@pushOnce('style')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css" />
@endPushOnce

@pushOnce('script')
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script>
        $(document).ready(function() {
            // datetime range
            $(".datetime_range_picker").flatpickr({
                mode: "range",
                enableTime: true,
                dateFormat: "Y-m-d H:i",
                // minDate: "today",
                time_24hr: false,
            });

            $(".date_range_picker").flatpickr({
                mode: "range",
                enableTime: false,
                dateFormat: "Y-m-d H:i",
                time_24hr: false,
            });

            $(".time_picker").flatpickr({
                noCalendar: true,
                enableTime: true,
                dateFormat: "H:i",
                time_24hr: false,
            });
        });
    </script>
@endPushOnce
