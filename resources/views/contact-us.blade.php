@extends('layout.main')

@section('headers')
    <script src="https://www.google.com/recaptcha/api.js?render=6Lf4B9AnAAAAAEfi6puoQQ-HpuN2WqwaABE_A0Fi"></script>
    <script>
        grecaptcha.ready(function() {
            // do request for recaptcha token
            // response is promise with passed token
            grecaptcha.execute('6Lf4B9AnAAAAAEfi6puoQQ-HpuN2WqwaABE_A0Fi', {action:'validate_captcha'})
                .then(function(token) {
                    // add token value to form
                    document.getElementById('g-recaptcha-response').value = token;
                });
        });
    </script>
@endsection

@section('content')
    <contact-us url="{{ route('contact-us') }}"
                user-first-name="{{ !auth()->guest() ? auth()->user()->first_name : '' }}"
                user-last-name="{{ !auth()->guest() ? auth()->user()->last_name : '' }}"
                user-email="{{ !auth()->guest() ? auth()->user()->email : '' }}">
    </contact-us>
@endsection
