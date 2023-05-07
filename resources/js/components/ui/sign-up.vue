<template>

        <div class="page-header">
            <div class="container">
                <div class="row">
                    <div class="col-xl-5 col-lg-8 col-md-9 d-flex flex-column ms-auto me-auto">
                        <div class="card">
                            <div class="card-header">
                                <h4 class="font-weight-bolder">Sign Up</h4>
                                <p class="mb-0">Please complete the form to register</p>
                            </div>
                            <div class="card-body">
                                <form role="form" :action="targetUrl" @submit.prevent="checkCredentials" method="POST" autocomplete="off">

                                    <input type="hidden" name="_token" :value="window._token">

                                    <div class="row row-cols-2">
                                        <div class="w-50">
                                            <div class="input-group input-group-outline mb-3 col" :class="first_name.length >= 1 ? 'is-valid' : ''">
                                                <label class="form-label">First name</label>
                                                <input required type="text" class="form-control" name="first_name" v-model="first_name">
                                            </div>
                                        </div>
                                        <div class="w-50">
                                            <div class="input-group input-group-outline mb-3" :class="last_name.length >= 1 ? 'is-valid' : ''">
                                                <label class="form-label">Last name</label>
                                                <input required type="text" class="form-control" name="last_name" v-model="last_name">
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row row-cols-2">
                                        <div class="w-50">
                                            <div class="input-group input-group-outline mb-3" :class="validEmailAddress ? 'is-valid' : ''">
                                                <label class="form-label">Email</label>
                                                <input required type="email" class="form-control" name="email" v-model="email">
                                            </div>
                                        </div>
                                        <div class="w-50">
                                            <div class="input-group input-group-outline mb-3" :class="(validEmailAddress && email === emailConfirmation) ? 'is-valid' : ''">
                                                <label class="form-label">Email confirmation </label>
                                                <input required type="email_confirmation" class="form-control" name="emailConfirmation" v-model="emailConfirmation">
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row row-cols-2">
                                        <div class="w-50">
                                            <div class="input-group input-group-outline mb-3" :class="password.length && !errorMessagePassword.length ? 'is-valid' : ''">
                                                <label class="form-label">Password</label>
                                                <input required type="password" class="form-control" name="password" v-model="password">
                                            </div>
                                        </div>
                                        <div class="w-50">
                                            <div class="input-group input-group-outline mb-3" :class="passwordConfirmation.length && !errorMessagePassword.length && passwordConfirmed ? 'is-valid' : ''">
                                                <label class="form-label">Pwd confirmation</label>
                                                <input required type="password" class="form-control" name="passwordConfirmation" v-model="passwordConfirmation">
                                            </div>
                                        </div>
                                    </div>
                                    <show-message :message="errorMessagePassword" role="danger"></show-message>

                                    <div class="mb-3">
                                        <div>Please select the main sector you work with</div>
                                        <select class="form-select bg-white border border-1 p-2" name="industry" v-model="industry">
                                            <option value=""></option>
                                            <option v-for="industry in industries" :value="industry">{{ industry }}</option>
                                        </select>
                                    </div>

                                    <div class="mb-3">
                                        <div>Please select your position</div>
                                        <select class="form-select bg-white border border-1 p-2"  name="job" v-model="job">
                                            <option value=""></option>
                                            <option v-for="job in jobs" :value="job">{{ job }}</option>
                                        </select>
                                    </div>

                                    <div class="mb-3">
                                        <div>Please select your main area of interest</div>
                                        <select class="form-select bg-white border border-1 p-2"  name="interest" v-model="interest">
                                            <option value=""></option>
                                            <option v-for="area in areas_of_interest" :value="area">{{ area }}</option>
                                        </select>
                                    </div>






