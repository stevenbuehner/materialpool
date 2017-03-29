<p>@lang('Keywords included in search:')
    @foreach($keywords as $k)
        @include('keywords.linked', ['keyword' => $k])
    @endforeach
</p>