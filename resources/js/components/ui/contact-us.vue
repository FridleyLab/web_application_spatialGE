<template>
    <div class="container-fluid py-4 col-xl-11 col-md-12 col-sm-12">
        <div class="row justify-content-center">
            <div class="col-xl-12 col-sm-12 mb-xl-0 mb-4 mt-4">
                <div class="card">
                    <div class="card-header p-3 pt-2">
                        <div class="icon icon-lg icon-shape bg-gradient-info shadow-dark text-center border-radius-xl mt-n4 position-absolute">
                            <i class="material-icons opacity-10">contact_mail</i>
                        </div>
                        <div class="text-end pt-1">
                            <img src="/images/spatialge-logo.png" class="img-fluid max-height-100">
                        </div>
                    </div>
                    <hr class="dark horizontal my-0">
                    <div class="card-body px-2 px-md-3 px-lg-6 text-justify text-dark">


                        <h4 class="my-3 text-center">Contact us</h4>

                        <p class="text-info"><b>If you have any questions or comments about spatialGE, we welcome all the feedback you can give us!</b></p>

                        <p>Please remember to include your project's name if applicable, in case you have a question about a specific feature or fuctionality of spatialGE.</p>

                        <div class="row">
                            <div class="w-90 w-md-70 w-lg-60 content-center">
                                <form method="post" autocomplete="off" @submit.prevent="submitData" ref="contactForm" :class="sendingMessage || messageSent ? 'disabled-clicks' : ''">
                                    <div class="p-4">
                                        <div class="text-sm text-secondary py-3 text-info">All fields are required *</div>
                                        <div class="mb-3 d-flex">
                                            <div class="w-45">
                                                <label class="form-label">First name</label>
                                                <input required type="text" class="form-control border border-1 p-2" name="first_name" v-model="first_name" maxlength="30">
                                            </div>
                                            <div class="w-45 ms-3">
                                                <label class="form-label">Last name</label>
                                                <input required type="text" class="form-control border border-1 p-2" name="last_name" v-model="last_name" maxlength="30">
                                            </div>
                                        </div>
                                        <div class="mb-3 w-70">
                                            <label class="form-label">Email address where we can reach you</label>
                                            <input required type="email" class="form-control border border-1 p-2" name="email" v-model="email" maxlength="255">
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Short description or subject</label>
                                            <input required type="text" class="form-control border border-1 p-2" name="subject" v-model="subject" maxlength="255">
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Additional information or comments</label>
                                            <textarea required class="form-control border border-1 p-2" name="description" v-model="description" maxlength="255" rows="6"></textarea>
                                        </div>
                                        <div>
                                            <button type="submit" class="btn btn-info float-end">{{ sendingMessage ? 'Sending' : messageSent ? 'Sent' : 'Send' }}</button>
                                        </div>
                                    </div>
                                </form>

                                <div v-if="messageSent" class="text-xl-center text-success">Your message was sent!</div>

                            </div>
                        </div>


                    </div>

                </div>
            </div>

        </div>

    </div>
</template>

<script>

export default {
    name: 'contactUs',

    props: {
        url: String,
        userFirstName: {type: String, default: ''},
        userLastName: {type: String, default: ''},
        userEmail: {type: String, default: ''},
    },

    data() {
        return {
            subject: '',
            description: '',
            email: this.userEmail,
            first_name: this.userFirstName,
            last_name: this.userLastName,

            messageSent:false,
            sendingMessage: false
        }
    },

    methods: {
        submitData(e) {
            this.sendingMessage = true;
            axios.post(this.url, {subject: this.subject, description: this.description, email: this.email})
                .then((response) => {
                    console.log(response.data);
                    this.sendingMessage = false;
                    this.messageSent = true;
                })
                .catch((error) => console.log(error));
        },
    },
}
</script>
