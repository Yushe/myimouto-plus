@extends('layouts.default')

@section('content')



<div class="container" style="max-width: 525px; margin-top: 2.5em;">
    <h2>Register</h2>
    
    <form method="POST" action="{{ url('/users/register') }}">
        {!! csrf_field() !!}

        <fieldset class="form-group">
            <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                <input class="mdl-textfield__input" type="text" name="name" value="{{ old('name') }}">
                <label class="mdl-textfield__label">Name</label>
            </div>
        </fieldset>

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
            <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                <input class="mdl-textfield__input" type="password" name="password_confirmation">
                <label class="mdl-textfield__label">Confirm Password</label>
            </div>
        </fieldset>

        <fieldset class="form-group">
            <button type="submit" class="mdl-button mdl-js-button mdl-button--raised mdl-button--colored">Register</button>
        </fieldset>
    </form>
</div>



@endsection
