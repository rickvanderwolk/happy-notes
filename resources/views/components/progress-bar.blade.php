@props(['progress' => null])

@if (!is_null($progress) && $progress > 0)
    <div class="progress">
        <div class="progress-bar bg-success"
             role="progressbar"
             style="width: {{ $progress }}%"
             aria-valuenow="{{ $progress }}"
             aria-valuemin="0"
             aria-valuemax="100"
             aria-label="Progress: {{ $progress }}%">
        </div>
    </div>
@endif
