<template>
        <div class="page-header min-vh-80">
            <div class="container">
                <div class="row">
                    <div class="col-xl-5 col-lg-6 col-md-8 d-flex flex-column ms-auto me-auto">
                        <div class="card">
                            <div class="card-header">
                                <div v-if="!passwordChanged">
                                    <h4 class="font-weight-bolder">Password reset form</h4>
                                    <p class="mb-0">Please provide a new password</p>
                                </div>
                                <div v-if="passwordChanged">
                                    <h4 class="font-weight-bolder">Password reset</h4>
                                    <p class="mb-0 text-success">{{ errorMessage }}</p>
                                </div>
                            </div>
                            <div class="card-body">
                                <form v-if="!passwordChanged" role="form" :action="targetUrl" @submit.prevent="changePassword" method="POST" autocomplete="off">

                                    <input type="hidden" name="_token" :value="window._token">
                                    <input type="hidden" name="code" :value="verificationCode">


                                        <div class="">
                                            <div class="input-group input-group-outline mb-3" :class="password.length && !errorMessagePassword.length ? 'is-valid' : ''">
                                                <label class="form-label">Password</label>
                                                <input required type="password" class="form-control" name="password" v-model="password">
                                            </div>
                                        </div>
                                        <div class="">
                                            <div class="input-group input-group-outline mb-3" :class="passwordConfirmation.length && !errorMessagePassword.length && passwordConfirmed ? 'is-valid' : password.length || passwordConfirmation.length ? 'is-invalid' : ''">
                                                <label class="form-label">Password confirmation</label>
                                                <input required type="password" class="form-control" name="passwordConfirmation" v-model="passwordConfirmation">
                                            </div>
                                        </div>


                                    <show-message v-if="!passwordStrength" :message="errorMessagePassword" role="danger"></show-message>

                                    <show-message :message="errorMessage" role="danger"></show-message>

                                    <div class="text-center">
                                        <button type="submit" class="btn btn-lg bg-gradient-info btn-lg w-100 mt-4 mb-0" :class="!this.password.length || !this.passwordStrength || !this.passwordConfirmed ? 'disabled' : ''">Reset your Password</button>
                                    </div>
                                </form>

                                <div v-if="passwordChanged">
                                    Please <a href="/login" class="text-info">log in</a>
                                </div>

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

                passwordChanged: false,
            }
        },

        computed: {
            passwordStrength() {
                this.errorMessagePassword = '';

                if(!this.password.length) return true;

                let upperLower = new RegExp('(?=.*[a-z])(?=.*[A-Z])');
                let numbers = new RegExp('(?=.*[0-9])');
                let strongPassword = new RegExp('(?=.*[a-z])(?=.*[A-Z])(?=.*[0-9])(?=.*[^A-Za-z0-9])(?=.{8,})');
                let specialChars = new RegExp('(?=.*[^A-Za-z0-9])');
                //let mediumPassword = new RegExp('((?=.*[a-z])(?=.*[A-Z])(?=.*[0-9])(?=.*[^A-Za-z0-9])(?=.{6,}))|((?=.*[a-z])(?=.*[A-Z])(?=.*[^A-Za-z0-9])(?=.{8,}))');
                if(!strongPassword.test(this.password)) {
                    this.errorMessagePassword =
                        'Password must:<ul>' +
                        (this.password.length < 8 ? '<li>Be at least 8 characters long</li>' : '') +
                        (!upperLower.test(this.password) ? '<li>Contain upper/lower case letter(s)</li>' : '') +
                        (!numbers.test(this.password) ? '<li>Contain number(s)</li>' : '') +
                        (!specialChars.test(this.password) ? '<li>Contain special character(s) (+, -, *, etc.)</li>' : '') +
                        '</ul>';
                }
                return strongPassword.test(this.password);
            },

            passwordConfirmed() {
                return this.password === this.passwordConfirmation;
            },

        },

        methods: {
            changePassword: function(e) {
                this.errorMessage = '';

                if(!this.passwordStrength || !this.passwordConfirmed) {
                    this.errorMessage = "You must complete the password and password confirmation fields!";
                    return;
                }

                axios.post(this.targetUrl , {'code' : this.verificationCode, 'password': this.password})
                    .then((response) => {
                        this.errorMessage = response.data;
                        this.passwordChanged = true;
                    })
                    .catch((error) => {
                        console.log(error.message);
                        this.errorMessage = error.response.data;
                    });
            },

        }
    }
</script>
