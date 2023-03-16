<template>
        <div class="page-header min-vh-100">
            <div class="container">
                <div class="row">
                    <div class="col-xl-4 col-lg-5 col-md-7 d-flex flex-column ms-auto me-auto">
                        <div class="card">
                            <div class="card-header">
                                <h4 class="font-weight-bolder">Password reset form</h4>
                                <p class="mb-0">Please provide a new password</p>
                            </div>
                            <div class="card-body">
                                <form role="form" :action="targetUrl" @submit.prevent="checkCredentials" method="POST" autocomplete="off">

                                    <input type="hidden" name="_token" :value="window._token">
                                    <input type="hidden" name="code" :value="verificationCode">

                                    <div class="input-group input-group-outline mb-3" :class="password.length && !errorMessagePassword.length ? 'is-valid' : ''">
                                        <label class="form-label">Password</label>
                                        <input ref="password" required type="password" class="form-control" name="password" v-model="password">
                                    </div>

                                    <div class="input-group input-group-outline mb-3" :class="passwordConfirmation.length && !errorMessagePassword.length && passwordConfirmed ? 'is-valid' : ''">
                                        <label class="form-label">Password confirmation</label>
                                        <input ref="passwordConfirmation" required type="password" class="form-control" name="passwordConfirmation" v-model="passwordConfirmation">
                                    </div>

                                    <show-message :message="errorMessagePassword" role="danger"></show-message>
                                    <show-message :message="errorMessage" role="danger"></show-message>
                                    <div class="text-center">
                                        <button type="submit" class="btn btn-lg bg-gradient-info btn-lg w-100 mt-4 mb-0">Reset your Password</button>
                                    </div>
                                </form>
                            </div>
                            <div class="card-footer text-center pt-0 px-lg-2 px-1">

                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
</template>
<script>

    export default {
        name: "signInPasswordReset",

        props: {
            targetUrl: String,
            verificationCode: String
        },

        data() {
            return {

                password: '',
                passwordConfirmation: '',
                errorMessage: '',

                errorMessagePassword: '',
            }
        },

        computed: {
            passwordStrength() {
                this.errorMessagePassword = '';

                if(!this.password.length) return true;

                let strongPassword = new RegExp('(?=.*[a-z])(?=.*[A-Z])(?=.*[0-9])(?=.*[^A-Za-z0-9])(?=.{8,})');
                //let mediumPassword = new RegExp('((?=.*[a-z])(?=.*[A-Z])(?=.*[0-9])(?=.*[^A-Za-z0-9])(?=.{6,}))|((?=.*[a-z])(?=.*[A-Z])(?=.*[^A-Za-z0-9])(?=.{8,}))');
                if(!strongPassword.test(this.password)) {
                    this.errorMessagePassword =
                        'Password must:<ul>' +
                        '<li>Be at least 8 characters long</li>' +
                        '<li>Contain upper/lower case letter(s)</li>' +
                        '<li>Contain number(s)</li>' +
                        '<li>Contain special character(s) (+, -, *, etc.)</li>' +
                        '</ul>';
                }
                return strongPassword.test(this.password);
            },

            passwordConfirmed() {
                return this.password === this.passwordConfirmation;
            },

        },

        methods: {
            checkCredentials: function(e) {
                this.errorMessage = '';

                if(!this.passwordStrength || !this.passwordConfirmed) {
                    this.errorMessage = "You must complete all the fields!-";
                    return;
                }

                axios.post(this.targetUrl , {'code' : this.verificationCode, 'password': this.password})
                    .then((response) => {
                        this.errorMessage = 'Your password has been changed!';
                    })
                    .catch((error) => {
                        console.log(error.message);
                        this.errorMessage = error.response.data;
                    });
            },

        }
    }
</script>
