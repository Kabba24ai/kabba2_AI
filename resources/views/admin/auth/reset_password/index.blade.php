@extends('admin.auth.layouts.layout')

@section('page_title', 'Reset Password')

@section('content')
    <div class="card rounded-3 w-md-550px">
        <div class="card-body d-flex flex-column p-10 p-lg-20 pb-lg-10">
            @include('flash::message')
            @include('admin.includes.formErrors')
            <div class="d-flex flex-center flex-column-fluid pb-lg-5">

                {{ html()->form()->attributes([
                        'class' => 'form w-100 module_form',
                        'id' => 'kt_reset_password_form',
                        'autocomplete' => 'off',
                        'data-parsley-validate' => true,
                    ])->open() }}


                <div class="text-center mb-11">
                    <h1 class="text-dark fw-bolder mb-3">Setup New Password</h1>
                    <div class="text-gray-500 fw-semibold fs-6">Have you already reset the password ? <a
                            href="{{ route('admin.auth.login') }}">Sign in</a>
                    </div>
                </div>

                <div class="row mb-1">
                    <div class="col-lg-12 mb-5">
                        <div class="fv-row mb-0 fv-plugins-icon-container positiom-relative password-toggle">
                            <label for="autocomplete" class="form-label fs-6 fw-bold mb-3 required">New Password</label>
                            <div class="input-group">

                                {{ html()->password('password')->attributes([
                                        'class' => 'form-control form-control-lg form-control-solid',
                                        'placeholder' => 'New Password',
                                        'autocomplete' => 'off',
                                        'id' => 'password',
                                        'data-parsley-errors-container' => '#new_password_parsley_error',
                                        'data-parsley-required-message' => 'Please enter your new password.',
                                        'data-parsley-minlength' => 8,
                                        'data-parsley-uppercase' => 1,
                                        'data-parsley-lowercase' => 1,
                                        'data-parsley-number' => 1,
                                       'data-parsley-pattern' => '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[!@#$%^&*])[A-Za-z\d!@#$%^&*]+$/',
                                        'placeholder' => 'New Password',
                                    ])->required() }}

                                <span class="input-group-text border-0 w-50px">
                                    <i class="fas fa-eye-slash" id="newPasswordToggle"></i>
                                </span>
                            </div>
                            <span class="errorspannewpassinput" id="new_password_parsley_error"></span>

                        </div>
                    </div>
                    <div class="col-lg-12">
                        <div class="fv-row mb-0 fv-plugins-icon-container positiom-relative password-toggle">
                            <label for="confirm_password" class="form-label fs-6 fw-bold mb-3 required">Confirm New
                                Password</label>

                            <div class="input-group">

                                {{ html()->password('confirmPassword')->attributes([
                                        'class' => 'form-control form-control-lg form-control-solid',
                                        'placeholder' => 'Confirm New Password',
                                        'autocomplete' => 'off',
                                        'id' => 'confirm_password',
                                        'data-parsley-minlength' => 8,
                                        'data-parsley-equalto' => '#password',
                                        'data-parsley-required-message' => 'Please confirm your new password.',
                                        'data-parsley-equalto-message' => 'This value should be the same as New password.',
                                        'data-parsley-errors-container' => '#confirm_password_parsley_error',
                                    ])->required() }}

                                <span class="input-group-text border-0 w-50px">
                                    <i class="fas fa-eye-slash" id="confirmPasswordToggle"></i>
                                </span>

                            </div>
                            <span class="clearfix" id="confirm_password_parsley_error"></span>
                        </div>
                    </div>
                </div>
                <div class="d-flex flex-stack flex-wrap gap-3 fs-base fw-semibold mb-8">
                </div>
                <div class="d-grid mb-10">
                    <button type="submit" class="btn btn-primary">
                        <span class="indicator-label">Submit</span>
                    </button>
                </div>
                {{ html()->form()->close() }}
            </div>
        </div>
    </div>
@endsection


@push('page_js')
    <script type="text/javascript">
        const newPasswordToggle = document.querySelector('#newPasswordToggle');
        const new_password = document.querySelector('#password');

        newPasswordToggle.addEventListener('click', function(e) {
            // toggle the type attribute
            const type = new_password.getAttribute('type') === 'password' ? 'text' : 'password';
            new_password.setAttribute('type', type);
            // toggle the eye slash icon
            this.classList.toggle('fa-eye');
        });

        const confirmPasswordToggle = document.querySelector('#confirmPasswordToggle');
        const confirm_password = document.querySelector('#confirm_password');

        confirmPasswordToggle.addEventListener('click', function(e) {
            // toggle the type attribute
            const type = confirm_password.getAttribute('type') === 'password' ? 'text' : 'password';
            confirm_password.setAttribute('type', type);
            // toggle the eye slash icon
            this.classList.toggle('fa-eye');
        });
    </script>

    <script type="text/javascript">
        //has uppercase
        window.Parsley.addValidator('uppercase', {
            requirementType: 'number',
            validateString: function(value, requirement) {
                var uppercases = value.match(/[A-Z]/g) || [];
                return uppercases.length >= requirement;
            },
            messages: {
                en: 'Your password must contain at least (%s) uppercase letter.'
            }
        });

        //has lowercase
        window.Parsley.addValidator('lowercase', {
            requirementType: 'number',
            validateString: function(value, requirement) {
                var lowecases = value.match(/[a-z]/g) || [];
                return lowecases.length >= requirement;
            },
            messages: {
                en: 'Your password must contain at least (%s) lowercase letter.'
            }
        });

        //has number
        window.Parsley.addValidator('number', {
            requirementType: 'number',
            validateString: function(value, requirement) {
                var numbers = value.match(/[0-9]/g) || [];
                return numbers.length >= requirement;
            },
            messages: {
                en: 'Your password must contain at least (%s) number.'
            }
        });

        //has special char
        window.Parsley.addValidator('special', {
            requirementType: 'number',
            validateString: function(value, requirement) {
                var specials = value.match(/[^a-zA-Z0-9]/g) || [];
                return specials.length >= requirement;
            },
            messages: {
                en: 'Your password must contain at least (%s) special characters.'
            }
        });
    </script>
@endpush
