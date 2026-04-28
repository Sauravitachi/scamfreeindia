@php /** @var \App\Models\Scam $scam */ @endphp

@php
    $statusRecords = $scam->statusRecords->where('status_type', $statusType);
    $assigneeRecords = $scam->assigneeRecords->where('assignee_type', $statusType->value);
    if($causer) {
        $statusRecords = $statusRecords->where('causer_id', $causer->id);
        $assigneeRecords = $assigneeRecords->where('causer_id', $causer->id);
    }

    $records = $statusRecords->concat($assigneeRecords)->sortBy('created_at');
@endphp

<div class="card mb-3">
    <div class="card-body">
        <div class="h2 card-title">
            Case {{ ucfirst($statusType->value) }} Status Lifecycle
        </div>
        <div class="h3 t-3">
            @if ($records->isNotEmpty())
                <div class="timeline_container">
                    <ul class="timeline_ul">
                        @foreach ($records as $record)
                            <li class="timeline_li">
                                @if ($at = $record->created_at)
                                    <div class="timeline_time">
                                        {{ format_date($at) }}
                                        @if ($record->causer)
                                            by
                                            <span class="text-primary">
                                                {{ $record->causer->name_with_username }} (ID:{{ $record->causer->id }})
                                            </span>
                                        @endif
                                    </div>
                                @endif
                                <p class="timeline_p">
                                    @if ($record instanceof \App\Models\ScamStatusRecord)
                                        @if ($record->status)
                                            <span class="text-muted">Updated status to :</span> {{ $record->status->title }}
                                        @else
                                            Removed status
                                        @endif
                                    @else
                                        @if ($record->assignee)
                                            <span class="text-muted">Assigned to :</span> {{ $record->assignee->name_with_username }} (ID:{{ $record->assignee->id }})
                                        @else
                                            <span class="text-muted">Unassigned</span>
                                        @endif
                                    @endif
                                </p>
                                @if ($record instanceof \App\Models\ScamStatusRecord && $record->status_remark)
                                    <p class="text-muted fw-normal">
                                    <span class="text-dark">Remark: </span> {{ $record->status_remark }}
                                    </p>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                </div>
            @else
                <span class="text-secondary">
                    <i class=" fs-3 me-2 ti ti-clock-x"></i>
                    No Activity!
                </span>
            @endif
        </div>
    </div>
</div>