<template>
    <section>
        <div class="page-header min-vh-100">
            <div class="container">
                <div class="row">
                    <div class="col-xl-4 col-lg-5 col-md-7 d-flex flex-column ms-auto me-auto">
                        <div class="card">
                            <div class="card-header">
                                <h4 class="font-weight-bolder">Sign Up</h4>
                                <p class="mb-0">Enter your email and password to register</p>
                            </div>
                            <div class="card-body">
                                <form role="form" :action="targetUrl" @submit="checkCredentials" method="POST">

                                    <input type="hidden" name="_token" :value="window._token">

                                    <div class="input-group input-group-outline mb-3" :class="name.length > 3 ? 'is-valid' : ''">
                                        <label class="form-label">Name</label>
                                        <input required type="text" class="form-control" name="name" v-model="name">
                                    </div>
                                    <div class="input-group input-group-outline mb-3" :class="validEmailAddress ? 'is-valid' : ''">
                                        <label class="form-label">Email</label>
                                        <input required type="email" class="form-control" name="email" v-model="email">
                                    </div>
                                    <div class="input-group input-group-outline mb-3" :class="password.length && !errorMessagePassword.length ? 'is-valid' : ''">
                                        <label class="form-label">Password</label>
                                        <input required type="password" class="form-control" name="password" v-model="password">
                                    </div>
                                    <show-message :message="errorMessagePassword"></show-message>
                                    <div class="form-check form-check-info text-start ps-0">
                                        <input class="form-check-input" type="checkbox" value="" id="flexCheckDefault" checked>
                                        <label class="form-check-label" for="flexCheckDefault">
                                            I agree the <a href="javascript:;" class="text-dark font-weight-bolder">Terms and Conditions</a>
                                        </label>
                                    </div>
                                    <show-message :message="errorMessage"></show-message>

                                    <div class="text-center">
                                        <div class="g-recaptcha" data-sitekey="6Lf1JIkkAAAAAEyfq5XDevFmu98B4K052NZi7z4K"></div>
                                    </div>

                                    <div class="text-center">
                                        <button type="submit" class="btn btn-lg bg-gradient-info btn-lg w-100 mt-4 mb-0">Sign Up</button>
                                    </div>
                                </form>
                            </div>
                            <div class="card-footer text-center pt-0 px-lg-2 px-1">
                                <p class="mb-2 text-sm mx-auto">
                                    Already have an account?
                                    <a :href="signInUrl" class="text-info text-gradient font-weight-bold">Sign in</a>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</template>
<script>

    export default {
        name: "signUp",

        props: {
            targetUrl: String,
            signInUrl: String,
        },

        data() {
            return {
                name: 'Roberto Manjarres',
                email: 'roberto.manjarres-betancur@moffitt.org',
                password: '12345678',
                errorMessage: '',
                errorMessagePassword: ''
            }
        },

        computed: {
            passwordStrength() {
                this.errorMessagePassword = '';

                if(!this.password.length) return true;

                let strongPassword = new RegExp('(?=.*[a-z])(?=.*[A-Z])(?=.*[0-9])(?=.*[^A-Za-z0-9])(?=.{8,})');
                //let mediumPassword = new RegExp('((?=.*[a-z])(?=.*[A-Z])(?=.*[0-9])(?=.*[^A-Za-z0-9])(?=.{6,}))|((?=.*[a-z])(?=.*[A-Z])(?=.*[^A-Za-z0-9])(?=.{8,}))');
                if(!strongPassword.test(this.password)) {
                    this.errorMessagePassword = 'Password must:<ul><li>Be at least 8 characters long</li>' +
                        '<li>Contain upper/lower case letter(s)</li>' +
                        '<li>Contain number(s)</li>' +
                        '<li>Contain special character(s) (+, -, *, etc.)</li></ul>';
                }
                return strongPassword.test(this.password);
            },

            validEmailAddress() {
                return String(this.email)
                    .toLowerCase()
                    .match(
                        /^(([^<>()[\]\\.,;:\s@"]+(\.[^<>()[\]\\.,;:\s@"]+)*)|(".+"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/
                    );
            }
        },

        methods: {
            checkCredentials: (e) => {
                this.errorMessage = '';
                if(!this.name.trim().length || !this.email.trim().length || !this.password.trim().length) {
                    this.errorMessage = "You must complete all the fields!";
                    e.preventDefault();
                }
            },
        }
    }
</script>
