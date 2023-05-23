<template>
        <div class="page-header min-vh-100">
            <div class="container">
                <div class="row">
                    <div class="col-xl-4 col-lg-5 col-md-7 d-flex flex-column ms-auto me-auto">
                        <div class="card">
                            <div class="card-header">
                                <h4 class="font-weight-bolder">{{ recoveringPassword ? 'Reset your password' : 'Sign In' }}</h4>
                                <p class="mb-0">{{ currentHeader }}</p>
                            </div>
                            <div class="card-body">
                                <form role="form" :action="targetUrl" @submit.prevent="checkCredentials" method="POST" autocomplete="off">

<!--                                    <input type="hidden" name="_token" :value="window._token">-->

                                    <div class="input-group input-group-outline mb-3" :class="(validEmailAddress ? 'is-valid' : '') + (this.overrideValidations ? 'focused is-focused is-valid' : '')">
                                        <label class="form-label">Email</label>
                                        <input ref="email" required type="email" class="form-control" name="email" v-model="email" @dblclick="testUser">
                                    </div>
                                    <div v-if="!recoveringPassword" class="input-group input-group-outline mb-3" :class="(this.overrideValidations ? 'focused is-focused' : '')">
                                        <label class="form-label">Password</label>
                                        <input ref="password" required type="password" class="form-control" name="password" v-model="password">
                                    </div>
                                    <show-message :message="errorMessage" role="danger"></show-message>
                                    <div class="text-center">
                                        <button type="submit" class="btn btn-lg bg-gradient-info btn-lg w-100 mt-4 mb-0">{{ recoveringPassword ? 'Send recovery email' : 'Sign In' }}</button>
                                    </div>
                                </form>
                            </div>
                            <div class="card-footer text-center pt-0 px-lg-2 px-1">
                                <p class="mb-2 text-sm mx-auto">
                                    <a v-if="!recoveringPassword" @click="forgotPassword" class="text-info text-gradient font-weight-bold cursor-pointer">Forgot your password?</a>
                                    <a v-if="recoveringPassword" @click="recoveringPassword = false" class="text-danger text-gradient font-weight-bold cursor-pointer">Cancel password recovery</a>
                                </p>
                                <p class="mb-2 text-sm mx-auto">
                                    Don't have an account?
                                    <a :href="signUpUrl" class="text-info text-gradient font-weight-bold">Sign up</a>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
</template>
<script>

    export default {
        name: "signIn",

        props: {
            targetUrl: String,
            signUpUrl: String,
            resetPasswordUrl: String
        },

        data() {
            return {
                email: '', //'roberto.manjarres-betancur@moffitt.org',
                password: '', // '12345678',
                errorMessage: '',

                overrideValidations: false,

                recoveringPassword: false,

                defaultHeader: 'Enter your email and password to sign in',

                currentHeader: 'Enter your email and password to sign in',
            }
        },

        computed: {
            validEmailAddress() {
                return String(this.email)
                    .toLowerCase()
                    .match(
                        /^(([^<>()[\]\\.,;:\s@"]+(\.[^<>()[\]\\.,;:\s@"]+)*)|(".+"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/
                    );
            }
        },

        methods: {
            checkCredentials: function(e) {
                this.errorMessage = '';

                if(!this.email.trim().length) {
                    this.errorMessage = "You must complete all the fields!";
                    return;
                }

                if(!this.password.trim().length) {
                    this.errorMessage = "You must complete all the fields!";
                    return;
                }

                if(this.recoveringPassword)
                {
                    axios.get(this.resetPasswordUrl, {params: {'email' : this.email}})
                        .then((response) => { location.href = this.resetPasswordUrl + '?email=' + this.email })
                        .catch((error) => { this.errorMessage = error.response.data});
                    return;
                }

                axios.post(this.targetUrl , {'email' : this.email, 'password': this.password})
                    .then((response) => {
                        window.location.href = "/projects";
                    })
                    .catch((error) => {
                        console.log(error.message);
                        this.errorMessage = error.response.data;
                    });
            },

            forgotPassword: function() {
                this.recoveringPassword = true;
                this.currentHeader = 'Please enter your email to continue';
            },

            //For developing purposes
            testUser: function() {

                if(!window.location.href.includes('.dev')) return;

                this.email = 'test@moffitt.org';
                this.password = '12345678';

                this.overrideValidations = true;

            }
        }
    }
</script>
