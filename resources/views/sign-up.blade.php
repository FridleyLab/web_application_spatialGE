@extends('layout.main')

@section('headers')
{{--    <script src='https://www.google.com/recaptcha/api.js' async defer></script>--}}
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
    <sign-up target-url="{{ route('signup') }}" sign-in-url="{{ route('login') }}" :jobs="{{ json_encode($jobs) }}" :industries="{{ json_encode($industries) }}" :areas_of_interest="{{ json_encode($areas_of_interest) }}"></sign-up>
@endsection
