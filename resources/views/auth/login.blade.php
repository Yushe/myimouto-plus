@extends('layouts.default')

@section('content')
<div class="container" style="max-width: 425px; margin-top: 2.5em;">
    <h1>Login</h1>
    <form method="POST" action="{{ url('/users/login') }}">
        {!! csrf_field() !!}

        <fieldset class="form-group">
            <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                <input class="mdl-textfield__input" type="email" name="email" value="{{ old('email') }}">
                <label class="mdl-textfield__label">Email</label>
            </div>
        </fieldset>
        
        <fieldset class="form-group">
            <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                <input class="mdl-textfield__input" type="password" name="password">
                <label class="mdl-textfield__label">Password</label>
            </div>
        </fieldset>
        
        <fieldset class="form-group">
            <label class="mdl-checkbox mdl-js-checkbox mdl-js-ripple-effect" for="chk-remmeber">
                <input type="checkbox" name="remember" id="chk-remmeber" class="mdl-checkbox__input" checked>
                <span class="mdl-checkbox__label">Remember Me</span>
            </label>
        </fieldset>
        
        <fieldset class="form-group">
            <button type="submit" class="mdl-button mdl-js-button mdl-button--raised mdl-button--colored">Login</button>
        </fieldset>
    </form>
</div>
@endsection