<!--                                    <div>-->
<!--                                        <input type="button" class="cursor-pointer btn btn-outline-info" @click="showTechnologies = !showTechnologies" value="Please, tell us a little about you">-->
<!--                                        <div>-->
<!--                                            <div v-if="showTechnologies" v-for="technology in technologies">-->
<!--                                                <label>-->
<!--                                                    <input type="checkbox" /> {{ technology }}-->
<!--                                                </label>-->
<!--                                            </div>-->
<!--                                        </div>-->
<!--                                    </div>-->

                                    <div class="form-check form-check-info text-start ps-0">
                                        <input class="form-check-input" type="checkbox" value="" id="flexCheckDefault" v-model="terms_and_conditions">
                                        <label class="form-check-label" for="flexCheckDefault">
                                            I agree the <a href="https://moffitt.org/legal-statements-and-policies/terms-conditions/" target="_blank" class="text-info font-weight-bolder">Terms and Conditions</a>
                                        </label>
                                    </div>

                                    <show-message class="text-center m-4" :message="errorMessage" role="danger"></show-message>

<!--                                    <div class="text-center m-3">-->
<!--                                        <div class="g-recaptcha" data-sitekey="6Lf1JIkkAAAAAEyfq5XDevFmu98B4K052NZi7z4K"></div>-->
<!--                                    </div>-->

                                    <div class="text-center">
                                        <button type="submit" class="btn btn-lg bg-gradient-info btn-lg w-100 w-md-50 mt-4 mb-0" :class="processing ? 'disabled' : ''">Sign Up</button>
                                    </div>
                                </form>
                            </div>
                            <div v-if="!processing" class="card-footer text-center pt-0 px-lg-2 px-1">
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

</template>
<script>
    export default {
        name: "signUp",

        props: {
            targetUrl: String,
            signInUrl: String,

            industries: Object,
            jobs: Object,
            areas_of_interest: Object,
        },

        data() {
            return {
                first_name: '',
                last_name: '',
                email: '',
                emailConfirmation: '',
                password: '',
                passwordConfirmation: '',
                terms_and_conditions: false,
                errorMessage: '',
                errorMessagePassword: '',

                job: '',
                interest: '',
                industry: '',

                processing: false,

                //industries: ['Biotech', 'Contract Research Organization', 'Government', 'Hospital/Medical Center', 'Institute', 'Pharma', 'Service', 'University', 'Vendor'],
                //jobs: ['Administrative', 'Bioinformatician', 'Biologist', 'Clinician', 'Data Analyst', 'Data Scientist', 'Field Application Scientist', 'Graduate Student', 'Intern', 'Lab Director', 'Lab Manager', 'Lab Technician', 'Non-scientific', 'Pathologist', 'Physician', 'Post-Doctoral', 'Principal Investigator', 'Professor', 'Researcher', 'Scientist', 'Senior Scientist', 'Statistician', 'Student', 'Undergraduate Student', 'Other'],
                //areas_of_interest: ['Agricultural Biotech', 'Biology', 'Cancer/Oncology', 'Cardiovascular', 'Development Biology', 'Diagnostics', 'Endocrine', 'Evolution', 'Gastroenterology', 'Genetics', 'Immunology', 'Infectious Disease', 'Metabolism', 'Microbiome', 'Molecular Biology', 'Multiple Interests', 'Neuroscience', 'Stem Cells', 'Synthetic Biology', 'Toxicology', 'Veterinary', 'Other'],

                // showTechnologies: false,
                // technologies: ['one', 'two', 'three']
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
                if(!this.first_name.trim().length || !this.last_name.trim().length || !this.email.trim().length || (this.email !== this.emailConfirmation) || !this.password.trim().length || !this.passwordStrength || !this.passwordConfirmed || !this.terms_and_conditions || !this.job.trim().length || !this.industry.trim().length  || !this.interest.trim().length) {
                    this.errorMessage = "You must complete all the fields!";
                    //e.preventDefault();
                    return;
                }

                this.processing = true;

                axios.post(this.targetUrl , {'first_name' : this.first_name, 'last_name' : this.last_name, 'email' : this.email, 'password': this.password, 'job': this.job, 'interest': this.interest, 'industry': this.industry})
                    .then((response) => {
                        //this.errorMessage = response.data;
                        window.location.href = response.data;
                    })
                    .catch((error) => {
                        this.processing = false;
                        console.log(error.message);
                        this.errorMessage = error.response.data;
                    });
            },
        }
    }
</script>
