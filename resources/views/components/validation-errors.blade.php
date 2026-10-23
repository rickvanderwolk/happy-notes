@if ($errors->any())
    <div {{ $attributes->merge(['class' => 'form-errors']) }}>
        <div class="form-errors-title">{{ __('Whoops! Something went wrong.') }}</div>

        <ul class="form-errors-list">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif
