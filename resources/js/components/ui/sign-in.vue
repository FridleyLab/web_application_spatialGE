<template>
    <section>
        <div class="page-header min-vh-100">
            <div class="container">
                <div class="row">
                    <div class="col-xl-4 col-lg-5 col-md-7 d-flex flex-column ms-auto me-auto">
                        <div class="card">
                            <div class="card-header">
                                <h4 class="font-weight-bolder">Sign In</h4>
                                <p class="mb-0">Enter your email and password to sign in</p>
                            </div>
                            <div class="card-body">
                                <form role="form" :action="targetUrl" @submit.prevent="checkCredentials" method="POST">

                                    <input type="hidden" name="_token" :value="window._token">

                                    <div class="input-group input-group-outline mb-3" :class="validEmailAddress ? 'is-valid' : ''">
                                        <label class="form-label">Email</label>
                                        <input required type="email" class="form-control" name="email" v-model="email">
                                    </div>
                                    <div class="input-group input-group-outline mb-3">
                                        <label class="form-label">Password</label>
                                        <input required type="password" class="form-control" name="password" v-model="password">
                                    </div>
                                    <show-message :message="errorMessage" role="danger"></show-message>
                                    <div class="text-center">
                                        <button type="submit" class="btn btn-lg bg-gradient-info btn-lg w-100 mt-4 mb-0">Sign In</button>
                                    </div>
                                </form>
                            </div>
                            <div class="card-footer text-center pt-0 px-lg-2 px-1">
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
    </section>
</template>
<script>

    export default {
        name: "signIn",

        props: {
            targetUrl: String,
            signUpUrl: String,
        },

        data() {
            return {
                email: 'roberto.manjarres-betancur@moffitt.org',
                password: '12345678',
                errorMessage: ''
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
                if(!this.email.trim().length || !this.password.trim().length) {
                    this.errorMessage = "You must complete all the fields!";
                }

                axios.post(this.targetUrl , {'email' : this.email, 'password': this.password})
                    .then((response) => {
                        window.location.href = "/";
                    })
                    .catch((error) => {
                        console.log(error.message);
                        this.errorMessage = error.response.data;
                    });
            }
        }
    }
</script>
